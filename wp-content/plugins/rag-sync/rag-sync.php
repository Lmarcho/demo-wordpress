<?php
/**
 * Plugin Name: RAG Sync
 * Plugin URI: https://askrag.app
 * Description: Syncs WordPress/WooCommerce content to the AskRAG backend and embeds the AI-powered chat widget.
 * Version: 1.0.3
 * Author: AskRAG
 * Author URI: https://askrag.app
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rag-sync
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 8.0
 *
 * WC requires at least: 8.0
 * WC tested up to: 10.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('RAG_SYNC_VERSION', '1.0.3');
define('RAG_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RAG_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RAG_SYNC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main RAG Sync Plugin Class
 */
final class RAG_Sync {

    /**
     * Single instance
     */
    private static ?RAG_Sync $instance = null;

    /**
     * Admin settings handler
     */
    public ?RAG_Sync_Admin $admin = null;

    /**
     * Webhook sender
     */
    public ?RAG_Sync_Webhook $webhook = null;

    /**
     * Get single instance
     */
    public static function instance(): RAG_Sync {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies(): void {
        require_once RAG_SYNC_PLUGIN_DIR . 'includes/class-rag-sync-db.php';
        require_once RAG_SYNC_PLUGIN_DIR . 'includes/class-rag-sync-admin.php';
        require_once RAG_SYNC_PLUGIN_DIR . 'includes/class-rag-sync-webhook.php';
        require_once RAG_SYNC_PLUGIN_DIR . 'includes/class-rag-sync-rest-api.php';
        require_once RAG_SYNC_PLUGIN_DIR . 'includes/class-rag-sync-wordpress-hooks.php';
        require_once RAG_SYNC_PLUGIN_DIR . 'includes/class-rag-sync-woocommerce-hooks.php';
        require_once RAG_SYNC_PLUGIN_DIR . 'includes/mcp/class-rag-sync-mcp.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks(): void {
        // Initialize on plugins_loaded
        add_action('plugins_loaded', [$this, 'init']);

        // Activation/deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Add settings link on plugins page
        add_filter('plugin_action_links_' . RAG_SYNC_PLUGIN_BASENAME, [$this, 'add_settings_link']);
    }

    /**
     * Initialize plugin components
     */
    public function init(): void {
        $this->maybe_upgrade_mcp();

        // Initialize admin
        $this->admin = new RAG_Sync_Admin();

        // Initialize webhook sender
        $this->webhook = new RAG_Sync_Webhook();

        // Initialize REST API for real-time product data
        new RAG_Sync_REST_API();

        // Initialize MCP endpoints and optional WordPress Abilities integration
        new RAG_Sync_MCP();

        // Initialize WordPress hooks (posts, pages)
        new RAG_Sync_WordPress_Hooks($this->webhook);

        // Initialize WooCommerce hooks if available
        if ($this->is_woocommerce_active()) {
            new RAG_Sync_WooCommerce_Hooks($this->webhook);
        }

        // Initialize chat widget on frontend
        add_action('wp_footer', [$this, 'render_chat_widget']);

    }

    /**
     * Ensure MCP tables exist for already-installed plugin upgrades.
     */
    private function maybe_upgrade_mcp(): void {
        if (get_option('rag_sync_mcp_db_version') === RAG_SYNC_VERSION) {
            return;
        }

        RAG_Sync_MCP::create_tables();
        RAG_Sync_MCP::upgrade_default_client_tools();
        update_option('rag_sync_mcp_db_version', RAG_SYNC_VERSION);
    }

    /**
     * Enqueue the chat widget loader on the frontend.
     *
     * Widget styling (colors, messages, position, voice) is configured in the
     * AskRAG backend. A local loader script (enqueued, not injected inline)
     * loads the remote widget bundle and initializes it with the tenant config.
     */
    public function render_chat_widget(): void {
        // Only render if widget is enabled
        if (!self::get_option('widget_enabled', false)) {
            return;
        }

        $backend_url = self::get_backend_url();
        $api_key = self::get_option('api_key', '');
        $tenant_slug = self::get_option('tenant_slug', '');

        if (empty($backend_url) || empty($api_key) || empty($tenant_slug)) {
            return;
        }

        $api_base_url = rtrim($backend_url, '/');

        // Build config URL with tenant parameter
        $config_url = $api_base_url . '/api/widget/config';
        if (!empty($tenant_slug)) {
            $config_url .= '?tenant=' . rawurlencode($tenant_slug);
        }

        // Cache-bust tied to the deployed bundle (not the static plugin version) so
        // a rebuilt widget loads immediately instead of a stale cached copy. Append
        // ?refresh to any page URL to force a one-off bypass.
        $script_base_url = $api_base_url . '/widget/widget.iife.js';
        $cache_bust = $this->get_widget_cache_bust($script_base_url);
        $widget_url = $script_base_url . '?v=' . rawurlencode($cache_bust);

        wp_enqueue_script(
            'rag-sync-widget-loader',
            RAG_SYNC_PLUGIN_URL . 'assets/js/widget-loader.js',
            [],
            RAG_SYNC_VERSION,
            true
        );

        wp_localize_script('rag-sync-widget-loader', 'RAGSyncConfig', [
            'scriptUrl' => $widget_url,
            'configUrl' => $config_url,
            'apiBaseUrl' => $api_base_url,
            'apiKey' => $api_key,
            'customer' => $this->get_customer_context(),
            'session' => $this->get_chat_session(),
            'customerAssertionEndpoint' => $this->get_customer_assertion_endpoint(),
            'customerAssertionNonce' => $this->get_customer_assertion_nonce(),
            'debug' => (bool) self::get_option('widget_debug', false),
        ]);
    }

    /**
     * Endpoint used by the remote widget to request a short-lived customer
     * assertion from the current logged-in WordPress/WooCommerce session.
     */
    private function get_customer_assertion_endpoint(): ?string {
        if (!RAG_Sync_MCP::is_enabled() || !is_user_logged_in()) {
            return null;
        }

        return rest_url(RAG_Sync_MCP::REST_NAMESPACE . RAG_Sync_MCP::ASSERTION_ROUTE);
    }

    /**
     * WordPress REST cookie authentication requires a REST nonce for logged-in
     * browser requests. The assertion endpoint still validates the active user.
     */
    private function get_customer_assertion_nonce(): ?string {
        if (!RAG_Sync_MCP::is_enabled() || !is_user_logged_in()) {
            return null;
        }

        return wp_create_nonce('wp_rest');
    }

    /**
     * Build a cache-busting token for the widget bundle.
     *
     * Derived from the deployed bundle's Last-Modified/ETag rather than the static
     * plugin version, so a rebuilt widget gets a fresh URL automatically. The probe
     * is cached in a transient to avoid a remote request on every page render.
     *
     * The probe URL carries a unique query string so it can never be answered from
     * a CDN's immutable cache of the real bundle URL; it always reaches origin and
     * returns the true current Last-Modified/ETag. nginx ignores the query and
     * serves the same static file.
     */
    private function get_widget_cache_bust(string $script_base_url): string {
        if (filter_input(INPUT_GET, 'refresh', FILTER_UNSAFE_RAW) !== null) {
            return RAG_SYNC_VERSION . '-' . time();
        }

        $cached = get_transient('rag_sync_widget_bust');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $bust = RAG_SYNC_VERSION;
        $probe_url = add_query_arg('probe', (string) time(), $script_base_url);
        $response = wp_remote_head($probe_url, [
            'timeout' => 2,
            'headers' => ['Cache-Control' => 'no-cache'],
        ]);
        if (!is_wp_error($response)) {
            $signature = wp_remote_retrieve_header($response, 'last-modified');
            if (empty($signature)) {
                $signature = wp_remote_retrieve_header($response, 'etag');
            }
            if (!empty($signature)) {
                $bust = RAG_SYNC_VERSION . '-' . substr(md5((string) $signature), 0, 8);
            }
        }

        set_transient('rag_sync_widget_bust', $bust, 5 * MINUTE_IN_SECONDS);

        return $bust;
    }

    /**
     * Get customer context for the widget
     *
     * Similar to Magento's getCustomerContextJson() method.
     *
     * @return array|null
     */
    private function get_customer_context(): ?array {
        // Check if WooCommerce is active for customer data
        if (!$this->is_woocommerce_active()) {
            return [
                'isLoggedIn' => is_user_logged_in(),
                'groupId' => null,
            ];
        }

        $context = [
            'isLoggedIn' => is_user_logged_in(),
            'groupId' => null,
        ];

        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            // WooCommerce customer group (if using a groups plugin)
            $context['groupId'] = apply_filters('rag_sync_customer_group_id', null, $user);
        }

        return $context;
    }

