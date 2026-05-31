<?php
/**
 * ListAllOrders.
 * php version 5.6
 *
 * @category ListAllOrders
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WooCommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;

/**
 * ListAllOrders
 *
 * @category ListAllOrders
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListAllOrders extends AutomateAction {

	use SingletonLoader;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WooCommerce';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wc_list_all_orders';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List All Orders', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id          User ID.
	 * @param int   $automation_id    Automation ID.
	 * @param array $fields           Fields.
	 * @param array $selected_options Selected options.
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return [
				'success' => false,
				'message' => __( 'WooCommerce order functions not available.', 'suretriggers' ),
			];
		}

		// Get parameters with defaults.
		$limit  = isset( $selected_options['limit'] ) ? intval( $selected_options['limit'] ) : 10;
		$status = isset( $selected_options['status'] ) ? $selected_options['status'] : 'any';

		// Validate limit.
		if ( $limit <= 0 || $limit > 100 ) {
			$limit = 10;
		}

		// Get orders using WooCommerce function.
		$args = [
			'limit'   => $limit,
			'status'  => $status,
			'orderby' => 'date',
			'order'   => 'DESC',
		];

		$orders = wc_get_orders( $args );

		if ( empty( $orders ) ) {
			return [
				'success'      => true,
				'orders'       => [],
				'orders_count' => 0,
				'message'      => __( 'No orders found.', 'suretriggers' ),
			];
		}

		// Format orders data.
		$formatted_orders = [];
		if ( is_array( $orders ) ) {
			foreach ( $orders as $order ) {
				$formatted_orders[] = $this->format_order_data( $order );
			}
		}

		$response_data = [
			'success'         => true,
			'orders'          => $formatted_orders,
			'orders_count'    => count( $formatted_orders ),
			'filters_applied' => [
				'limit'  => $limit,
				'status' => $status,
			],
			'message'         => sprintf( __( 'Retrieved %d orders.', 'suretriggers' ), count( $formatted_orders ) ),
		];

		return $response_data;
	}

	/**
	 * Format order data.
	 *
	 * @param \WC_Order $order WooCommerce order object.
	 * @return array
	 */
	private function format_order_data( $order ) {
		// Type check to ensure we have a WC_Order object.
		if ( ! is_a( $order, 'WC_Order' ) ) {
			return [];
		}
		// Get dates with null checks.
		$date_created   = $order->get_date_created();
		$date_modified  = $order->get_date_modified();
		$date_completed = $order->get_date_completed();
		$date_paid      = $order->get_date_paid();

		$order_data = [
			'id'                   => $order->get_id(),
			'order_number'         => $order->get_order_number(),
			'status'               => $order->get_status(),
			'currency'             => $order->get_currency(),
			'total'                => $order->get_total(),
			'subtotal'             => $order->get_subtotal(),
			'tax_total'            => $order->get_total_tax(),
			'shipping_total'       => $order->get_shipping_total(),
			'discount_total'       => $order->get_discount_total(),
			'payment_method'       => $order->get_payment_method(),
			'payment_method_title' => $order->get_payment_method_title(),
			'date_created'         => $date_created ? $date_created->date( 'Y-m-d H:i:s' ) : '',
			'date_modified'        => $date_modified ? $date_modified->date( 'Y-m-d H:i:s' ) : '',
			'date_completed'       => $date_completed ? $date_completed->date( 'Y-m-d H:i:s' ) : '',
			'date_paid'            => $date_paid ? $date_paid->date( 'Y-m-d H:i:s' ) : '',
		];

		// Add customer information.
		$customer_id            = $order->get_customer_id();
		$order_data['customer'] = [
			'id'         => $customer_id,
			'email'      => $order->get_billing_email(),
			'first_name' => $order->get_billing_first_name(),
			'last_name'  => $order->get_billing_last_name(),
			'phone'      => $order->get_billing_phone(),
		];

		// Add billing address.
		$order_data['billing'] = [
			'first_name' => $order->get_billing_first_name(),
			'last_name'  => $order->get_billing_last_name(),
			'company'    => $order->get_billing_company(),
			'address_1'  => $order->get_billing_address_1(),
			'address_2'  => $order->get_billing_address_2(),
			'city'       => $order->get_billing_city(),
			'state'      => $order->get_billing_state(),
			'postcode'   => $order->get_billing_postcode(),
			'country'    => $order->get_billing_country(),
			'email'      => $order->get_billing_email(),
			'phone'      => $order->get_billing_phone(),
		];

		// Add shipping address.
		$order_data['shipping'] = [
			'first_name' => $order->get_shipping_first_name(),
			'last_name'  => $order->get_shipping_last_name(),
			'company'    => $order->get_shipping_company(),
			'address_1'  => $order->get_shipping_address_1(),
			'address_2'  => $order->get_shipping_address_2(),
			'city'       => $order->get_shipping_city(),
			'state'      => $order->get_shipping_state(),
			'postcode'   => $order->get_shipping_postcode(),
			'country'    => $order->get_shipping_country(),
		];

		// Add order items.
		$items = [];
		foreach ( $order->get_items() as $item_id => $item ) {
			// Type check for WooCommerce order item.
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			$product = $item->get_product();
			$items[] = [
				'id'           => $item_id,
				'product_id'   => $item->get_product_id(),
				'variation_id' => $item->get_variation_id(),
				'name'         => $item->get_name(),
				'quantity'     => $item->get_quantity(),
				'total'        => $item->get_total(),
				'subtotal'     => $item->get_subtotal(),
				'sku'          => ( $product && is_object( $product ) && is_a( $product, 'WC_Product' ) ) ? $product->get_sku() : '',
			];
		}
		$order_data['items'] = $items;

		// Add shipping methods.
		$shipping_methods = [];
		foreach ( $order->get_shipping_methods() as $shipping_method ) {
			$shipping_methods[] = [
				'id'           => $shipping_method->get_id(),
				'method_title' => $shipping_method->get_method_title(),
				'method_id'    => $shipping_method->get_method_id(),
				'total'        => $shipping_method->get_total(),
			];
		}
		$order_data['shipping_methods'] = $shipping_methods;

		// Add order notes count.
		$notes                     = wc_get_order_notes(
			[
				'order_id' => $order->get_id(),
				'limit'    => 1,
			] 
		);
		$order_data['notes_count'] = count( $notes );

		// Add formatted country names if available.
		if ( ! empty( $order_data['billing']['country'] ) ) {
			$countries                             = WC()->countries->get_countries();
			$order_data['billing']['country_name'] = isset( $countries[ $order_data['billing']['country'] ] ) 
				? $countries[ $order_data['billing']['country'] ] 
				: $order_data['billing']['country'];
		}

		if ( ! empty( $order_data['shipping']['country'] ) ) {
			$countries                              = WC()->countries->get_countries();
			$order_data['shipping']['country_name'] = isset( $countries[ $order_data['shipping']['country'] ] ) 
				? $countries[ $order_data['shipping']['country'] ] 
				: $order_data['shipping']['country'];
		}

		// Add order totals summary.
		$order_data['totals'] = [
			'subtotal'       => wc_format_decimal( $order->get_subtotal(), 2 ),
			'discount_total' => wc_format_decimal( $order->get_discount_total(), 2 ),
			'shipping_total' => wc_format_decimal( $order->get_shipping_total(), 2 ),
			'tax_total'      => wc_format_decimal( $order->get_total_tax(), 2 ),
			'total'          => wc_format_decimal( $order->get_total(), 2 ),
		];

		return $order_data;
	}
}

ListAllOrders::get_instance();
