<?php
/**
 * CreateBundleProductOrder.
 * php version 5.6
 *
 * @category CreateBundleProductOrder
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
 * CreateBundleProductOrder
 *
 * @category CreateBundleProductOrder
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateBundleProductOrder extends AutomateAction {

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
	public $action = 'wc_create_bundle_product_order';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create a new order for bundle product with licensing support', 'suretriggers' ),
			'action'   => 'wc_create_bundle_product_order',
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
		
		$order->set_created_via( 'admin' );
		
		if ( is_int( $user_id ) ) {
			$order->set_customer_id( $user_id );
		}

		$quantity = isset( $selected_options['quantity'] ) ? (int) $selected_options['quantity'] : 1;

		$product = wc_get_product( $selected_options['product_id'] );
		
		if ( ! $product ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product not found.', 'suretriggers' ),
			];
		}

		if ( class_exists( '\WC_PB_Order' ) && function_exists( 'WC_PB' ) && $product->is_type( 'bundle' ) ) {
			// Use WooCommerce Product Bundles built-in configuration generator.
			$configuration = WC_PB()->cart->get_posted_bundle_configuration( $product );
			
			$pb_order_instance = \WC_PB_Order::instance();
			$pb_order_instance->add_bundle_to_order(
				$product,
				$order,
				$quantity,
				[
					'configuration' => $configuration,
					'silent'        => true,
				] 
			);
		} else {
			$order->add_product( $product, $quantity );
		}

		if ( ! empty( $selected_options['coupon_id'] ) ) {
			if ( method_exists( $order, 'apply_coupon' ) ) {
				$order->apply_coupon( $selected_options['coupon_id'] );
			}
		}

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
			'first_name' => isset( $selected_options['shipping_first_name'] ) ? $selected_options['shipping_first_name'] : ( isset( $selected_options['billing_first_name'] ) ? $selected_options['billing_first_name'] : '' ),
			'last_name'  => isset( $selected_options['shipping_last_name'] ) ? $selected_options['shipping_last_name'] : ( isset( $selected_options['billing_last_name'] ) ? $selected_options['billing_last_name'] : '' ),
			'company'    => isset( $selected_options['shipping_company'] ) ? $selected_options['shipping_company'] : ( isset( $selected_options['billing_company'] ) ? $selected_options['billing_company'] : '' ),
			'country'    => isset( $selected_options['shipping_country'] ) ? $selected_options['shipping_country'] : ( isset( $selected_options['billing_country'] ) ? $selected_options['billing_country'] : '' ),
			'address_1'  => isset( $selected_options['shipping_address_1'] ) ? $selected_options['shipping_address_1'] : ( isset( $selected_options['billing_address_1'] ) ? $selected_options['billing_address_1'] : '' ),
			'address_2'  => isset( $selected_options['shipping_address_2'] ) ? $selected_options['shipping_address_2'] : ( isset( $selected_options['billing_address_2'] ) ? $selected_options['billing_address_2'] : '' ),
			'city'       => isset( $selected_options['shipping_city'] ) ? $selected_options['shipping_city'] : ( isset( $selected_options['billing_city'] ) ? $selected_options['billing_city'] : '' ),
			'state'      => isset( $selected_options['shipping_state'] ) ? $selected_options['shipping_state'] : ( isset( $selected_options['billing_state'] ) ? $selected_options['billing_state'] : '' ),
			'postcode'   => isset( $selected_options['shipping_zip_code'] ) ? $selected_options['shipping_zip_code'] : ( isset( $selected_options['billing_zip_code'] ) ? $selected_options['billing_zip_code'] : '' ),
			'phone'      => isset( $selected_options['shipping_phone'] ) ? $selected_options['shipping_phone'] : ( isset( $selected_options['billing_phone'] ) ? $selected_options['billing_phone'] : '' ),
			'email'      => isset( $selected_options['shipping_email'] ) ? $selected_options['shipping_email'] : ( isset( $selected_options['billing_email'] ) ? $selected_options['billing_email'] : '' ),
		];

		$order->set_address( $billing_address, 'billing' );
		$use_billing = isset( $selected_options['shipping_billing_address'] ) ? $selected_options['shipping_billing_address'] : false;
		$order->set_address( $use_billing ? $billing_address : $shipping_address, 'shipping' );

		if ( ! empty( $selected_options['payment_method'] ) ) {
			$order->set_payment_method( $selected_options['payment_method'] );
		}
		if ( ! empty( $selected_options['payment_method_title'] ) ) {
			$order->set_payment_method_title( $selected_options['payment_method_title'] );
		}

		$status = isset( $selected_options['status'] ) ? $selected_options['status'] : 'completed';
		$order->set_status( $status );

		$order->save();

		return WooCommerce::get_order_context( $order->get_id() );
	}
	

}

CreateBundleProductOrder::get_instance();
