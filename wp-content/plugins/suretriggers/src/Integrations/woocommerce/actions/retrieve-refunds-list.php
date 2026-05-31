<?php
/**
 * RetrieveRefundsList.
 * php version 5.6
 *
 * @category RetrieveRefundsList
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
 * RetrieveRefundsList
 *
 * @category RetrieveRefundsList
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveRefundsList extends AutomateAction {

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
	public $action = 'wc_retrieve_refunds_list';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve a List of Refunds', 'suretriggers' ),
			'action'   => 'wc_retrieve_refunds_list',
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

		// Get refunds directly instead of getting all orders first.
		$refunds = wc_get_orders(
			[
				'type'   => 'shop_order_refund',
				'limit'  => -1,
				'return' => 'objects',
			]
		);
		
		if ( ! is_array( $refunds ) ) {
			$refunds = [];
		}
		
		$result = [];
		foreach ( $refunds as $refund ) {
			if ( ! $refund || ! is_a( $refund, 'WC_Order_Refund' ) ) {
				continue;
			}
			
			$date_created = $refund->get_date_created();
			$date_string  = $date_created ? $date_created->format( 'c' ) : '';
			
			$result[] = [
				'id'               => $refund->get_id(),
				'parent_id'        => $refund->get_parent_id(),
				'date_created'     => $date_string,
				'date_created_gmt' => $date_string,
				'amount'           => $refund->get_amount(),
				'reason'           => $refund->get_reason(),
				'refunded_by'      => $refund->get_refunded_by(),
				'refunded_payment' => false,
			];
		}

		return $result;
	}
}

RetrieveRefundsList::get_instance();
