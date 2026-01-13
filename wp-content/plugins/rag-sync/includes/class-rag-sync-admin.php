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

        register_setting('rag_sync_settings', 'rag_sync_widget_welcome_message', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_widget_placeholder', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_widget_primary_color', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_hex_color',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_widget_position', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        register_setting('rag_sync_settings', 'rag_sync_widget_size', [
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ]);

        // Widget section
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
            'rag_sync_widget_welcome_message',
            __('Welcome Message', 'rag-sync'),
            [$this, 'render_widget_welcome_field'],
            'rag-sync',
            'rag_sync_widget'
        );

        add_settings_field(
            'rag_sync_widget_placeholder',
            __('Input Placeholder', 'rag-sync'),
            [$this, 'render_widget_placeholder_field'],
            'rag-sync',
            'rag_sync_widget'
        );

        add_settings_field(
            'rag_sync_widget_primary_color',
            __('Primary Color', 'rag-sync'),
            [$this, 'render_widget_color_field'],
            'rag-sync',
            'rag_sync_widget'
        );

        add_settings_field(
            'rag_sync_widget_position',
            __('Position', 'rag-sync'),
            [$this, 'render_widget_position_field'],
            'rag-sync',
            'rag_sync_widget'
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
                    <span class="status-label"><?php _e('Status:', 'rag-sync'); ?></span>
                    <span class="status-value status-<?php echo esc_attr($sync_status); ?>" id="rag-sync-status">
                        <?php echo esc_html(ucfirst($sync_status)); ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?php _e('Last Sync:', 'rag-sync'); ?></span>
                    <span class="status-value" id="rag-sync-last-sync">
                        <?php echo $last_sync ? esc_html(human_time_diff(strtotime($last_sync)) . ' ago') : __('Never', 'rag-sync'); ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label"><?php _e('WooCommerce:', 'rag-sync'); ?></span>
                    <span class="status-value <?php echo $is_woocommerce ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $is_woocommerce ? __('Active', 'rag-sync') : __('Not Installed', 'rag-sync'); ?>
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
                <h2><?php _e('Actions', 'rag-sync'); ?></h2>

                <div class="action-buttons">
                    <button type="button" class="button button-secondary" id="rag-sync-test-connection">
                        <?php _e('Test Connection', 'rag-sync'); ?>
                    </button>

                    <button type="button" class="button button-primary" id="rag-sync-full-sync">
                        <?php _e('Trigger Full Sync', 'rag-sync'); ?>
                    </button>
                </div>

                <div id="rag-sync-message" class="rag-sync-message" style="display: none;"></div>
            </div>

            <?php
            $webhook_endpoint = RAG_Sync::get_option('webhook_endpoint', '');
            $has_webhook_secret = !empty(RAG_Sync::get_option('webhook_secret', ''));
            $is_configured = $webhook_endpoint && $has_webhook_secret;
            ?>
            <div class="rag-sync-webhook-info">
                <h2><?php _e('Webhook Status', 'rag-sync'); ?></h2>
                <p><?php _e('Real-time updates will be sent to your RAG backend when content changes.', 'rag-sync'); ?></p>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Configuration Status', 'rag-sync'); ?></th>
                        <td>
                            <?php if ($is_configured): ?>
                                <span class="status-value status-active"><?php _e('Ready', 'rag-sync'); ?></span>
                                <span class="description"><?php _e('Use "Test Connection" to verify the webhook secret.', 'rag-sync'); ?></span>
                            <?php else: ?>
                                <span class="status-value status-error"><?php _e('Incomplete', 'rag-sync'); ?></span>
                                <?php if (!$webhook_endpoint): ?>
                                    <p class="description"><?php _e('Missing: Webhook Endpoint', 'rag-sync'); ?></p>
                                <?php endif; ?>
                                <?php if (!$has_webhook_secret): ?>
                                    <p class="description"><?php _e('Missing: Webhook Secret', 'rag-sync'); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('WordPress Site URL', 'rag-sync'); ?></th>
                        <td>
                            <code id="site-url"><?php echo esc_url(get_site_url()); ?></code>
                            <button type="button" class="button button-small copy-btn" data-target="site-url">
                                <?php _e('Copy', 'rag-sync'); ?>
                            </button>
                            <p class="description"><?php _e('Enter this URL as the WooCommerce Store URL in your RAG backend.', 'rag-sync'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <?php $this->render_sync_status_table(); ?>

            <div class="rag-sync-debug">
                <h2><?php _e('Debug Information', 'rag-sync'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th><?php _e('WordPress Version', 'rag-sync'); ?></th>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('PHP Version', 'rag-sync'); ?></th>
                        <td><?php echo esc_html(phpversion()); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Site URL', 'rag-sync'); ?></th>
                        <td><?php echo esc_url(get_site_url()); ?></td>
                    </tr>
                    <?php if ($is_woocommerce): ?>
                    <tr>
                        <th><?php _e('WooCommerce Version', 'rag-sync'); ?></th>
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
        echo '<p>' . __('Configure the connection to your RAG backend server.', 'rag-sync') . '</p>';
    }

    /**
     * Render content section description
     */
    public function render_content_section(): void {
        echo '<p>' . __('Select which content types to sync to the RAG backend.', 'rag-sync') . '</p>';
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
            <?php _e('The URL of your RAG backend server (e.g., https://api.yoursite.com)', 'rag-sync'); ?>
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
            <?php _e('Your tenant slug in the RAG backend (used for webhook URL)', 'rag-sync'); ?>
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
            <?php _e('Widget API key from your RAG backend (starts with wgt_)', 'rag-sync'); ?>
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
            <?php _e('Copy the Webhook Secret from your RAG backend (Settings → WooCommerce Integration)', 'rag-sync'); ?>
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
            <?php _e('Use Default', 'rag-sync'); ?>
        </button>
        <?php endif; ?>
        <p class="description">
            <?php _e('The Laravel endpoint URL where webhooks will be sent. Copy from RAG backend settings.', 'rag-sync'); ?>
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
            <?php _e('Enable automatic sync when content is created or updated', 'rag-sync'); ?>
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
                <?php _e('Install WooCommerce to sync products, categories, and coupons.', 'rag-sync'); ?>
            </p>
            <?php
        }
    }

    /**
     * Render widget section description
     */
    public function render_widget_section(): void {
        echo '<p>' . __('Configure the AI chat widget that appears on your site.', 'rag-sync') . '</p>';
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
            <?php _e('Show chat widget on frontend', 'rag-sync'); ?>
        </label>
        <p class="description">
            <?php _e('Requires API Key to be configured above.', 'rag-sync'); ?>
        </p>
        <?php
    }

    /**
     * Render widget welcome message field
     */
    public function render_widget_welcome_field(): void {
        $value = RAG_Sync::get_option('widget_welcome_message', 'Hi! How can I help you today?');
        ?>
        <input type="text"
               name="rag_sync_widget_welcome_message"
               id="rag_sync_widget_welcome_message"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text">
        <?php
    }

    /**
     * Render widget placeholder field
     */
    public function render_widget_placeholder_field(): void {
        $value = RAG_Sync::get_option('widget_placeholder', 'Type your message...');
        ?>
        <input type="text"
               name="rag_sync_widget_placeholder"
               id="rag_sync_widget_placeholder"
               value="<?php echo esc_attr($value); ?>"
               class="regular-text">
        <?php
    }

    /**
     * Render widget color field
     */
    public function render_widget_color_field(): void {
        $value = RAG_Sync::get_option('widget_primary_color', '#13AA75');
        ?>
        <input type="color"
               name="rag_sync_widget_primary_color"
               id="rag_sync_widget_primary_color"
               value="<?php echo esc_attr($value); ?>">
        <span style="margin-left: 10px; vertical-align: middle;"><?php echo esc_html($value); ?></span>
        <?php
    }

    /**
     * Render widget position field
     */
    public function render_widget_position_field(): void {
        $value = RAG_Sync::get_option('widget_position', 'bottom-right');
        $positions = [
            'bottom-right' => __('Bottom Right', 'rag-sync'),
            'bottom-left' => __('Bottom Left', 'rag-sync'),
        ];
        ?>
        <select name="rag_sync_widget_position" id="rag_sync_widget_position">
            <?php foreach ($positions as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
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

        // Send sync trigger to backend
        $payload = [
            'topic' => 'sync.triggered',
            'data' => [
                'action' => 'full_sync',
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
                    __('Full sync triggered successfully! Tracked %d products, %d categories, %d coupons, %d pages.', 'rag-sync'),
                    $counts['products'],
                    $counts['categories'],
                    $counts['coupons'],
                    $counts['pages']
                ),
            ]);
        } else {
            RAG_Sync::update_option('sync_status', 'error');
            wp_send_json_error([
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
        $table_name = RAG_Sync_DB::get_table_name();

        $wpdb->query($wpdb->prepare(
            "UPDATE {$table_name} SET status = %s, last_synced = %s, last_webhook_topic = %s WHERE status != 'deleted'",
            'synced',
            current_time('mysql'),
            'sync.triggered'
        ));
    }

    /**
     * AJAX: Get status
     */
    public function ajax_get_status(): void {
        check_ajax_referer('rag_sync_nonce', 'nonce');

        wp_send_json_success([
            'status' => RAG_Sync::get_option('sync_status', 'idle'),
            'last_sync' => RAG_Sync::get_option('last_sync'),
        ]);
    }

    /**
     * Render sync status table
     */
    private function render_sync_status_table(): void {
        // Get filter parameters
        $type_filter = isset($_GET['sync_type']) ? sanitize_text_field($_GET['sync_type']) : '';
        $status_filter = isset($_GET['sync_status']) ? sanitize_text_field($_GET['sync_status']) : '';
        $search = isset($_GET['sync_search']) ? sanitize_text_field($_GET['sync_search']) : '';
        $page = isset($_GET['sync_page']) ? max(1, intval($_GET['sync_page'])) : 1;

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
            <h2><?php _e('Sync Status', 'rag-sync'); ?></h2>

            <!-- Stats summary -->
            <div class="sync-stats-summary">
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
                    <span class="stat-label"><?php _e('Total Items', 'rag-sync'); ?></span>
                </div>
                <div class="stat-box stat-synced">
                    <span class="stat-number"><?php echo esc_html($stats['by_status']['synced'] ?? 0); ?></span>
                    <span class="stat-label"><?php _e('Synced', 'rag-sync'); ?></span>
                </div>
                <div class="stat-box stat-pending">
                    <span class="stat-number"><?php echo esc_html($stats['by_status']['pending'] ?? 0); ?></span>
                    <span class="stat-label"><?php _e('Pending', 'rag-sync'); ?></span>
                </div>
                <div class="stat-box stat-failed">
                    <span class="stat-number"><?php echo esc_html($stats['by_status']['failed'] ?? 0); ?></span>
                    <span class="stat-label"><?php _e('Failed', 'rag-sync'); ?></span>
                </div>
            </div>

            <!-- Filters -->
            <div class="sync-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="rag-sync">

                    <select name="sync_type">
                        <option value=""><?php _e('All Types', 'rag-sync'); ?></option>
                        <option value="product" <?php selected($type_filter, 'product'); ?>><?php _e('Products', 'rag-sync'); ?></option>
                        <option value="category" <?php selected($type_filter, 'category'); ?>><?php _e('Categories', 'rag-sync'); ?></option>
                        <option value="coupon" <?php selected($type_filter, 'coupon'); ?>><?php _e('Coupons', 'rag-sync'); ?></option>
                        <option value="page" <?php selected($type_filter, 'page'); ?>><?php _e('Pages', 'rag-sync'); ?></option>
                    </select>

                    <select name="sync_status">
                        <option value=""><?php _e('All Statuses', 'rag-sync'); ?></option>
                        <option value="synced" <?php selected($status_filter, 'synced'); ?>><?php _e('Synced', 'rag-sync'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'rag-sync'); ?></option>
                        <option value="failed" <?php selected($status_filter, 'failed'); ?>><?php _e('Failed', 'rag-sync'); ?></option>
                        <option value="deleted" <?php selected($status_filter, 'deleted'); ?>><?php _e('Deleted', 'rag-sync'); ?></option>
                    </select>

                    <input type="text" name="sync_search" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Search by name or SKU...', 'rag-sync'); ?>">

                    <button type="submit" class="button"><?php _e('Filter', 'rag-sync'); ?></button>
                    <a href="<?php echo admin_url('options-general.php?page=rag-sync'); ?>" class="button"><?php _e('Reset', 'rag-sync'); ?></a>
                </form>
            </div>

            <?php if (empty($items)): ?>
                <p class="no-items"><?php _e('No items found. Items will appear here when you sync content or when content is created/updated.', 'rag-sync'); ?></p>
            <?php else: ?>
                <!-- Items table -->
                <table class="wp-list-table widefat fixed striped sync-items-table">
                    <thead>
                        <tr>
                            <th class="column-type"><?php _e('Type', 'rag-sync'); ?></th>
                            <th class="column-name"><?php _e('Name', 'rag-sync'); ?></th>
                            <th class="column-sku"><?php _e('SKU', 'rag-sync'); ?></th>
                            <th class="column-status"><?php _e('Status', 'rag-sync'); ?></th>
                            <th class="column-webhook"><?php _e('Last Webhook', 'rag-sync'); ?></th>
                            <th class="column-synced"><?php _e('Last Synced', 'rag-sync'); ?></th>
                            <th class="column-actions"><?php _e('Actions', 'rag-sync'); ?></th>
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
                                        <br><small><?php echo esc_html($item->sync_count); ?> <?php _e('syncs', 'rag-sync'); ?></small>
                                    <?php else: ?>
                                        <span class="na"><?php _e('Never', 'rag-sync'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button"
                                            class="button button-small sync-item-btn"
                                            data-type="<?php echo esc_attr($item->item_type); ?>"
                                            data-id="<?php echo esc_attr($item->item_id); ?>">
                                        <?php _e('Sync Now', 'rag-sync'); ?>
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
                            <a href="<?php echo esc_url($url); ?>" class="<?php echo $class; ?>"><?php echo $i; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }
}
