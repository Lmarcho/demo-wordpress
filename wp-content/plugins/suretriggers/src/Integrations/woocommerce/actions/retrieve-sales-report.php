<?php
/**
 * RetrieveSalesReport.
 * php version 5.6
 *
 * @category RetrieveSalesReport
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
use Automattic\WooCommerce\Admin\API\Reports\Revenue\Query;


/**
 * RetrieveSalesReport
 *
 * @category RetrieveSalesReport
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveSalesReport extends AutomateAction {

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
	public $action = 'wc_retrieve_sales_report';

	use SingletonLoader;

	/**
	 * Register an action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve Sales Report', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, '_action_listener' ],
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

		$start_date = isset( $selected_options['start_date'] ) ? $selected_options['start_date'] : gmdate( 'Y-m-d', strtotime( '-7 days' ) );
		$end_date   = isset( $selected_options['end_date'] ) ? $selected_options['end_date'] : gmdate( 'Y-m-d' );
		$interval   = isset( $selected_options['interval'] ) ? $selected_options['interval'] : 'week';

		$args = [
			'interval' => $interval,
			'after'    => $start_date . ' 00:00:00',
			'before'   => $end_date . ' 23:59:59',
		];

		if ( ! class_exists( 'Automattic\\WooCommerce\\Admin\\API\\Reports\\Revenue\\Query' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce Admin Reports API not available.', 'suretriggers' ), 
				
			];
		}
		
		$report  = new Query( $args );
		$results = $report->get_data();
	
		$totals  = is_object( $results ) && isset( $results->totals ) ? $results->totals : ( isset( $results['totals'] ) ? $results['totals'] : [] );
		$summary = (array) $totals;
		
		$response = [
			'orders_count'        => intval( $summary['orders_count'] ),
			'num_items_sold'      => intval( $summary['num_items_sold'] ),
			'gross_sales'         => floatval( $summary['gross_sales'] ),
			'total_sales'         => floatval( $summary['total_sales'] ),
			'net_revenue'         => floatval( $summary['net_revenue'] ),
			'coupons'             => floatval( $summary['coupons'] ),
			'coupons_count'       => intval( $summary['coupons_count'] ),
			'refunds'             => floatval( $summary['refunds'] ),
			'taxes'               => floatval( $summary['taxes'] ),
			'shipping'            => floatval( $summary['shipping'] ),
			'avg_items_per_order' => floatval( $summary['avg_items_per_order'] ),
			'avg_order_value'     => floatval( $summary['avg_order_value'] ),
			'total_customers'     => isset( $summary['total_customers'] ) ? intval( $summary['total_customers'] ) : 0,
			'intervals'           => [],
		];
		
		$intervals = is_object( $results ) && isset( $results->intervals ) ? $results->intervals : ( isset( $results['intervals'] ) ? $results['intervals'] : [] );
		
		if ( ! empty( $intervals ) ) {
			foreach ( $intervals as $interval ) {
				$interval_data = is_array( $interval ) ? $interval : (array) $interval;
				$subtotals     = isset( $interval_data['subtotals'] ) ? (array) $interval_data['subtotals'] : [];
				
				$response['intervals'][] = [
					'interval'       => $interval_data['interval'],
					'date_start'     => $interval_data['date_start'],
					'date_end'       => $interval_data['date_end'],
					'orders_count'   => isset( $subtotals['orders_count'] ) ? intval( $subtotals['orders_count'] ) : 0,
					'num_items_sold' => isset( $subtotals['num_items_sold'] ) ? intval( $subtotals['num_items_sold'] ) : 0,
					'total_sales'    => isset( $subtotals['total_sales'] ) ? floatval( $subtotals['total_sales'] ) : 0,
					'net_revenue'    => isset( $subtotals['net_revenue'] ) ? floatval( $subtotals['net_revenue'] ) : 0,
				];
			}
		}

		return $response;
	}
}

RetrieveSalesReport::get_instance();
