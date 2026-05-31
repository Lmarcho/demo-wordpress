<?php
/**
 * RetrieveBooking.
 * php version 5.6
 *
 * @category RetrieveBooking
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPTravelEngine\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * RetrieveBooking
 *
 * @category RetrieveBooking
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveBooking extends AutomateAction {

	use SingletonLoader;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WPTravelEngine';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wte_retrieve_booking';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve a Booking', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, '_action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id User ID.
	 * @param int   $automation_id Automation ID.
	 * @param array $fields Fields.
	 * @param array $selected_options Selected options.
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( empty( $selected_options['booking_id'] ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Booking ID is required.', 'suretriggers' ), 
				
			];
		}

		$booking_id = absint( $selected_options['booking_id'] );
		$booking    = get_post( $booking_id );

		if ( ! $booking || 'booking' !== $booking->post_type ) {
			return [
				'status'  => 'error',
				'message' => __( 'Booking not found.', 'suretriggers' ), 
				
			];
		}

		$cart_info_raw  = get_post_meta( $booking_id, 'cart_info', true );
		$traveler_raw   = get_post_meta( $booking_id, 'wptravelengine_travelers_details', true );
		$booking_status = get_post_meta( $booking_id, 'wp_travel_engine_booking_status', true );
		$paid_amount    = get_post_meta( $booking_id, 'paid_amount', true );
		$due_amount     = get_post_meta( $booking_id, 'due_amount', true );

		$trip_id = 0;
		if (
			is_array( $cart_info_raw ) &&
			isset( $cart_info_raw['items'][0] ) &&
			is_array( $cart_info_raw['items'][0] ) &&
			isset( $cart_info_raw['items'][0]['trip_id'] )
		) {
			$trip_id = absint( $cart_info_raw['items'][0]['trip_id'] );
		}
		$trip = get_post( $trip_id );

		$traveler_details = [];
		if (
			is_array( $traveler_raw ) &&
			isset( $traveler_raw[0] ) &&
			is_array( $traveler_raw[0] )
		) {
			$traveler_details = [
				'first_name' => isset( $traveler_raw[0]['fname'] ) ? sanitize_text_field( (string) $traveler_raw[0]['fname'] ) : '',
				'last_name'  => isset( $traveler_raw[0]['lname'] ) ? sanitize_text_field( (string) $traveler_raw[0]['lname'] ) : '',
				'email'      => isset( $traveler_raw[0]['email'] ) ? sanitize_text_field( (string) $traveler_raw[0]['email'] ) : '',
				'phone'      => isset( $traveler_raw[0]['phone'] ) ? sanitize_text_field( (string) $traveler_raw[0]['phone'] ) : '',
				'country'    => isset( $traveler_raw[0]['country'] ) ? sanitize_text_field( (string) $traveler_raw[0]['country'] ) : '',
			];
		}

		$trip_dates = [
			'start_date' => (
				is_array( $cart_info_raw ) &&
				isset( $cart_info_raw['items'][0]['trip_date'] )
			) ? sanitize_text_field( (string) $cart_info_raw['items'][0]['trip_date'] ) : '',
			'end_date'   => (
				is_array( $cart_info_raw ) &&
				isset( $cart_info_raw['items'][0]['end_date'] )
			) ? sanitize_text_field( (string) $cart_info_raw['items'][0]['end_date'] ) : '',
		];

		$travelers_count = (
			is_array( $cart_info_raw ) &&
			isset( $cart_info_raw['items'][0]['travelers_count'] )
		) ? absint( $cart_info_raw['items'][0]['travelers_count'] ) : 0;

		$total_cost = ( is_array( $cart_info_raw ) && isset( $cart_info_raw['total'] ) ) ? floatval( $cart_info_raw['total'] ) : 0;

		$payment_details = [
			'total_cost'  => $total_cost,
			'paid_amount' => $paid_amount,
			'due_amount'  => $due_amount,
		];

		$booking_details = [
			'booking_id'       => $booking_id,
			'booking_date'     => get_the_date( 'Y-m-d H:i:s', $booking_id ),
			'booking_status'   => $booking_status,
			'trip_id'          => $trip_id,
			'trip_name'        => $trip ? $trip->post_title : '',
			'trip_dates'       => $trip_dates,
			'travelers_count'  => $travelers_count,
			'traveler_details' => $traveler_details,
			'payment_details'  => $payment_details,
		];

		return [
			'status' => 'success',
			'data'   => $booking_details,
		];
	}
}

RetrieveBooking::get_instance();
