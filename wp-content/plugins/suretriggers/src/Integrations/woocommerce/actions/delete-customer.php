<?php
/**
 * DeleteCustomer.
 * php version 5.6
 *
 * @category DeleteCustomer
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
 * DeleteCustomer
 *
 * @category DeleteCustomer
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteCustomer extends AutomateAction {

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
	public $action = 'wc_delete_customer';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Customer', 'suretriggers' ),
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

		$email = isset( $selected_options['email'] ) ? trim( $selected_options['email'] ) : '';

		if ( empty( $email ) ) {
			return [
				'success' => false,
				'message' => __( 'Customer email is required.', 'suretriggers' ),
			];
		}

		$customer_user = get_user_by( 'email', $email );

		if ( ! $customer_user ) {
			return [
				'success' => false,
				'message' => sprintf( __( 'Customer not found with email: %s', 'suretriggers' ), $email ),
			];
		}

		// Safety check - prevent deletion of administrators.
		if ( user_can( $customer_user, 'manage_options' ) ) {
			return [
				'success' => false,
				'message' => __( 'Cannot delete administrator accounts for security reasons.', 'suretriggers' ),
			];
		}

		// Get customer data before deletion for response.
		$customer      = new \WC_Customer( $customer_user->ID );
		$customer_data = [
			'id'           => $customer->get_id(),
			'username'     => $customer->get_username(),
			'email'        => $customer->get_email(),
			'first_name'   => $customer->get_first_name(),
			'last_name'    => $customer->get_last_name(),
			'orders_count' => $customer->get_order_count(),
			'total_spent'  => wc_format_decimal( $customer->get_total_spent(), 2 ),
		];

		// Handle orders - keep as guest orders by default.
		$orders_handled = $this->handle_customer_orders( $customer_user->ID, 'keep' );

		// Delete the customer.
		$deletion_result = wp_delete_user( $customer_user->ID );

		if ( ! $deletion_result ) {
			return [
				'success' => false,
				'message' => __( 'Failed to delete customer account.', 'suretriggers' ),
			];
		}

		$response_data = [
			'success'          => true,
			'deleted_customer' => $customer_data,
			'orders_handled'   => $orders_handled,
			'message'          => sprintf( __( 'Customer "%s" has been successfully deleted.', 'suretriggers' ), $customer_data['email'] ),
		];

		return $response_data;
	}

	/**
	 * Handle customer orders based on the selected option.
	 *
	 * @param int    $customer_id   Customer ID.
	 * @param string $handle_orders How to handle orders.
	 * @return array
	 */
	private function handle_customer_orders( $customer_id, $handle_orders ) {
		// Get customer orders.
		$orders = wc_get_orders(
			[
				'customer' => $customer_id,
				'limit'    => -1,
				'status'   => 'any',
			] 
		);

		if ( ! is_array( $orders ) ) {
			$orders = [];
		}

		$orders_count = count( $orders );

		switch ( $handle_orders ) {
			case 'keep':
				// Keep orders but remove customer association.
				if ( is_array( $orders ) ) {
					foreach ( $orders as $order ) {
						// Set customer ID to 0 (guest order).
						$order->set_customer_id( 0 );
						$order->save();
					}
				}
				return [
					'action'       => 'kept_as_guest_orders',
					'orders_count' => $orders_count,
					'description'  => sprintf( __( '%d orders converted to guest orders.', 'suretriggers' ), $orders_count ),
				];

			case 'delete':
				// Delete all customer orders.
				$deleted_count = 0;
				if ( is_array( $orders ) ) {
					foreach ( $orders as $order ) {
						if ( wp_delete_post( $order->get_id(), true ) ) {
							$deleted_count++;
						}
					}
				}
				return [
					'action'       => 'deleted_orders',
					'orders_count' => $deleted_count,
					'total_orders' => $orders_count,
					'description'  => sprintf( __( '%1$d out of %2$d orders deleted.', 'suretriggers' ), $deleted_count, $orders_count ),
				];

			case 'reassign':
				// Reassign orders to a default user (admin or shop manager).
				$reassign_user = $this->get_reassign_user();
				if ( ! $reassign_user || ! is_a( $reassign_user, 'WP_User' ) ) {
					return [ 'error' => __( 'No suitable user found to reassign orders to.', 'suretriggers' ) ];
				}

				$reassigned_count = 0;
				if ( is_array( $orders ) ) {
					foreach ( $orders as $order ) {
						$order->set_customer_id( $reassign_user->ID );
						$order->add_order_note( sprintf( __( 'Order reassigned from deleted customer to %s', 'suretriggers' ), $reassign_user->display_name ) );
						$order->save();
						$reassigned_count++;
					}
				}

				return [
					'action'        => 'reassigned_orders',
					'orders_count'  => $reassigned_count,
					'reassigned_to' => [
						'id'    => $reassign_user->ID,
						'name'  => $reassign_user->display_name,
						'email' => $reassign_user->user_email,
					],
					'description'   => sprintf( __( '%1$d orders reassigned to %2$s.', 'suretriggers' ), $reassigned_count, $reassign_user->display_name ),
				];

			default:
				return [ 'error' => __( 'Invalid order handling method.', 'suretriggers' ) ];
		}
	}

	/**
	 * Get a suitable user to reassign orders to.
	 *
	 * @return \WP_User|null
	 */
	private function get_reassign_user() {
		// First try to find a shop manager.
		$shop_managers = get_users(
			[
				'role'   => 'shop_manager',
				'number' => 1,
			] 
		);

		if ( ! empty( $shop_managers ) ) {
			return $shop_managers[0];
		}

		// Fallback to administrator.
		$admins = get_users(
			[
				'role'   => 'administrator',
				'number' => 1,
			] 
		);

		if ( ! empty( $admins ) ) {
			return $admins[0];
		}

		return null;
	}
}

DeleteCustomer::get_instance();
