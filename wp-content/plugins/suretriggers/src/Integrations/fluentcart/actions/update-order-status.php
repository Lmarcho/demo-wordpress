<?php
/**
 * UpdateOrderStatus.
 * php version 5.6
 *
 * @category UpdateOrderStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCart\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCart\App\Models\Order;

/**
 * UpdateOrderStatus
 *
 * @category UpdateOrderStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateOrderStatus extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCart';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcart_update_order_status';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Order Status', 'suretriggers' ),
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
	 * @return array|void
	 *
	 * @throws \Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( '\FluentCart\App\Models\Order' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCart is not installed or activated.', 'suretriggers' ),
			];
		}

		$order_id   = isset( $selected_options['order_id'] ) ? $selected_options['order_id'] : '';
		$new_status = isset( $selected_options['new_status'] ) ? $selected_options['new_status'] : '';
		
		if ( empty( $order_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Order ID is required.', 'suretriggers' ),
			];
		}

		if ( empty( $new_status ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Order status is required.', 'suretriggers' ),
			];
		}

		try {
			$order = Order::find( $order_id );

			if ( ! $order ) {
				return [
					'status'  => 'error',
					'message' => __( 'Order not found.', 'suretriggers' ),
				];
			}

			$old_status = $order->status;

			// Update order status.
			$valid_statuses = [ 'processing', 'completed', 'on-hold', 'canceled', 'failed' ];
			if ( ! in_array( $new_status, $valid_statuses ) ) {
				return [
					'status'  => 'error',
					'message' => sprintf( __( 'Invalid order status. Valid statuses are: %s', 'suretriggers' ), implode( ', ', $valid_statuses ) ),
				];
			}
			$order->status = $new_status;

			$order->save();

			// Fire WordPress action for status change.
			if ( $old_status !== $order->status ) {
				do_action( 'fluent_cart_order_status_changed', $order, $old_status, $order->status );
			}

			$context = [
				'order_id'     => $order->id,
				'old_status'   => $old_status,
				'new_status'   => $order->status,
				'total_amount' => $order->total_amount,
			];

			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

UpdateOrderStatus::get_instance();
