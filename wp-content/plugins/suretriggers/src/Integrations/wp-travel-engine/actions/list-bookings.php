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
class ListBookings extends AutomateAction {

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
	public $action = 'wte_list_bookings';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'       => __( 'List Bookings', 'suretriggers' ),
			'description' => __( 'Get a list of bookings with optional filtering', 'suretriggers' ),
			'action'      => $this->action,
			'function'    => [ $this, '_action_listener' ],
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
		if ( ! function_exists( 'wp_travel_engine_get_booking_status' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WP Travel Engine plugin is not active.', 'suretriggers' ), 
				
			];
		}

		$limit = ! empty( $selected_options['limit'] ) ? intval( $selected_options['limit'] ) : 20;

		$args = [
			'post_type'      => 'booking',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'meta_query'     => [],
		];

		$bookings_query = new \WP_Query( $args );
		$bookings       = [];

		if ( $bookings_query->have_posts() ) {
			while ( $bookings_query->have_posts() ) {
				$bookings_query->the_post();
				$booking_id = get_the_ID();

				if ( ! $booking_id ) {
					continue;
				}

				$booking_status = get_post_meta( $booking_id, 'wp_travel_engine_booking_status', true );

				$cart_info_raw = get_post_meta( $booking_id, 'cart_info', true );
				$traveler_raw  = get_post_meta( $booking_id, 'wptravelengine_travelers_details', true );

				$cart_info     = is_string( $cart_info_raw ) ? json_decode( wp_unslash( $cart_info_raw ), true ) : $cart_info_raw;
				$traveler_data = is_string( $traveler_raw ) ? json_decode( wp_unslash( $traveler_raw ), true ) : $traveler_raw;

				if ( ! is_array( $cart_info ) ) {
					$cart_info = [];
				}
				if ( ! is_array( $traveler_data ) ) {
					$traveler_data = [];
				}

				$trip_id = 0;
				if ( isset( $cart_info['items'][0]['trip_id'] ) ) {
					$trip_id = absint( $cart_info['items'][0]['trip_id'] );
				}

				$trip = $trip_id ? get_post( $trip_id ) : null;

				$fname = '';
				$lname = '';
				$email = '';
				if ( isset( $traveler_data[0] ) && is_array( $traveler_data[0] ) ) {
					$fname = isset( $traveler_data[0]['fname'] ) ? sanitize_text_field( $traveler_data[0]['fname'] ) : '';
					$lname = isset( $traveler_data[0]['lname'] ) ? sanitize_text_field( $traveler_data[0]['lname'] ) : '';
					$email = isset( $traveler_data[0]['email'] ) ? sanitize_email( $traveler_data[0]['email'] ) : '';
				}

				$total_cost     = isset( $cart_info['total'] ) ? floatval( $cart_info['total'] ) : 0;
				$payment_status = isset( $cart_info['items'][0]['payment_status'] ) ? sanitize_text_field( $cart_info['items'][0]['payment_status'] ) : '';

				if ( ! empty( $selected_options['trip_id'] ) && absint( $selected_options['trip_id'] ) !== $trip_id ) {
					continue;
				}

				$bookings[] = [
					'booking_id'     => $booking_id,
					'booking_date'   => get_the_date( 'Y-m-d H:i:s' ),
					'trip_id'        => $trip_id,
					'trip_name'      => $trip ? $trip->post_title : '',
					'customer_name'  => trim( $fname . ' ' . $lname ),
					'customer_email' => $email,
					'total_cost'     => $total_cost,
					'booking_status' => $booking_status,
					'payment_status' => $payment_status,
				];
			}
			wp_reset_postdata();
		}

		return [
			'status' => 'success',
			'data'   => [
				'bookings' => $bookings,
				'count'    => count( $bookings ),
			],
		];
	}
}

ListBookings::get_instance();