    /**
     * Get chat session data for the widget
     *
     * Similar to Magento's getChatSessionJson() method.
     * Handles session persistence for both guests and logged-in users.
     *
     * @return array
     */
    private function get_chat_session(): array {
        $cookie_name = 'ragsync_chat_session';
        $is_logged_in = is_user_logged_in();
        $guest_session_id = isset($_COOKIE[$cookie_name]) ? sanitize_text_field(wp_unslash($_COOKIE[$cookie_name])) : null;

        $session = [
            'sessionId' => $this->get_chat_session_id(),
            'customerId' => null,
            'customerEmail' => null,
            'customerName' => null,
            'isLoggedIn' => $is_logged_in,
            'previousGuestSessionId' => null,
        ];

        if ($is_logged_in) {
            $user = wp_get_current_user();
            $session['customerId'] = (int) $user->ID;
            $session['customerEmail'] = $user->user_email;
            $session['customerName'] = trim($user->first_name . ' ' . $user->last_name);
            if (empty(trim($session['customerName']))) {
                $session['customerName'] = $user->display_name;
            }

            // If logged-in user still has a guest cookie, include it for session merging
            if (!empty($guest_session_id) && strpos($guest_session_id, 'guest_') === 0) {
                $session['previousGuestSessionId'] = $guest_session_id;
                // Clear the guest cookie since user is now logged in
                $this->clear_guest_session_cookie();
            }
        }

        return $session;
    }

