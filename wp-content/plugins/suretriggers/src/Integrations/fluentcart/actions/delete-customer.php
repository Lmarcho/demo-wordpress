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

namespace SureTriggers\Integrations\FluentCart\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCart\App\Models\Customer;

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
	public $action = 'fluentcart_delete_customer';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
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
		if ( ! class_exists( '\FluentCart\App\Models\Customer' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCart is not installed or activated.', 'suretriggers' ),
			];
		}

		$customer_id    = isset( $selected_options['customer_id'] ) ? $selected_options['customer_id'] : '';
		$customer_email = isset( $selected_options['customer_email'] ) ? $selected_options['customer_email'] : '';
		
		if ( empty( $customer_id ) && empty( $customer_email ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Customer ID or email is required.', 'suretriggers' ),
			];
		}

		try {
			$customer = null;

			// Find customer by ID first, then by email.
			if ( ! empty( $customer_id ) ) {
				$customer = Customer::find( $customer_id );
			} elseif ( ! empty( $customer_email ) && is_email( $customer_email ) ) {
				$customer = Customer::where( 'email', $customer_email )->first();
			}

			if ( ! $customer ) {
				return [
					'status'  => 'error',
					'message' => __( 'Customer not found.', 'suretriggers' ),
				];
			}

			// Store customer data before deletion for context.
			$deleted_customer_data = [
				'customer_id'    => $customer->id,
				'email'          => $customer->email,
				'first_name'     => $customer->first_name,
				'last_name'      => $customer->last_name,
				'full_name'      => $customer->first_name . ' ' . $customer->last_name,
				'status'         => $customer->status,
				'purchase_count' => $customer->purchase_count,
				'purchase_value' => $customer->purchase_value,
				'ltv'            => $customer->ltv,
				'user_id'        => $customer->user_id,
				'created_at'     => $customer->created_at,
			];


			// Fire before delete hook.
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- FluentCart uses forward slashes in hook names
			do_action( 'fluent_cart/before_customer_delete', $customer );

			// Delete the customer.
			$deleted = $customer->delete();

			if ( ! $deleted ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to delete customer.', 'suretriggers' ),
				];
			}

			// Fire after delete hook.
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- FluentCart uses forward slashes in hook names
			do_action( 'fluent_cart/customer_deleted', $deleted_customer_data );

			$context = array_merge(
				$deleted_customer_data,
				[
					'deleted_successfully' => true,
					'deletion_timestamp'   => current_time( 'mysql' ),
				]
			);

			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

DeleteCustomer::get_instance();
