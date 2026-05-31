<?php
/**
 * RemoveProductAccess.
 * php version 5.6
 *
 * @category RemoveProductAccess
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
 * RemoveProductAccess
 *
 * @category RemoveProductAccess
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RemoveProductAccess extends AutomateAction {

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
	public $action = 'ta_remove_product_access';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Remove Product Access', 'suretriggers' ),
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

		if ( ! class_exists( 'TVA_User' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'TVA_User class not available', 'suretriggers' ),
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

		$tva_user          = new \TVA_User( $user->ID );
		$had_access_before = $tva_user->has_bought( $product_id );
		
		if ( ! $had_access_before ) {
			return [
				'status'  => 'error',
				'message' => __( 'User does not have access to this product', 'suretriggers' ),
			];
		}
		
		$result = \TVA_Customer::remove_user_from_product( $user, $product_id );
		
		if ( ! $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to remove product access', 'suretriggers' ),
			];
		}
		
		wp_cache_delete( $user->ID, 'tva_user' );
		$tva_user         = new \TVA_User( $user->ID );
		$still_has_access = $tva_user->has_bought( $product_id );
		if ( $still_has_access ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product access could not be removed completely', 'suretriggers' ),
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
		
		do_action( 'tva_user_product_access_revoked', $user, $product_id );

		return [
			'status'       => 'success',
			'message'      => __( 'Product access removed successfully', 'suretriggers' ),
			'product_data' => $product_data,
			'user_data'    => $user_data,
		];
	}
}

RemoveProductAccess::get_instance();