    /**
     * Get or create chat session ID
     *
     * For logged-in users, the session is tied to user ID.
     * For guests, a UUID is generated and stored in a cookie.
     *
     * @return string
     */
    private function get_chat_session_id(): string {
        $cookie_name = 'ragsync_chat_session';

        // For logged-in users, use a user-based session ID
        if (is_user_logged_in()) {
            return 'customer_' . get_current_user_id();
        }

        // For guests, use cookie-based session ID
        if (isset($_COOKIE[$cookie_name]) && !empty($_COOKIE[$cookie_name])) {
            return sanitize_text_field(wp_unslash($_COOKIE[$cookie_name]));
        }

        // Generate new session ID for guest
        $session_id = $this->generate_session_id();
        $this->set_chat_session_cookie($session_id);

        return $session_id;
    }

    /**
     * Generate a new session ID (UUID v4 style)
     *
     * @return string
     */
    private function generate_session_id(): string {
        return sprintf(
            'guest_%s%s-%s-%s-%s-%s%s%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2))
        );
    }

    /**
     * Set chat session cookie
     *
     * @param string $session_id Session ID to store.
     */
    private function set_chat_session_cookie(string $session_id): void {
        $cookie_name = 'ragsync_chat_session';
        $duration = 30 * DAY_IN_SECONDS; // 30 days

        setcookie(
            $cookie_name,
            $session_id,
            [
                'expires' => time() + $duration,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => false, // Allow JS access for widget
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Clear guest session cookie after merging to customer session
     */
    private function clear_guest_session_cookie(): void {
        $cookie_name = 'ragsync_chat_session';

        setcookie(
            $cookie_name,
            '',
            [
                'expires' => time() - 3600,
                'path' => COOKIEPATH,
                'domain' => COOKIE_DOMAIN,
                'secure' => is_ssl(),
                'httponly' => false,
                'samesite' => 'Lax',
            ]
        );
    }

    /**
     * Plugin activation
     */
    public function activate(): void {
        // Load DB class if not already loaded
        if (!class_exists('RAG_Sync_DB')) {
            require_once RAG_SYNC_PLUGIN_DIR . 'includes/class-rag-sync-db.php';
        }

        // Create sync tracking table
        RAG_Sync_DB::create_table();
        RAG_Sync_MCP::create_tables();

        // Set default options
        $defaults = [
            'rag_sync_backend_url' => '',
            'rag_sync_tenant_slug' => '',
            'rag_sync_api_key' => '',
            'rag_sync_webhook_secret' => '', // User must copy from RAG backend
            'rag_sync_webhook_endpoint' => '', // User must copy from RAG backend
            'rag_sync_enabled' => false,
            'rag_sync_content_types' => [
                'posts' => true,
                'pages' => true,
                'products' => true,
                'categories' => true,
                'coupons' => true,
            ],
            'rag_sync_last_sync' => null,
            'rag_sync_sync_status' => 'idle',
            // Widget settings - styling and voice are managed in Laravel backend
            'rag_sync_widget_enabled' => false,
            'rag_sync_widget_debug' => false,
        ];

        $defaults = array_merge($defaults, RAG_Sync_MCP::defaults());

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }

        // Update database version
        update_option('rag_sync_db_version', '1.0.0');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate(): void {
        // Clean up scheduled events
        wp_clear_scheduled_hook('rag_sync_full_sync');

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Check if WooCommerce is active
     */
    public function is_woocommerce_active(): bool {
        return class_exists('WooCommerce');
    }

    /**
     * Add settings link to plugins page
     */
    public function add_settings_link(array $links): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('options-general.php?page=rag-sync'),
            __('Settings', 'rag-sync')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Get plugin option
     */
    public static function get_option(string $key, $default = null) {
        return get_option('rag_sync_' . $key, $default);
    }

    /**
     * Update plugin option
     */
    public static function update_option(string $key, $value): bool {
        return update_option('rag_sync_' . $key, $value);
    }

    /**
     * Check if sync is enabled
     */
    public static function is_enabled(): bool {
        return (bool) self::get_option('enabled', false);
    }

    /**
     * Get backend URL
     */
    public static function get_backend_url(): string {
        return rtrim(self::get_option('backend_url', ''), '/');
    }

    /**
     * Get API key
     */
    public static function get_api_key(): string {
        return self::get_option('api_key', '');
    }

    /**
     * Get webhook secret
     */
    public static function get_webhook_secret(): string {
        return self::get_option('webhook_secret', '');
    }

    /**
     * Check if content type is enabled
     */
    public static function is_content_type_enabled(string $type): bool {
        $types = self::get_option('content_types', []);
        return isset($types[$type]) && $types[$type];
    }
}

/**
 * Get RAG Sync instance
 */
function rag_sync(): RAG_Sync {
    return RAG_Sync::instance();
}

// Initialize plugin
rag_sync();
