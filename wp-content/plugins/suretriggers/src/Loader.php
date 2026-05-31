<?php
/**
 * Loader.
 * php version 5.6
 *
 * @category Loader
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers;

use DirectoryIterator;
use SureTriggers\Controllers\AuthController;
use SureTriggers\Controllers\AutomationController;
use SureTriggers\Controllers\EventController;
use SureTriggers\Controllers\GlobalSearchController;
use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Controllers\OptionController;
use SureTriggers\Controllers\RestController;
use SureTriggers\Controllers\RoutesController;
use SureTriggers\Controllers\WebhookRequestsController;
use SureTriggers\Controllers\SettingsController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Models\SaasApiToken;
use function add_menu_page;
use function add_submenu_page;

/**
 * Loader
 *
 * @category Loader
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class Loader {



	use SingletonLoader;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		register_activation_hook( SURE_TRIGGERS_FILE, [ $this, 'st_activate' ] );

		$this->define_constants();
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_action( 'plugins_loaded', [ $this, 'initialize_core' ] );
		// Admin Menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_head', [ $this, 'add_admin_menu_styles' ] );
		add_action( 'admin_init', [ $this, 'reset_plugin' ] );

		add_filter( 'plugin_action_links_' . plugin_basename( SURE_TRIGGERS_FILE ), [ $this, 'add_settings_link' ] );
		add_action( 'admin_init', [ $this, 'redirect_after_activation' ] );

		add_action( 'admin_notices', [ $this, 'display_notice' ] );

		add_action( 'all_admin_notices', [ $this, 'suretriggers_show_api_connection_error' ] );

		add_action( 'wp_dashboard_setup', [ $this, 'add_dashboard_widgets' ] );

		// Remove Webhook Requests retry cron and requests table.
		register_uninstall_hook(
			SURE_TRIGGERS_FILE,
			[ WebhookRequestsController::class, 'suretriggers_remove_table_retry_cron' ]
		);
	}

	/**
	 * Adding dashboard widget.
	 *
	 * @return void
	 */
	public function add_dashboard_widgets() {
		if ( isset( OptionController::$options['secret_key'] ) ) {
			return;
		}

		wp_add_dashboard_widget(
			'suretriggers_dashboard_widget',
			__( 'Stop Doing It Manually!', 'suretriggers' ),
			[ $this, 'dashboard_widget_display' ],
			null,
			null,
			'side',
			'high'
		);
	}

	/**
	 * Dashboard widget callback.
	 *
	 * @return void
	 */
	public function dashboard_widget_display() {            ?>
		<div>
			<p> <?php esc_html_e( 'Automation That’s Easy Enough for Anyone.', 'suretriggers' ); ?></p>
			<p> 
			<?php
			esc_html_e(
				'OttoKit connects all your tools - WooCommerce, Forms, CRM, and more, so your website runs smoothly while you focus on your business.
',
				'suretriggers' 
			);
			?>
				</p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=suretriggers' ) ); ?>" class="button button-primary"> <?php esc_html_e( 'Start Automating', 'suretriggers' ); ?> </a>
		</div>
		<?php
	}

	/**
	 * Load Plugin Text Domain.
	 * This will load the translation textdomain depending on the file priorities.
	 *      1. Global Languages /wp-content/languages/suretriggers/ folder
	 *      2. Local directory /wp-content/plugins/suretriggers/languages/ folder
	 *
	 * @return void
	 */
	public function load_textdomain() {
		// Default languages directory.
		$lang_dir = SURE_TRIGGERS_DIR . 'languages/';

		/**
		 * Filters the languages directory path to use for plugin.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'suretriggers_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter.
		global $wp_version;

		$get_locale = get_locale();

		if ( $wp_version >= 4.7 ) {
			$get_locale = get_user_locale();
		}

		/**
		 * Language Locale for plugin
		 *
		 * Uses get_user_locale()` in WordPress 4.7 or greater,
		 * otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'suretriggers' );//phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- wordpress hook
		$mofile = sprintf( '%1$s-%2$s.mo', 'suretriggers', $locale );

		// Setup paths to current locale file.
		$mofile_global = WP_LANG_DIR . '/plugins/' . $mofile;
		$mofile_local  = $lang_dir . $mofile;

		$mofile_global = wp_normalize_path( $mofile_global );
		$mofile_local  = wp_normalize_path( $mofile_local );

		if ( file_exists( $mofile_global ) && wp_is_file_mod_allowed( 'load_textdomain_mofile' ) ) {
			// Look in global /wp-content/languages/suretriggers/ folder.
			load_textdomain( 'suretriggers', $mofile_global );
		} elseif ( file_exists( $mofile_local ) && wp_is_file_mod_allowed( 'load_textdomain_mofile' ) && $this->st_valid_file_path( $mofile_local ) ) {
			// Look in local /wp-content/plugins/suretriggers/languages/ folder.
			load_textdomain( 'suretriggers', $mofile_local );
		} else {
			load_plugin_textdomain( 'suretriggers', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		}
	}

	/**
	 * Check if file is valid.
	 *
	 * @param string $file_path file_path.
	 * @return bool
	 */
	private function st_valid_file_path( $file_path ) {
		$allowed_dir    = realpath( SURE_TRIGGERS_DIR . 'languages/' );
		$real_file_path = realpath( $file_path );
		return false !== $real_file_path && $allowed_dir && 0 === strpos( $real_file_path, $allowed_dir );
	}

	/**
	 * Display notice.
	 *
	 * @return void
	 */
	public function display_notice() {
		global $pagenow;
		if ( isset( OptionController::$options['secret_key'] ) ) {
			return;
		}
		if ( 'index.php' != $pagenow ) {
			return;
		}
		?>
		<div class="notice notice-success" style="padding-bottom: 15px;">
			<p>
				<strong>
					<?php esc_html_e( 'Automate Your WordPress Site. Save Hours. Earn More.', 'suretriggers' ); ?>
					<span style="transform: rotate(-90deg); font-size: 15px;" class="dashicons dashicons-admin-plugins"></span>
				</strong>
			</p>
			<p> <?php esc_html_e( 'OttoKit connects your plugins and favorite apps so your business runs on autopilot — while you focus on what matters most.', 'suretriggers' ); ?> </p>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=suretriggers' ) ); ?>" class="button button-primary"> <?php esc_html_e( 'Start Automating', 'suretriggers' ); ?> </a>
			<a href="https://ottokit.com/?utm_source=wpplugin&utm_medium=dashboard&utm_campaign=top+bar" class="button button-secondary"> <?php esc_html_e( 'See How It Works', 'suretriggers' ); ?> </a>
		</div>
		<?php
	}

	/**
	 * Show Connection Error Admin Notice.
	 *
	 * @return void
	 */
	public function suretriggers_show_api_connection_error() {
		global $pagenow;
		$is_authorized = true;
		if ( ! current_user_can( 'manage_options' ) ) {
			$is_authorized = self::suretriggers_user_permission_check();
		}
		if ( 'index.php' != $pagenow || ! isset( OptionController::$options['secret_key'] ) || ! $is_authorized ) {
			return;
		}
		$notice = get_option( 'suretriggers_verify_connection' );
		// If empty option value for connection status, then verify the connection.
		if ( empty( $notice ) || 'suretriggers_connection_successful' != $notice ) {
			$connection_status      = RestController::suretriggers_verify_wp_connection();
			$connection_status_code = wp_remote_retrieve_response_code( $connection_status );
			if ( is_wp_error( $connection_status ) ) {
				update_option( 'suretriggers_verify_connection', 'suretriggers_connection_wp_error' );
			} else {
				if ( 200 !== $connection_status_code ) {
					update_option( 'suretriggers_verify_connection', 'suretriggers_connection_error' );
				} else {
					update_option( 'suretriggers_verify_connection', 'suretriggers_connection_successful' );
				}
			}
		}
		$notice = get_option( 'suretriggers_verify_connection' );
		if ( 'suretriggers_connection_successful' != $notice ) {
			// If connection status is not successful, then show the notice.
			?>
			<div class="notice notice-error is-dismissible">
				<p>
					<strong>
						<?php esc_html_e( 'OttoKit Connection Issue', 'suretriggers' ); ?>
						<span style="transform: rotate(-180deg); font-size: 20px;" class="dashicons dashicons-warning"></span>
					</strong>
				</p>
				<p>
					<?php esc_html_e( 'There is an issue with the established connection between WordPress and OttoKit. Please visit the OttoKit dashboard to verify and re-establish the connection if necessary.', 'suretriggers' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=suretriggers' ) ); ?>" class="button button-secondary"> <?php esc_html_e( 'Go To OttoKit', 'suretriggers' ); ?> </a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Redirect user after plugin activation.
	 *
	 * @return void
	 */
	public function redirect_after_activation() {
		$is_redirect = get_transient( 'st-redirect-after-activation' );
		if ( $is_redirect ) {
			delete_transient( 'st-redirect-after-activation' );
			$url = get_admin_url() . 'admin.php?page=suretriggers';
			wp_safe_redirect( $url );
			die;
		}
	}

	/**
	 * Adding setting link.
	 *
	 * @param array $links links.
	 * @return array
	 */
	public function add_settings_link( array $links ) {
		$url            = get_admin_url() . 'admin.php?page=suretriggers';
		$setting_option = get_option( 'suretrigger_options' );
		if ( isset( $setting_option ) && ! empty( $setting_option ) ) {
			$settings_link = '<a href="' . $url . '">' . __( 'Dashboard', 'suretriggers' ) . '</a>';
		} else {
			$settings_link = '<a href="' . $url . '">' . __( 'Connect', 'suretriggers' ) . '</a>';
		}
		$links[] = $settings_link;
		
		// Add Get OttoKit Pro link for free and pro users.
		if ( $this->should_show_upgrade_button() ) {
			$upgrade_link = '<a href="https://ottokit.com/pricing/?utm_source=wpplugin&utm_medium=plugin+list&utm_campaign=plugin+list" target="_blank" style="color: #28a745; font-weight: bold;">' . __( 'Get OttoKit Pro', 'suretriggers' ) . '</a>';
			$links[]      = $upgrade_link;
		}
		
		return $links;
	}

	/**
	 * Define constants
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function define_constants() {
		$sass_url    = 'https://app.ottokit.com';
		$api_url     = 'https://api.ottokit.com';
		$webhook_url = 'https://webhook.ottokit.com';

		define( 'SURE_TRIGGERS_BASE', plugin_basename( SURE_TRIGGERS_FILE ) );
		define( 'SURE_TRIGGERS_DIR', plugin_dir_path( SURE_TRIGGERS_FILE ) );
		define( 'SURE_TRIGGERS_URL', plugins_url( '/', SURE_TRIGGERS_FILE ) );
		define( 'SURE_TRIGGERS_VER', '1.1.17' );
		define( 'SURE_TRIGGERS_DB_VER', '1.1.17' );
		define( 'SURE_TRIGGERS_REST_NAMESPACE', 'sure-triggers/v1' );
		define( 'SURE_TRIGGERS_SASS_URL', $sass_url . '/wp-json/wp-plugs/v1/' );
		define( 'SURE_TRIGGERS_SITE_URL', $sass_url );
		define( 'SURE_TRIGGERS_API_SERVER_URL', $api_url );
		define( 'SURE_TRIGGERS_WEBHOOK_SERVER_URL', $webhook_url );

		define( 'SURE_TRIGGERS_PAGE', 'OttoKit' );
		define( 'SURE_TRIGGERS_AS_GROUP', 'OttoKit' );

		define( 'SURE_TRIGGERS_ACTION_ERROR_MESSAGE', 'An unexpected error occurred. Something went wrong with the action.' );
	}

	/**
	 * Flush permalink rules while plugin activation.
	 *
	 * @return void
	 */
	public function st_activate() {
		flush_rewrite_rules(); //phpcs:ignore

		set_transient( 'st-redirect-after-activation', true, 120 );
	}

	/**
	 * SureTriggers Access for Users and User Roles.
	 *
	 * @return bool
	 */
	private function suretriggers_user_permission_check() {
		$current_user       = wp_get_current_user();
		$current_user_id    = $current_user->ID;
		$current_user_roles = $current_user->roles;

		// Get enabled users and roles with proper default values.
		$enabled_users      = get_option( 'suretriggers_enabled_users', [] );
		$enabled_user_roles = get_option( 'suretriggers_enabled_user_roles', [] );

		$enabled_users      = is_array( $enabled_users ) ? $enabled_users : [];
		$enabled_user_roles = is_array( $enabled_user_roles ) ? $enabled_user_roles : [];

		$is_authorized = in_array( $current_user_id, (array) $enabled_users ) || array_intersect( $current_user_roles, (array) $enabled_user_roles );

		return $is_authorized;
	}

	/**
	 * Add main menu
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function admin_menu() {
		$page_title = apply_filters( 'st_menu_page_title', esc_html__( 'OttoKit', 'suretriggers' ) );
		$logo       = file_get_contents( plugin_dir_path( SURE_TRIGGERS_FILE ) . 'assets/images/OttoKitLogo.svg' );

		$is_authorized = true;
		if ( ! current_user_can( 'manage_options' ) ) {
			$is_authorized = self::suretriggers_user_permission_check();
		}

		if ( $is_authorized ) {
			add_menu_page(
				$page_title,
				$page_title,
				'read',
				'suretriggers',
				[ $this, 'menu_callback' ],
				$logo ? 'data:image/svg+xml;base64,' . base64_encode( $logo ) : '',
				30.6002
			);

			add_submenu_page(
				'suretriggers',
				__( 'OttoKit Status', 'suretriggers' ),
				__( 'Status', 'suretriggers' ),
				'read',
				'suretriggers-status',
				[ $this, 'suretriggers_status_menu_callback' ]
			);

			// Add Get OttoKit Pro menu for free and pro users.
			if ( $this->should_show_upgrade_button() ) {
				add_submenu_page(
					'suretriggers',
					__( 'Get OttoKit Pro', 'suretriggers' ),
					'<span class="ottokit-upgrade-btn">' . __( 'Get OttoKit Pro', 'suretriggers' ) . '</span>',
					'read',
					'suretriggers-upgrade-plan',
					[ $this, 'suretriggers_upgrade_plan_callback' ]
				);
			}
		}

		if ( isset( OptionController::$options['secret_key'] ) ) {
			add_options_page(
				__( 'OttoKit Settings', 'suretriggers' ),
				__( 'OttoKit Settings', 'suretriggers' ),
				'manage_options',
				'ottokit-settings',
				[ $this, 'suretriggers_render_interface_callback' ]
			);
		}
	}

	/**
	 * Enqueue the admin scripts
	 *
	 * @param string $hook hook.
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function enqueue_scripts( $hook = '' ) {
		// Always enqueue admin CSS for upgrade button styling.
		wp_enqueue_style( 'st-trigger-style', SURE_TRIGGERS_URL . 'assets/admin-css/st-admin-css.css', [], SURE_TRIGGERS_VER );
		
		if ( ! in_array( $hook, [ 'toplevel_page_suretriggers', 'ottokit_page_suretriggers-status', 'settings_page_ottokit-settings' ], true ) ) {
			return;
		}

		remove_all_actions( 'admin_notices' );

		$file = SURE_TRIGGERS_DIR . 'app/build/main.asset.php';
		if ( ! file_exists( $file ) ) {
			return;
		}

		$asset = require_once $file;

		if ( ! isset( $asset ) ) {
			return;
		}

		wp_register_script(
			'sure-trigger-admin',
			SURE_TRIGGERS_URL . 'app/build/main.js',
			array_merge( $asset['dependencies'], [ 'regenerator-runtime' ], [ 'wp-i18n' ] ),
			$asset['version'],
			true
		);

		wp_localize_script(
			'sure-trigger-admin',
			'sureTriggerData',
			$this->get_localized_array()
		);
		wp_enqueue_script( 'sure-trigger-admin' );
		// Set the script translations.
		wp_set_script_translations( 'sure-trigger-admin', 'suretriggers', SURE_TRIGGERS_DIR . 'languages' );
		wp_enqueue_style( 'sure-trigger-components', SURE_TRIGGERS_URL . 'app/build/style-main.css', [], SURE_TRIGGERS_VER );
		wp_enqueue_style( 'sure-trigger-css', SURE_TRIGGERS_URL . 'app/build/main.css', [], SURE_TRIGGERS_VER );
	}

	/**
	 * Get localized array for sure triggers.
	 *
	 * @return array
	 */
	private function get_localized_array() {
		$settings_nonce = wp_create_nonce( 'suretriggers_settings_nonce_action' );
		$current_user   = wp_get_current_user();
		global $wp_roles;
		$is_authorized = true;
		if ( ! current_user_can( 'manage_options' ) ) {
			$is_authorized = self::suretriggers_user_permission_check();
		}

		$source_type = get_option( 'suretriggers_source' );

		// Get enabled users and roles with proper default values.
		$enabled_users      = get_option( 'suretriggers_enabled_users', [] );
		$enabled_user_roles = get_option( 'suretriggers_enabled_user_roles', [] );

		$enabled_users      = is_array( $enabled_users ) ? $enabled_users : [];
		$enabled_user_roles = is_array( $enabled_user_roles ) ? $enabled_user_roles : [];

		$data = [
			'siteContent'         => [
				'siteUrl'       => str_replace( '/wp-json/', '', get_rest_url() ),
				'redirectUrl'   => get_site_url() . '/wp-admin/themes.php?page=suretriggers',
				'connectNonce'  => wp_create_nonce( 'sure-trigger-connect' ),
				'connectUrl'    => SURE_TRIGGERS_SITE_URL . '/connect-st/connect',
				'siteTitle'     => get_bloginfo( 'name' ),
				'resetUrl'      => base64_encode( wp_nonce_url( admin_url( 'admin.php?st-reset=true' ), 'st-reset-action' ) ),
				'sourceType'    => $source_type,
				'adminLanguage' => get_user_locale(),
			],
			'user'                => [
				'name'  => $current_user->display_name,
				'email' => $current_user->user_email,
			],
			'stSaasURL'           => trailingslashit( SURE_TRIGGERS_SITE_URL ),
			'stPluginURL'         => plugin_dir_url( SURE_TRIGGERS_FILE ),
			'integrations'        => IntegrationsController::get_activated_integrations(),
			'enabledIntegrations' => OptionController::get_option( 'enabled_integrations' ),
			'verification_status' => false,
			'projects'            => [],
			'apiSlug'             => SURE_TRIGGERS_REST_NAMESPACE,
			'reConnectSorryMsg'   => (bool) OptionController::get_option( 'st_connect_notice_deprecated' ),
			'usersRoles'          => array_diff_key( $wp_roles->get_names(), [ 'administrator' => '' ] ),
			'usersList'           => get_users(
				[
					'fields'       => [
						'ID',
						'display_name',
					],
					'role__not_in' => [
						'administrator',
					],
				]
			),
			'enabledUsers'        => $enabled_users,
			'enabledUserRoles'    => $enabled_user_roles,
		];

		if ( $is_authorized ) {
			$data['siteContent']['accessKey']       = SaasApiToken::get();
			$data['siteContent']['connected_email'] = OptionController::get_option( 'connected_email_key' );
		}

		$data['settingsNonce'] = esc_js( $settings_nonce );
		$data['ajaxurl']       = esc_url( admin_url( 'admin-ajax.php' ) );

		return apply_filters( 'sure_trigger_control_localize_vars', $data );
	}

	/**
	 * Render OttoKit Interface callback.
	 *
	 * @return void
	 */
	public function suretriggers_render_interface_callback() {
		?>
		<div id="ottokit-settings-page" class="ottokit-settings"></div>
		<?php
	}

	/**
	 * Menu callback.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function menu_callback() {
		// Check permalink structure first.
		$permalink_structure = get_option( 'permalink_structure' );
		$is_plain_permalink  = empty( $permalink_structure );

		// If permalink structure is "Plain" and we're trying to connect or already connected, show a prominent warning.
		if ( $is_plain_permalink ) {
			?>
			<div class="st-permalink-warning-wrapper">
				<div class="st-permalink-warning-card">
					<div class="st-permalink-warning-icon">
						<span class="st-permalink-warning-icon-text">!</span>
					</div>
					<div class="st-permalink-warning-content">
						<p class="st-permalink-warning-title">
							<?php esc_html_e( '“Plain” Permalink Structure Detected', 'suretriggers' ); ?>
						</p>
						<p class="st-permalink-warning-message">
							<?php esc_html_e( 'Your site is currently using the “Plain” permalink structure, which is not supported by OttoKit. Please visit', 'suretriggers' ); ?>
							<a href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>" target="_blank" class="st-permalink-warning-link">
								<?php esc_html_e( 'Permalinks Settings', 'suretriggers' ); ?>
							</a>
							<?php esc_html_e( 'on your WordPress Dashboard and choose any structure other than “Plain”.', 'suretriggers' ); ?>
						</p>
					</div>
				</div>
			</div>
			<?php
		}

		// Verify Token.
		$response      = RestController::verify_user_token();
		$response_body = wp_remote_retrieve_body( $response );
		$response_code = wp_remote_retrieve_response_code( $response );
		if ( ! empty( $response_body ) ) {
			$response_body = json_decode( $response_body, true );
		}
		if ( 200 === $response_code || 401 === $response_code ) {
			if ( is_array( $response_body ) && isset( $response_body['is_iframe_enabled'] ) && 'NO' === $response_body['is_iframe_enabled'] ) {
				?>
				<div class="suretriggers-nobase">
					<div>
						<div>
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-circle-check inline-block h-8 w-8 text-green-400 mb-6" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="m9 12 2 2 4-4"></path></svg>
							<h2 class="suretriggers-info-title">
								<?php esc_html_e( 'OttoKit is connected.', 'suretriggers' ); ?>
							</h2>
							<p class="suretriggers-info-content">
								<?php esc_html_e( 'Your WordPress site is successfully connected to the OttoKit SaaS platform. However, the OttoKit interface display is currently disabled. Click below to enable it.', 'suretriggers' ); ?>
							</p>
							<a class="suretriggers-info-link" href="<?php echo esc_url( SURE_TRIGGERS_SITE_URL . '/apps/WordPress' ); ?>" target="_blank">
								<?php esc_html_e( 'Access Connection Page', 'suretriggers' ); ?>
							</a>
						</div>
					</div>
				</div>
				<?php
			} else {
				?>
				<div id="sure-triggger-entry" class="st-base"></div>
				<?php
			}
		} elseif ( isset( $response ) && is_wp_error( $response ) || 200 !== $response_code ) {
			?>
			<div class="suretriggers-nobase">
				<div>
					<div>
						<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30" height="24" viewBox="0 0 122.88 122.879" enable-background="new 0 0 122.88 122.879" xml:space="preserve" class="lucide lucide-circle-check inline-block h-8 w-8 text-green-400 mb-6"><g><path fill="#FF4141" d="M61.44,0c16.96,0,32.328,6.882,43.453,17.986c11.104,11.125,17.986,26.494,17.986,43.453 c0,16.961-6.883,32.328-17.986,43.453C93.769,115.998,78.4,122.879,61.44,122.879c-16.96,0-32.329-6.881-43.454-17.986 C6.882,93.768,0,78.4,0,61.439C0,44.48,6.882,29.111,17.986,17.986C29.112,6.882,44.48,0,61.44,0L61.44,0z M73.452,39.152 c2.75-2.792,7.221-2.805,9.986-0.026c2.764,2.776,2.775,7.292,0.027,10.083L71.4,61.445l12.077,12.25 c2.728,2.77,2.689,7.256-0.081,10.021c-2.772,2.766-7.229,2.758-9.954-0.012L61.445,71.541L49.428,83.729 c-2.75,2.793-7.22,2.805-9.985,0.025c-2.763-2.775-2.776-7.291-0.026-10.082L51.48,61.435l-12.078-12.25 c-2.726-2.769-2.689-7.256,0.082-10.022c2.772-2.765,7.229-2.758,9.954,0.013L61.435,51.34L73.452,39.152L73.452,39.152z M96.899,25.98C87.826,16.907,75.29,11.296,61.44,11.296c-13.851,0-26.387,5.611-35.46,14.685 c-9.073,9.073-14.684,21.609-14.684,35.459s5.611,26.387,14.684,35.459c9.073,9.074,21.609,14.686,35.46,14.686 c13.85,0,26.386-5.611,35.459-14.686c9.073-9.072,14.684-21.609,14.684-35.459S105.973,35.054,96.899,25.98L96.899,25.98z"></path></g></svg>
						<h2 class="suretriggers-info-title">
							<?php esc_html_e( 'OttoKit Not Connected.', 'suretriggers' ); ?>
						</h2>
						<p class="suretriggers-info-content">
							<?php esc_html_e( 'It looks like your WordPress site’s connection with OttoKit has been affected because the URL used for communication has changed. The current link for your site is different from the one OttoKit was originally connected to.', 'suretriggers' ); ?>
						</p>
						<a class="suretriggers-info-link" href="<?php echo esc_url( SURE_TRIGGERS_SITE_URL ); ?>" target="_blank">
							<?php esc_html_e( 'Access Dashboard', 'suretriggers' ); ?>
						</a>
						<a class="suretriggers-info-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?st-reset=true' ), 'st-reset-action' ) ); ?>">
							<?php esc_html_e( 'Disconnect OttoKit', 'suretriggers' ); ?>
						</a>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Status Menu callback.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function suretriggers_status_menu_callback() {
		if ( ! isset( OptionController::$options['secret_key'] ) ) {
			?>
			<div class="suretriggers-nobase">
				<div>
					<div>
						<svg version="1.1" id="Layer_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="30" height="24" viewBox="0 0 122.88 122.879" enable-background="new 0 0 122.88 122.879" xml:space="preserve" class="lucide lucide-circle-check inline-block h-8 w-8 text-green-400 mb-6"><g><path fill="#FF4141" d="M61.44,0c16.96,0,32.328,6.882,43.453,17.986c11.104,11.125,17.986,26.494,17.986,43.453 c0,16.961-6.883,32.328-17.986,43.453C93.769,115.998,78.4,122.879,61.44,122.879c-16.96,0-32.329-6.881-43.454-17.986 C6.882,93.768,0,78.4,0,61.439C0,44.48,6.882,29.111,17.986,17.986C29.112,6.882,44.48,0,61.44,0L61.44,0z M73.452,39.152 c2.75-2.792,7.221-2.805,9.986-0.026c2.764,2.776,2.775,7.292,0.027,10.083L71.4,61.445l12.077,12.25 c2.728,2.77,2.689,7.256-0.081,10.021c-2.772,2.766-7.229,2.758-9.954-0.012L61.445,71.541L49.428,83.729 c-2.75,2.793-7.22,2.805-9.985,0.025c-2.763-2.775-2.776-7.291-0.026-10.082L51.48,61.435l-12.078-12.25 c-2.726-2.769-2.689-7.256,0.082-10.022c2.772-2.765,7.229-2.758,9.954,0.013L61.435,51.34L73.452,39.152L73.452,39.152z M96.899,25.98C87.826,16.907,75.29,11.296,61.44,11.296c-13.851,0-26.387,5.611-35.46,14.685 c-9.073,9.073-14.684,21.609-14.684,35.459s5.611,26.387,14.684,35.459c9.073,9.074,21.609,14.686,35.46,14.686 c13.85,0,26.386-5.611,35.459-14.686c9.073-9.072,14.684-21.609,14.684-35.459S105.973,35.054,96.899,25.98L96.899,25.98z"></path></g></svg>
						<h2 class="suretriggers-info-title">
							<?php esc_html_e( 'OttoKit Not Connected.', 'suretriggers' ); ?>
						</h2>
						<p class="suretriggers-info-content">
							<?php esc_html_e( 'Please connect your OttoKit account to access registered events and outgoing requests.', 'suretriggers' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=suretriggers' ) ); ?>" class="suretriggers-info-link"> <?php esc_html_e( 'Connect With OttoKit', 'suretriggers' ); ?> </a>
					</div>
				</div>
			</div>
			<?php
			return;
		}
		?>
		<div class="wrap">
			<?php
			$tabs        = [
				'st_system_page'       => __( 'Status', 'suretriggers' ),
				'st_outgoing_requests' => __( 'Outgoing Requests', 'suretriggers' ),
				'st_troubleshooting'   => __( 'Troubleshooting', 'suretriggers' ),
			];
			$current_tab = 'st_system_page';
			if ( isset( $_REQUEST['tab'], $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'suretriggers_tab_nonce' ) ) {
				if ( array_key_exists( sanitize_key( $_REQUEST['tab'] ), $tabs ) ) {
					$current_tab = sanitize_key( $_REQUEST['tab'] );
				}
			}
			?>
			<nav class="suretriggers-nav-tab nav-tab-wrapper">
				<?php
				foreach ( $tabs as $name => $label ) {
					$tab_url = add_query_arg(
						[
							'tab'      => $name,
							'_wpnonce' => wp_create_nonce( 'suretriggers_tab_nonce' ),
						],
						admin_url( 'admin.php?page=suretriggers-status' )
					);
					echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab ';
					if ( $current_tab == $name ) {
						echo 'nav-tab-active';
					}
					echo '">' . esc_html( $label ) . '</a>';
				}
				?>
			</nav>
			<?php
			switch ( $current_tab ) {
				case 'st_system_page':
					include_once __DIR__ . '/Admin/Views/st-admin-system-page.php';
					break;
				case 'st_outgoing_requests':
					include_once __DIR__ . '/Admin/Views/st-admin-outgoing-req-page.php';
					break;
				case 'st_troubleshooting':
					include_once __DIR__ . '/Admin/Views/st-admin-troubleshooting-page.php';
					break;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Get OttoKit Pro Menu callback.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function suretriggers_upgrade_plan_callback() {
		?>
		<script type="text/javascript">
			window.location.href = 'https://ottokit.com/pricing/?utm_source=wpplugin&utm_medium=menu&utm_campaign=left';
		</script>
		<?php
		exit;
	}

	/**
	 * Add admin menu styles.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function add_admin_menu_styles() {
		?>
		<script>
		jQuery(document).ready(function($) {
			// Direct redirect for admin menu upgrade button
			$('a[href*="suretriggers-lifetime-access"]').on('click', function(e) {
				e.preventDefault();
				window.open('https://ottokit.com/pricing/?utm_source=wpplugin&utm_medium=menu&utm_campaign=left', '_blank');
				return false;
			});
		});
		</script>
		<?php
	}

	/**
	 * Include all files from the folder.
	 *
	 * @param string $folder folder path.
	 * @return void
	 */
	public function include_all_files( $folder ) {
		$dir = new DirectoryIterator( $folder );
		foreach ( $dir as $file ) {
			if ( ! $file->isDot() ) {
				if ( $file->isDir() ) {
					$this->include_all_files( $file->getPathname() );
				} else {
					require_once $file->getPathname();
				}
			}
		}
	}

	/**
	 * Initialize core trigger and actions.
	 *
	 * @return void
	 */
	public function initialize_core() {

		IntegrationsController::load_event_files();

		EventController::get_instance();
		IntegrationsController::get_instance();
		GlobalSearchController::get_instance();
		RestController::get_instance();
		OptionController::get_instance();
		AutomationController::get_instance();
		AuthController::get_instance();
		RoutesController::get_instance();
		WebhookRequestsController::get_instance();
		SettingsController::get_instance();

		// SureTriggers Custom Filter data.
		add_filter( 'suretriggers_get_iframe_url', [ $this, 'suretriggers_iframe_data' ] );
		add_filter( 'suretriggers_is_user_connected', [ $this, 'suretriggers_saas_connected_data' ] );

		// Create Webhook Request Log table.
		WebhookRequestsController::suretriggers_webhook_request_log_table();
		// Schedule the cron jon to retry failed triggers.
		WebhookRequestsController::suretriggers_setup_custom_cron();

		$this->include_all_files( SURE_TRIGGERS_DIR . 'src/Integrations/' );
	}

	/**
	 * Added option to reset plugin in case of testing.
	 *
	 * @return void
	 */
	public function reset_plugin() {
		$nonce = sanitize_text_field( wp_unslash( isset( $_GET['_wpnonce'] ) ? $_GET['_wpnonce'] : false ) );

		if ( $nonce && wp_verify_nonce( $nonce, 'st-reset-action' ) ) {
			$is_reset = sanitize_text_field( wp_unslash( isset( $_GET['st-reset'] ) ? $_GET['st-reset'] : false ) );
			if ( $is_reset && current_user_can( 'manage_options' ) ) {
				delete_option( 'suretrigger_options' );
				self::clear_verification_cache();
				wp_safe_redirect( admin_url( 'admin.php?page=suretriggers' ) );
				exit();
			}
		}
	}

	/**
	 * Custom Filter data.
	 *
	 * @param string $site_url Optional. Site URL to include in the iframe data.
	 * @return string
	 */
	public function suretriggers_iframe_data( $site_url = '' ) {
		$site_url = esc_url_raw( $site_url );
		if ( ! current_user_can( 'manage_options' ) ) {
			return $site_url;
		}
		$site_content_data = [
			'stSaasURL' => $site_url . 'wp-login',
			'stCode'    => SaasApiToken::get(),
			'baseUrl'   => str_replace( '/wp-json/', '', get_rest_url() ),
			'resetUrl'  => rtrim( base64_encode( wp_nonce_url( admin_url( 'admin.php?st-reset=true' ), 'st-reset-action' ) ), '=' ), // phpcs:ignore
		];
		$params            = [
			'st-code'      => $site_content_data['stCode'],
			'base_url'     => $site_content_data['baseUrl'],
			'reset_url'    => $site_content_data['resetUrl'],
			'redirect_url' => $site_url . 'embed-login',
			'is_embedded'  => true,
		];

		if ( filter_var( $site_url, FILTER_VALIDATE_URL ) ) {
			$iframe_url = add_query_arg( $params, $site_content_data['stSaasURL'] );
		} else {
			$default_url = trailingslashit( SURE_TRIGGERS_SITE_URL ) . '?path=dashboard';
			$iframe_url  = add_query_arg( $params, $default_url );
		}
		return esc_url_raw( $iframe_url );
	}


	/**
	 * Custom Filter data to check if user is logged in iframe.
	 *
	 * @return bool
	 */
	public function suretriggers_saas_connected_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$token = SaasApiToken::get();

		if ( '' === $token || null === $token || false === $token || 'connection-denied' === $token ) {
			$logged_in = false;
		} else {
			$logged_in = true;
		}
		return $logged_in;
	}

	/**
	 * Check if user has lifetime plan
	 *
	 * @return bool
	 */
	public function is_lifetime_plan() {
		// Check if verification data is already stored in database.
		$verification_data = get_option( 'suretriggers_lifetime_user_plan_data' );
		// Return lifetime plan status from cached data.
		if ( is_array( $verification_data ) && isset( $verification_data['is_lifetime_plan'] ) ) {
			return (bool) $verification_data['is_lifetime_plan'];
		}
		
		return false;
	}

	/**
	 * Check if user should see upgrade button (free or pro plan)
	 *
	 * @return bool
	 */
	public function should_show_upgrade_button() {
		$verification_data = get_option( 'suretriggers_lifetime_user_plan_data' );
		
		if ( is_array( $verification_data ) && isset( $verification_data['plan_id'] ) ) {
			$plan_id = $verification_data['plan_id'];
			return in_array( $plan_id, [ 'free', 'pro' ], true );
		}
		
		return false;
	}

	/**
	 * Clear cached user verification data when token changes
	 *
	 * @return void
	 */
	public static function clear_verification_cache() {
		delete_option( 'suretriggers_lifetime_user_plan_data' );
	}
}
