<?php
/**
 * RAG Sync WooCommerce Hooks
 *
 * Handles WooCommerce product, category, and coupon hooks
 * Tracks sync status in database table
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Sync_WooCommerce_Hooks {

    /**
     * Webhook sender
     */
    private RAG_Sync_Webhook $webhook;

    /**
     * Track items being processed to avoid duplicate webhooks
     */
    private array $processing = [];

    /**
     * Constructor
     */
    public function __construct(RAG_Sync_Webhook $webhook) {
        $this->webhook = $webhook;

        // Only initialize hooks if WooCommerce is active
        if (class_exists('WooCommerce')) {
            $this->init_hooks();
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Product hooks
        add_action('woocommerce_new_product', [$this, 'on_product_created'], 10, 2);
        add_action('woocommerce_update_product', [$this, 'on_product_updated'], 10, 2);
        add_action('woocommerce_delete_product', [$this, 'on_product_deleted']);
        add_action('woocommerce_trash_product', [$this, 'on_product_trashed']);

        // Product variation hooks
        add_action('woocommerce_new_product_variation', [$this, 'on_variation_saved'], 10, 2);
        add_action('woocommerce_update_product_variation', [$this, 'on_variation_saved'], 10, 2);

        // Stock changes
        add_action('woocommerce_product_set_stock', [$this, 'on_stock_changed']);
        add_action('woocommerce_variation_set_stock', [$this, 'on_stock_changed']);

        // Category hooks
        add_action('create_product_cat', [$this, 'on_category_created']);
        add_action('edited_product_cat', [$this, 'on_category_updated']);
        add_action('delete_product_cat', [$this, 'on_category_deleted']);

        // Coupon hooks
        add_action('woocommerce_new_coupon', [$this, 'on_coupon_created']);
        add_action('woocommerce_update_coupon', [$this, 'on_coupon_updated']);
        add_action('woocommerce_delete_coupon', [$this, 'on_coupon_deleted']);
        add_action('woocommerce_trash_coupon', [$this, 'on_coupon_trashed']);

        // Order hooks (for live data updates)
        add_action('woocommerce_order_status_changed', [$this, 'on_order_status_changed'], 10, 4);
    }

    // =========================================
    // Product Hooks
    // =========================================

    /**
     * Product created
     */
    public function on_product_created(int $product_id, WC_Product $product): void {
        if (!RAG_Sync::is_content_type_enabled('products')) {
            return;
        }

        // Avoid duplicate processing
        $key = 'product_' . $product_id;
        if (isset($this->processing[$key])) {
            return;
        }
        $this->processing[$key] = true;

        // Only sync published products
        if ($product->get_status() !== 'publish') {
            unset($this->processing[$key]);
            return;
        }

        // Track in database
        RAG_Sync_DB::get_or_create_item('product', $product_id, $product->get_name(), $product->get_sku());

        $topic = RAG_Sync_Webhook::TOPIC_PRODUCT_CREATED;
        $result = $this->webhook->send($topic, $this->webhook->build_product_payload($product));

        // Update tracking
        RAG_Sync_DB::record_webhook('product', $product_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::update_status('product', $product_id, $result ? 'synced' : 'failed');

        unset($this->processing[$key]);
    }

    /**
     * Product updated
     */
    public function on_product_updated(int $product_id, WC_Product $product): void {
        if (!RAG_Sync::is_content_type_enabled('products')) {
            return;
        }

        // Avoid duplicate processing
        $key = 'product_' . $product_id;
        if (isset($this->processing[$key])) {
            return;
        }
        $this->processing[$key] = true;

        // Track in database
        RAG_Sync_DB::get_or_create_item('product', $product_id, $product->get_name(), $product->get_sku());

        // Only sync published products
        if ($product->get_status() !== 'publish') {
            // If was published and now isn't, send delete
            $topic = RAG_Sync_Webhook::TOPIC_PRODUCT_DELETED;
            $result = $this->webhook->send($topic, ['id' => $product_id]);
            RAG_Sync_DB::record_webhook('product', $product_id, $topic, $result ? 'sent' : 'failed');
            RAG_Sync_DB::update_status('product', $product_id, 'deleted');
            unset($this->processing[$key]);
            return;
        }

        $topic = RAG_Sync_Webhook::TOPIC_PRODUCT_UPDATED;
        $result = $this->webhook->send($topic, $this->webhook->build_product_payload($product));

        // Update tracking
        RAG_Sync_DB::record_webhook('product', $product_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::update_status('product', $product_id, $result ? 'synced' : 'failed');

        unset($this->processing[$key]);
    }

    /**
     * Product deleted
     */
    public function on_product_deleted(int $product_id): void {
        if (!RAG_Sync::is_content_type_enabled('products')) {
            return;
        }

        $topic = RAG_Sync_Webhook::TOPIC_PRODUCT_DELETED;
        $result = $this->webhook->send($topic, ['id' => $product_id]);

        // Update tracking
        RAG_Sync_DB::record_webhook('product', $product_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::mark_deleted('product', $product_id);
    }

    /**
     * Product trashed
     */
    public function on_product_trashed(int $product_id): void {
        $this->on_product_deleted($product_id);
    }

    /**
     * Variation saved
     */
    public function on_variation_saved(int $variation_id, WC_Product_Variation $variation): void {
        if (!RAG_Sync::is_content_type_enabled('products')) {
            return;
        }

        // Get parent product and send update
        $parent_id = $variation->get_parent_id();
        $parent = wc_get_product($parent_id);

        if ($parent && $parent->get_status() === 'publish') {
            $this->webhook->send(
                RAG_Sync_Webhook::TOPIC_PRODUCT_UPDATED,
                $this->webhook->build_product_payload($parent)
            );
        }
    }

    /**
     * Stock changed
     */
    public function on_stock_changed(WC_Product $product): void {
        if (!RAG_Sync::is_content_type_enabled('products')) {
            return;
        }

        // For variations, update parent
        if ($product->is_type('variation')) {
            $parent_id = $product->get_parent_id();
            $product = wc_get_product($parent_id);
        }

        if ($product && $product->get_status() === 'publish') {
            $this->webhook->send(
                RAG_Sync_Webhook::TOPIC_PRODUCT_UPDATED,
                $this->webhook->build_product_payload($product)
            );
        }
    }

    // =========================================
    // Category Hooks
    // =========================================

    /**
     * Category created
     */
    public function on_category_created(int $term_id): void {
        if (!RAG_Sync::is_content_type_enabled('categories')) {
            return;
        }

        $term = get_term($term_id, 'product_cat');
        if (!$term || is_wp_error($term)) {
            return;
        }

        // Track in database
        RAG_Sync_DB::get_or_create_item('category', $term_id, $term->name);

        $topic = RAG_Sync_Webhook::TOPIC_CATEGORY_CREATED;
        $result = $this->webhook->send($topic, $this->webhook->build_category_payload($term));

        // Update tracking
        RAG_Sync_DB::record_webhook('category', $term_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::update_status('category', $term_id, $result ? 'synced' : 'failed');
    }

    /**
     * Category updated
     */
    public function on_category_updated(int $term_id): void {
        if (!RAG_Sync::is_content_type_enabled('categories')) {
            return;
        }

        $term = get_term($term_id, 'product_cat');
        if (!$term || is_wp_error($term)) {
            return;
        }

        // Track in database
        RAG_Sync_DB::get_or_create_item('category', $term_id, $term->name);

        $topic = RAG_Sync_Webhook::TOPIC_CATEGORY_UPDATED;
        $result = $this->webhook->send($topic, $this->webhook->build_category_payload($term));

        // Update tracking
        RAG_Sync_DB::record_webhook('category', $term_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::update_status('category', $term_id, $result ? 'synced' : 'failed');
    }

    /**
     * Category deleted
     */
    public function on_category_deleted(int $term_id): void {
        if (!RAG_Sync::is_content_type_enabled('categories')) {
            return;
        }

        $topic = RAG_Sync_Webhook::TOPIC_CATEGORY_DELETED;
        $result = $this->webhook->send($topic, ['id' => $term_id]);

        // Update tracking
        RAG_Sync_DB::record_webhook('category', $term_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::mark_deleted('category', $term_id);
    }

    // =========================================
    // Coupon Hooks
    // =========================================

    /**
     * Coupon created
     */
    public function on_coupon_created(int $coupon_id): void {
        if (!RAG_Sync::is_content_type_enabled('coupons')) {
            return;
        }

        $coupon = new WC_Coupon($coupon_id);

        // Track in database
        RAG_Sync_DB::get_or_create_item('coupon', $coupon_id, $coupon->get_code());

        $topic = RAG_Sync_Webhook::TOPIC_COUPON_CREATED;
        $result = $this->webhook->send($topic, $this->webhook->build_coupon_payload($coupon));

        // Update tracking
        RAG_Sync_DB::record_webhook('coupon', $coupon_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::update_status('coupon', $coupon_id, $result ? 'synced' : 'failed');
    }

    /**
     * Coupon updated
     */
    public function on_coupon_updated(int $coupon_id): void {
        if (!RAG_Sync::is_content_type_enabled('coupons')) {
            return;
        }

        $coupon = new WC_Coupon($coupon_id);

        // Track in database
        RAG_Sync_DB::get_or_create_item('coupon', $coupon_id, $coupon->get_code());

        $topic = RAG_Sync_Webhook::TOPIC_COUPON_UPDATED;
        $result = $this->webhook->send($topic, $this->webhook->build_coupon_payload($coupon));

        // Update tracking
        RAG_Sync_DB::record_webhook('coupon', $coupon_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::update_status('coupon', $coupon_id, $result ? 'synced' : 'failed');
    }

    /**
     * Coupon deleted
     */
    public function on_coupon_deleted(int $coupon_id): void {
        if (!RAG_Sync::is_content_type_enabled('coupons')) {
            return;
        }

        $topic = RAG_Sync_Webhook::TOPIC_COUPON_DELETED;
        $result = $this->webhook->send($topic, ['id' => $coupon_id]);

        // Update tracking
        RAG_Sync_DB::record_webhook('coupon', $coupon_id, $topic, $result ? 'sent' : 'failed');
        RAG_Sync_DB::mark_deleted('coupon', $coupon_id);
    }

    /**
     * Coupon trashed
     */
    public function on_coupon_trashed(int $coupon_id): void {
        $this->on_coupon_deleted($coupon_id);
    }

    // =========================================
    // Order Hooks
    // =========================================

    /**
     * Order status changed
     */
    public function on_order_status_changed(int $order_id, string $old_status, string $new_status, WC_Order $order): void {
        // This hook is mainly for triggering stock updates
        // The actual stock change will be handled by on_stock_changed
        // We could also send order notifications to the backend here if needed
    }
}
