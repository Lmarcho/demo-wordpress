<?php
/**
 * GrantProductAccess.
 * php version 5.6
 *
 * @category GrantProductAccess
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ThriveApprentice\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

/**
 * GrantProductAccess
 *
 * @category GrantProductAccess
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GrantProductAccess extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'ThriveApprentice';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'ta_grant_product_access';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Grant Product Access', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selectedOptions.
	 *
	 * @return array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		if ( ! defined( 'TVA_IS_APPRENTICE' ) && ! class_exists( 'TVA_Const' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Thrive Apprentice is not active', 'suretriggers' ),
			];
		}

		if ( ! class_exists( 'TVA_Customer' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'TVA_Customer class not available', 'suretriggers' ),
			];
		}

		$product_id = isset( $selected_options['product_id'] ) ? $selected_options['product_id'] : '';
		$user_email = isset( $selected_options['wp_user_email'] ) ? $selected_options['wp_user_email'] : '';

		if ( empty( $product_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product ID is required', 'suretriggers' ),
			];
		}

		if ( empty( $user_email ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'User email is required', 'suretriggers' ),
			];
		}

		$user = get_user_by( 'email', $user_email );
		if ( ! $user ) {
			return [
				'status'  => 'error',
				'message' => __( 'WordPress user not found with this email address', 'suretriggers' ),
			];
		}

		$result = \TVA_Customer::enrol_user_to_product( $user->ID, $product_id );
		
		if ( ! $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to grant product access - product may not exist', 'suretriggers' ),
			];
		}

		$product_post = get_post( $product_id );
		$product_data = [
			'product_id'   => $product_id,
			'product_name' => $product_post ? $product_post->post_title : '',
		];

		$user_data = [
			'user_id'    => $user->ID,
			'user_email' => $user->user_email,
			'user_login' => $user->user_login,
		];
		
		do_action( 'tva_user_receives_product_access', $user, $product_id );

		return [
			'status'       => 'success',
			'message'      => __( 'Product access granted successfully', 'suretriggers' ),
			'product_data' => $product_data,
			'user_data'    => $user_data,
		];
	}
}

GrantProductAccess::get_instance();
