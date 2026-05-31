<?php
/**
 * RetrieveTopSellersReport.
 * php version 5.6
 *
 * @category RetrieveTopSellersReport
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
use Automattic\WooCommerce\Admin\API\Reports\Products\Query;

/**
 * RetrieveTopSellersReport
 *
 * @category RetrieveTopSellersReport
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveTopSellersReport extends AutomateAction {

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
	public $action = 'wc_retrieve_top_sellers_report';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve Top Sellers Report', 'suretriggers' ),
			'action'   => 'wc_retrieve_top_sellers_report',
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

		$date_after  = isset( $selected_options['date_after'] ) ? $selected_options['date_after'] : '';
		$date_before = isset( $selected_options['date_before'] ) ? $selected_options['date_before'] : '';

		$args = [
			'limit' => 50,
		];

		if ( ! empty( $date_after ) ) {
			$args['after'] = $date_after . ' 00:00:00';
		}
		
		if ( ! empty( $date_before ) ) {
			$args['before'] = $date_before . ' 23:59:59';
		}

		if ( ! class_exists( 'Automattic\\WooCommerce\\Admin\\API\\Reports\\Products\\Query' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce Admin Reports API not available.', 'suretriggers' ), 
				
			];
		}
		
		$report  = new Query( $args );
		$results = $report->get_data();

		$data = is_object( $results ) && isset( $results->data ) ? $results->data : ( isset( $results['data'] ) ? $results['data'] : [] );
		
		if ( empty( $data ) ) {
			return [];
		}

		$top_sellers = [];
		foreach ( $data as $product_data ) {
			if ( is_array( $product_data ) ) {
				$product_data = (object) $product_data;
			}
			
			$product = wc_get_product( $product_data->product_id );
			if ( $product ) {
				$top_sellers[] = [
					'title'        => $product->get_name(),
					'product_id'   => (int) $product_data->product_id,
					'quantity'     => (int) $product_data->items_sold,
					'net_revenue'  => (int) $product_data->net_revenue,
					'orders_count' => (int) $product_data->orders_count,
				];
			}
		}

		return $top_sellers;
	}
}

RetrieveTopSellersReport::get_instance();
