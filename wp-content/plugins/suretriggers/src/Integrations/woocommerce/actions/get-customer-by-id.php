<?php
/**
 * GetCustomerById.
 * php version 5.6
 *
 * @category GetCustomerById
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use WC_Customer;
use Exception;

/**
 * GetCustomerById
 *
 * @category GetCustomerById
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetCustomerById extends AutomateAction {

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
	public $action = 'wc_get_customer_by_id';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Customer by ID', 'suretriggers' ),
			'action'   => 'wc_get_customer_by_id',
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
	 * @return void|array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Customer' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce not available', 'suretriggers' ),
			];
		}

		// Validate required fields.
		foreach ( $fields as $field ) {
			if ( array_key_exists( 'validationProps', $field ) && empty( $selected_options[ $field['name'] ] ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Required field is missing: ', 'suretriggers' ) . $field['name'],
				];
			}
		}

		// Get customer ID from options or use provided user_id.
		$customer_id = ! empty( $selected_options['customer_id'] ) ? intval( $selected_options['customer_id'] ) : $user_id;

		if ( empty( $customer_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Customer ID is required', 'suretriggers' ),
			];
		}

		try {
			// Get the customer.
			$customer = new WC_Customer( $customer_id );

			// Check if customer exists.
			if ( ! $customer->get_id() ) {
				return [
					'status'  => 'error',
					'message' => __( 'Customer not found', 'suretriggers' ),
				];
			}

			// Get WordPress user data.
			$user_data = get_userdata( $customer_id );
			if ( ! $user_data ) {
				return [
					'status'  => 'error',
					'message' => __( 'WordPress user not found', 'suretriggers' ),
				];
			}

			// Get customer's last order.
			$last_order      = $customer->get_last_order();
			$last_order_data = [];
			if ( $last_order ) {
				$last_order_data = [
					'id'           => $last_order->get_id(),
					'status'       => $last_order->get_status(),
					'total'        => $last_order->get_total(),
					'date_created' => $last_order->get_date_created() ? $last_order->get_date_created()->getTimestamp() : null,
				];
			}

			// Get customer's orders count and total spent.
			$orders_count = $customer->get_order_count();
			$total_spent  = $customer->get_total_spent();

			// Get billing address.
			$billing_address = [
				'first_name' => $customer->get_billing_first_name(),
				'last_name'  => $customer->get_billing_last_name(),
				'company'    => $customer->get_billing_company(),
				'address_1'  => $customer->get_billing_address_1(),
				'address_2'  => $customer->get_billing_address_2(),
				'city'       => $customer->get_billing_city(),
				'state'      => $customer->get_billing_state(),
				'postcode'   => $customer->get_billing_postcode(),
				'country'    => $customer->get_billing_country(),
				'email'      => $customer->get_billing_email(),
				'phone'      => $customer->get_billing_phone(),
			];

			// Get shipping address.
			$shipping_address = [
				'first_name' => $customer->get_shipping_first_name(),
				'last_name'  => $customer->get_shipping_last_name(),
				'company'    => $customer->get_shipping_company(),
				'address_1'  => $customer->get_shipping_address_1(),
				'address_2'  => $customer->get_shipping_address_2(),
				'city'       => $customer->get_shipping_city(),
				'state'      => $customer->get_shipping_state(),
				'postcode'   => $customer->get_shipping_postcode(),
				'country'    => $customer->get_shipping_country(),
			];

			// Get country and state names.
			$billing_country_name  = '';
			$billing_state_name    = '';
			$shipping_country_name = '';
			$shipping_state_name   = '';

			if ( function_exists( 'WC' ) && WC()->countries ) {
				$countries = WC()->countries->get_countries();
				
				// Get billing country and state names.
				if ( ! empty( $billing_address['country'] ) && isset( $countries[ $billing_address['country'] ] ) ) {
					$billing_country_name = $countries[ $billing_address['country'] ];
					$states               = WC()->countries->get_states( $billing_address['country'] );
					if ( ! empty( $billing_address['state'] ) && isset( $states[ $billing_address['state'] ] ) ) {
						$billing_state_name = $states[ $billing_address['state'] ];
					}
				}

				// Get shipping country and state names.
				if ( ! empty( $shipping_address['country'] ) && isset( $countries[ $shipping_address['country'] ] ) ) {
					$shipping_country_name = $countries[ $shipping_address['country'] ];
					$states                = WC()->countries->get_states( $shipping_address['country'] );
					if ( ! empty( $shipping_address['state'] ) && isset( $states[ $shipping_address['state'] ] ) ) {
						$shipping_state_name = $states[ $shipping_address['state'] ];
					}
				}
			}

			// Get user meta data.
			$user_meta       = get_user_meta( $customer_id );
			$additional_data = [];
			if ( is_array( $user_meta ) ) {
				foreach ( $user_meta as $key => $value ) {
					if ( is_string( $key ) && ! str_starts_with( $key, '_' ) ) { // Skip private meta keys.
						$additional_data[ $key ] = is_array( $value ) && count( $value ) === 1 ? $value[0] : $value;
					}
				}
			}

			return [
				'status'                => 'success',
				'message'               => __( 'Customer retrieved successfully', 'suretriggers' ),
				'customer_id'           => $customer->get_id(),
				'username'              => $customer->get_username(),
				'email'                 => $customer->get_email(),
				'first_name'            => $customer->get_first_name(),
				'last_name'             => $customer->get_last_name(),
				'display_name'          => $customer->get_display_name(),
				'date_created'          => $customer->get_date_created() ? $customer->get_date_created()->getTimestamp() : null,
				'date_modified'         => $customer->get_date_modified() ? $customer->get_date_modified()->getTimestamp() : null,
				'role'                  => ! empty( $user_data->roles ) ? $user_data->roles[0] : '',
				'avatar_url'            => $customer->get_avatar_url(),
				'orders_count'          => $orders_count,
				'total_spent'           => wc_format_decimal( $total_spent, 2 ),
				'last_order'            => $last_order_data,
				'billing_address'       => $billing_address,
				'shipping_address'      => $shipping_address,
				'billing_country_name'  => $billing_country_name,
				'billing_state_name'    => $billing_state_name,
				'shipping_country_name' => $shipping_country_name,
				'shipping_state_name'   => $shipping_state_name,
				'is_paying_customer'    => $customer->get_is_paying_customer(),
				'additional_data'       => $additional_data,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Error retrieving customer: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

GetCustomerById::get_instance();
