<?php
/**
 * AddEmailsToCoupon.
 * php version 5.6
 *
 * @category AddEmailsToCoupon
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
 * AddEmailsToCoupon
 *
 * @category AddEmailsToCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddEmailsToCoupon extends AutomateAction {

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
	public $action = 'wc_add_emails_to_coupon';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Email(s) to Coupon', 'suretriggers' ),
			'action'   => 'wc_add_emails_to_coupon',
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
		if ( ! function_exists( 'WC' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce not available', 'suretriggers' ),
			];
		}

		$coupon_code = sanitize_text_field( $selected_options['coupon_code'] );
		$emails      = $selected_options['emails'];

		if ( empty( $coupon_code ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon code is required', 'suretriggers' ),
			];
		}

		if ( empty( $emails ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'At least one email address is required', 'suretriggers' ),
			];
		}

		// Get the coupon.
		$coupon = new WC_Coupon( $coupon_code );
		if ( ! $coupon->get_id() ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon not found', 'suretriggers' ),
			];
		}

		// Process emails - handle both string and array formats.
		$email_list = [];
		if ( is_string( $emails ) ) {
			// Split by comma and clean up.
			$email_array = array_map( 'trim', explode( ',', $emails ) );
		} elseif ( is_array( $emails ) ) {
			$email_array = $emails;
		} else {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid email format', 'suretriggers' ),
			];
		}

		// Validate and sanitize emails.
		foreach ( $email_array as $email ) {
			$sanitized_email = sanitize_email( $email );
			if ( is_email( $sanitized_email ) ) {
				$email_list[] = $sanitized_email;
			}
		}

		if ( empty( $email_list ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'No valid email addresses provided', 'suretriggers' ),
			];
		}

		// Get existing emails from coupon.
		$existing_emails = $coupon->get_email_restrictions();
		if ( ! is_array( $existing_emails ) ) {
			$existing_emails = [];
		}

		// Merge with new emails and remove duplicates.
		$updated_emails = array_unique( array_merge( $existing_emails, $email_list ) );

		// Update the coupon with new email restrictions.
		$coupon->set_email_restrictions( $updated_emails );
		$coupon->save();

		// Get the added emails (new ones only).
		$added_emails = array_diff( $email_list, $existing_emails );

		return [
			'status'           => 'success',
			'message'          => __( 'Emails added to coupon successfully', 'suretriggers' ),
			'coupon_code'      => $coupon_code,
			'coupon_id'        => $coupon->get_id(),
			'added_emails'     => $added_emails,
			'total_emails'     => $updated_emails,
			'emails_count'     => count( $updated_emails ),
			'new_emails_count' => count( $added_emails ),
		];
	}
}

AddEmailsToCoupon::get_instance();
