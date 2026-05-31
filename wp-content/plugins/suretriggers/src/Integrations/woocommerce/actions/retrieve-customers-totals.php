<?php
/**
 * RetrieveCustomersTotals.
 * php version 5.6
 *
 * @category RetrieveCustomersTotals
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WooCommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * RetrieveCustomersTotals
 *
 * @category RetrieveCustomersTotals
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveCustomersTotals extends AutomateAction {

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
	public $action = 'wc_retrieve_customers_totals';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve Customers Totals', 'suretriggers' ),
			'action'   => 'wc_retrieve_customers_totals',
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
	 * @return array|null
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce is not installed or activated.', 'suretriggers' ), 
				
			];
		}

		global $wpdb;

		$customer_table    = $wpdb->prefix . 'wc_customer_lookup';
		$order_stats_table = $wpdb->prefix . 'wc_order_stats';

		// Validate tables exist.
		$tables_exist = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $customer_table ) ) === $customer_table
			&& $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $order_stats_table ) ) === $order_stats_table;

		if ( ! $tables_exist ) {
			return [
				'status'  => 'error',
				'message' => __( 'Required WooCommerce tables not found.', 'suretriggers' ), 
				
			];
		}

		$order_stats = $wpdb->prefix . 'wc_order_stats';

		// Paying customers: at least one completed order.
		$paying = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT customer_id) FROM {$wpdb->prefix}wc_order_stats WHERE status = %s",
				'wc-completed'
			)
		);

		// Non-paying customers: have orders but none completed.
		$non_paying = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT os.customer_id)
		FROM {$wpdb->prefix}wc_order_stats os
		LEFT JOIN (
			SELECT DISTINCT customer_id
			FROM {$wpdb->prefix}wc_order_stats
			WHERE status = %s
		) completed_customers ON os.customer_id = completed_customers.customer_id
		WHERE completed_customers.customer_id IS NULL",
				'wc-completed'
			)
		);

		if ( 0 === $paying && 0 === $non_paying ) {
			return [
				'status'  => 'error',
				'message' => __( 'No customers found.', 'suretriggers' ), 
				
			];
		}

		return [
			[
				'slug'  => 'paying',
				'name'  => 'Paying Customers',
				'total' => $paying,
			],
			[
				'slug'  => 'non_paying',
				'name'  => 'Non-paying Customers',
				'total' => $non_paying,
			],
		];
	
	}
}

RetrieveCustomersTotals::get_instance();
