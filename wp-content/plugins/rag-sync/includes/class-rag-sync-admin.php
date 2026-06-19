<?php
/**
 * RAG Sync Admin Settings
 *
 * Handles the admin settings page and AJAX endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class RAG_Sync_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_rag_sync_test_connection', [$this, 'ajax_test_connection']);
        add_action('wp_ajax_rag_sync_trigger_full_sync', [$this, 'ajax_trigger_full_sync']);
        add_action('wp_ajax_rag_sync_get_status', [$this, 'ajax_get_status']);
        add_action('admin_post_rag_sync_mcp_create_client', [$this, 'handle_mcp_create_client']);
        add_action('admin_post_rag_sync_mcp_revoke_client', [$this, 'handle_mcp_revoke_client']);
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu(): void {
        add_options_page(
            __('RAG Sync Settings', 'rag-sync'),
            __('RAG Sync', 'rag-sync'),
            'manage_options',
            'rag-sync',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings(): void {
        // Connection settings
        register_setting('rag_sync_settings', 'rag_sync_backend_url', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_api_key', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_tenant_slug', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_webhook_secret', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_webhook_endpoint', [
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_enabled', [
            'type' => 'boolean',
            'default' => false,
        ]);

        register_setting('rag_sync_settings', 'rag_sync_content_types', [
            'type' => 'array',
            'default' => [
                'posts' => true,
                'pages' => true,
                'products' => true,
                'categories' => true,
                'coupons' => true,
            ],
        ]);

        // Connection section
        add_settings_section(
            'rag_sync_connection',
            __('Connection Settings', 'rag-sync'),
            [$this, 'render_connection_section'],
            'rag-sync'
        );

        add_settings_field(
            'rag_sync_backend_url',
            __('Backend URL', 'rag-sync'),
            [$this, 'render_backend_url_field'],
            'rag-sync',
            'rag_sync_connection'
        );

        add_settings_field(
            'rag_sync_tenant_slug',
            __('Tenant Slug', 'rag-sync'),
            [$this, 'render_tenant_slug_field'],
            'rag-sync',
            'rag_sync_connection'
        );

        add_settings_field(
            'rag_sync_api_key',
            __('API Key', 'rag-sync'),
            [$this, 'render_api_key_field'],
            'rag-sync',
            'rag_sync_connection'
        );

        add_settings_field(
            'rag_sync_webhook_secret',
            __('Webhook Secret', 'rag-sync'),
            [$this, 'render_webhook_secret_field'],
            'rag-sync',
            'rag_sync_connection'
        );

        add_settings_field(
            'rag_sync_webhook_endpoint',
            __('Webhook Endpoint', 'rag-sync'),
            [$this, 'render_webhook_endpoint_field'],
            'rag-sync',
            'rag_sync_connection'
        );

        add_settings_field(
            'rag_sync_enabled',
            __('Enable Sync', 'rag-sync'),
            [$this, 'render_enabled_field'],
            'rag-sync',
            'rag_sync_connection'
        );

        // Content types section
        add_settings_section(
            'rag_sync_content',
            __('Content Types', 'rag-sync'),
            [$this, 'render_content_section'],
            'rag-sync'
        );

        add_settings_field(
            'rag_sync_content_types',
            __('Sync Content Types', 'rag-sync'),
            [$this, 'render_content_types_field'],
            'rag-sync',
            'rag_sync_content'
        );

        // Widget settings
        register_setting('rag_sync_settings', 'rag_sync_widget_enabled', [
            'type' => 'boolean',
            'default' => false,
        ]);

        register_setting('rag_sync_settings', 'rag_sync_widget_debug', [
            'type' => 'boolean',
            'default' => false,
        ]);

        // Widget section
        // Note: Widget styling (colors, messages, position, voice) is configured in Laravel backend
        add_settings_section(
            'rag_sync_widget',
            __('Chat Widget', 'rag-sync'),
            [$this, 'render_widget_section'],
            'rag-sync'
        );

        add_settings_field(
            'rag_sync_widget_enabled',
            __('Enable Widget', 'rag-sync'),
            [$this, 'render_widget_enabled_field'],
            'rag-sync',
            'rag_sync_widget'
        );

        add_settings_field(
            'rag_sync_widget_debug',
            __('Debug Mode', 'rag-sync'),
            [$this, 'render_widget_debug_field'],
            'rag-sync',
            'rag_sync_widget'
        );

        // MCP settings
        register_setting('rag_sync_settings', 'rag_sync_mcp_enabled', [
            'type' => 'boolean',
            'default' => false,
        ]);

        register_setting('rag_sync_settings', 'rag_sync_mcp_public_coupon_codes', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_mcp_assertion_lifetime', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 300,
        ]);

        register_setting('rag_sync_settings', 'rag_sync_mcp_max_skus', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 20,
        ]);

        register_setting('rag_sync_settings', 'rag_sync_mcp_max_results', [
            'type' => 'integer',
            'sanitize_callback' => 'absint',
            'default' => 10,
        ]);

        add_settings_section(
            'rag_sync_mcp',
            __('MCP Server', 'rag-sync'),
            [$this, 'render_mcp_section'],
            'rag-sync'
        );

        add_settings_field(
            'rag_sync_mcp_enabled',
            __('Enable MCP', 'rag-sync'),
            [$this, 'render_mcp_enabled_field'],
            'rag-sync',
            'rag_sync_mcp'
        );

        add_settings_field(
            'rag_sync_mcp_public_coupon_codes',
            __('Public Coupon Codes', 'rag-sync'),
            [$this, 'render_mcp_public_coupon_codes_field'],
            'rag-sync',
            'rag_sync_mcp'
        );

        add_settings_field(
            'rag_sync_mcp_limits',
            __('Tool Limits', 'rag-sync'),
            [$this, 'render_mcp_limits_field'],
            'rag-sync',
            'rag_sync_mcp'
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets(string $hook): void {
        if ($hook !== 'settings_page_rag-sync') {
            return;
        }

        wp_enqueue_style(
            'rag-sync-admin',
            RAG_SYNC_PLUGIN_URL . 'assets/css/admin.css',
            [],
            RAG_SYNC_VERSION
        );

        wp_enqueue_script(
            'rag-sync-admin',
            RAG_SYNC_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            RAG_SYNC_VERSION,
            true
        );

        wp_localize_script('rag-sync-admin', 'ragSyncAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('rag_sync_nonce'),
            'strings' => [
                'testing' => __('Testing...', 'rag-sync'),
                'syncing' => __('Syncing...', 'rag-sync'),
                'success' => __('Success!', 'rag-sync'),
                'error' => __('Error', 'rag-sync'),
                'connected' => __('Connected', 'rag-sync'),
                'disconnected' => __('Disconnected', 'rag-sync'),
            ],
        ]);
    }

    /**
     * Render settings page
     */
    public function render_settings_page(): void {
        if (!current_user_can('manage_options')) {
            return;
        }

        $last_sync = RAG_Sync::get_option('last_sync');
        $sync_status = RAG_Sync::get_option('sync_status', 'idle');
        $is_woocommerce = rag_sync()->is_woocommerce_active();
        ?>
        <div class="wrap rag-sync-settings">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="rag-sync-status-bar">
                <div class="status-item">
                    <span class="status-label"><?php esc_html_e('Status:', 'rag-sync'); ?></span>
                    <span class="status-value status-<?php echo esc_attr($sync_status); ?>" id="rag-sync-status">
                        <?php echo esc_html(ucfirst($sync_status)); ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?php esc_html_e('Last Sync:', 'rag-sync'); ?></span>
                    <span class="status-value" id="rag-sync-last-sync">
                        <?php echo $last_sync ? esc_html(human_time_diff(strtotime($last_sync)) . ' ago') : esc_html__('Never', 'rag-sync'); ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?php esc_html_e('WooCommerce:', 'rag-sync'); ?></span>
                    <span class="status-value <?php echo $is_woocommerce ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $is_woocommerce ? esc_html__('Active', 'rag-sync') : esc_html__('Not Installed', 'rag-sync'); ?>
                    </span>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php
                settings_fields('rag_sync_settings');
                do_settings_sections('rag-sync');
                submit_button();
                ?>
            </form>

            <div class="rag-sync-actions">
                <h2><?php esc_html_e('Actions', 'rag-sync'); ?></h2>

                <div class="action-buttons">
                    <button type="button" class="button button-secondary" id="rag-sync-test-connection">
                        <?php esc_html_e('Test Connection', 'rag-sync'); ?>
                    </button>

                    <button type="button" class="button button-primary" id="rag-sync-full-sync">
                        <?php esc_html_e('Trigger Full Sync', 'rag-sync'); ?>
                    </button>
                </div>

            <div id="rag-sync-message" class="rag-sync-message" style="display: none;"></div>
            </div>

            <?php $this->render_mcp_clients_panel(); ?>

            <?php
            $webhook_endpoint = RAG_Sync::get_option('webhook_endpoint', '');
            $has_webhook_secret = !empty(RAG_Sync::get_option('webhook_secret', ''));
            $is_configured = $webhook_endpoint && $has_webhook_secret;
            ?>
            <div class="rag-sync-webhook-info">
                <h2><?php esc_html_e('Webhook Status', 'rag-sync'); ?></h2>
                <p><?php esc_html_e('Real-time updates will be sent to your RAG backend when content changes.', 'rag-sync'); ?></p>

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Configuration Status', 'rag-sync'); ?></th>
                        <td>
                            <?php if ($is_configured): ?>
                                <span class="status-value status-active"><?php esc_html_e('Ready', 'rag-sync'); ?></span>
                                <span class="description"><?php esc_html_e('Use "Test Connection" to verify the webhook secret.', 'rag-sync'); ?></span>
                            <?php else: ?>
                                <span class="status-value status-error"><?php esc_html_e('Incomplete', 'rag-sync'); ?></span>
                                <?php if (!$webhook_endpoint): ?>
                                    <p class="description"><?php esc_html_e('Missing: Webhook Endpoint', 'rag-sync'); ?></p>
                                <?php endif; ?>
                                <?php if (!$has_webhook_secret): ?>
                                    <p class="description"><?php esc_html_e('Missing: Webhook Secret', 'rag-sync'); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('WordPress Site URL', 'rag-sync'); ?></th>
                        <td>
                            <code id="site-url"><?php echo esc_url(get_site_url()); ?></code>
                            <button type="button" class="button button-small copy-btn" data-target="site-url">
                                <?php esc_html_e('Copy', 'rag-sync'); ?>
                            </button>
                            <p class="description"><?php esc_html_e('Enter this URL as the WooCommerce Store URL in your RAG backend.', 'rag-sync'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php $this->render_sync_status_table(); ?>

            <div class="rag-sync-debug">
                <h2><?php esc_html_e('Debug Information', 'rag-sync'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('WordPress Version', 'rag-sync'); ?></th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('PHP Version', 'rag-sync'); ?></th>
                        <td><?php echo esc_html(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Site URL', 'rag-sync'); ?></th>
                        <td><?php echo esc_url(get_site_url()); ?></td>
                    </tr>
                    <?php if ($is_woocommerce): ?>
                    <tr>
                        <th><?php esc_html_e('WooCommerce Version', 'rag-sync'); ?></th>
                        <td><?php echo esc_html(WC()->version); ?></td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render connection section description
     */
    public function render_connection_section(): void {
        echo '<p>' . esc_html__('Configure the connection to your RAG backend server.', 'rag-sync') . '</p>';
    }

    /**
     * Render content section description
     */
    public function render_content_section(): void {
        echo '<p>' . esc_html__('Select which content types to sync to the RAG backend.', 'rag-sync') . '</p>';
    }

    /**
     * Render backend URL field
     */
    public function render_backend_url_field(): void {
        $value = RAG_Sync::get_option('backend_url', '');
        ?>
        <input type="url"
               name="rag_sync_backend_url"
               id="rag_sync_backend_url"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="https://your-rag-backend.com">
        <p class="description">
            <?php esc_html_e('The URL of your RAG backend server (e.g., https://api.yoursite.com)', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render tenant slug field
     */
    public function render_tenant_slug_field(): void {
        $value = RAG_Sync::get_option('tenant_slug', '');
        ?>
        <input type="text"
               name="rag_sync_tenant_slug"
               id="rag_sync_tenant_slug"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="my-store">
        <p class="description">
            <?php esc_html_e('Your tenant slug in the RAG backend (used for webhook URL)', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render API key field
     */
    public function render_api_key_field(): void {
        $value = RAG_Sync::get_option('api_key', '');
        ?>
        <input type="password"
               name="rag_sync_api_key"
               id="rag_sync_api_key"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="wgt_xxxxx">
        <p class="description">
            <?php esc_html_e('Widget API key from your RAG backend (starts with wgt_)', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render webhook secret field
     */
    public function render_webhook_secret_field(): void {
        $value = RAG_Sync::get_option('webhook_secret', '');
        ?>
        <input type="password"
               name="rag_sync_webhook_secret"
               id="rag_sync_webhook_secret"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="whsec_wc_xxxxx">
        <p class="description">
            <?php esc_html_e('Copy the Webhook Secret from your RAG backend (Settings → WooCommerce Integration)', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render webhook endpoint field
     */
    public function render_webhook_endpoint_field(): void {
        $value = RAG_Sync::get_option('webhook_endpoint', '');
        $backend_url = RAG_Sync::get_option('backend_url', '');
        $tenant_slug = RAG_Sync::get_option('tenant_slug', '');
        $suggested = '';
        if ($backend_url && $tenant_slug) {
            $suggested = rtrim($backend_url, '/') . '/api/woocommerce/webhook/' . $tenant_slug;
        }
        ?>
        <input type="url"
               name="rag_sync_webhook_endpoint"
               id="rag_sync_webhook_endpoint"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="<?php echo esc_attr($suggested); ?>">
        <?php if ($suggested && $suggested !== $value): ?>
        <button type="button" class="button button-small" id="rag-sync-auto-endpoint" data-endpoint="<?php echo esc_attr($suggested); ?>">
            <?php esc_html_e('Use Default', 'rag-sync'); ?>
        </button>
        <?php endif; ?>
        <p class="description">
            <?php esc_html_e('The Laravel endpoint URL where webhooks will be sent. Copy from RAG backend settings.', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render enabled field
     */
    public function render_enabled_field(): void {
        $value = RAG_Sync::get_option('enabled', false);
        ?>
        <label>
            <input type="checkbox"
                   name="rag_sync_enabled"
                   id="rag_sync_enabled"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable automatic sync when content is created or updated', 'rag-sync'); ?>
        </label>
        <?php
    }

    /**
     * Render content types field
     */
    public function render_content_types_field(): void {
        $types = RAG_Sync::get_option('content_types', []);
        $is_woocommerce = rag_sync()->is_woocommerce_active();

        $available_types = [
            'posts' => __('Blog Posts', 'rag-sync'),
            'pages' => __('Pages', 'rag-sync'),
        ];

        if ($is_woocommerce) {
            $available_types['products'] = __('Products', 'rag-sync');
            $available_types['categories'] = __('Product Categories', 'rag-sync');
            $available_types['coupons'] = __('Coupons', 'rag-sync');
        }

        foreach ($available_types as $key => $label) {
            $checked = isset($types[$key]) && $types[$key];
            ?>
            <label style="display: block; margin-bottom: 8px;">
                <input type="checkbox"
                       name="rag_sync_content_types[<?php echo esc_attr($key); ?>]"
                       value="1"
                       <?php checked($checked, true); ?>>
                <?php echo esc_html($label); ?>
            </label>
            <?php
        }

        if (!$is_woocommerce) {
            ?>
            <p class="description" style="margin-top: 10px;">
                <?php esc_html_e('Install WooCommerce to sync products, categories, and coupons.', 'rag-sync'); ?>
            </p>
            <?php
        }
    }

    /**
     * Render widget section description
     */
    public function render_widget_section(): void {
        echo '<p>' . esc_html__('Enable the AI chat widget on your site. Widget styling (colors, messages, position) and voice settings are configured in the Laravel backend.', 'rag-sync') . '</p>';
    }

    /**
     * Render widget enabled field
     */
    public function render_widget_enabled_field(): void {
        $value = RAG_Sync::get_option('widget_enabled', false);
        ?>
        <label>
            <input type="checkbox"
                   name="rag_sync_widget_enabled"
                   id="rag_sync_widget_enabled"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Show chat widget on frontend', 'rag-sync'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Requires API Key to be configured above.', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render widget debug field
     */
    public function render_widget_debug_field(): void {
        $value = RAG_Sync::get_option('widget_debug', false);
        ?>
        <label>
            <input type="checkbox"
                   name="rag_sync_widget_debug"
                   id="rag_sync_widget_debug"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable debug logging in browser console', 'rag-sync'); ?>
        </label>
        <p class="description">
            <?php esc_html_e('Useful for troubleshooting widget configuration loading. Disable in production.', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render MCP section description.
     */
    public function render_mcp_section(): void {
        ?>
        <p><?php esc_html_e('Expose read-only WordPress and WooCommerce tools to your AskRAG backend over MCP.', 'rag-sync'); ?></p>
        <p>
            <?php esc_html_e('Fallback endpoint:', 'rag-sync'); ?>
            <code><?php echo esc_url(RAG_Sync_MCP::fallback_endpoint()); ?></code>
        </p>
        <p>
            <?php esc_html_e('Official adapter endpoint, when the WordPress MCP Adapter is installed:', 'rag-sync'); ?>
            <code><?php echo esc_url(RAG_Sync_MCP::official_endpoint()); ?></code>
        </p>
        <?php
    }

    /**
     * Render MCP enabled field.
     */
    public function render_mcp_enabled_field(): void {
        $value = RAG_Sync::get_option('mcp_enabled', false);
        ?>
        <label>
            <input type="checkbox"
                   name="rag_sync_mcp_enabled"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php esc_html_e('Enable MCP server endpoints', 'rag-sync'); ?>
        </label>
        <?php
    }

    /**
     * Render public coupon allow-list.
     */
    public function render_mcp_public_coupon_codes_field(): void {
        $value = RAG_Sync::get_option('mcp_public_coupon_codes', '');
        ?>
        <input type="text"
               name="rag_sync_mcp_public_coupon_codes"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text"
               placeholder="WELCOME10, FREESHIP">
        <p class="description"><?php esc_html_e('Only these coupon codes may be disclosed through MCP promotion tools. Leave blank to hide all codes.', 'rag-sync'); ?></p>
        <?php
    }

    /**
     * Render MCP limits.
     */
    public function render_mcp_limits_field(): void {
        ?>
        <label>
            <?php esc_html_e('Max SKUs:', 'rag-sync'); ?>
            <input type="number" min="1" max="100" name="rag_sync_mcp_max_skus" value="<?php echo esc_attr((string) RAG_Sync::get_option('mcp_max_skus', 20)); ?>" class="small-text">
        </label>
        <label style="margin-left: 12px;">
            <?php esc_html_e('Max results:', 'rag-sync'); ?>
            <input type="number" min="1" max="100" name="rag_sync_mcp_max_results" value="<?php echo esc_attr((string) RAG_Sync::get_option('mcp_max_results', 10)); ?>" class="small-text">
        </label>
        <label style="margin-left: 12px;">
            <?php esc_html_e('Assertion lifetime:', 'rag-sync'); ?>
            <input type="number" min="60" max="3600" name="rag_sync_mcp_assertion_lifetime" value="<?php echo esc_attr((string) RAG_Sync::get_option('mcp_assertion_lifetime', 300)); ?>" class="small-text">
            <?php esc_html_e('seconds', 'rag-sync'); ?>
        </label>
        <?php
    }

    /**
     * Render MCP clients panel.
     */
    private function render_mcp_clients_panel(): void {
        if (!class_exists('RAG_Sync_MCP')) {
            return;
        }

        $created_token = '';
        $created_token_param = filter_input(INPUT_GET, 'rag_sync_mcp_token', FILTER_UNSAFE_RAW);
        if (is_string($created_token_param)) {
            $created_token = sanitize_text_field(wp_unslash($created_token_param));
        }
        $clients = RAG_Sync_MCP::list_clients();
        ?>
        <div class="rag-sync-mcp-clients">
            <h2><?php esc_html_e('MCP Clients', 'rag-sync'); ?></h2>
            <?php if ($created_token): ?>
                <div class="notice notice-success inline">
                    <p><?php esc_html_e('Copy this token now. It will not be shown again:', 'rag-sync'); ?></p>
                    <p><code><?php echo esc_html($created_token); ?></code></p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('rag_sync_mcp_create_client'); ?>
                <input type="hidden" name="action" value="rag_sync_mcp_create_client">
                <input type="text" name="client_name" class="regular-text" placeholder="<?php esc_attr_e('Laravel Chat', 'rag-sync'); ?>" required>
                <button type="submit" class="button button-secondary"><?php esc_html_e('Create MCP Token', 'rag-sync'); ?></button>
            </form>

            <table class="widefat striped" style="margin-top: 12px;">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Client', 'rag-sync'); ?></th>
                        <th><?php esc_html_e('Status', 'rag-sync'); ?></th>
                        <th><?php esc_html_e('Last Used', 'rag-sync'); ?></th>
                        <th><?php esc_html_e('Created', 'rag-sync'); ?></th>
                        <th><?php esc_html_e('Action', 'rag-sync'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$clients): ?>
                    <tr><td colspan="5"><?php esc_html_e('No MCP clients created yet.', 'rag-sync'); ?></td></tr>
                <?php endif; ?>
                <?php foreach ($clients as $client): ?>
                    <tr>
                        <td><?php echo esc_html($client['name']); ?></td>
                        <td><?php echo !empty($client['is_active']) && empty($client['revoked_at']) ? esc_html__('Active', 'rag-sync') : esc_html__('Revoked', 'rag-sync'); ?></td>
                        <td><?php echo !empty($client['last_used_at']) ? esc_html($client['last_used_at']) : esc_html__('Never', 'rag-sync'); ?></td>
                        <td><?php echo esc_html($client['created_at']); ?></td>
                        <td>
                            <?php if (!empty($client['is_active']) && empty($client['revoked_at'])): ?>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                                    <?php wp_nonce_field('rag_sync_mcp_revoke_client'); ?>
                                    <input type="hidden" name="action" value="rag_sync_mcp_revoke_client">
                                    <input type="hidden" name="client_id" value="<?php echo esc_attr((string) $client['id']); ?>">
                                    <button type="submit" class="button button-small"><?php esc_html_e('Revoke', 'rag-sync'); ?></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Create MCP client token.
     */
    public function handle_mcp_create_client(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied', 'rag-sync'));
        }
        check_admin_referer('rag_sync_mcp_create_client');
        $name = isset($_POST['client_name']) ? sanitize_text_field(wp_unslash($_POST['client_name'])) : '';
        if ($name === '') {
            $name = 'MCP Client';
        }
        $created = RAG_Sync_MCP::create_client($name);
        wp_safe_redirect(add_query_arg([
            'page' => 'rag-sync',
            'rag_sync_mcp_token' => rawurlencode($created['token']),
        ], admin_url('options-general.php')));
        exit;
    }

    /**
     * Revoke MCP client.
     */
    public function handle_mcp_revoke_client(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied', 'rag-sync'));
        }
        check_admin_referer('rag_sync_mcp_revoke_client');
        $client_id = isset($_POST['client_id']) ? absint($_POST['client_id']) : 0;
        if ($client_id > 0) {
            RAG_Sync_MCP::revoke_client($client_id);
        }
        wp_safe_redirect(add_query_arg('page', 'rag-sync', admin_url('options-general.php')));
        exit;
    }

    /**
     * AJAX: Test connection
     */
    public function ajax_test_connection(): void {
        check_ajax_referer('rag_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'rag-sync')]);
        }

        $webhook_endpoint = RAG_Sync::get_option('webhook_endpoint', '');
        $webhook_secret = RAG_Sync::get_option('webhook_secret', '');

        if (empty($webhook_endpoint)) {
            wp_send_json_error(['message' => __('Webhook Endpoint not configured', 'rag-sync')]);
        }

        if (empty($webhook_secret)) {
            wp_send_json_error(['message' => __('Webhook Secret not configured', 'rag-sync')]);
        }

        // Send a test webhook with signature to verify both connectivity and secret
        $payload = [
            'topic' => 'connection.test',
            'data' => [
                'test' => true,
                'timestamp' => current_time('c'),
            ],
            'site_url' => get_site_url(),
            'timestamp' => current_time('c'),
        ];

        $body = wp_json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, $webhook_secret, true));

        $response = wp_remote_post($webhook_endpoint, [
            'timeout' => 15,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'X-WC-Webhook-Topic' => 'connection.test',
                'X-WC-Webhook-Signature' => $signature,
                'X-WC-Webhook-Source' => get_site_url(),
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error([
                'message' => __('Connection failed: ', 'rag-sync') . $response->get_error_message(),
            ]);
        }

        $code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($code === 401) {
            wp_send_json_error([
                'message' => __('Webhook Secret mismatch! The secret does not match what the backend expects.', 'rag-sync'),
            ]);
        } elseif ($code === 404) {
            wp_send_json_error([
                'message' => __('Webhook endpoint not found. Check the Webhook Endpoint URL.', 'rag-sync'),
            ]);
        } elseif ($code >= 200 && $code < 300) {
            wp_send_json_success([
                'message' => __('Connection successful! Webhook secret verified.', 'rag-sync'),
                'data' => $data,
            ]);
        } else {
            wp_send_json_error([
                /* translators: %d: HTTP response status code. */
                'message' => sprintf(__('Connection failed: HTTP %d', 'rag-sync'), $code),
                'data' => $data,
            ]);
        }
    }

    /**
     * AJAX: Trigger full sync
     */
    public function ajax_trigger_full_sync(): void {
        check_ajax_referer('rag_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'rag-sync')]);
        }

        $webhook_endpoint = RAG_Sync::get_option('webhook_endpoint', '');
        $webhook_secret = RAG_Sync::get_option('webhook_secret', '');

        if (empty($webhook_endpoint) || empty($webhook_secret)) {
            wp_send_json_error(['message' => __('Webhook Endpoint or Secret not configured', 'rag-sync')]);
        }

        // Update status
        RAG_Sync::update_option('sync_status', 'syncing');

        // Populate tracking table with all existing content
        $counts = $this->populate_sync_tracking_table();

        // Build store configuration
        $store_config = [
            'currency_code' => function_exists('get_woocommerce_currency') ? get_woocommerce_currency() : null,
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : null,
            'locale' => get_locale(),
            'timezone' => wp_timezone_string(),
            'date_format' => get_option('date_format'),
            'site_name' => get_bloginfo('name'),
        ];

        // Send sync trigger to backend
        $payload = [
            'topic' => 'sync.triggered',
            'data' => [
                'action' => 'full_sync',
                'store_config' => $store_config,
            ],
            'site_url' => get_site_url(),
            'timestamp' => current_time('c'),
        ];

        $body = wp_json_encode($payload);
        $signature = base64_encode(hash_hmac('sha256', $body, $webhook_secret, true));

        $response = wp_remote_post($webhook_endpoint, [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-WC-Webhook-Topic' => 'sync.triggered',
                'X-WC-Webhook-Signature' => $signature,
                'X-WC-Webhook-Source' => get_site_url(),
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            RAG_Sync::update_option('sync_status', 'error');
            wp_send_json_error([
                'message' => $response->get_error_message(),
            ]);
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code === 401) {
            RAG_Sync::update_option('sync_status', 'error');
            wp_send_json_error([
                'message' => __('Webhook Secret mismatch! Check your configuration.', 'rag-sync'),
            ]);
        } elseif ($code >= 200 && $code < 300) {
            RAG_Sync::update_option('last_sync', current_time('mysql'));
            RAG_Sync::update_option('sync_status', 'idle');

            // Mark all items as synced
            $this->mark_all_items_synced();

            wp_send_json_success([
                'message' => sprintf(
                    /* translators: 1: product count, 2: category count, 3: coupon count, 4: page count, 5: currency code, 6: currency symbol. */
                    __('Full sync triggered successfully! Tracked %1$d products, %2$d categories, %3$d coupons, %4$d pages. Store config: %5$s (%6$s)', 'rag-sync'),
                    $counts['products'],
                    $counts['categories'],
                    $counts['coupons'],
                    $counts['pages'],
                    $store_config['currency_code'] ?? 'N/A',
                    $store_config['currency_symbol'] ?? 'N/A'
                ),
            ]);
        } else {
            RAG_Sync::update_option('sync_status', 'error');
            wp_send_json_error([
                /* translators: %d: HTTP response status code. */
                'message' => sprintf(__('Sync failed: HTTP %d', 'rag-sync'), $code),
            ]);
        }
    }

    /**
     * Populate sync tracking table with all existing content
     */
    private function populate_sync_tracking_table(): array {
        $counts = [
            'products' => 0,
            'categories' => 0,
            'coupons' => 0,
            'pages' => 0,
        ];

        // Products (if WooCommerce is active)
        if (class_exists('WooCommerce') && RAG_Sync::is_content_type_enabled('products')) {
            $products = wc_get_products([
                'status' => 'publish',
                'limit' => -1,
                'type' => ['simple', 'variable', 'grouped', 'external'],
            ]);

            foreach ($products as $product) {
                RAG_Sync_DB::get_or_create_item(
                    'product',
                    $product->get_id(),
                    $product->get_name(),
                    $product->get_sku()
                );
                $counts['products']++;
            }
        }

        // Categories
        if (class_exists('WooCommerce') && RAG_Sync::is_content_type_enabled('categories')) {
            $categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
            ]);

            if (!is_wp_error($categories)) {
                foreach ($categories as $category) {
                    RAG_Sync_DB::get_or_create_item(
                        'category',
                        $category->term_id,
                        $category->name
                    );
                    $counts['categories']++;
                }
            }
        }

        // Coupons
        if (class_exists('WooCommerce') && RAG_Sync::is_content_type_enabled('coupons')) {
            $coupons = get_posts([
                'post_type' => 'shop_coupon',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ]);

            foreach ($coupons as $coupon_post) {
                $coupon = new WC_Coupon($coupon_post->ID);
                RAG_Sync_DB::get_or_create_item(
                    'coupon',
                    $coupon->get_id(),
                    $coupon->get_code()
                );
                $counts['coupons']++;
            }
        }

        // Pages
        if (RAG_Sync::is_content_type_enabled('pages')) {
            $pages = get_posts([
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ]);

            foreach ($pages as $page) {
                RAG_Sync_DB::get_or_create_item(
                    'page',
                    $page->ID,
                    $page->post_title
                );
                $counts['pages']++;
            }
        }

        return $counts;
    }

    /**
     * Mark all items as synced after full sync completes
     */
    private function mark_all_items_synced(): void {
        global $wpdb;
        $table_name = esc_sql(RAG_Sync_DB::get_table_name());

        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom plugin table name is escaped above.
        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET status = %s, last_synced = %s, last_webhook_topic = %s WHERE status != 'deleted'",
            'synced',
            current_time('mysql'),
            'sync.triggered'
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
    }

    /**
     * AJAX: Get status
     */
    public function ajax_get_status(): void {
        check_ajax_referer('rag_sync_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'rag-sync')]);
        }

        wp_send_json_success([
            'status' => RAG_Sync::get_option('sync_status', 'idle'),
            'last_sync' => RAG_Sync::get_option('last_sync'),
        ]);
    }

    /**
     * Render sync status table
     */
    private function render_sync_status_table(): void {
        // Get read-only filter parameters for the admin list view.
        $type_filter = sanitize_text_field((string) filter_input(INPUT_GET, 'sync_type', FILTER_UNSAFE_RAW));
        $status_filter = sanitize_text_field((string) filter_input(INPUT_GET, 'sync_status', FILTER_UNSAFE_RAW));
        $search = sanitize_text_field((string) filter_input(INPUT_GET, 'sync_search', FILTER_UNSAFE_RAW));
        $page = max(1, (int) filter_input(INPUT_GET, 'sync_page', FILTER_VALIDATE_INT));

        // Get items
        $result = RAG_Sync_DB::get_items([
            'type' => $type_filter,
            'status' => $status_filter,
            'search' => $search,
            'page' => $page,
            'per_page' => 20,
        ]);

        $items = $result['items'];
        $total = $result['total'];
        $pages = $result['pages'];

        // Get stats
        $stats = RAG_Sync_DB::get_stats();
        ?>
        <div class="rag-sync-items">
            <h2><?php esc_html_e('Sync Status', 'rag-sync'); ?></h2>

            <!-- Stats summary -->
            <div class="sync-stats-summary">
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total Items', 'rag-sync'); ?></span>
                </div>
                <div class="stat-box stat-synced">
                    <span class="stat-number"><?php echo esc_html($stats['by_status']['synced'] ?? 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Synced', 'rag-sync'); ?></span>
                </div>
                <div class="stat-box stat-pending">
                    <span class="stat-number"><?php echo esc_html($stats['by_status']['pending'] ?? 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Pending', 'rag-sync'); ?></span>
                </div>
                <div class="stat-box stat-failed">
                    <span class="stat-number"><?php echo esc_html($stats['by_status']['failed'] ?? 0); ?></span>
                    <span class="stat-label"><?php esc_html_e('Failed', 'rag-sync'); ?></span>
                </div>
            </div>

            <!-- Filters -->
            <div class="sync-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="rag-sync">

                    <select name="sync_type">
                        <option value=""><?php esc_html_e('All Types', 'rag-sync'); ?></option>
                        <option value="product" <?php selected($type_filter, 'product'); ?>><?php esc_html_e('Products', 'rag-sync'); ?></option>
                        <option value="category" <?php selected($type_filter, 'category'); ?>><?php esc_html_e('Categories', 'rag-sync'); ?></option>
                        <option value="coupon" <?php selected($type_filter, 'coupon'); ?>><?php esc_html_e('Coupons', 'rag-sync'); ?></option>
                        <option value="page" <?php selected($type_filter, 'page'); ?>><?php esc_html_e('Pages', 'rag-sync'); ?></option>
                    </select>

                    <select name="sync_status">
                        <option value=""><?php esc_html_e('All Statuses', 'rag-sync'); ?></option>
                        <option value="synced" <?php selected($status_filter, 'synced'); ?>><?php esc_html_e('Synced', 'rag-sync'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php esc_html_e('Pending', 'rag-sync'); ?></option>
                        <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php esc_html_e('Failed', 'rag-sync'); ?></option>
                        <option value="deleted" <?php selected($status_filter, 'deleted'); ?>><?php esc_html_e('Deleted', 'rag-sync'); ?></option>
                    </select>

                    <input type="text" name="sync_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search by name or SKU...', 'rag-sync'); ?>">

                    <button type="submit" class="button"><?php esc_html_e('Filter', 'rag-sync'); ?></button>
                    <a href="<?php echo esc_url(admin_url('options-general.php?page=rag-sync')); ?>" class="button"><?php esc_html_e('Reset', 'rag-sync'); ?></a>
                </form>
            </div>

            <?php if (empty($items)): ?>
                <p class="no-items"><?php esc_html_e('No items found. Items will appear here when you sync content or when content is created/updated.', 'rag-sync'); ?></p>
            <?php else: ?>
                <!-- Items table -->
                <table class="wp-list-table widefat striped sync-items-table">
                    <thead>
                        <tr>
                            <th class="column-type"><?php esc_html_e('Type', 'rag-sync'); ?></th>
                            <th class="column-name"><?php esc_html_e('Name', 'rag-sync'); ?></th>
                            <th class="column-sku"><?php esc_html_e('SKU', 'rag-sync'); ?></th>
                            <th class="column-status"><?php esc_html_e('Status', 'rag-sync'); ?></th>
                            <th class="column-webhook"><?php esc_html_e('Last Webhook', 'rag-sync'); ?></th>
                            <th class="column-synced"><?php esc_html_e('Last Synced', 'rag-sync'); ?></th>
                            <th class="column-actions"><?php esc_html_e('Actions', 'rag-sync'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="column-type">
                                    <span class="item-type item-type-<?php echo esc_attr($item->item_type); ?>">
                                        <?php echo esc_html(ucfirst($item->item_type)); ?>
                                    </span>
                                </td>
                                <td class="column-name">
                                    <strong><?php echo esc_html($item->item_name); ?></strong>
                                    <br><small class="item-id">ID: <?php echo esc_html($item->item_id); ?></small>
                                </td>
                                <td class="column-sku">
                                    <?php echo $item->item_sku ? esc_html($item->item_sku) : '<span class="na">—</span>'; ?>
                                </td>
                                <td class="column-status">
                                    <span class="sync-status sync-status-<?php echo esc_attr($item->status); ?>">
                                        <?php echo esc_html(ucfirst($item->status)); ?>
                                    </span>
                                    <?php if ($item->status === 'failed' && $item->error_message): ?>
                                        <br><small class="error-message" title="<?php echo esc_attr($item->error_message); ?>">
                                            <?php echo esc_html(wp_trim_words($item->error_message, 5)); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td class="column-webhook">
                                    <?php if ($item->last_webhook_at): ?>
                                        <span title="<?php echo esc_attr($item->last_webhook_topic); ?>">
                                            <?php echo esc_html(human_time_diff(strtotime($item->last_webhook_at)) . ' ago'); ?>
                                        </span>
                                        <br><small><?php echo esc_html($item->last_webhook_topic); ?></small>
                                    <?php else: ?>
                                        <span class="na">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-synced">
                                    <?php if ($item->last_synced): ?>
                                        <?php echo esc_html(human_time_diff(strtotime($item->last_synced)) . ' ago'); ?>
                                        <br><small><?php echo esc_html($item->sync_count); ?> <?php esc_html_e('syncs', 'rag-sync'); ?></small>
                                    <?php else: ?>
                                        <span class="na"><?php esc_html_e('Never', 'rag-sync'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button"
                                            class="button button-small sync-item-btn"
                                            data-type="<?php echo esc_attr($item->item_type); ?>"
                                            data-id="<?php echo esc_attr($item->item_id); ?>">
                                        <?php esc_html_e('Sync Now', 'rag-sync'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($pages > 1): ?>
                    <div class="sync-pagination">
                        <?php
                        $base_url = add_query_arg([
                            'page' => 'rag-sync',
                            'sync_type' => $type_filter,
                            'sync_status' => $status_filter,
                            'sync_search' => $search,
                        ], admin_url('options-general.php'));

                        for ($i = 1; $i <= $pages; $i++):
                            $url = add_query_arg('sync_page', $i, $base_url);
                            $class = $i === $page ? 'button button-primary' : 'button';
                        ?>
                            <a href="<?php echo esc_url($url); ?>" class="<?php echo esc_attr($class); ?>"><?php echo esc_html($i); ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
