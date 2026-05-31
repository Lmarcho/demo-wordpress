<?php
/**
 * GetAllCustomers.
 * php version 5.6
 *
 * @category GetAllCustomers
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
 * GetAllCustomers
 *
 * @category GetAllCustomers
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetAllCustomers extends AutomateAction {

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
	public $action = 'wc_get_all_customers';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get All Customers', 'suretriggers' ),
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
		if ( ! class_exists( 'WC_Customer' ) ) {
			return [
				'success' => false,
				'message' => __( 'WooCommerce Customer class not available.', 'suretriggers' ),
			];
		}

		// Get parameters with defaults.
		$limit = isset( $selected_options['limit'] ) ? intval( $selected_options['limit'] ) : 10;
		$role  = isset( $selected_options['role'] ) ? $selected_options['role'] : 'customer';
		
		// Validate limit.
		if ( $limit <= 0 || $limit > 100 ) {
			$limit = 10;
		}

		// Get customers using WordPress users query.
		$args = [
			'role'    => $role,
			'number'  => $limit,
			'orderby' => 'registered',
			'order'   => 'DESC',
		];

		$users = get_users( $args );

		if ( empty( $users ) ) {
			return [
				'success'         => true,
				'customers'       => [],
				'customers_count' => 0,
				'message'         => __( 'No customers found.', 'suretriggers' ),
			];
		}

		// Format customers data.
		$formatted_customers = [];
		foreach ( $users as $user ) {
			$formatted_customers[] = $this->format_customer_data( $user );
		}

		$response_data = [
			'success'         => true,
			'customers'       => $formatted_customers,
			'customers_count' => count( $formatted_customers ),
			'filters_applied' => [
				'limit' => $limit,
				'role'  => $role,
			],
			'message'         => sprintf( __( 'Retrieved %d customers.', 'suretriggers' ), count( $formatted_customers ) ),
		];

		return $response_data;
	}

	/**
	 * Format customer data.
	 *
	 * @param \WP_User $user WordPress user object.
	 * @return array
	 */
	private function format_customer_data( $user ) {
		if ( ! is_a( $user, 'WP_User' ) ) {
			return [];
		}
		$customer = new \WC_Customer( $user->ID );

		// Get dates with null checks.
		$date_created  = $customer->get_date_created();
		$date_modified = $customer->get_date_modified();

		$customer_data = [
			'id'            => $customer->get_id(),
			'username'      => $customer->get_username(),
			'email'         => $customer->get_email(),
			'first_name'    => $customer->get_first_name(),
			'last_name'     => $customer->get_last_name(),
			'display_name'  => $customer->get_display_name(),
			'role'          => is_array( $user->roles ) ? implode( ', ', $user->roles ) : '',
			'date_created'  => $date_created ? $date_created->date( 'Y-m-d H:i:s' ) : '',
			'date_modified' => $date_modified ? $date_modified->date( 'Y-m-d H:i:s' ) : '',
			'orders_count'  => $customer->get_order_count(),
			'total_spent'   => wc_format_decimal( $customer->get_total_spent(), 2 ),
			'avatar_url'    => $customer->get_avatar_url(),
		];

		// Add billing information.
		$customer_data['billing'] = [
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

		// Add shipping information.
		$customer_data['shipping'] = [
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

		// Get last order information.
		$last_order = $customer->get_last_order();
		if ( $last_order ) {
			$order_date                  = $last_order->get_date_created();
			$customer_data['last_order'] = [
				'id'           => $last_order->get_id(),
				'order_number' => $last_order->get_order_number(),
				'status'       => $last_order->get_status(),
				'total'        => $last_order->get_total(),
				'date_created' => $order_date ? $order_date->date( 'Y-m-d H:i:s' ) : '',
			];
		} else {
			$customer_data['last_order'] = null;
		}

		// Add formatted country names.
		if ( ! empty( $customer_data['billing']['country'] ) ) {
			$countries                                = WC()->countries->get_countries();
			$customer_data['billing']['country_name'] = isset( $countries[ $customer_data['billing']['country'] ] ) 
				? $countries[ $customer_data['billing']['country'] ] 
				: $customer_data['billing']['country'];
		}

		if ( ! empty( $customer_data['shipping']['country'] ) ) {
			$countries                                 = WC()->countries->get_countries();
			$customer_data['shipping']['country_name'] = isset( $countries[ $customer_data['shipping']['country'] ] ) 
				? $countries[ $customer_data['shipping']['country'] ] 
				: $customer_data['shipping']['country'];
		}

		// Add formatted state names.
		if ( ! empty( $customer_data['billing']['state'] ) && ! empty( $customer_data['billing']['country'] ) ) {
			$states = WC()->countries->get_states( $customer_data['billing']['country'] );
			if ( ! empty( $states ) && isset( $states[ $customer_data['billing']['state'] ] ) ) {
				$customer_data['billing']['state_name'] = $states[ $customer_data['billing']['state'] ];
			}
		}

		if ( ! empty( $customer_data['shipping']['state'] ) && ! empty( $customer_data['shipping']['country'] ) ) {
			$states = WC()->countries->get_states( $customer_data['shipping']['country'] );
			if ( ! empty( $states ) && isset( $states[ $customer_data['shipping']['state'] ] ) ) {
				$customer_data['shipping']['state_name'] = $states[ $customer_data['shipping']['state'] ];
			}
		}

		// Calculate customer lifetime value stats.
		$customer_data['stats'] = [
			'orders_count'        => $customer->get_order_count(),
			'total_spent'         => wc_format_decimal( $customer->get_total_spent(), 2 ),
			'average_order_value' => $customer->get_order_count() > 0 
				? wc_format_decimal( $customer->get_total_spent() / $customer->get_order_count(), 2 ) 
				: '0.00',
		];

		return $customer_data;
	}
}

GetAllCustomers::get_instance();
