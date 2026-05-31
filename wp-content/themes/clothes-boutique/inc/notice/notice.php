<?php

/**
 * file for holding dashboard welcome page for theme
 */
if (!function_exists('clothes_boutique_is_plugin_installed')) {
    function clothes_boutique_is_plugin_installed($plugin_slug)
    {
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
        return file_exists($plugin_path);
    }
}
if (!function_exists('clothes_boutique_is_plugin_activated')) {
    function clothes_boutique_is_plugin_activated($plugin_slug)
    {
        return is_plugin_active($plugin_slug);
    }
}

// Hook into a custom action when the button is clicked
add_action('wp_ajax_clothes_boutique_install_and_activate_plugins', 'clothes_boutique_install_and_activate_plugins');
add_action('wp_ajax_nopriv_clothes_boutique_install_and_activate_plugins', 'clothes_boutique_install_and_activate_plugins');
add_action('wp_ajax_clothes_boutique_rplugin_activation', 'clothes_boutique_rplugin_activation');
add_action('wp_ajax_nopriv_clothes_boutique_rplugin_activation', 'clothes_boutique_rplugin_activation');

// Function to install and activate the plugins



function check_plugin_installed_status($pugin_slug, $plugin_file)
{
    return file_exists(ABSPATH . 'wp-content/plugins/' . $pugin_slug . '/' . $plugin_file) ? true : false;
}

/* Check if plugin is activated */


function check_plugin_active_status($pugin_slug, $plugin_file)
{
    return is_plugin_active($pugin_slug . '/' . $plugin_file) ? true : false;
}

require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/misc.php');
require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
// Helper function to check if all recommended plugins are installed and activated
function clothes_boutique_all_plugins_active() {
    $recommended_plugins = array(
        array(
            'name' => __( 'WooCommerce', 'clothes-boutique' ),
            'slug' => 'woocommerce',
            'file' => 'woocommerce.php'
        ),
        array(
            'name' => __( 'Classic Blog Grid', 'clothes-boutique' ),
            'slug' => 'classic-blog-grid',
            'file' => 'classic-blog-grid.php'
        ),
        array(
            'name' => __( 'Gutentor', 'clothes-boutique' ),
            'slug' => 'gutentor',
            'file' => 'gutentor.php'
        )
    );

    foreach ($recommended_plugins as $plugin) {
        $plugin_slug = $plugin['slug'];
        $plugin_file = $plugin['file'];

        // Check if the plugin is active
        if (!is_plugin_active($plugin_slug . '/' . $plugin_file)) {
            return false; // If any plugin is not active, return false
        }
    }

    return true; // All plugins are active
}

class Silent_Skin extends WP_Upgrader_Skin {
    public function header() {}
    public function footer() {}
    public function feedback($string, ...$args) {}
    public function error($errors) {}
    public function before() {}
    public function after() {}
}

// Function to install and activate plugins
function clothes_boutique_install_and_activate_plugins() {
    if (!current_user_can('manage_options')) {
        return;
    }
    check_ajax_referer('clothes_boutique_welcome_nonce', 'nonce');

    // Define the recommended plugins
    $recommended_plugins = array(
        array(
            'name' => __( 'WooCommerce', 'clothes-boutique' ),
            'slug' => 'woocommerce',
            'file' => 'woocommerce.php'
        ),
        array(
            'name' => __( 'Classic Blog Grid', 'clothes-boutique' ),
            'slug' => 'classic-blog-grid',
            'file' => 'classic-blog-grid.php'
        ),
        array(
            'name' => __( 'Gutentor', 'clothes-boutique' ),
            'slug' => 'gutentor',
            'file' => 'gutentor.php'
        )
    );

    set_transient('install_and_activate_progress', array(), MINUTE_IN_SECONDS * 10);

    require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/misc.php');
    require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');

    foreach ($recommended_plugins as $plugin) {
        $plugin_slug = $plugin['slug'];
        $plugin_file = $plugin['file'];
        $plugin_name = $plugin['name'];

        // Check if the plugin is active
        if (is_plugin_active($plugin_slug . '/' . $plugin_file)) {
            update_install_and_activate_progress($plugin_name, 'Already Active');
            continue;
        }

        // Check if the plugin is installed but not active
        if (is_clothes_boutique_plugin_installed($plugin_slug)) {
            $activate = activate_plugin($plugin_slug . '/' . $plugin_file);
            if (is_wp_error($activate)) {
                update_install_and_activate_progress($plugin_name, 'Error');
                continue;
            }
            update_install_and_activate_progress($plugin_name, 'Activated');
            continue;
        }

        // Plugin is not installed or activated, proceed with installation
        update_install_and_activate_progress($plugin_name, 'Installing');

        $api = plugins_api('plugin_information', array('slug' => $plugin_slug, 'fields' => array('sections' => false)));
        if (is_wp_error($api)) {
            update_install_and_activate_progress($plugin_name, 'Error');
            continue;
        }

        $upgrader = new Plugin_Upgrader(new Silent_Skin());
        $install = $upgrader->install($api->download_link);

        if ($install) {
            $activate = activate_plugin($plugin_slug . '/' . $plugin_file);
            if (is_wp_error($activate)) {
                update_install_and_activate_progress($plugin_name, 'Error');
                continue;
            }
            update_install_and_activate_progress($plugin_name, 'Activated');
            continue;
        } else {
            update_install_and_activate_progress($plugin_name, 'Error');
        }
    }
    delete_transient('install_and_activate_progress');
    if (ob_get_length()) ob_clean();

    header('Content-Type: application/json; charset=utf-8');
    $redirect_url = admin_url('themes.php?page=clothes-boutique');
    echo json_encode([
        'success' => true,
        'data' => [
            'redirect_url' => $redirect_url,
        ],
    ]);

    wp_die();
}

