<?php
/**
 * RAG Sync Webhook Sender
 *
 * Handles sending webhook notifications to the RAG backend
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Sync_Webhook {

    /**
     * Webhook topics
     */
    const TOPIC_POST_CREATED = 'post.created';
    const TOPIC_POST_UPDATED = 'post.updated';
    const TOPIC_POST_DELETED = 'post.deleted';
    const TOPIC_PAGE_CREATED = 'page.created';
    const TOPIC_PAGE_UPDATED = 'page.updated';
    const TOPIC_PAGE_DELETED = 'page.deleted';
    const TOPIC_PRODUCT_CREATED = 'product.created';
    const TOPIC_PRODUCT_UPDATED = 'product.updated';
    const TOPIC_PRODUCT_DELETED = 'product.deleted';
    const TOPIC_CATEGORY_CREATED = 'category.created';
    const TOPIC_CATEGORY_UPDATED = 'category.updated';
    const TOPIC_CATEGORY_DELETED = 'category.deleted';
    const TOPIC_COUPON_CREATED = 'coupon.created';
    const TOPIC_COUPON_UPDATED = 'coupon.updated';
    const TOPIC_COUPON_DELETED = 'coupon.deleted';

    /**
     * Queue for batching webhooks
     */
    private array $queue = [];

    /**
     * Whether to batch webhooks
     */
    private bool $batching = false;

    /**
     * Constructor
     */
    public function __construct() {
        // Send queued webhooks on shutdown
        add_action('shutdown', [$this, 'flush_queue']);
    }

    /**
     * Send a webhook notification
     */
    public function send(string $topic, array $data, bool $async = true): bool {
        if (!RAG_Sync::is_enabled()) {
            return false;
        }

        $webhook_endpoint = RAG_Sync::get_option('webhook_endpoint', '');
        $webhook_secret = RAG_Sync::get_option('webhook_secret', '');

        if (empty($webhook_endpoint) || empty($webhook_secret)) {
            $this->log('Webhook not sent: missing endpoint or secret configuration', 'warning');
            return false;
        }

        $payload = [
            'topic' => $topic,
            'data' => $data,
            'site_url' => get_site_url(),
            'timestamp' => current_time('c'),
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_active' => rag_sync()->is_woocommerce_active(),
        ];

        if ($this->batching) {
            $this->queue[] = [
                'topic' => $topic,
                'payload' => $payload,
            ];
            return true;
        }

        return $this->send_webhook($webhook_endpoint, $topic, $payload, $async);
    }

    /**
     * Send webhook request
     */
    private function send_webhook(string $url, string $topic, array $payload, bool $async = true): bool {
        $body = wp_json_encode($payload);
        $signature = $this->generate_signature($body);

        $args = [
            'timeout' => $async ? 1 : 30,
            'blocking' => !$async,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WC-Webhook-Topic' => $topic,
                'X-WC-Webhook-Signature' => $signature,
                'X-WC-Webhook-Source' => get_site_url(),
                'X-WC-Webhook-ID' => wp_generate_uuid4(),
                'User-Agent' => 'RAG-Sync/' . RAG_SYNC_VERSION,
            ],
            'body' => $body,
        ];

        $this->log("Sending webhook: {$topic}", 'info', [
            'url' => $url,
            'async' => $async,
        ]);

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            $this->log("Webhook failed: {$response->get_error_message()}", 'error');
            return false;
        }

        if (!$async) {
            $code = wp_remote_retrieve_response_code($response);
            if ($code >= 200 && $code < 300) {
                $this->log("Webhook successful: HTTP {$code}", 'info');
                return true;
            } else {
                $this->log("Webhook failed: HTTP {$code}", 'error');
                return false;
            }
        }

        return true;
    }

    /**
     * Generate HMAC signature
     */
    private function generate_signature(string $payload): string {
        $secret = RAG_Sync::get_webhook_secret();
        return base64_encode(hash_hmac('sha256', $payload, $secret, true));
    }

    /**
     * Start batching webhooks
     */
    public function start_batch(): void {
        $this->batching = true;
    }

    /**
     * End batching and send all queued webhooks
     */
    public function end_batch(): void {
        $this->batching = false;
        $this->flush_queue();
    }

    /**
     * Flush the webhook queue
     */
    public function flush_queue(): void {
        if (empty($this->queue)) {
            return;
        }

        $webhook_endpoint = RAG_Sync::get_option('webhook_endpoint', '');
        $webhook_secret = RAG_Sync::get_option('webhook_secret', '');

        if (empty($webhook_endpoint) || empty($webhook_secret)) {
            $this->queue = [];
            return;
        }

        // Send batch webhook
        if (count($this->queue) > 1) {
            $batch_payload = [
                'topic' => 'batch',
                'webhooks' => $this->queue,
                'site_url' => get_site_url(),
                'timestamp' => current_time('c'),
            ];

            $this->send_webhook($webhook_endpoint, 'batch', $batch_payload, true);
        } else if (count($this->queue) === 1) {
            $item = $this->queue[0];
            $this->send_webhook($webhook_endpoint, $item['topic'], $item['payload'], true);
        }

        $this->queue = [];
    }

    /**
     * Log message
     */
    private function log(string $message, string $level = 'info', array $context = []): void {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $log_message = sprintf(
            '[RAG Sync] [%s] %s %s',
            strtoupper($level),
            $message,
            !empty($context) ? wp_json_encode($context) : ''
        );

        error_log($log_message);
    }

    // =========================================
    // Helper methods for building payloads
    // =========================================

    /**
     * Build post payload
     */
    public function build_post_payload(WP_Post $post): array {
        $author = get_userdata($post->post_author);

        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'type' => $post->post_type,
            'status' => $post->post_status,
            'content' => apply_filters('the_content', $post->post_content),
            'excerpt' => get_the_excerpt($post),
            'author' => $author ? $author->display_name : null,
            'author_id' => $post->post_author,
            'url' => get_permalink($post),
            'featured_image' => get_the_post_thumbnail_url($post, 'full'),
            'categories' => $this->get_post_categories($post),
            'tags' => $this->get_post_tags($post),
            'date' => $post->post_date,
            'modified' => $post->post_modified,
        ];
    }

    /**
     * Build page payload
     */
    public function build_page_payload(WP_Post $page): array {
        return [
            'id' => $page->ID,
            'title' => get_the_title($page),
            'slug' => $page->post_name,
            'status' => $page->post_status,
            'content' => apply_filters('the_content', $page->post_content),
            'excerpt' => get_the_excerpt($page),
            'url' => get_permalink($page),
            'parent' => $page->post_parent,
            'menu_order' => $page->menu_order,
            'date' => $page->post_date,
            'modified' => $page->post_modified,
        ];
    }

    /**
     * Build product payload (WooCommerce)
     */
    public function build_product_payload($product): array {
        if (!$product instanceof WC_Product) {
            $product = wc_get_product($product);
        }

        if (!$product) {
            return [];
        }

        return [
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'on_sale' => $product->is_on_sale(),
            // Currency information
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'price_formatted' => wc_price($product->get_price()),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'url' => $product->get_permalink(),
            'image' => wp_get_attachment_url($product->get_image_id()),
            'gallery' => $this->get_product_gallery($product),
            'categories' => $this->get_product_categories($product),
            'attributes' => $this->get_product_attributes($product),
            'date_created' => $product->get_date_created()?->format('c'),
            'date_modified' => $product->get_date_modified()?->format('c'),
        ];
    }

    /**
     * Build category payload (WooCommerce)
     */
    public function build_category_payload($term): array {
        if (is_int($term)) {
            $term = get_term($term, 'product_cat');
        }

        if (!$term || is_wp_error($term)) {
            return [];
        }

        $thumbnail_id = get_term_meta($term->term_id, 'thumbnail_id', true);

        return [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'parent' => $term->parent,
            'count' => $term->count,
            'url' => get_term_link($term),
            'image' => $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : null,
        ];
    }

    /**
     * Build coupon payload (WooCommerce)
     */
    public function build_coupon_payload($coupon): array {
        if (!$coupon instanceof WC_Coupon) {
            $coupon = new WC_Coupon($coupon);
        }

        return [
            'id' => $coupon->get_id(),
            'code' => $coupon->get_code(),
            'description' => $coupon->get_description(),
            'discount_type' => $coupon->get_discount_type(),
            'amount' => $coupon->get_amount(),
            'usage_limit' => $coupon->get_usage_limit(),
            'usage_count' => $coupon->get_usage_count(),
            'date_expires' => $coupon->get_date_expires()?->format('c'),
            'minimum_amount' => $coupon->get_minimum_amount(),
            'maximum_amount' => $coupon->get_maximum_amount(),
            'free_shipping' => $coupon->get_free_shipping(),
        ];
    }

    /**
     * Get post categories
     */
    private function get_post_categories(WP_Post $post): array {
        $categories = get_the_category($post->ID);
        return array_map(function($cat) {
            return [
                'id' => $cat->term_id,
                'name' => $cat->name,
                'slug' => $cat->slug,
            ];
        }, $categories);
    }

    /**
     * Get post tags
     */
    private function get_post_tags(WP_Post $post): array {
        $tags = get_the_tags($post->ID);
        if (!$tags) {
            return [];
        }
        return array_map(function($tag) {
            return [
                'id' => $tag->term_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ];
        }, $tags);
    }

    /**
     * Get product gallery images
     */
    private function get_product_gallery(WC_Product $product): array {
        $gallery_ids = $product->get_gallery_image_ids();
        return array_map('wp_get_attachment_url', $gallery_ids);
    }

    /**
     * Get product categories
     */
    private function get_product_categories(WC_Product $product): array {
        $term_ids = $product->get_category_ids();
        $categories = [];

        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, 'product_cat');
            if ($term && !is_wp_error($term)) {
                $categories[] = [
                    'id' => $term->term_id,
                    'name' => $term->name,
                    'slug' => $term->slug,
                ];
            }
        }

        return $categories;
    }

    /**
     * Get product attributes
     */
    private function get_product_attributes(WC_Product $product): array {
        $attributes = $product->get_attributes();
        $result = [];

        foreach ($attributes as $attribute) {
            if ($attribute instanceof WC_Product_Attribute) {
                $result[] = [
                    'name' => $attribute->get_name(),
                    'options' => $attribute->get_options(),
                    'visible' => $attribute->get_visible(),
                    'variation' => $attribute->get_variation(),
                ];
            }
        }

        return $result;
    }
}
