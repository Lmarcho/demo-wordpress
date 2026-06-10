<?php
/**
 * RAG Sync MCP server and shared commerce tools.
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Sync_MCP {
    const REST_NAMESPACE = 'rag-sync/v1';
    const FALLBACK_ROUTE = '/mcp';
    const ASSERTION_ROUTE = '/mcp/customer/assertion';
    const OPTION_ENABLED = 'mcp_enabled';
    const OPTION_PUBLIC_COUPONS = 'mcp_public_coupon_codes';
    const OPTION_ASSERTION_LIFETIME = 'mcp_assertion_lifetime';
    const OPTION_ASSERTION_KEY = 'mcp_assertion_signing_key';
    const OPTION_MAX_SKUS = 'mcp_max_skus';
    const OPTION_MAX_RESULTS = 'mcp_max_results';
    const OPTION_MAX_GALLERY = 'mcp_max_gallery';
    const OPTION_MAX_VARIANTS = 'mcp_max_variants';
    const OPTION_RATE_LIMIT = 'mcp_rate_limit';

    private const PROTOCOL_VERSION = '2025-11-25';
    private const SERVER_NAME = 'RAG Sync Commerce MCP';

    private const TOOLS = [
        'get_store_context' => 'Return WordPress/WooCommerce public store context.',
        'get_products_live' => 'Return normalized live WooCommerce data for requested SKUs.',
        'search_products_live' => 'Search public WooCommerce products.',
        'get_category_products' => 'Return public WooCommerce products assigned to a product category.',
        'get_product_variants' => 'Return bounded WooCommerce variation data.',
        'get_related_products' => 'Return related, upsell, or cross-sell WooCommerce products.',
        'get_active_promotions' => 'Return public active WooCommerce coupon summaries.',
        'get_content_live' => 'Return published WordPress posts or pages by ID or slug.',
        'search_content_live' => 'Search published WordPress posts and pages.',
        'get_order_status' => 'Return an asserted customer-owned WooCommerce order status.',
        'get_customer_cart' => 'Return the asserted customer persistent WooCommerce cart when available.',
        'get_customer_purchase_history' => 'Return asserted customer product-level purchase history.',
    ];

    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
        add_action('wp_abilities_api_init', [$this, 'register_abilities']);
        add_filter('mcp_adapter_tool_name', [$this, 'map_adapter_tool_name'], 10, 2);
    }

    public static function defaults(): array {
        return [
            'rag_sync_' . self::OPTION_ENABLED => false,
            'rag_sync_' . self::OPTION_PUBLIC_COUPONS => '',
            'rag_sync_' . self::OPTION_ASSERTION_LIFETIME => 300,
            'rag_sync_' . self::OPTION_ASSERTION_KEY => '',
            'rag_sync_' . self::OPTION_MAX_SKUS => 20,
            'rag_sync_' . self::OPTION_MAX_RESULTS => 10,
            'rag_sync_' . self::OPTION_MAX_GALLERY => 4,
            'rag_sync_' . self::OPTION_MAX_VARIANTS => 25,
            'rag_sync_' . self::OPTION_RATE_LIMIT => 120,
        ];
    }

    public static function client_table(): string {
        global $wpdb;
        return $wpdb->prefix . 'rag_sync_mcp_clients';
    }

    public static function create_tables(): void {
        global $wpdb;
        $table_name = self::client_table();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name varchar(128) NOT NULL,
            token_hash char(64) NOT NULL,
            allowed_tools text NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            expires_at datetime DEFAULT NULL,
            revoked_at datetime DEFAULT NULL,
            last_used_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY is_active (is_active),
            KEY revoked_at (revoked_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function drop_tables(): void {
        global $wpdb;
        $wpdb->query('DROP TABLE IF EXISTS ' . self::client_table());
    }

    public static function create_client(string $name, ?string $expires_at = null, ?array $allowed_tools = null): array {
        global $wpdb;
        $token = self::generate_token();
        $tools = $allowed_tools ?: array_keys(self::TOOLS);

        $wpdb->insert(self::client_table(), [
            'name' => sanitize_text_field($name),
            'token_hash' => hash('sha256', $token),
            'allowed_tools' => wp_json_encode(array_values(array_intersect($tools, array_keys(self::TOOLS)))),
            'is_active' => 1,
            'expires_at' => $expires_at,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ]);

        return [
            'id' => (int) $wpdb->insert_id,
            'token' => $token,
        ];
    }

    public static function revoke_client(int $client_id): bool {
        global $wpdb;
        return false !== $wpdb->update(
            self::client_table(),
            [
                'is_active' => 0,
                'revoked_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $client_id]
        );
    }

    public static function list_clients(): array {
        global $wpdb;
        return $wpdb->get_results(
            'SELECT id, name, allowed_tools, is_active, expires_at, revoked_at, last_used_at, created_at FROM ' . self::client_table() . ' ORDER BY created_at DESC',
            ARRAY_A
        ) ?: [];
    }

    public static function fallback_endpoint(): string {
        return rest_url(self::REST_NAMESPACE . self::FALLBACK_ROUTE);
    }

    public static function official_endpoint(): string {
        return rest_url('rag-sync-commerce-mcp/mcp');
    }

    public static function is_enabled(): bool {
        return (bool) RAG_Sync::get_option(self::OPTION_ENABLED, false);
    }

    public function register_routes(): void {
        register_rest_route(self::REST_NAMESPACE, self::FALLBACK_ROUTE, [
            'methods' => 'POST',
            'callback' => [$this, 'handle_mcp_request'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route(self::REST_NAMESPACE, self::ASSERTION_ROUTE, [
            'methods' => 'POST',
            'callback' => [$this, 'issue_customer_assertion'],
            'permission_callback' => function () {
                return self::is_enabled() && is_user_logged_in();
            },
        ]);
    }

    public function handle_mcp_request(WP_REST_Request $request): WP_REST_Response {
        $correlation_id = $this->correlation_id($request);
        if (!self::is_enabled()) {
            return $this->json_rpc_error(null, -32000, 'MCP server disabled', $correlation_id, [], 503);
        }

        $client = $this->authenticate($request);
        if (!$client) {
            return $this->json_rpc_error(null, -32001, 'Authentication failed', $correlation_id, [], 401);
        }
        if (!$this->rate_allowed('client:' . $client['id'], (int) RAG_Sync::get_option(self::OPTION_RATE_LIMIT, 120))) {
            return $this->json_rpc_error(null, -32007, 'Rate limit exceeded', $correlation_id, [], 429);
        }

        $payload = $request->get_body();
        $decoded = json_decode($payload, true);
        if (!is_array($decoded) || array_is_list($decoded)) {
            return $this->json_rpc_error(null, -32700, 'Parse error', $correlation_id);
        }

        $id = $decoded['id'] ?? null;
        $method = $decoded['method'] ?? null;
        $params = $decoded['params'] ?? [];
        if (($decoded['jsonrpc'] ?? null) !== '2.0' || !is_string($method) || !is_array($params) || array_is_list($params)) {
            return $this->json_rpc_error($id, -32600, 'Invalid Request', $correlation_id);
        }
        if (!array_key_exists('id', $decoded)) {
            return new WP_REST_Response(null, 202, $this->no_cache_headers($correlation_id));
        }

        try {
            $result = match ($method) {
                'initialize' => $this->initialize_result(),
                'ping' => [],
                'tools/list' => ['tools' => $this->list_tools($client['allowed_tools'])],
                'tools/call' => $this->handle_tool_call($params, $client, $correlation_id),
                default => throw new RAG_Sync_MCP_Exception('Method not found', -32601),
            };
            $result['_meta']['correlation_id'] = $correlation_id;
            return new WP_REST_Response([
                'jsonrpc' => '2.0',
                'id' => $id,
                'result' => $result,
            ], 200, $this->no_cache_headers($correlation_id));
        } catch (RAG_Sync_MCP_Exception $e) {
            return $this->json_rpc_error($id, $e->rpc_code, $e->getMessage(), $correlation_id, $e->data);
        } catch (Throwable $e) {
            error_log('[RAG Sync MCP] ' . $e->getMessage());
            return $this->json_rpc_error($id, -32603, 'Internal error', $correlation_id);
        }
    }

    public function issue_customer_assertion(WP_REST_Request $request): WP_REST_Response {
        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            return new WP_REST_Response(['error' => 'Customer login required'], 401, $this->no_cache_headers($this->correlation_id($request)));
        }

        return new WP_REST_Response([
            'customer_assertion' => $this->sign_assertion($user_id),
            'expires_in' => $this->assertion_lifetime(),
        ], 200, $this->no_cache_headers($this->correlation_id($request)));
    }

    public function register_abilities(): void {
        if (!function_exists('wp_register_ability') || !function_exists('wp_register_ability_category')) {
            return;
        }

        wp_register_ability_category('rag-sync-commerce', [
            'label' => __('RAG Sync Commerce', 'rag-sync'),
            'description' => __('Read-only RAG Sync commerce and content abilities.', 'rag-sync'),
        ]);

        foreach (self::TOOLS as $name => $description) {
            wp_register_ability('rag-sync/' . str_replace('_', '-', $name), [
                'label' => ucwords(str_replace('_', ' ', $name)),
                'description' => $description,
                'category' => 'rag-sync-commerce',
                'execute_callback' => function ($input = []) use ($name) {
                    try {
                        return $this->execute_tool($name, is_array($input) ? $input : []);
                    } catch (RAG_Sync_MCP_Exception $e) {
                        return new WP_Error('rag_sync_mcp_error', $e->getMessage(), $e->data);
                    }
                },
                'permission_callback' => function () {
                    return self::is_enabled();
                },
                'input_schema' => $this->schema_for($name),
                'output_schema' => ['type' => 'object'],
                'meta' => [
                    'annotations' => [
                        'readonly' => true,
                        'destructive' => false,
                        'idempotent' => true,
                    ],
                ],
            ]);
        }
    }

    public function map_adapter_tool_name(string $tool_name, $ability = null): string {
        $ability_name = is_object($ability) && method_exists($ability, 'get_name') ? $ability->get_name() : '';
        if (str_starts_with($ability_name, 'rag-sync/')) {
            return str_replace('-', '_', substr($ability_name, strlen('rag-sync/')));
        }
        return $tool_name;
    }

    private function initialize_result(): array {
        return [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => ['tools' => ['listChanged' => false]],
            'serverInfo' => ['name' => self::SERVER_NAME, 'version' => RAG_SYNC_VERSION],
            'instructions' => 'Read-only WordPress and WooCommerce tools. Customer tools require a customer assertion.',
        ];
    }

    private function handle_tool_call(array $params, array $client, string $correlation_id): array {
        $name = $params['name'] ?? null;
        if (!is_string($name) || !isset(self::TOOLS[$name])) {
            throw new RAG_Sync_MCP_Exception('Unknown tool', -32602);
        }
        if (!in_array($name, $client['allowed_tools'], true)) {
            throw new RAG_Sync_MCP_Exception('Access denied', -32003);
        }
        if ($name === 'get_order_status' && !$this->rate_allowed('order:' . $client['id'], 20)) {
            throw new RAG_Sync_MCP_Exception('Rate limit exceeded', -32007);
        }
        $arguments = $params['arguments'] ?? [];
        if (!is_array($arguments) || ($arguments !== [] && array_is_list($arguments))) {
            throw new RAG_Sync_MCP_Exception('Invalid tool arguments', -32602);
        }

        $structured = $this->execute_tool($name, $arguments);
        return $this->tool_result($structured);
    }

    private function execute_tool(string $name, array $args): array {
        return match ($name) {
            'get_store_context' => $this->get_store_context(),
            'get_products_live' => $this->get_products_live($args),
            'search_products_live' => $this->search_products_live($args),
            'get_category_products' => $this->get_category_products($args),
            'get_product_variants' => $this->get_product_variants($args),
            'get_related_products' => $this->get_related_products($args),
            'get_active_promotions' => $this->get_active_promotions($args),
            'get_content_live' => $this->get_content_live($args),
            'search_content_live' => $this->search_content_live($args),
            'get_order_status' => $this->get_order_status($args),
            'get_customer_cart' => $this->get_customer_cart($args),
            'get_customer_purchase_history' => $this->get_customer_purchase_history($args),
            default => throw new RAG_Sync_MCP_Exception('Unknown tool', -32602),
        };
    }

    private function tool_result(array $structured): array {
        $structured['schema_version'] ??= '1.0';
        $structured['fetched_at'] = gmdate('c');
        return [
            'content' => [[
                'type' => 'text',
                'text' => wp_json_encode($structured, JSON_UNESCAPED_SLASHES),
            ]],
            'structuredContent' => $structured,
            'isError' => false,
        ];
    }

    private function list_tools(array $allowed): array {
        $tools = [];
        foreach (self::TOOLS as $name => $description) {
            if (!in_array($name, $allowed, true)) {
                continue;
            }
            $tools[] = [
                'name' => $name,
                'description' => $description,
                'inputSchema' => $this->schema_for($name),
            ];
        }
        return $tools;
    }

    private function schema_for(string $name): array {
        return match ($name) {
            'get_products_live' => $this->object_schema(['skus' => ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 1]], ['skus']),
            'search_products_live', 'search_content_live' => $this->object_schema(['query' => ['type' => 'string'], 'limit' => ['type' => 'integer', 'minimum' => 1]], ['query']),
            'get_category_products' => $this->object_schema(['category_id' => ['type' => 'integer'], 'slug' => ['type' => 'string'], 'limit' => ['type' => 'integer', 'minimum' => 1]]),
            'get_product_variants', 'get_related_products' => $this->object_schema(['sku' => ['type' => 'string'], 'product_id' => ['type' => 'integer'], 'link_type' => ['type' => 'string']]),
            'get_content_live' => $this->object_schema(['ids' => ['type' => 'array', 'items' => ['type' => 'integer']], 'slugs' => ['type' => 'array', 'items' => ['type' => 'string']], 'type' => ['type' => 'string']]),
            'get_order_status' => $this->object_schema(['order_number' => ['type' => 'string'], 'customer_assertion' => ['type' => 'string']], ['order_number', 'customer_assertion']),
            'get_customer_cart', 'get_customer_purchase_history' => $this->object_schema(['customer_assertion' => ['type' => 'string'], 'limit' => ['type' => 'integer', 'minimum' => 1]], ['customer_assertion']),
            default => $this->object_schema(),
        };
    }

    private function object_schema(array $properties = [], array $required = []): array {
        $schema = [
            'type' => 'object',
            'properties' => $properties,
            'additionalProperties' => false,
        ];
        if ($required !== []) {
            $schema['required'] = $required;
        }
        return $schema;
    }

    private function get_store_context(): array {
        return [
            'store' => [
                'site_url' => get_site_url(),
                'home_url' => home_url('/'),
                'name' => get_bloginfo('name'),
                'locale' => get_locale(),
                'timezone' => wp_timezone_string(),
                'currency' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : null,
                'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : null,
                'woocommerce_active' => class_exists('WooCommerce'),
                'woocommerce_version' => class_exists('WooCommerce') && function_exists('WC') ? WC()->version : null,
            ],
        ];
    }

    private function get_products_live(array $args): array {
        $this->require_wc();
        $skus = array_slice(array_values(array_unique(array_filter(array_map('strval', $args['skus'] ?? [])))), 0, $this->max_skus());
        if (!$skus) {
            throw new RAG_Sync_MCP_Exception('Invalid tool arguments', -32602, ['error_code' => 'SKUS_REQUIRED']);
        }
        $products = [];
        $errors = [];
        foreach ($skus as $sku) {
            $product_id = wc_get_product_id_by_sku($sku);
            $product = $product_id ? wc_get_product($product_id) : null;
            if (!$product || $product->get_status() !== 'publish') {
                $errors[] = ['sku' => $sku, 'error_code' => 'PRODUCT_NOT_FOUND'];
                continue;
            }
            $products[] = $this->product_payload($product, true);
        }
        return ['products' => $products, 'errors' => $errors];
    }

    private function search_products_live(array $args): array {
        $this->require_wc();
        $query = sanitize_text_field((string) ($args['query'] ?? ''));
        if ($query === '') {
            throw new RAG_Sync_MCP_Exception('Invalid tool arguments', -32602, ['error_code' => 'QUERY_REQUIRED']);
        }
        $ids = wc_get_products([
            'status' => 'publish',
            'limit' => $this->limit($args),
            's' => $query,
            'return' => 'ids',
        ]);
        return ['products' => $this->products_from_ids($ids), 'query' => $query];
    }

    private function get_category_products(array $args): array {
        $this->require_wc();
        $category = null;
        if (!empty($args['category_id'])) {
            $category = get_term((int) $args['category_id'], 'product_cat');
        } elseif (!empty($args['slug'])) {
            $category = get_term_by('slug', sanitize_title((string) $args['slug']), 'product_cat');
        }
        if (!$category || is_wp_error($category)) {
            throw new RAG_Sync_MCP_Exception('Category not found', -32602, ['error_code' => 'CATEGORY_NOT_FOUND']);
        }
        $ids = wc_get_products([
            'status' => 'publish',
            'limit' => $this->limit($args),
            'category' => [$category->slug],
            'return' => 'ids',
        ]);
        return [
            'category' => $this->category_payload($category),
            'products' => $this->products_from_ids($ids),
        ];
    }

    private function get_product_variants(array $args): array {
        $this->require_wc();
        $product = $this->product_from_args($args);
        if (!$product) {
            throw new RAG_Sync_MCP_Exception('Product not found', -32602, ['error_code' => 'PRODUCT_NOT_FOUND']);
        }
        return [
            'product' => $this->product_payload($product, false),
            'variants' => $this->variant_payloads($product),
        ];
    }

    private function get_related_products(array $args): array {
        $this->require_wc();
        $product = $this->product_from_args($args);
        if (!$product) {
            throw new RAG_Sync_MCP_Exception('Product not found', -32602, ['error_code' => 'PRODUCT_NOT_FOUND']);
        }
        $type = (string) ($args['link_type'] ?? 'related');
        $ids = match ($type) {
            'upsell' => $product->get_upsell_ids(),
            'cross_sell', 'cross-sell' => $product->get_cross_sell_ids(),
            default => wc_get_related_products($product->get_id(), $this->limit($args), $product->get_upsell_ids()),
        };
        return [
            'link_type' => $type,
            'product' => $this->product_payload($product, false),
            'products' => $this->products_from_ids(array_slice($ids, 0, $this->limit($args))),
        ];
    }

    private function get_active_promotions(array $args): array {
        $this->require_wc();
        $allowed = array_filter(array_map('strtolower', array_map('trim', explode(',', (string) RAG_Sync::get_option(self::OPTION_PUBLIC_COUPONS, '')))));
        $coupons = get_posts([
            'post_type' => 'shop_coupon',
            'post_status' => 'publish',
            'posts_per_page' => $this->limit($args),
            'fields' => 'ids',
        ]);
        $promotions = [];
        foreach ($coupons as $coupon_id) {
            $coupon = new WC_Coupon($coupon_id);
            if (!$coupon->get_id() || !$this->coupon_is_active($coupon)) {
                continue;
            }
            $code = strtolower($coupon->get_code());
            $promotions[] = [
                'id' => $coupon->get_id(),
                'code' => in_array($code, $allowed, true) ? $coupon->get_code() : null,
                'description' => wp_strip_all_tags($coupon->get_description()),
                'discount_type' => $coupon->get_discount_type(),
                'amount' => (float) $coupon->get_amount(),
                'free_shipping' => (bool) $coupon->get_free_shipping(),
                'minimum_amount' => $coupon->get_minimum_amount(),
                'maximum_amount' => $coupon->get_maximum_amount(),
                'expires_at' => $coupon->get_date_expires() ? $coupon->get_date_expires()->date('c') : null,
            ];
        }
        return ['promotions' => $promotions];
    }

    private function get_content_live(array $args): array {
        $type = $this->content_type($args['type'] ?? null);
        $items = [];
        foreach ((array) ($args['ids'] ?? []) as $id) {
            $post = get_post((int) $id);
            if ($post && $post->post_type === $type && $post->post_status === 'publish') {
                $items[] = $this->post_payload($post);
            }
        }
        foreach ((array) ($args['slugs'] ?? []) as $slug) {
            $post = get_page_by_path(sanitize_title((string) $slug), OBJECT, $type);
            if ($post && $post->post_status === 'publish') {
                $items[] = $this->post_payload($post);
            }
        }
        return ['content' => array_values($items)];
    }

    private function search_content_live(array $args): array {
        $query = sanitize_text_field((string) ($args['query'] ?? ''));
        if ($query === '') {
            throw new RAG_Sync_MCP_Exception('Invalid tool arguments', -32602, ['error_code' => 'QUERY_REQUIRED']);
        }
        $wp_query = new WP_Query([
            'post_type' => ['post', 'page'],
            'post_status' => 'publish',
            's' => $query,
            'posts_per_page' => $this->limit($args),
        ]);
        return [
            'query' => $query,
            'content' => array_map([$this, 'post_payload'], $wp_query->posts),
        ];
    }

    private function get_order_status(array $args): array {
        $this->require_wc();
        $customer_id = $this->asserted_customer_id($args);
        $number = sanitize_text_field((string) ($args['order_number'] ?? ''));
        if ($number === '') {
            throw new RAG_Sync_MCP_Exception('Invalid tool arguments', -32602, ['error_code' => 'ORDER_NUMBER_REQUIRED']);
        }
        $orders = wc_get_orders([
            'customer_id' => $customer_id,
            'limit' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        foreach ($orders as $order) {
            if ((string) $order->get_order_number() === $number || (string) $order->get_id() === $number) {
                return ['order' => $this->order_payload($order)];
            }
        }
        throw new RAG_Sync_MCP_Exception('Order is not accessible', -32602, ['error_code' => 'ORDER_NOT_ACCESSIBLE']);
    }

    private function get_customer_cart(array $args): array {
        $this->require_wc();
        $customer_id = $this->asserted_customer_id($args);
        $cart = [];
        foreach (get_user_meta($customer_id) as $key => $values) {
            if (!str_starts_with($key, '_woocommerce_persistent_cart')) {
                continue;
            }
            $saved = maybe_unserialize($values[0] ?? null);
            if (is_array($saved) && !empty($saved['cart']) && is_array($saved['cart'])) {
                $cart = $saved['cart'];
                break;
            }
        }
        $items = [];
        foreach ($cart as $item) {
            $product = !empty($item['product_id']) ? wc_get_product((int) $item['product_id']) : null;
            if (!$product) {
                continue;
            }
            $items[] = [
                'sku' => $product->get_sku(),
                'product_sku' => $product->get_sku(),
                'name' => $product->get_name(),
                'quantity' => (float) ($item['quantity'] ?? 0),
                'currency' => get_woocommerce_currency(),
                'price' => (float) $product->get_price(),
                'row_total' => round(((float) $product->get_price()) * ((float) ($item['quantity'] ?? 0)), 2),
            ];
        }
        return [
            'cart' => [
                'items_count' => count($items),
                'items_qty' => array_sum(array_column($items, 'quantity')),
                'currency' => get_woocommerce_currency(),
                'items' => $items,
            ],
        ];
    }

    private function get_customer_purchase_history(array $args): array {
        $this->require_wc();
        $customer_id = $this->asserted_customer_id($args);
        $orders = wc_get_orders([
            'customer_id' => $customer_id,
            'limit' => 25,
            'orderby' => 'date',
            'order' => 'DESC',
            'status' => array_keys(wc_get_order_statuses()),
        ]);
        $by_sku = [];
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $product = $item->get_product();
                if (!$product || $product->get_sku() === '') {
                    continue;
                }
                $sku = $product->get_sku();
                $by_sku[$sku] ??= [
                    'sku' => $sku,
                    'product_sku' => $sku,
                    'name' => $item->get_name(),
                    'total_quantity' => 0.0,
                    'order_count' => 0,
                    'last_purchased_at' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
                ];
                $by_sku[$sku]['total_quantity'] += (float) $item->get_quantity();
                $by_sku[$sku]['order_count']++;
            }
        }
        return ['history' => ['returned' => count($by_sku), 'items' => array_values(array_slice($by_sku, 0, $this->limit($args), true))]];
    }

    private function product_payload(WC_Product $product, bool $include_variants): array {
        $payload = [
            'id' => $product->get_id(),
            'sku' => $product->get_sku(),
            'name' => $product->get_name(),
            'type' => $product->get_type(),
            'status' => $product->get_status(),
            'url' => $product->get_permalink(),
            'description' => wp_strip_all_tags($product->get_description()),
            'short_description' => wp_strip_all_tags($product->get_short_description()),
            'image' => wp_get_attachment_url($product->get_image_id()) ?: null,
            'gallery' => array_slice(array_values(array_filter(array_map('wp_get_attachment_url', $product->get_gallery_image_ids()))), 0, $this->max_gallery()),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'price_html' => wp_strip_all_tags($product->get_price_html()),
            'on_sale' => $product->is_on_sale(),
            'stock_status' => $product->get_stock_status(),
            'stock_quantity' => $product->get_stock_quantity(),
            'manage_stock' => $product->get_manage_stock(),
            'backorders_allowed' => $product->backorders_allowed(),
            'purchasable' => $product->is_purchasable(),
            'in_stock' => $product->is_in_stock(),
            'currency' => get_woocommerce_currency(),
            'currency_symbol' => get_woocommerce_currency_symbol(),
            'categories' => array_map(fn($term) => $this->category_payload($term), wp_get_post_terms($product->get_id(), 'product_cat')),
        ];
        if ($include_variants && $product->is_type('variable')) {
            $payload['variants'] = $this->variant_payloads($product);
        }
        return $payload;
    }

    private function variant_payloads(WC_Product $product): array {
        if (!$product->is_type('variable')) {
            return [];
        }
        $variants = [];
        foreach (array_slice($product->get_children(), 0, $this->max_variants()) as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) {
                continue;
            }
            $variants[] = [
                'id' => $variation->get_id(),
                'sku' => $variation->get_sku(),
                'attributes' => $variation->get_attributes(),
                'price' => $variation->get_price(),
                'regular_price' => $variation->get_regular_price(),
                'sale_price' => $variation->get_sale_price(),
                'stock_status' => $variation->get_stock_status(),
                'stock_quantity' => $variation->get_stock_quantity(),
                'in_stock' => $variation->is_in_stock(),
                'purchasable' => $variation->is_purchasable(),
            ];
        }
        return $variants;
    }

    private function category_payload($term): array {
        return [
            'id' => (int) $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => wp_strip_all_tags($term->description),
            'parent' => (int) $term->parent,
            'count' => (int) $term->count,
            'url' => get_term_link($term),
        ];
    }

    private function post_payload(WP_Post $post): array {
        return [
            'id' => $post->ID,
            'title' => get_the_title($post),
            'slug' => $post->post_name,
            'type' => $post->post_type,
            'status' => $post->post_status,
            'content' => wp_strip_all_tags(apply_filters('the_content', $post->post_content)),
            'excerpt' => wp_strip_all_tags(get_the_excerpt($post)),
            'url' => get_permalink($post),
            'featured_image' => get_the_post_thumbnail_url($post, 'full') ?: null,
            'date' => get_post_time('c', true, $post),
            'modified' => get_post_modified_time('c', true, $post),
        ];
    }

    private function order_payload(WC_Order $order): array {
        $items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $items[] = [
                'sku' => $product ? $product->get_sku() : '',
                'name' => $item->get_name(),
                'quantity' => (float) $item->get_quantity(),
            ];
        }
        return [
            'order_number' => (string) $order->get_order_number(),
            'status' => $order->get_status(),
            'status_label' => wc_get_order_status_name($order->get_status()),
            'placed_at' => $order->get_date_created() ? $order->get_date_created()->date('c') : null,
            'currency' => $order->get_currency(),
            'grand_total' => (float) $order->get_total(),
            'items' => $items,
        ];
    }

    private function products_from_ids(array $ids): array {
        $products = [];
        foreach ($ids as $id) {
            $product = wc_get_product((int) $id);
            if ($product && $product->get_status() === 'publish') {
                $products[] = $this->product_payload($product, false);
            }
        }
        return $products;
    }

    private function product_from_args(array $args): ?WC_Product {
        if (!empty($args['product_id'])) {
            $product = wc_get_product((int) $args['product_id']);
            return $product && $product->get_status() === 'publish' ? $product : null;
        }
        if (!empty($args['sku'])) {
            $id = wc_get_product_id_by_sku(sanitize_text_field((string) $args['sku']));
            $product = $id ? wc_get_product($id) : null;
            return $product && $product->get_status() === 'publish' ? $product : null;
        }
        return null;
    }

    private function coupon_is_active(WC_Coupon $coupon): bool {
        $expires = $coupon->get_date_expires();
        if ($expires && $expires->getTimestamp() < time()) {
            return false;
        }
        $limit = $coupon->get_usage_limit();
        return !$limit || $coupon->get_usage_count() < $limit;
    }

    private function asserted_customer_id(array $args): int {
        $assertion = (string) ($args['customer_assertion'] ?? '');
        $claims = $this->verify_assertion($assertion);
        return (int) $claims['sub'];
    }

    private function sign_assertion(int $user_id): string {
        $now = time();
        $payload = [
            'iss' => 'rag-sync',
            'aud' => 'rag-sync-mcp',
            'sub' => $user_id,
            'iat' => $now,
            'exp' => $now + $this->assertion_lifetime(),
            'nonce' => wp_generate_uuid4(),
        ];
        $encoded = $this->b64url(wp_json_encode($payload));
        return $encoded . '.' . $this->b64url(hash_hmac('sha256', $encoded, $this->assertion_key(), true));
    }

    private function verify_assertion(string $assertion): array {
        $parts = explode('.', $assertion);
        if (count($parts) !== 2) {
            throw new RAG_Sync_MCP_Exception('Invalid customer assertion', -32602, ['error_code' => 'INVALID_CUSTOMER_ASSERTION']);
        }
        [$payload, $signature] = $parts;
        $expected = hash_hmac('sha256', $payload, $this->assertion_key(), true);
        if (!hash_equals($expected, (string) $this->b64url_decode($signature))) {
            throw new RAG_Sync_MCP_Exception('Invalid customer assertion', -32602, ['error_code' => 'INVALID_CUSTOMER_ASSERTION']);
        }
        $claims = json_decode((string) $this->b64url_decode($payload), true);
        if (!is_array($claims) || ($claims['iss'] ?? '') !== 'rag-sync' || ($claims['aud'] ?? '') !== 'rag-sync-mcp' || (int) ($claims['exp'] ?? 0) < time()) {
            throw new RAG_Sync_MCP_Exception('Invalid customer assertion', -32602, ['error_code' => 'INVALID_CUSTOMER_ASSERTION']);
        }
        return $claims;
    }

    private function authenticate(WP_REST_Request $request): ?array {
        global $wpdb;
        $header = $request->get_header('authorization');
        if (!is_string($header) || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::client_table() . ' WHERE token_hash = %s AND is_active = 1 AND revoked_at IS NULL LIMIT 1',
            hash('sha256', trim($matches[1]))
        ), ARRAY_A);
        if (!$row || (!empty($row['expires_at']) && strtotime($row['expires_at']) <= time())) {
            return null;
        }
        $wpdb->update(self::client_table(), ['last_used_at' => current_time('mysql')], ['id' => (int) $row['id']]);
        $tools = json_decode((string) $row['allowed_tools'], true);
        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'allowed_tools' => is_array($tools) ? array_values(array_intersect($tools, array_keys(self::TOOLS))) : [],
        ];
    }

    private function rate_allowed(string $bucket, int $limit): bool {
        $limit = max(1, $limit);
        $key = 'rag_sync_mcp_rate_' . md5($bucket . ':' . gmdate('YmdHi'));
        $count = (int) get_transient($key);
        if ($count >= $limit) {
            return false;
        }
        set_transient($key, $count + 1, MINUTE_IN_SECONDS + 5);
        return true;
    }

    private function json_rpc_error($id, int $code, string $message, string $correlation_id, array $data = [], int $status = 200): WP_REST_Response {
        $data['correlation_id'] = $correlation_id;
        return new WP_REST_Response([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ],
        ], $status, $this->no_cache_headers($correlation_id));
    }

    private function no_cache_headers(string $correlation_id): array {
        return [
            'X-Correlation-ID' => $correlation_id,
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];
    }

    private function correlation_id(WP_REST_Request $request): string {
        $value = $request->get_header('x-correlation-id');
        return is_string($value) && $value !== '' ? sanitize_text_field($value) : wp_generate_uuid4();
    }

    private function content_type($type): string {
        return $type === 'page' ? 'page' : 'post';
    }

    private function limit(array $args): int {
        return min(max(1, (int) ($args['limit'] ?? $this->max_results())), $this->max_results());
    }

    private function max_skus(): int {
        return max(1, (int) RAG_Sync::get_option(self::OPTION_MAX_SKUS, 20));
    }

    private function max_results(): int {
        return max(1, (int) RAG_Sync::get_option(self::OPTION_MAX_RESULTS, 10));
    }

    private function max_gallery(): int {
        return max(0, (int) RAG_Sync::get_option(self::OPTION_MAX_GALLERY, 4));
    }

    private function max_variants(): int {
        return max(1, (int) RAG_Sync::get_option(self::OPTION_MAX_VARIANTS, 25));
    }

    private function assertion_lifetime(): int {
        return min(max(60, (int) RAG_Sync::get_option(self::OPTION_ASSERTION_LIFETIME, 300)), 3600);
    }

    private function assertion_key(): string {
        $configured = (string) RAG_Sync::get_option(self::OPTION_ASSERTION_KEY, '');
        return $configured !== '' ? $configured : wp_salt('auth');
    }

    private function require_wc(): void {
        if (!class_exists('WooCommerce')) {
            throw new RAG_Sync_MCP_Exception('WooCommerce unavailable', -32010, ['error_code' => 'WOOCOMMERCE_UNAVAILABLE']);
        }
    }

    private static function generate_token(): string {
        return 'wmcp_' . rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function b64url(string $value): string {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function b64url_decode(string $value): ?string {
        $value .= str_repeat('=', (4 - strlen($value) % 4) % 4);
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);
        return $decoded === false ? null : $decoded;
    }
}

class RAG_Sync_MCP_Exception extends Exception {
    public function __construct(
        string $message,
        public int $rpc_code,
        public array $data = []
    ) {
        parent::__construct($message);
    }
}
