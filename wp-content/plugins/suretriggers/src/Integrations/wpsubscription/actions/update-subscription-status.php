<?php
/**
 * UpdateSubscriptionStatus.
 * php version 5.6
 *
 * @category UpdateSubscriptionStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPSubscription\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * UpdateSubscriptionStatus
 *
 * @category UpdateSubscriptionStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateSubscriptionStatus extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WPSubscription';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wp_update_subscription_status';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Subscription Status', 'suretriggers' ),
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
	 * @param array $selected_options selected_options.
	 *
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$subscription_id = isset( $selected_options['subscription_id'] ) ? absint( $selected_options['subscription_id'] ) : '';
		$new_status      = isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : '';

		if ( empty( $subscription_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Subscription ID is required.', 'suretriggers' ), 
				
			];
		}

		if ( empty( $new_status ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Status is required.', 'suretriggers' ), 
				
			];
		}

		$subscription = get_post( $subscription_id );
		if ( ! $subscription || 'subscrpt_order' !== $subscription->post_type ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid subscription ID.', 'suretriggers' ), 
				
			];
		}

		$valid_statuses = [ 'active', 'cancelled', 'expired', 'pending', 'pe_cancelled' ];
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid status provided.', 'suretriggers' ), 
				
			];
		}

		$old_status = $subscription->post_status;
		
		if ( class_exists( '\SpringDevs\Subscription\Illuminate\Action' ) && is_callable( [ '\SpringDevs\Subscription\Illuminate\Action', 'status' ] ) ) {
			$update_result = \SpringDevs\Subscription\Illuminate\Action::status( $new_status, $subscription_id );
		} else {
			$update_result = wp_update_post(
				[
					'ID'          => $subscription_id,
					'post_status' => $new_status,
				]
			);
		}
		
		return [
			'subscription_id' => $subscription_id,
			'old_status'      => $old_status,
			'new_status'      => $new_status,
			'updated'         => true,
			'success'         => true,
		];
	}
}

UpdateSubscriptionStatus::get_instance();
