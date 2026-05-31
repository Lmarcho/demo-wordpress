<?php
/**
 * Plugin Loader.
 *
 * @package modern-cart
 * @since 0.0.1
 */

namespace ModernCart;

use ModernCart\Admin_Core\Admin_Menu;
use ModernCart\Inc\Floating;
use ModernCart\Inc\Floating_Ajax;
use ModernCart\Inc\Helper;
use ModernCart\Inc\Scripts;
use ModernCart\Inc\Slide_Out;
use ModernCart\Inc\Slide_Out_Ajax;

/**
 * Plugin_Loader
 *
 * @since 0.0.1
 */
class Plugin_Loader {
	/**
	 * Instance
	 *
	 * @access private
	 * @var object Class Instance.
	 * @since 0.0.1
	 */
	private static $instance;

	/**
	 * Constructor
	 *
	 * @since 0.0.1
	 */
	public function __construct() {
		spl_autoload_register( [ $this, 'autoload' ] );

		register_activation_hook( MODERNCART_FILE, [ $this, 'activate' ] );
		add_action( 'admin_init', [ $this, 'redirect_to_onboarding' ] );

		add_action( 'init', [ $this, 'save_version_info' ] );
		add_action( 'plugins_loaded', [ $this, 'load_classes' ] );
		add_filter( 'plugin_action_links_' . MODERNCART_BASE, [ $this, 'action_links' ] );

		do_action( 'moderncart_loaded' );
	}

	/**
	 * Activate plugin and set onboarding redirect.
	 */
	public function activate(): void {
		$is_onboarding_complete = get_option( 'moderncart_is_onboarding_complete', 'no' );

		if ( 'yes' === $is_onboarding_complete ) {
			return;
		}

		set_transient( 'moderncart_redirect_to_onboarding', 'yes' );
	}

	/**
	 * Redirect to onboarding page.
	 */
	public function redirect_to_onboarding(): void {
		if ( ! get_transient( 'moderncart_redirect_to_onboarding' ) ) {
			return;
		}

		// Avoid redirection in case of ajax calls.
		if ( wp_doing_ajax() ) {
			return;
		}

		$url = add_query_arg(
			[
				'page'       => 'moderncart_settings',
				'nonce'      => wp_create_nonce( 'moderncart_onboarding_nonce' ),
				'onboarding' => 1,
			],
			admin_url( 'admin.php' )
		);

		delete_transient( 'moderncart_redirect_to_onboarding' );

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Save version information.
	 */
	public function save_version_info(): void {
		$version = get_option( 'moderncart_version' );

		if ( is_array( $version ) && isset( $version['current'] ) && MODERNCART_VER === $version['current'] ) {
			// Already updated.
			return;
		}

		$version = [
			'current'  => MODERNCART_VER,
			'previous' => ( is_array( $version ) && isset( $version['current'] ) ) ? $version['current'] : '',
		];

		update_option( 'moderncart_version', $version );
	}

	/**
	 * Initiator
	 *
	 * @since 0.0.1
	 * @return object initialized object of class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Autoload classes.
	 *
	 * @param string $class class name.
	 */
	public function autoload( $class ): void {
		if ( 0 !== strpos( $class, __NAMESPACE__ ) ) {
			return;
		}

		$class_to_load = $class;

		$filename = preg_replace(
			[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
			[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
			$class_to_load
		);

		if ( is_string( $filename ) ) {
			$filename = strtolower( $filename );

			$file = MODERNCART_DIR . $filename . '.php';

			// if the file redable, include it.
			if ( is_readable( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 *  Declare the woo HPOS compatibility.
	 *
	 * @since 1.0.1
	 * @return void
	 */
	public function declare_woo_hpos_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', MODERNCART_FILE, true );
		}
	}

	/**
	 * Loads plugin classes as per requirement.
	 *
	 * @return void
	 * @since 0.0.1
	 */
	public function load_classes(): void {
		if ( ! class_exists( 'woocommerce' ) ) {
			return;
		}

		// Let WooCommerce know, CartFlows is compatible with HPOS.
		add_action( 'before_woocommerce_init', array( $this, 'declare_woo_hpos_compatibility' ) );

		if ( is_admin() ) {
			Admin_Menu::get_instance();
		}

		Scripts::get_instance();
		Floating::get_instance();
		Slide_Out::get_instance();
		Slide_Out_Ajax::get_instance();
		Floating_Ajax::get_instance();
	}

	/**
	 * Adds links in Plugins page.
	 *
	 * @param array<string> $links Existing links.
	 * @return array<string> Filtered links with settings added.
	 * @since 0.0.1
	 */
	public function action_links( $links ) {
		$plugin_links = apply_filters(
			'moderncart_cpsw_plugin_action_links',
			[
				'moderncart_settings' => '<a href="' . admin_url( 'admin.php?page=moderncart_settings' ) . '">' . __( 'Settings', 'modern-cart' ) . '</a>',
			]
		);

		return array_merge( $plugin_links, $links );
	}
}

/**
 * Kicking this off by calling 'get_instance()' method
 */
Plugin_Loader::get_instance();
