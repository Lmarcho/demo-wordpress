<?php
/**
 * CreateDiscountFree.
 * php version 5.6
 *
 * @category CreateDiscountFree
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\EDD\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * CreateDiscountFree
 *
 * @category CreateDiscountFree
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateDiscountFree extends AutomateAction {

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
	public $action = 'create_discount_edd_free';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Discount (Free)', 'suretriggers' ),
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
	 * @param array $selected_options selected_options.
	 * @return array|bool
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'edd_add_discount' ) || ! function_exists( 'edd_get_discount' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD plugin is not active.',
			];
		}        
		
		if ( empty( $selected_options['name'] ) || empty( $selected_options['code'] ) || empty( $selected_options['amount'] ) ) {
			throw new Exception( 'Missing required parameters (name, code, amount)' );
		}
		
		$args = [
			'name'              => sanitize_text_field( $selected_options['name'] ),
			'code'              => sanitize_text_field( $selected_options['code'] ),
			'amount'            => floatval( $selected_options['amount'] ),
			'amount_type'       => sanitize_text_field( $selected_options['type'] ), 
			'status'            => 'active', 
			'product_reqs'      => isset( $selected_options['products'] ) && is_array( $selected_options['products'] )
			? array_map(
				function( $product ) {
					return sanitize_text_field( $product['value'] ); 
				},
				$selected_options['products'] 
			)
			: [],

			'excluded_products' => isset( $selected_options['excluded_downloads'] ) && is_array( $selected_options['excluded_downloads'] )
							? array_map(
								function( $product ) {
									return sanitize_text_field( $product['value'] );
								},
								$selected_options['excluded_downloads'] 
							)
							: [],

			'scope'             => sanitize_text_field( $selected_options['scope'] ),
			'product_condition' => sanitize_text_field( $selected_options['product_condition'] ),
			'start_date'        => isset( $selected_options['start_date'] ) ? sanitize_text_field( $selected_options['start_date'] ) : '',
			'categories'        => isset( $selected_options['categories'] ) && is_array( $selected_options['categories'] )
			? array_map(
				function( $category ) {
					return intval( $category['value'] ); 
				},
				$selected_options['categories']
			)
			: [],
			'end_date'          => isset( $selected_options['expiration_date'] ) ? sanitize_text_field( $selected_options['expiration_date'] ) : '',
			'min_charge_amount' => isset( $selected_options['min_charge_amount'] ) ? floatval( $selected_options['min_charge_amount'] ) : 0,
			'max_uses'          => isset( $selected_options['max_uses'] ) ? intval( $selected_options['max_uses'] ) : 0,
			'once_per_customer' => isset( $selected_options['once_per_customer'] ) ? $selected_options['once_per_customer'] : 0,
		];
		
		$discount_id = edd_add_discount( $args );
		$discount    = edd_get_discount( $discount_id );

		if ( ! $discount_id ) {
			$error_message = 'Failed to create discount. Please check your parameters and try again.';
			return [
				'status'  => 'error',
				'message' => $error_message,
			];
		}
		
		$discount_data = [
			'id'                => $discount->get_id(),
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
		
		return [
			'discount_id' => $discount_id,
			'discount'    => $discount_data, 
		];
	}
	
		
}

CreateDiscountFree::get_instance();
