<?php
/**
 * RAG Sync REST API
 *
 * Provides real-time product data endpoint for Laravel to fetch
 * current price, stock, and availability information
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Sync_REST_API {

    /**
     * REST API namespace
     */
    const NAMESPACE = 'rag-sync/v1';

    /**
     * Constructor
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register REST API routes
     */
    public function register_routes(): void {
        // Get real-time product data (price, stock)
        register_rest_route(self::NAMESPACE, '/products/(?P<id>\d+)/live', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product_live_data'],
            'permission_callback' => [$this, 'check_api_key'],
            'args' => [
                'id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);

        // Get multiple products live data
        register_rest_route(self::NAMESPACE, '/products/live', [
            'methods' => 'POST',
            'callback' => [$this, 'get_products_live_data'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);

        // Get product by SKU
        register_rest_route(self::NAMESPACE, '/products/sku/(?P<sku>[a-zA-Z0-9_-]+)/live', [
            'methods' => 'GET',
            'callback' => [$this, 'get_product_by_sku_live_data'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);

        // Health check endpoint
        register_rest_route(self::NAMESPACE, '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check'],
            'permission_callback' => '__return_true',
        ]);
    }

    /**
     * Check API key for authentication
     */
    public function check_api_key(WP_REST_Request $request): bool {
        $api_key = $request->get_header('X-Api-Key');

        if (!$api_key) {
            return false;
        }

        // Verify against webhook secret (shared with Laravel)
        $webhook_secret = RAG_Sync::get_option('webhook_secret', '');

        if (empty($webhook_secret)) {
            return false;
        }

        // Simple API key check - the key should match webhook secret
        // In production, you might want a separate API key
        return hash_equals($webhook_secret, $api_key);
    }

    /**
     * Get live data for a single product
     */
    public function get_product_live_data(WP_REST_Request $request): WP_REST_Response {
        $product_id = (int) $request->get_param('id');
        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->build_live_product_data($product),
        ]);
    }

    /**
     * Get live data for multiple products
     */
    public function get_products_live_data(WP_REST_Request $request): WP_REST_Response {
        $body = $request->get_json_params();
        $product_ids = $body['ids'] ?? [];
        $skus = $body['skus'] ?? [];

        if (empty($product_ids) && empty($skus)) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'No product IDs or SKUs provided',
            ], 400);
        }

        $products = [];

        // Get by IDs
        foreach ($product_ids as $id) {
            $product = wc_get_product((int) $id);
            if ($product) {
                $products[] = $this->build_live_product_data($product);
            }
        }

        // Get by SKUs
        foreach ($skus as $sku) {
            $product_id = wc_get_product_id_by_sku($sku);
            if ($product_id) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $products[] = $this->build_live_product_data($product);
                }
            }
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $products,
            'count' => count($products),
        ]);
    }

    /**
     * Get live data for a product by SKU
     */
    public function get_product_by_sku_live_data(WP_REST_Request $request): WP_REST_Response {
        $sku = $request->get_param('sku');
        $product_id = wc_get_product_id_by_sku($sku);

        if (!$product_id) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_REST_Response([
                'success' => false,
                'error' => 'Product not found',
            ], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $this->build_live_product_data($product),
        ]);
    }

    /**
     * Health check endpoint
     */
    public function health_check(WP_REST_Request $request): WP_REST_Response {
        return new WP_REST_Response([
            'success' => true,
            'status' => 'healthy',
            'woocommerce' => class_exists('WooCommerce'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : null,
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
            'site_url' => get_site_url(),
            'timestamp' => current_time('c'),
        ]);
    }

    /**
     * Build live product data (price, stock, availability)
     * This data changes frequently and should not be cached in RAG
     */
    private function build_live_product_data($product): array {
        $data = [
            'id' => $product->get_id(),
            'sku' => $product->get_sku(),
            'name' => $product->get_name(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'permalink' => $product->get_permalink(),

            // Price data (real-time)
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => $product->get_price_html(),
            'on_sale' => $product->is_on_sale(),
            'date_on_sale_from' => $product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->format('Y-m-d H:i:s') : null,
            'date_on_sale_to' => $product->get_date_on_sale_to() ? $product->get_date_on_sale_to()->format('Y-m-d H:i:s') : null,

            // Stock data (real-time)
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'backorders' => $product->get_backorders(),
            'backorders_allowed' => $product->backorders_allowed(),
            'backordered' => $product->is_on_backorder(),
            'low_stock_amount' => $product->get_low_stock_amount(),

            // Availability
            'purchasable' => $product->is_purchasable(),
            'in_stock' => $product->is_in_stock(),
            'availability' => $this->get_availability_text($product),

            // Currency
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),

            // Timestamp
            'fetched_at' => current_time('c'),
        ];

        // Variable product: include variation data
        if ($product->is_type('variable')) {
            $data['variations'] = $this->get_variations_live_data($product);
            $data['min_price'] = $product->get_variation_price('min');
            $data['max_price'] = $product->get_variation_price('max');
            $data['price_range'] = $this->format_price_range($product);
        }

        return $data;
    }

    /**
     * Get variations live data for variable products
     */
    private function get_variations_live_data($product): array {
        $variations = [];

        foreach ($product->get_available_variations() as $variation_data) {
            $variation = wc_get_product($variation_data['variation_id']);
            if (!$variation) {
                continue;
            }

            $variations[] = [
                'id' => $variation->get_id(),
                'sku' => $variation->get_sku(),
                'attributes' => $variation_data['attributes'],

                // Price
                'price' => $variation->get_price(),
                'regular_price' => $variation->get_regular_price(),
                'sale_price' => $variation->get_sale_price(),
                'on_sale' => $variation->is_on_sale(),
                'price_html' => $variation->get_price_html(),

                // Stock
                'stock_status' => $variation->get_stock_status(),
                'stock_quantity' => $variation->get_stock_quantity(),
                'in_stock' => $variation->is_in_stock(),
                'purchasable' => $variation->is_purchasable(),
                'backorders_allowed' => $variation->backorders_allowed(),

                // Availability text
                'availability' => $this->get_availability_text($variation),
            ];
        }

        return $variations;
    }

    /**
     * Get human-readable availability text
     */
    private function get_availability_text($product): string {
        $availability = $product->get_availability();
        return $availability['availability'] ?? '';
    }

    /**
     * Format price range for variable products
     */
    private function format_price_range($product): string {
        $min_price = $product->get_variation_price('min');
        $max_price = $product->get_variation_price('max');

        if ($min_price === $max_price) {
            return wc_price($min_price);
        }

        return wc_price($min_price) . ' - ' . wc_price($max_price);
    }
}
