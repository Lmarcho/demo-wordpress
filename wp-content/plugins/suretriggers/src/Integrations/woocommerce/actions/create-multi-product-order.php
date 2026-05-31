<?php
/**
 * CreateMultiProductOrder.
 * php version 5.6
 *
 * @category CreateMultiProductOrder
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;

/**
 * CreateMultiProductOrder
 *
 * @category CreateMultiProductOrder
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateMultiProductOrder extends AutomateAction {

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
	public $action = 'wc_create_multi_product_order';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create a new order with multiple products', 'suretriggers' ),
			'action'   => 'wc_create_multi_product_order',
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
	 * @return object|array|null
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$user_id = ap_get_user_id_from_email( $selected_options['billing_email'] );
		$order   = wc_create_order();
		if ( ! $order instanceof \WC_Order ) {
			return [
				'status'  => 'error',
				'message' => __( 'Unable to create order.', 'suretriggers' ), 
				
			];
		}
		
		if ( is_int( $user_id ) ) {
			$order->set_customer_id( $user_id );
		}
		// Handle multiple products.
		if ( isset( $selected_options['products'] ) && is_array( $selected_options['products'] ) ) {
			foreach ( $selected_options['products'] as $product_data ) {
				if ( ! isset( $product_data['product_id'] ) ) {
					continue;
				}

				// Extract product ID from array format.
				$product_id = is_array( $product_data['product_id'] ) ? $product_data['product_id']['value'] : $product_data['product_id'];
				$quantity   = isset( $product_data['quantity'] ) ? intval( $product_data['quantity'] ) : 1;
				
				$product = wc_get_product( $product_id );
				if ( ! $product instanceof \WC_Product ) {
					continue;
				}

				// Handle variation products.
				if ( $product->is_type( 'variation' ) ) {
					if ( method_exists( $product, 'get_variation_attributes' ) ) {
						$variation_data = $product->get_variation_attributes();
						if ( method_exists( $order, 'add_product' ) ) {
							$order->add_product( $product, $quantity, $variation_data );
						}
					}
				} else {
					// Handle simple products.
					if ( method_exists( $order, 'add_product' ) ) {
						$order->add_product( $product, $quantity );
					}
				}
			}
		}

		// Apply coupon if provided.
		if ( ! empty( $selected_options['coupon_id'] ) ) {
			if ( method_exists( $order, 'apply_coupon' ) ) {
				$order->apply_coupon( $selected_options['coupon_id'] );
			}
		}

		// Add billing and shipping addresses.
		$billing_address = [
			'first_name' => isset( $selected_options['billing_first_name'] ) ? $selected_options['billing_first_name'] : '',
			'last_name'  => isset( $selected_options['billing_last_name'] ) ? $selected_options['billing_last_name'] : '',
			'company'    => isset( $selected_options['billing_company'] ) ? $selected_options['billing_company'] : '',
			'country'    => isset( $selected_options['billing_country'] ) ? $selected_options['billing_country'] : '',
			'address_1'  => isset( $selected_options['billing_address_1'] ) ? $selected_options['billing_address_1'] : '',
			'address_2'  => isset( $selected_options['billing_address_2'] ) ? $selected_options['billing_address_2'] : '',
			'city'       => isset( $selected_options['billing_city'] ) ? $selected_options['billing_city'] : '',
			'state'      => isset( $selected_options['billing_state'] ) ? $selected_options['billing_state'] : '',
			'postcode'   => isset( $selected_options['billing_zip_code'] ) ? $selected_options['billing_zip_code'] : '',
			'phone'      => isset( $selected_options['billing_phone'] ) ? $selected_options['billing_phone'] : '',
			'email'      => isset( $selected_options['billing_email'] ) ? $selected_options['billing_email'] : '',
		];

		$shipping_address = [
			'first_name' => ! empty( $selected_options['shipping_first_name'] ) ? $selected_options['shipping_first_name'] : ( isset( $selected_options['billing_first_name'] ) ? $selected_options['billing_first_name'] : '' ),
			'last_name'  => ! empty( $selected_options['shipping_last_name'] ) ? $selected_options['shipping_last_name'] : ( isset( $selected_options['billing_last_name'] ) ? $selected_options['billing_last_name'] : '' ),
			'company'    => ! empty( $selected_options['shipping_company'] ) ? $selected_options['shipping_company'] : ( isset( $selected_options['billing_company'] ) ? $selected_options['billing_company'] : '' ),
			'country'    => ! empty( $selected_options['shipping_country'] ) ? $selected_options['shipping_country'] : ( isset( $selected_options['billing_country'] ) ? $selected_options['billing_country'] : '' ),
			'address_1'  => ! empty( $selected_options['shipping_address_1'] ) ? $selected_options['shipping_address_1'] : ( isset( $selected_options['billing_address_1'] ) ? $selected_options['billing_address_1'] : '' ),
			'address_2'  => ! empty( $selected_options['shipping_address_2'] ) ? $selected_options['shipping_address_2'] : ( isset( $selected_options['billing_address_2'] ) ? $selected_options['billing_address_2'] : '' ),
			'city'       => ! empty( $selected_options['shipping_city'] ) ? $selected_options['shipping_city'] : ( isset( $selected_options['billing_city'] ) ? $selected_options['billing_city'] : '' ),
			'state'      => ! empty( $selected_options['shipping_state'] ) ? $selected_options['shipping_state'] : ( isset( $selected_options['billing_state'] ) ? $selected_options['billing_state'] : '' ),
			'postcode'   => ! empty( $selected_options['shipping_zip_code'] ) ? $selected_options['shipping_zip_code'] : ( isset( $selected_options['billing_zip_code'] ) ? $selected_options['billing_zip_code'] : '' ),
			'phone'      => ! empty( $selected_options['shipping_phone'] ) ? $selected_options['shipping_phone'] : ( isset( $selected_options['billing_phone'] ) ? $selected_options['billing_phone'] : '' ),
			'email'      => ! empty( $selected_options['shipping_email'] ) ? $selected_options['shipping_email'] : ( isset( $selected_options['billing_email'] ) ? $selected_options['billing_email'] : '' ),
		];

		$order->set_address( $billing_address, 'billing' );
		$order->set_address( ( ! empty( $selected_options['shipping_billing_address'] ) ) ? $billing_address : $shipping_address, 'shipping' );

		// Add payment method.
		if ( ! empty( $selected_options['payment_method'] ) ) {
			$order->set_payment_method( $selected_options['payment_method'] );
		}
		if ( ! empty( $selected_options['payment_method_title'] ) ) {
			$order->set_payment_method_title( $selected_options['payment_method_title'] );
		}

		// Set order status.
		if ( ! empty( $selected_options['status'] ) ) {
			$order->set_status( $selected_options['status'] );
		}

		// Calculate and save.
		$order->calculate_totals();
		$order->save();

		return WooCommerce::get_order_context( $order->get_id() );
	}
}

CreateMultiProductOrder::get_instance();
