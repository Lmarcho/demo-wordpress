<?php
/**
 * GetBookingDetails.
 * php version 5.6
 *
 * @category GetBookingDetails
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
 * GetBookingDetails
 *
 * @category GetBookingDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */
class GetBookingDetails extends AutomateAction {

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
	public $action = 'fluent_booking_get_booking_details';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Booking Details', 'suretriggers' ),
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

		if ( ! class_exists( 'FluentBooking\App\Models\Booking' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBooking Booking model not found.', 'suretriggers' ),
			];
		}

		$booking_id               = isset( $selected_options['booking_id'] ) ? intval( $selected_options['booking_id'] ) : 0;
		$booking_email            = isset( $selected_options['booking_email'] ) ? sanitize_email( $selected_options['booking_email'] ) : '';
		$booking_hash             = isset( $selected_options['booking_hash'] ) ? sanitize_text_field( $selected_options['booking_hash'] ) : '';
		$include_meta             = isset( $selected_options['include_meta'] ) ? (bool) $selected_options['include_meta'] : true;
		$include_custom_fields    = isset( $selected_options['include_custom_fields'] ) ? (bool) $selected_options['include_custom_fields'] : true;
		$include_calendar_details = isset( $selected_options['include_calendar_details'] ) ? (bool) $selected_options['include_calendar_details'] : true;

		if ( empty( $booking_id ) && empty( $booking_email ) && empty( $booking_hash ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'At least one booking identifier (ID, email, or hash) is required.', 'suretriggers' ),
			];
		}

		try {
			$booking = null;

			if ( $booking_id ) {
				$booking = \FluentBooking\App\Models\Booking::find( $booking_id );
			}
			
			if ( ! $booking && $booking_hash ) {
				$booking = \FluentBooking\App\Models\Booking::where( 'hash', $booking_hash )->first();
			}
			
			if ( ! $booking && $booking_email ) {
				$booking = \FluentBooking\App\Models\Booking::where( 'email', $booking_email )
					->orderBy( 'created_at', 'desc' )
					->first();
			}

			if ( ! $booking ) {
				return [
					'status'  => 'error',
					'message' => __( 'Booking not found with the provided identifiers.', 'suretriggers' ),
				];
			}

			$booking_data = $booking->toArray();

			if ( $booking->start_time ) {
				$booking_data['start_time_formatted'] = date_i18n( 'Y-m-d H:i:s', strtotime( $booking->start_time ) );
				$booking_data['start_date']           = date_i18n( 'Y-m-d', strtotime( $booking->start_time ) );
				$booking_data['start_time_only']      = date_i18n( 'H:i:s', strtotime( $booking->start_time ) );
			}

			if ( $booking->end_time ) {
				$booking_data['end_time_formatted'] = date_i18n( 'Y-m-d H:i:s', strtotime( $booking->end_time ) );
				$booking_data['end_date']           = date_i18n( 'Y-m-d', strtotime( $booking->end_time ) );
				$booking_data['end_time_only']      = date_i18n( 'H:i:s', strtotime( $booking->end_time ) );
			}

			$statuses                     = [
				'scheduled' => 'Scheduled',
				'pending'   => 'Pending',
				'cancelled' => 'Cancelled',
				'completed' => 'Completed',
				'rejected'  => 'Rejected',
			];
			$booking_data['status_label'] = isset( $statuses[ $booking->status ] ) ? $statuses[ $booking->status ] : $booking->status;

			if ( $include_custom_fields ) {
				try {
					$booking_data['custom_fields']     = $booking->getCustomFormData( true, false );
					$booking_data['custom_fields_raw'] = $booking->getCustomFormData( false, false );
				} catch ( Exception $e ) {
					$booking_data['custom_fields']     = [];
					$booking_data['custom_fields_raw'] = [];
				}
			}

			if ( $include_meta ) {
				$booking_data['additional_guests'] = $booking->getAdditionalGuests();
				
				$meta_fields = [ 'cancel_reason', 'reschedule_reason', 'payment_data', 'custom_location' ];
				foreach ( $meta_fields as $meta_field ) {
					$booking_data[ $meta_field ] = $booking->getMeta( $meta_field, null );
				}
			}

			if ( $include_calendar_details ) {
				try {
					$calendar_event = $booking->calendar_event;
					if ( $calendar_event ) {
						$booking_data['calendar_event'] = [
							'id'          => $calendar_event->id,
							'title'       => $calendar_event->title,
							'description' => $calendar_event->description,
							'duration'    => $calendar_event->duration,
							'event_type'  => $calendar_event->event_type,
							'status'      => $calendar_event->status,
						];

						$calendar = $booking->calendar;
						if ( $calendar ) {
							$booking_data['calendar'] = [
								'id'          => $calendar->id,
								'title'       => $calendar->title,
								'description' => $calendar->description,
								'type'        => $calendar->type,
								'status'      => $calendar->status,
							];
						}
					}
				} catch ( Exception $e ) {
					$booking_data['calendar_event'] = null;
					$booking_data['calendar']       = null;
				}
			}

			if ( $booking->host_user_id ) {
				$host_user = get_user_by( 'ID', $booking->host_user_id );
				if ( $host_user ) {
					$booking_data['host'] = [
						'id'           => $host_user->ID,
						'display_name' => $host_user->display_name,
						'email'        => $host_user->user_email,
						'first_name'   => get_user_meta( $host_user->ID, 'first_name', true ),
						'last_name'    => get_user_meta( $host_user->ID, 'last_name', true ),
					];
				}
			}

			return [
				'success'    => true,
				'message'    => 'Booking details retrieved successfully',
				'booking_id' => $booking->id,
				'booking'    => $booking_data,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to get booking details: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

GetBookingDetails::get_instance();
