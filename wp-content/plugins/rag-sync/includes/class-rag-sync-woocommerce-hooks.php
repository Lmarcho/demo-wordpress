<?php
/**
 * RAG Sync WooCommerce Hooks
 *
 * Handles WooCommerce product, category, and coupon hooks
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

        // Only sync published products
        if ($product->get_status() !== 'publish') {
            return;
        }

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_PRODUCT_CREATED,
            $this->webhook->build_product_payload($product)
        );
    }

    /**
     * Product updated
     */
    public function on_product_updated(int $product_id, WC_Product $product): void {
        if (!RAG_Sync::is_content_type_enabled('products')) {
            return;
        }

        // Only sync published products
        if ($product->get_status() !== 'publish') {
            // If was published and now isn't, send delete
            $this->webhook->send(
                RAG_Sync_Webhook::TOPIC_PRODUCT_DELETED,
                ['id' => $product_id]
            );
            return;
        }

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_PRODUCT_UPDATED,
            $this->webhook->build_product_payload($product)
        );
    }

    /**
     * Product deleted
     */
    public function on_product_deleted(int $product_id): void {
        if (!RAG_Sync::is_content_type_enabled('products')) {
            return;
        }

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_PRODUCT_DELETED,
            ['id' => $product_id]
        );
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

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_CATEGORY_CREATED,
            $this->webhook->build_category_payload($term)
        );
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

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_CATEGORY_UPDATED,
            $this->webhook->build_category_payload($term)
        );
    }

    /**
     * Category deleted
     */
    public function on_category_deleted(int $term_id): void {
        if (!RAG_Sync::is_content_type_enabled('categories')) {
            return;
        }

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_CATEGORY_DELETED,
            ['id' => $term_id]
        );
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

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_COUPON_CREATED,
            $this->webhook->build_coupon_payload($coupon)
        );
    }

    /**
     * Coupon updated
     */
    public function on_coupon_updated(int $coupon_id): void {
        if (!RAG_Sync::is_content_type_enabled('coupons')) {
            return;
        }

        $coupon = new WC_Coupon($coupon_id);

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_COUPON_UPDATED,
            $this->webhook->build_coupon_payload($coupon)
        );
    }

    /**
     * Coupon deleted
     */
    public function on_coupon_deleted(int $coupon_id): void {
        if (!RAG_Sync::is_content_type_enabled('coupons')) {
            return;
        }

        $this->webhook->send(
            RAG_Sync_Webhook::TOPIC_COUPON_DELETED,
            ['id' => $coupon_id]
        );
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
