<?php
/**
 * ApplyCoupon.
 * php version 5.6
 *
 * @category ApplyCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use WC_Coupon;

/**
 * ApplyCoupon
 *
 * @category ApplyCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ApplyCoupon extends AutomateAction {

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
	public $action = 'wc_apply_coupon';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Apply Coupon to Cart', 'suretriggers' ),
			'action'   => 'wc_apply_coupon',
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
	 * @return void|array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		// Handle user identification from selected options.
		if ( ! empty( $selected_options['user_id'] ) ) {
			$user_id = intval( $selected_options['user_id'] );
		} elseif ( ! empty( $selected_options['customer_email'] ) ) {
			$user = get_user_by( 'email', sanitize_email( $selected_options['customer_email'] ) );
			if ( $user ) {
				$user_id = $user->ID;
			}
		}

		if ( ! function_exists( 'WC' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce not available', 'suretriggers' ),
			];
		}

		// Set the current user context for cart operations.
		wp_set_current_user( $user_id );
		
		// Initialize WooCommerce components properly.
		if ( ! WC()->session ) {
			WC()->session = new \WC_Session_Handler();
			WC()->session->init();
		}
		
		// Initialize customer.
		if ( ! WC()->customer ) {
			WC()->customer = new \WC_Customer( $user_id, true );
		}
		
		// Initialize cart for the user.
		if ( ! WC()->cart ) {
			WC()->cart = new \WC_Cart();
		}
		
		// Ensure cart is properly initialized.
		WC()->cart->get_cart();

		foreach ( $fields as $field ) {
			if ( array_key_exists( 'validationProps', $field ) && empty( $selected_options[ $field['name'] ] ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Required field is missing: ', 'suretriggers' ) . $field['name'],
				];
			}
		}

		$coupon_code = sanitize_text_field( $selected_options['coupon_code'] );

		if ( empty( $coupon_code ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon code is required', 'suretriggers' ),
			];
		}

		// Check if coupon exists and is valid.
		$coupon = new WC_Coupon( $coupon_code );
		if ( ! $coupon->is_valid() ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid coupon code', 'suretriggers' ),
			];
		}

		// Check if coupon is already applied.
		$applied_coupons = WC()->cart->get_applied_coupons();
		if ( in_array( $coupon_code, $applied_coupons, true ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon is already applied to cart', 'suretriggers' ),
			];
		}

		// Apply the coupon to cart.
		$result = WC()->cart->apply_coupon( $coupon_code );

		if ( $result ) {
			// Get cart totals after applying coupon.
			WC()->cart->calculate_totals();
			
			$cart_total      = WC()->cart->get_total();
			$cart_subtotal   = WC()->cart->get_subtotal();
			$discount_amount = WC()->cart->get_coupon_discount_amount( $coupon_code );
			
			return [
				'status'          => 'success',
				'message'         => __( 'Coupon applied successfully', 'suretriggers' ),
				'coupon_code'     => $coupon_code,
				'coupon_id'       => $coupon->get_id(),
				'discount_amount' => $discount_amount,
				'cart_total'      => $cart_total,
				'cart_subtotal'   => $cart_subtotal,
				'applied_coupons' => WC()->cart->get_applied_coupons(),
				'user_id'         => $user_id,
			];
		} else {
			// Get WooCommerce notices for error details.
			$notices       = wc_get_notices( 'error' );
			$error_message = ! empty( $notices ) ? $notices[0]['notice'] : __( 'Failed to apply coupon', 'suretriggers' );
			
			// Clear notices to prevent them showing elsewhere.
			wc_clear_notices();
			
			return [
				'status'  => 'error',
				'message' => $error_message,
			];
		}
	}
}

ApplyCoupon::get_instance();
