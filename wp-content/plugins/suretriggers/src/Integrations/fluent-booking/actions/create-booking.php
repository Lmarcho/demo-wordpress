<?php
/**
 * CreateBooking.
 * php version 5.6
 *
 * @category CreateBooking
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */

namespace SureTriggers\Integrations\FluentBooking\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * CreateBooking
 *
 * @category CreateBooking
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */
class CreateBooking extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentBooking';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluent_booking_create_booking';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Booking', 'suretriggers' ),
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
	 *
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! defined( 'FLUENT_BOOKING_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBooking is not installed or activated.', 'suretriggers' ),
			];
		}

		if ( ! class_exists( 'FluentBooking\App\Services\BookingService' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBooking BookingService class not found.', 'suretriggers' ),
			];
		}

		if ( ! class_exists( 'FluentBooking\App\Models\CalendarSlot' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBooking CalendarSlot model not found.', 'suretriggers' ),
			];
		}

		if ( ! class_exists( 'FluentBooking\App\Services\DateTimeHelper' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBooking DateTimeHelper service not found.', 'suretriggers' ),
			];
		}

		if ( ! class_exists( 'FluentBooking\App\Services\Helper' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBooking Helper service not found.', 'suretriggers' ),
			];
		}

		$event_id             = isset( $selected_options['event_id'] ) ? intval( $selected_options['event_id'] ) : 0;
		$name                 = isset( $selected_options['name'] ) ? sanitize_text_field( $selected_options['name'] ) : '';
		$email                = isset( $selected_options['email'] ) ? sanitize_email( $selected_options['email'] ) : '';
		$start_time           = isset( $selected_options['start_time'] ) ? sanitize_text_field( $selected_options['start_time'] ) : '';
		$timezone             = isset( $selected_options['timezone'] ) ? sanitize_text_field( $selected_options['timezone'] ) : 'UTC';
		$phone                = isset( $selected_options['phone'] ) ? sanitize_text_field( $selected_options['phone'] ) : '';
		$message              = isset( $selected_options['message'] ) ? sanitize_textarea_field( $selected_options['message'] ) : '';
		$status               = isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : 'scheduled';
		$location_type        = isset( $selected_options['location_type'] ) ? sanitize_text_field( $selected_options['location_type'] ) : '';
		$location_description = isset( $selected_options['location_description'] ) ? sanitize_text_field( $selected_options['location_description'] ) : '';
		$host_user_id         = isset( $selected_options['host_user_id'] ) ? intval( $selected_options['host_user_id'] ) : null;

		if ( empty( $event_id ) || empty( $name ) || empty( $email ) || empty( $start_time ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Event ID, Name, Email, and Start Time are required.', 'suretriggers' ),
			];
		}

		if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid email address provided.', 'suretriggers' ),
			];
		}

		try {
			$calendar_slot = \FluentBooking\App\Models\CalendarSlot::find( $event_id );
			
			if ( ! $calendar_slot ) {
				return [
					'status'  => 'error',
					'message' => sprintf( __( 'Calendar event not found with ID: %d', 'suretriggers' ), $event_id ),
				];
			}

			if ( 'active' !== $calendar_slot->status ) {
				return [
					'status'  => 'error',
					'message' => __( 'This calendar event is not accepting bookings.', 'suretriggers' ),
				];
			}

			$start_time_utc = \FluentBooking\App\Services\DateTimeHelper::convertToUtc( $start_time, $timezone );
			$duration       = $calendar_slot->getDuration();
			$end_time_utc   = gmdate( 'Y-m-d H:i:s', strtotime( $start_time_utc ) + ( $duration * 60 ) );

			$booking_data = [
				'event_id'         => $event_id,
				'calendar_id'      => $calendar_slot->calendar_id,
				'host_user_id'     => $host_user_id ? $host_user_id : $calendar_slot->user_id,
				'name'             => $name,
				'email'            => $email,
				'phone'            => $phone,
				'message'          => $message,
				'person_time_zone' => $timezone,
				'start_time'       => $start_time_utc,
				'end_time'         => $end_time_utc,
				'slot_minutes'     => $duration,
				'status'           => $status,
				'source'           => 'automation',
				'event_type'       => $calendar_slot->event_type,
				'ip_address'       => \FluentBooking\App\Services\Helper::getIp(),
			];

			if ( $location_type ) {
				$location_details = [ 'type' => $location_type ];
				
				if ( $location_description ) {
					$location_details['description'] = $location_description;
				}
				
				$booking_data['location_details'] = $location_details;
			}

			$booking = \FluentBooking\App\Services\BookingService::createBooking( $booking_data, $calendar_slot );

			if ( is_wp_error( $booking ) ) {
				return [
					'status'  => 'error',
					'message' => $booking->get_error_message(),
				];
			}

			return [
				'success'    => true,
				'message'    => 'Booking created successfully',
				'booking_id' => $booking->id,
				'booking'    => $booking->toArray(),
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create booking: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

CreateBooking::get_instance();
