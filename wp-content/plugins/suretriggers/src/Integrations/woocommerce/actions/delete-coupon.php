<?php
/**
 * WCDeleteCouponCode.
 * php version 5.6
 *
 * @category WCDeleteCouponCode
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * WCDeleteCouponCode
 *
 * @category WCDeleteCouponCode
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class WCDeleteCouponCode extends AutomateAction {

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
	public $action = 'wc_delete_coupon_code';

	use SingletonLoader;

	/**
	 * Register the action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete a coupon code.', 'suretriggers' ),
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
	 * @throws Exception Exception.
	 *
	 * @return void|array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( empty( $selected_options['coupon_code'] ) ) {
			return [
				'success' => false,
				'message' => 'Coupon code is required.',
			];
		}
		
		$coupon_code = sanitize_text_field( $selected_options['coupon_code'] );
		
		$coupon_query = new \WP_Query(
			[
				'post_type'      => 'shop_coupon',
				'title'          => $coupon_code,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			]
		);
		
		$coupon_id = ! empty( $coupon_query->posts ) ? $coupon_query->posts[0] : 0;
		
		if ( ! $coupon_id ) {
			return [
				'success' => false,
				'message' => 'Coupon not found.',
			];
		}
		
		$coupon = get_post( $coupon_id );
		
		if ( ! ( $coupon instanceof \WP_Post ) ) {
			return [
				'success' => false,
				'message' => 'Coupon not found.',
			];
		}
		
		$deleted = wp_delete_post( $coupon->ID, true );
		
		if ( ! $deleted ) {
			return [
				'success' => false,
				'message' => 'Failed to delete the coupon.',
			];
		}
		
		return [
			'coupon_code' => $coupon_code,
			'coupon_id'   => $coupon->ID,
			'status'      => 'deleted',
		];
	}
}

WCDeleteCouponCode::get_instance();
