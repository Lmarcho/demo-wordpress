<?php
/**
 * Plugin Name: RAG Sync
 * Plugin URI: https://github.com/your-repo/rag-sync
 * Description: Syncs WordPress/WooCommerce content to RAG backend for AI-powered chatbot
 * Version: 1.0.0
 * Author: Your Company
 * Author URI: https://yourcompany.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rag-sync
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 *
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('RAG_SYNC_VERSION', '1.0.0');
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
        // Initialize admin
        $this->admin = new RAG_Sync_Admin();

        // Initialize webhook sender
        $this->webhook = new RAG_Sync_Webhook();

        // Initialize REST API for real-time product data
        new RAG_Sync_REST_API();

        // Initialize WordPress hooks (posts, pages)
        new RAG_Sync_WordPress_Hooks($this->webhook);

        // Initialize WooCommerce hooks if available
        if ($this->is_woocommerce_active()) {
            new RAG_Sync_WooCommerce_Hooks($this->webhook);
        }

        // Initialize chat widget on frontend
        add_action('wp_footer', [$this, 'render_chat_widget']);

        // Load text domain
        load_plugin_textdomain('rag-sync', false, dirname(RAG_SYNC_PLUGIN_BASENAME) . '/languages');
    }

    /**
     * Render chat widget on frontend
     *
     * Widget styling (colors, messages, position) is configured in Laravel backend.
     * This only provides essential connection config.
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

        // Essential config only - widget fetches styling from Laravel backend
        $debug_mode = self::get_option('widget_debug', false);
        $config = [
            'tenant' => $tenant_slug,
            'apiKey' => $api_key,
            'apiUrl' => rtrim($backend_url, '/'),
            'debug' => (bool) $debug_mode,
        ];

        // Add cache busting to ensure latest widget version is loaded
        // Uses plugin version + daily timestamp to bust cache when plugin updates or daily
        $cache_bust = RAG_SYNC_VERSION . '-' . gmdate('Ymd');
        $widget_url = rtrim($backend_url, '/') . '/widget/widget.iife.js?v=' . $cache_bust;
        ?>
        <script>
            (function() {
                var config = <?php echo wp_json_encode($config); ?>;
                var script = document.createElement('script');
                script.src = '<?php echo esc_url($widget_url); ?>';
                script.onload = function() {
                    if (typeof RAGWidget !== 'undefined') {
                        RAGWidget.init(config).then(function(instance) {
                            console.log('[RAG Widget] Initialized successfully');
                        }).catch(function(error) {
                            console.error('[RAG Widget] Initialization failed:', error);
                        });
                    } else {
                        console.error('[RAG Widget] RAGWidget not found after script load');
                    }
                };
                script.onerror = function() {
                    console.error('[RAG Widget] Failed to load widget script from:', '<?php echo esc_url($widget_url); ?>');
                };
                document.body.appendChild(script);
            })();
        </script>
        <?php
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
            // Widget settings - styling is managed in Laravel backend
            'rag_sync_widget_enabled' => false,
            'rag_sync_widget_debug' => false,
        ];

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
