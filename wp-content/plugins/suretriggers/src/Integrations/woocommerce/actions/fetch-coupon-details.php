<?php
/**
 * FetchCouponDetails.
 * php version 5.6
 *
 * @category FetchCouponDetails
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
use WC_Coupon;

/**
 * FetchCouponDetails
 *
 * @category FetchCouponDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class FetchCouponDetails extends AutomateAction {

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
	public $action = 'wc_fetch_coupon_details';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Fetch Coupon Details', 'suretriggers' ),
			'action'   => 'wc_fetch_coupon_details',
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
		$coupon_identifier = isset( $selected_options['coupon_identifier'] ) ? sanitize_text_field( $selected_options['coupon_identifier'] ) : '';
		
		if ( empty( $coupon_identifier ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon code or ID is required.', 'suretriggers' ), 
				
			];
		}

		$coupon = new WC_Coupon( $coupon_identifier );
		
		if ( ! $coupon->get_id() ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon not found.', 'suretriggers' ), 
				
			];
		}

		$expiry_date      = $coupon->get_date_expires();
		$expiry_timestamp = $expiry_date ? $expiry_date->getTimestamp() : null;

		return [
			'coupon_id'                   => $coupon->get_id(),
			'coupon_code'                 => $coupon->get_code(),
			'description'                 => $coupon->get_description(),
			'discount_type'               => $coupon->get_discount_type(),
			'amount'                      => $coupon->get_amount(),
			'status'                      => get_post_status( $coupon->get_id() ),
			'free_shipping'               => $coupon->get_free_shipping() ? 'yes' : 'no',
			'expiry_date'                 => $expiry_timestamp,
			'minimum_amount'              => $coupon->get_minimum_amount(),
			'maximum_amount'              => $coupon->get_maximum_amount(),
			'individual_use'              => $coupon->get_individual_use() ? 'yes' : 'no',
			'exclude_sale_items'          => $coupon->get_exclude_sale_items() ? 'yes' : 'no',
			'usage_limit'                 => $coupon->get_usage_limit(),
			'usage_limit_per_user'        => $coupon->get_usage_limit_per_user(),
			'limit_usage_to_x_items'      => $coupon->get_limit_usage_to_x_items(),
			'usage_count'                 => $coupon->get_usage_count(),
			'product_ids'                 => $coupon->get_product_ids(),
			'excluded_product_ids'        => $coupon->get_excluded_product_ids(),
			'product_categories'          => $coupon->get_product_categories(),
			'excluded_product_categories' => $coupon->get_excluded_product_categories(),
			'email_restrictions'          => $coupon->get_email_restrictions(),
		];
	}
}

FetchCouponDetails::get_instance();
