<?php
/**
 * FetchDiscountDetails.
 * php version 5.6
 *
 * @category FetchDiscountDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\EDD\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * FetchDiscountDetails
 *
 * @category FetchDiscountDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class FetchDiscountDetails extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'EDD';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'edd_fetch_discount_details';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Fetch Discount Details', 'suretriggers' ),
			'action'   => 'edd_fetch_discount_details',
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
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'edd_get_discount_by' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'EDD plugin is not active.', 'suretriggers' ), 
				
			];
		}

		$discount_identifier = isset( $selected_options['discount_identifier'] ) ? sanitize_text_field( $selected_options['discount_identifier'] ) : '';
		
		if ( empty( $discount_identifier ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Discount code or ID is required.', 'suretriggers' ), 
				
			];
		}

		$discount = edd_get_discount_by( 'code', $discount_identifier );
		if ( ! $discount && is_numeric( $discount_identifier ) ) {
			$discount = edd_get_discount_by( 'id', $discount_identifier );
		}
		
		if ( ! $discount ) {
			return [
				'status'  => 'error',
				'message' => __( 'Discount not found.', 'suretriggers' ), 
				
			];
		}

		return [
			'discount_id'       => $discount->get_id(),
			'name'              => $discount->get_name(),
			'code'              => $discount->get_code(),
			'status'            => $discount->get_status(),
			'amount_type'       => $discount->get_amount_type(),
			'amount'            => $discount->get_amount(),
			'product_reqs'      => $discount->get_product_reqs(),
			'scope'             => $discount->get_scope(),
			'excluded_products' => $discount->get_excluded_products(),
			'product_condition' => $discount->get_product_condition(),
			'start_date'        => $discount->get_start_date(),
			'end_date'          => $discount->get_end_date(),
			'use_count'         => $discount->get_use_count(),
			'max_uses'          => $discount->get_max_uses(),
			'min_charge_amount' => $discount->get_min_charge_amount(),
			'once_per_customer' => $discount->get_once_per_customer(),
			'categories'        => $discount->get_categories(),
			'term_condition'    => $discount->get_term_condition(),
		];
	}
}

FetchDiscountDetails::get_instance();
