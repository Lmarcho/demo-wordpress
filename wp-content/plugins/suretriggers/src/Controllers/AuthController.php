<?php
/**
 * AuthController.
 * php version 5.6
 *
 * @category AuthController
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Controllers;

use SureCart\Models\ApiToken;
use SureTriggers\Models\SaasApiToken;
use SureTriggers\Traits\SingletonLoader;
use WP_REST_Request;

/**
 * AuthController- Connect and revoke user access_token.
 *
 * @category AuthController
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 *
 * @psalm-suppress UndefinedTrait
 */
class AuthController {

	use SingletonLoader;

	/**
	 * Secret Key for authentication.
	 *
	 * @var string|mixed $secret_key
	 */
	private $secret_key;

	/**
	 * Initialize data.
	 */
	public function __construct() {
		$this->secret_key = SaasApiToken::get();
		add_action( 'admin_init', [ $this, 'save_connection' ] );
		add_action( 'updated_option', [ $this, 'updated_sc_api_key' ], 10, 3 );
	}

	/**
	 * Add or revoke access token from SaaS.
	 *
	 * @param WP_REST_Request $request Request.
	 * 
	 * @return object|void
	 */
	public function revoke_connection( $request ) {
		$secret_key = $request->get_header( 'st_authorization' );
		
		if ( ! is_string( $secret_key ) || empty( $secret_key ) || empty( $this->secret_key ) ) { 
			return;
		}

		$parsed = sscanf( $secret_key, 'Bearer %s' );
		if ( is_array( $parsed ) ) {
			list( $secret_key ) = $parsed;
		}

		if ( empty( $secret_key ) ) { 
			return;
		}

		if ( $this->secret_key !== $secret_key ) {
			return RestController::error_message( 'Invalid secret key.' );
		}

		// delete the suretrigger_options from wp_options table once the connection is deleted on SAAS.
		SaasApiToken::save( null );

		return RestController::success_message();

	}

	/**
	 * Save sure triggers connection.
	 *
	 * @return void
	 */
	public function save_connection() {
		if ( ! isset( $_GET['sure-trigger-connect-nonce'] ) ) {
			return;
		}

		if ( ! isset( $_GET['connection-status'] ) ) {
			return;
		}

		$nonce             = sanitize_text_field( wp_unslash( $_GET['sure-trigger-connect-nonce'] ) );
		$connection_status = (bool) sanitize_text_field( wp_unslash( $_GET['connection-status'] ) );

		if ( false === wp_verify_nonce( $nonce, 'sure-trigger-connect' ) ) {
			return;
		}

		if ( false === current_user_can( 'administrator' ) ) {
			return;
		}

		$access_key = isset( $_GET['sure-triggers-access-key'] ) ? sanitize_text_field( wp_unslash( $_GET['sure-triggers-access-key'] ) ) : false;

		if ( false === $connection_status ) {
			$access_key = 'connection-denied';
		}

		$connected_email_id = isset( $_GET['connected_email'] ) ? sanitize_email( wp_unslash( $_GET['connected_email'] ) ) : '';

		if ( isset( $access_key ) ) {
			SaasApiToken::save( $access_key );
		}
		OptionController::set_option( 'connected_email_key', $connected_email_id );

		/**
		 * If there any SureCart
		 */
		$this->post_authorize_create_sc_connection();
	}

	/**
	 * Create SureCart connection at saas end.
	 *
	 * @return void
	 */
	public function post_authorize_create_sc_connection() {
		if ( ! is_plugin_active( 'surecart/surecart.php' ) || ! class_exists( ApiToken::class ) ) {
			return;
		}

		$this->create_sc_connection();
	}

	/**
	 * Send a request to the SAAS to create SureCart connection for authorized user
	 *
	 * @return void
	 */
	public function create_sc_connection() {
		if ( ! class_exists( ApiToken::class ) ) {
			return;
		}
		$sc_api_key = ApiToken::get();

		if ( empty( $sc_api_key ) ) {
			return;
		}

		$secret_key      = SaasApiToken::get();
		$connected_email = OptionController::get_option( 'connected_email_key' );

		wp_remote_post(
			trailingslashit( SURE_TRIGGERS_API_SERVER_URL ) . 'connection/create-sc',
			[
				'sslverify' => false,
				'timeout'   => 60, //phpcs:ignore WordPressVIPMinimum.Performance.RemoteRequestTimeout.timeout_timeout
				'headers'   => [
					'Authorization' => 'Bearer ' . $secret_key,
					'scapikey'      => $sc_api_key,
				],
				'body'      => [
					'email' => $connected_email,
					'title' => 'SureCart | ' . get_bloginfo( 'name' ),
				],
			]
		);
	}

	/**
	 * Update Sure Cart connection whenever update the API key
	 *
	 * @param string $option Option.
	 * @param mixed  $old_value Old value.
	 * @param mixed  $value Value.
	 * @return void
	 */
	public function updated_sc_api_key( $option, $old_value, $value ) {
		if ( 'sc_api_token' !== $option ) {
			return;
		}

		if ( $value ) {
			$this->create_sc_connection();
		}
	}

}

AuthController::get_instance();
