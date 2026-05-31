<?php
/**
 * ListCustomers.
 * php version 5.6
 *
 * @category ListCustomers
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
 * ListCustomers
 *
 * @category ListCustomers
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListCustomers extends AutomateAction {

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
	public $action = 'fluentcart_list_customers';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Customers', 'suretriggers' ),
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

		$limit  = isset( $selected_options['limit'] ) ? $selected_options['limit'] : 50;
		$search = isset( $selected_options['search'] ) ? $selected_options['search'] : '';
		
		// Validate limit.
		$limit = min( max( intval( $limit ), 1 ), 500 ); // Between 1 and 500.

		try {
			// Build query.
			$query = Customer::query();

			if ( ! empty( $search ) ) {
				$query->where(
					function( $q ) use ( $search ) {
						$q->where( 'first_name', 'like', '%' . $search . '%' )
						->orWhere( 'last_name', 'like', '%' . $search . '%' )
						->orWhere( 'email', 'like', '%' . $search . '%' );
					}
				);
			}

			// Get total count before applying limit.
			$total_count = $query->count();

			// Apply limit.
			$query->limit( $limit );

			$customers = $query->get();

			// Format customer data.
			$customers_data = [];
			foreach ( $customers as $customer ) {
				$customer_data = [
					'customer_id'         => $customer->id,
					'email'               => $customer->email,
					'first_name'          => $customer->first_name,
					'last_name'           => $customer->last_name,
					'full_name'           => $customer->first_name . ' ' . $customer->last_name,
					'status'              => $customer->status,
					'country'             => $customer->country,
					'city'                => $customer->city,
					'state'               => $customer->state,
					'postcode'            => $customer->postcode,
					'user_id'             => $customer->user_id,
					'purchase_count'      => $customer->purchase_count,
					'purchase_value'      => $customer->purchase_value,
					'ltv'                 => $customer->ltv,
					'aov'                 => $customer->aov,
					'first_purchase_date' => $customer->first_purchase_date,
					'last_purchase_date'  => $customer->last_purchase_date,
					'created_at'          => $customer->created_at,
					'updated_at'          => $customer->updated_at,
				];


				$customers_data[] = $customer_data;
			}

			$context = [
				'customers'       => $customers_data,
				'total_count'     => $total_count,
				'returned_count'  => count( $customers_data ),
				'limit'           => $limit,
				'has_more'        => count( $customers_data ) >= $limit,
				'filters_applied' => [
					'search' => $search,
					'limit'  => $limit,
				],
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

ListCustomers::get_instance();
