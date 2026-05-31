<?php
/**
 * GetOrder.
 * php version 5.6
 *
 * @category GetOrder
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
use FluentCart\App\Models\OrderItem;

/**
 * GetOrder
 *
 * @category GetOrder
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetOrder extends AutomateAction {

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
	public $action = 'fluentcart_get_order';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Order', 'suretriggers' ),
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

		$order_id = isset( $selected_options['order_id'] ) ? $selected_options['order_id'] : '';
		
		if ( empty( $order_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Order ID is required.', 'suretriggers' ),
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

			$context = [
				'order_id'              => $order->id,
				'status'                => $order->status,
				'payment_status'        => $order->payment_status,
				'payment_method'        => $order->payment_method,
				'payment_method_title'  => $order->payment_method_title,
				'currency'              => $order->currency,
				'subtotal'              => $order->subtotal,
				'discount_total'        => $order->coupon_discount_total + $order->manual_discount_total,
				'coupon_discount_total' => $order->coupon_discount_total,
				'manual_discount_total' => $order->manual_discount_total,
				'shipping_total'        => $order->shipping_total,
				'shipping_tax'          => $order->shipping_tax,
				'tax_total'             => $order->tax_total,
				'total_amount'          => $order->total_amount,
				'total_paid'            => $order->total_paid,
				'total_refund'          => $order->total_refund,
				'invoice_no'            => $order->invoice_no,
				'receipt_number'        => $order->receipt_number,
				'note'                  => $order->note,
				'ip_address'            => $order->ip_address,
				'created_at'            => $order->created_at,
				'completed_at'          => $order->completed_at,
				'refunded_at'           => $order->refunded_at,
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

GetOrder::get_instance();
