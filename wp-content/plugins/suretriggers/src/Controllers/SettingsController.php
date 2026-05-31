<?php
/**
 * SettingsController.
 * php version 5.6
 *
 * @category SettingsController
 * @package  SureTrigger
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Controllers;

use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'SettingsController' ) ) :
	/**
	 * SettingsController
	 *
	 * @category SettingsController
	 * @package  SureTrigger
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class SettingsController {

		use SingletonLoader;

		/**
		 * SettingsController constructor.
		 */
		public function __construct() {
			add_action( 'wp_ajax_suretriggers_save_settings', [ $this, 'suretriggers_save_settings_callback' ] );
			add_action( 'wp_ajax_nopriv_suretriggers_save_settings', [ $this, 'suretriggers_settings_unauthorized_access' ] );
		}
		
		/**
		 * Save settings.
		 * 
		 * @return void
		 */
		public function suretriggers_settings_unauthorized_access() {
			wp_send_json_error( [ 'message' => 'Unauthorized access' ] );
		}

		/**
		 * Save settings.
		 * 
		 * @return void
		 */
		public function suretriggers_save_settings_callback() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( [ 'message' => 'Permission denied' ] );
			}
			if ( ! isset( $_POST['suretriggers_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['suretriggers_settings_nonce'] ) ), 'suretriggers_settings_nonce_action' ) ) {
				wp_send_json_error( [ 'message' => 'Security check failed' ] );
			}
			$user_id        = get_current_user_id();
			$transient_name = 'suretriggers_settings_save_' . $user_id;
			if ( get_transient( $transient_name ) ) {
				wp_send_json_error( [ 'message' => 'Please wait before submitting again' ] );
			}
			set_transient( $transient_name, true, 3 );

			// Always reset to empty arrays first.
			$user_ids   = [];
			$user_roles = [];

			// Process user IDs if present.
			if ( isset( $_POST['st_selected_users'] ) && is_array( $_POST['st_selected_users'] ) ) {
				$user_ids = array_map( 'absint', array_map( 'sanitize_text_field', wp_unslash( $_POST['st_selected_users'] ) ) );
			}

			// Process user roles if present.
			if ( isset( $_POST['st_selected_user_roles'] ) && is_array( $_POST['st_selected_user_roles'] ) ) {
				$user_roles = array_map( 'sanitize_key', array_map( 'sanitize_text_field', wp_unslash( $_POST['st_selected_user_roles'] ) ) );
			}

			// Update options with the processed arrays (empty or with values).
			update_option( 'suretriggers_enabled_users', $user_ids );
			update_option( 'suretriggers_enabled_user_roles', $user_roles );

			wp_send_json_success( [ 'message' => 'Settings saved successfully' ] );
		}

	}
	SettingsController::get_instance();

endif;