// Function to check if a plugin is installed
function is_clothes_boutique_plugin_installed($plugin_slug) {
    $installed_plugins = get_plugins();
    foreach ($installed_plugins as $path => $details) {
        if (strpos($path, $plugin_slug) === 0) {
            return true;
        }
    }
    return false;
}

// Function to update the installation progress
function update_install_and_activate_progress($plugin_name, $status) {
    $progress = get_transient('install_and_activate_progress');
    $progress[] = array('plugin' => $plugin_name, 'status' => $status);
    set_transient('install_and_activate_progress', $progress, MINUTE_IN_SECONDS * 10);
}

// Dismiss function for AJAX request
add_action('wp_ajax_clothes_boutique_dismissed_notice_handler', 'clothes_boutique_ajax_notice_dismiss_function');

function clothes_boutique_ajax_notice_dismiss_function() {
    if (!wp_verify_nonce($_POST['wpnonce'], 'clothes_boutique_welcome_nonce')) {
        wp_send_json_error('Invalid nonce');
        exit;
    }
    
    if (isset($_POST['type'])) {
        $type = sanitize_text_field(wp_unslash($_POST['type']));
        update_option('dismissed-' . $type, true);
        wp_send_json_success('Notice dismissed');
    } else {
        wp_send_json_error('Type not set');
    }
}

/* Activation Notice */
function clothes_boutique_custom_admin_notice() {
    if (!get_option('dismissed-get_started_notice', false)) {
        $clothes_boutique_current_screen = get_current_screen();
        $clothes_boutique_theme = wp_get_theme();
        if ($clothes_boutique_current_screen && $clothes_boutique_current_screen->id !== 'appearance_page_clothes-boutique') {
            ?>
                <div class="getstrat updated notice notice-success is-dismissible notice-get-started-class clothes-boutique-admin-notice notice notice-info is-dismissible content-install-plugin theme-info-notice" id="clothes-boutique-dismiss-notice" data-notice="get_started_notice">
                    <div class="admin-image">
                        <img src="<?php echo esc_url(get_stylesheet_directory_uri()) .'/screenshot.png'; ?>" />
                    </div>
                    <div class="admin-content" >
                        <h1><?php 
                        /* translators: 1: Theme name, 2: Theme version. */
                        printf( esc_html__( 'Welcome to %1$s %2$s', 'clothes-boutique' ), esc_html($clothes_boutique_theme->get( 'Name' )), esc_html($clothes_boutique_theme->get( 'Version' ))); ?>
                        </h1>
                        <p><?php _e('Get Started With Theme By Clicking On Getting Started.', 'clothes-boutique'); ?></p>
                        <div style="display: grid;">
                            <a class="admin-notice-btn button button-hero upgrade-pro" target="_blank" href="<?php echo esc_url( CLOTHES_BOUTIQUE_PREMIUM_PAGE ); ?>"><?php esc_html_e('Upgrade Pro', 'clothes-boutique') ?><i class="dashicons dashicons-cart"></i></a>

                            <a class="admin-notice-btn button button-hero theme-install" id="install-activate-button" href="#"><?php esc_html_e( 'Get started', 'clothes-boutique' ) ?><i class="dashicons dashicons-backup"></i></a>

                            <a class="admin-notice-btn button button-hero" target="_blank" href="<?php echo esc_url( CLOTHES_BOUTIQUE_THEME_DOCUMENTATION ); ?>"><?php esc_html_e('Free Doc', 'clothes-boutique') ?><i class="dashicons dashicons-visibility"></i></a>

                            <a  class="admin-notice-btn button button-hero" target="_blank" href="<?php echo esc_url( CLOTHES_BOUTIQUE_PRO_DEMO ); ?>"><?php esc_html_e('View Demo', 'clothes-boutique') ?><i class="dashicons dashicons-awards"></i></a>
                        </div>
                    </div>
                    <div class="admin-bundle-image">
                        <a href="<?php echo esc_url( CLOTHES_BOUTIQUE_BUNDLE_PAGE ); ?>" target="_blank"><img src="<?php echo esc_url(get_stylesheet_directory_uri()) .'/assets/images/image_1.webp'; ?>" /></a>
                    </div>
                </div>
            <?php
        }
    }
}
add_action('admin_notices', 'clothes_boutique_custom_admin_notice');

// After switching theme, reset dismissed notice option
add_action('after_switch_theme', 'clothes_boutique_after_switch_theme');
function clothes_boutique_after_switch_theme() {
    update_option('dismissed-get_started_notice', FALSE);
} ?>