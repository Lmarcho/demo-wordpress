<?php
/**
 * AddNewOrder.
 * php version 5.6
 *
 * @category AddNewOrder
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */

namespace SureTriggers\Integrations\ProfilePress\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use ProfilePress\Core\Membership\Models\Customer\CustomerFactory;
use ProfilePress\Core\Membership\Services\Calculator;
use ProfilePressVendor\Carbon\CarbonImmutable;
use ProfilePress\Core\Membership\Models\Order\OrderFactory;

/**
 * AddNewOrder
 *
 * @category AddNewOrder
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddNewOrder extends AutomateAction {


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'ProfilePress';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'add_new_order';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add New Order', 'suretriggers' ),
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
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		if ( ! function_exists( 'ppress_get_plan' ) ||
		! function_exists( 'ppress_subscribe_user_to_plan' ) ||
		! function_exists( 'ppress_clean' )
		) {
			return [
				'status'  => 'error',
				'message' => __( 'ProfilePress functions not found.', 'suretriggers' ), 
				
			];
		}

		if ( ! class_exists( 'ProfilePress\Core\Membership\Models\Customer\CustomerFactory' ) ||
			! class_exists( 'ProfilePress\Core\Membership\Services\Calculator' ) ||
			! class_exists( 'ProfilePressVendor\Carbon\CarbonImmutable' ) ||
			! class_exists( 'ProfilePress\Core\Membership\Models\Order\OrderFactory' )
			) {
			return [
				'status'  => 'error',
				'message' => __( 'ProfilePress classes not found.', 'suretriggers' ), 
				
			];
		}

		$order_data = $selected_options;

		// Validate required fields.
		if ( empty( $order_data['plan'] ) ) {
			return [
				'status'   => __( 'Error', 'suretriggers' ),
				'response' => __( 'No plan was selected. Please try again.', 'suretriggers' ), 
				
			];
		}

		if ( empty( $order_data['customer'] ) ) {
			return [
				'status'   => __( 'Error', 'suretriggers' ),
				'response' => __( 'No customer was selected. Please try again.', 'suretriggers' ), 
				
			];
		}

		$plan = ppress_get_plan( absint( $order_data['plan'] ) );
		if ( ! $plan || empty( $plan->id ) ) {
			return [
				'status'   => __( 'Error', 'suretriggers' ),
				'response' => __( 'The selected plan does not exist.', 'suretriggers' ), 
				
			];
		}

		$customer = CustomerFactory::fromId( absint( $order_data['customer'] ) );
		if ( ! $customer || ! $customer->user_exists() ) {
			return [
				'status'   => __( 'Error', 'suretriggers' ),
				'response' => __( 'The selected customer does not exist.', 'suretriggers' ), 
				
			];
		}

		// Default order creation date.
		$date_created = current_time( 'mysql' );

		if ( ! empty( $order_data['order_date'] ) ) {
			$date_created = CarbonImmutable::parse(
				sanitize_text_field( $order_data['order_date'] ),
				wp_timezone()
			)->setTimeFromTimeString(
				CarbonImmutable::now( wp_timezone() )->toTimeString()
			)->utc()->toDateTimeString();
		}

		// Fallback to plan price if no amount given.
		if ( empty( $order_data['amount'] ) || Calculator::init( $order_data['amount'] )->isNegativeOrZero() ) {
			$order_data['amount'] = ppress_get_plan( $order_data['plan'] )->get_price();
		}

		// Create the order.
		$response = ppress_subscribe_user_to_plan(
			$order_data['plan'],
			$order_data['customer'],
			[
				'amount'         => $order_data['amount'],
				'order_status'   => isset( $order_data['order_status'] ) ? sanitize_text_field( $order_data['order_status'] ) : 'pending',
				'payment_method' => isset( $order_data['payment_method'] ) ? sanitize_text_field( $order_data['payment_method'] ) : '',
				'transaction_id' => isset( $order_data['transaction_id'] ) ? sanitize_text_field( $order_data['transaction_id'] ) : '',
				'date_created'   => $date_created,
			],
			isset( $order_data['send_receipt'] ) && 'true' === $order_data['send_receipt']
		);

		if ( is_wp_error( $response ) ) {
			return [
				'status'   => __( 'Error', 'suretriggers' ),
				'response' => $response->get_error_message(),
			];
		}

		if ( $response['order_id'] ) {
			$order        = OrderFactory::fromId( absint( $response['order_id'] ) );
			$billing_keys = [ 'address', 'city', 'country', 'state', 'postcode', 'phone' ];
			foreach ( $billing_keys as $key ) {
				$setter         = 'billing_' . $key;
				$value          = isset( $selected_options[ $setter ] ) ? sanitize_textarea_field( $selected_options[ $setter ] ) : '';
				$order->$setter = ppress_clean( $value, 'sanitize_text_field' );
			}
			$order->save();
		}

		return [
			'status'   => __( 'Success', 'suretriggers' ),
			'response' => sprintf(
				__( 'Order successfully created with ID %d.', 'suretriggers' ),
				$response['order_id']
			),
			'order'    => $response,
		];
	}
}

AddNewOrder::get_instance();
