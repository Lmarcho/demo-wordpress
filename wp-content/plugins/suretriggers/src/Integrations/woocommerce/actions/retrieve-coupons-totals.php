<?php
/**
 * RetrieveCouponsTotals.
 * php version 5.6
 *
 * @category RetrieveCouponsTotals
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
 * RetrieveCouponsTotals
 *
 * @category RetrieveCouponsTotals
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveCouponsTotals extends AutomateAction {

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
	public $action = 'wc_retrieve_coupons_totals';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve Coupons Totals', 'suretriggers' ),
			'action'   => 'wc_retrieve_coupons_totals',
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

		$coupons = get_posts(
			[
				'post_type'   => 'shop_coupon',
				'post_status' => 'publish',
				'numberposts' => -1,
			]
		);

		$coupon_types = [];

		foreach ( $coupons as $coupon_post ) {
			$coupon        = new \WC_Coupon( $coupon_post->ID );
			$discount_type = $coupon->get_discount_type();
			
			if ( ! isset( $coupon_types[ $discount_type ] ) ) {
				$coupon_types[ $discount_type ] = 0;
			}
			$coupon_types[ $discount_type ]++;
		}

		$type_names = [
			'percent'       => 'Percentage discount',
			'fixed_cart'    => 'Fixed cart discount',
			'fixed_product' => 'Fixed product discount',
		];

		$result = [];
		foreach ( $type_names as $slug => $name ) {
			$result[] = [
				'slug'  => $slug,
				'name'  => $name,
				'total' => isset( $coupon_types[ $slug ] ) ? $coupon_types[ $slug ] : 0,
			];
		}

		return $result;
	}
}

RetrieveCouponsTotals::get_instance();
