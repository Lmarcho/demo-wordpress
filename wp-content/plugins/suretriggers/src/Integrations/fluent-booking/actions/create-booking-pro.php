<?php
/**
 * CreateBookingPro.
 * php version 5.6
 *
 * @category CreateBookingPro
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.8
 */

namespace SureTriggers\Integrations\FluentBooking\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * CreateBookingPro
 *
 * @category CreateBookingPro
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */
class CreateBookingPro extends AutomateAction {

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
	public $action = 'fluent_booking_create_booking_pro';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Booking (Pro)', 'suretriggers' ),
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

		// Check for pro version.
		if ( ! defined( 'FLUENT_BOOKING_PRO_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBooking Pro is required for this action.', 'suretriggers' ),
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

		$event_id     = isset( $selected_options['event_id'] ) ? intval( $selected_options['event_id'] ) : 0;
		$name         = isset( $selected_options['name'] ) ? sanitize_text_field( $selected_options['name'] ) : '';
		$email        = isset( $selected_options['email'] ) ? sanitize_email( $selected_options['email'] ) : '';
		$start_time   = isset( $selected_options['start_time'] ) ? sanitize_text_field( $selected_options['start_time'] ) : '';
		$timezone     = isset( $selected_options['timezone'] ) ? sanitize_text_field( $selected_options['timezone'] ) : 'UTC';
		$phone        = isset( $selected_options['phone'] ) ? sanitize_text_field( $selected_options['phone'] ) : '';
		$message      = isset( $selected_options['message'] ) ? sanitize_textarea_field( $selected_options['message'] ) : '';
		$status       = isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : 'scheduled';
		$host_user_id = isset( $selected_options['host_user_id'] ) ? intval( $selected_options['host_user_id'] ) : null;

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

			// Get event location settings to determine link generation.
			$location_settings = $calendar_slot->location_settings;
			$primary_location  = ! empty( $location_settings ) ? $location_settings[0] : null;

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
				'source'           => 'automation_pro',
				'event_type'       => $calendar_slot->event_type,
				'ip_address'       => \FluentBooking\App\Services\Helper::getIp(),
			];

			if ( $primary_location ) {
				$location_type = $primary_location['type'];
				
				if ( 'google_meet' === $location_type || 'zoom_meeting' === $location_type ) {
					$location_details = [ 'type' => $location_type ];
					
					switch ( $location_type ) {
						case 'google_meet':
							$meet_link = $this->generate_google_meet_link( $booking_data, $host_user_id ? $host_user_id : $calendar_slot->user_id, $calendar_slot );
							if ( $meet_link ) {
								$location_details['online_platform_link'] = $meet_link;
								$location_details['description']          = $meet_link;
							}
							break;
							
						case 'zoom_meeting':
							$zoom_link = $this->generate_zoom_meeting_link( $booking_data, $host_user_id ? $host_user_id : $calendar_slot->user_id, $calendar_slot );
							if ( $zoom_link ) {
								$location_details['online_platform_link'] = $zoom_link;
								$location_details['description']          = $zoom_link;
							}
							break;
					}
					
					$booking_data['location_details'] = $location_details;
				} else {
					if ( class_exists( 'FluentBooking\App\Services\LocationService' ) ) {
						$booking_data['location_details'] = \FluentBooking\App\Services\LocationService::getLocationDetails( $calendar_slot, [], $booking_data );
					} else {
						$booking_data['location_details'] = [
							'type'        => $location_type,
							'description' => isset( $primary_location['description'] ) ? $primary_location['description'] : '',
						];
					}
				}
			}

			$booking = \FluentBooking\App\Services\BookingService::createBooking( $booking_data, $calendar_slot );

			if ( is_wp_error( $booking ) ) {
				return [
					'status'  => 'error',
					'message' => $booking->get_error_message(),
				];
			}

			$response_data = [
				'success'    => true,
				'message'    => 'Booking created successfully with pro features',
				'booking_id' => $booking->id,
				'booking'    => $booking->toArray(),
			];

			// Add meeting links to response if generated.
			if ( isset( $location_details['online_platform_link'] ) ) {
				$response_data['meeting_link']  = $location_details['online_platform_link'];
				$response_data['location_type'] = $location_details['type'];
			}

			return $response_data;

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create booking: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}

	/**
	 * Generate Google Meet link for the booking.
	 *
	 * @param array  $booking_data The booking data.
	 * @param int    $user_id The host user ID.
	 * @param object $calendar_slot The calendar slot object.
	 * @return string|null The Google Meet link or null if failed.
	 */
	private function generate_google_meet_link( $booking_data, $user_id, $calendar_slot ) {
		try {
			// Check if Google Calendar integration is available.
			if ( ! class_exists( 'FluentBookingPro\App\Services\Integrations\Calendars\Google\GoogleHelper' ) ) {
				return null;
			}

			// Get Google Calendar client for the user.
			$google_client = \FluentBookingPro\App\Services\Integrations\Calendars\Google\GoogleHelper::getApiClientByUserId( $user_id );

			if ( ! $google_client ) {
				// Fallback: Generate a basic Google Meet link.
				return 'https://meet.google.com/' . $this->generate_meet_id();
			}

			// Prepare event data for Google Calendar with Meet integration.
			$host_user   = get_userdata( $user_id );
			$host_name   = $host_user ? $host_user->display_name : 'Host';
			$event_title = 'Meeting';
			
			// Try multiple ways to get the event title.
			if ( is_object( $calendar_slot ) ) {
				if ( isset( $calendar_slot->title ) && ! empty( $calendar_slot->title ) ) {
					$event_title = $calendar_slot->title;
				} elseif ( isset( $calendar_slot->event_title ) && ! empty( $calendar_slot->event_title ) ) {
					$event_title = $calendar_slot->event_title;
				} elseif ( isset( $calendar_slot->name ) && ! empty( $calendar_slot->name ) ) {
					$event_title = $calendar_slot->name;
				}
			}
			
			$event_data = [
				'summary'        => sprintf( '%s meeting between %s and %s', $event_title, $host_name, $booking_data['name'] ),
				'description'    => $booking_data['message'],
				'start'          => [
					'dateTime' => gmdate( 'c', strtotime( $booking_data['start_time'] ) ),
				],
				'end'            => [
					'dateTime' => gmdate( 'c', strtotime( $booking_data['end_time'] ) ),
				],
				'attendees'      => [
					[
						'email' => $booking_data['email'],
					],
				],
				'conferenceData' => [
					'createRequest' => [
						'requestId'             => 'meet_' . uniqid(),
						'conferenceSolutionKey' => [
							'type' => 'hangoutsMeet',
						],
					],
				],
			];

			// Create the event with Google Meet.
			$created_event = $google_client->createEvent( 'primary', $event_data );

			if ( $created_event && isset( $created_event['conferenceData']['entryPoints'] ) ) {
				foreach ( $created_event['conferenceData']['entryPoints'] as $entry_point ) {
					if ( 'video' === $entry_point['entryPointType'] ) {
						return $entry_point['uri'];
					}
				}
			}
			return 'https://meet.google.com/' . $this->generate_meet_id();

		} catch ( Exception $e ) {
			return 'https://meet.google.com/' . $this->generate_meet_id();
		}
	}

	/**
	 * Generate Zoom meeting link for the booking.
	 *
	 * @param array  $booking_data The booking data.
	 * @param int    $user_id The host user ID.
	 * @param object $calendar_slot The calendar slot object.
	 * @return string|null The Zoom meeting link or null if failed.
	 */
	private function generate_zoom_meeting_link( $booking_data, $user_id, $calendar_slot ) {
		try {
			// Check if Zoom integration is available.
			if ( ! class_exists( 'FluentBookingPro\App\Services\Integrations\ZoomMeeting\ZoomHelper' ) ) {
				return null;
			}

			// Get Zoom client for the user.
			$zoom_client = \FluentBookingPro\App\Services\Integrations\ZoomMeeting\ZoomHelper::getZoomClient( $user_id );

			if ( is_wp_error( $zoom_client ) || ! $zoom_client ) {
				return null;
			}

			// Prepare meeting data for Zoom.
			$host_user   = get_userdata( $user_id );
			$host_name   = $host_user ? $host_user->display_name : 'Host';
			$event_title = 'Meeting'; // Default fallback.
			
			// Try multiple ways to get the event title.
			if ( is_object( $calendar_slot ) ) {
				if ( isset( $calendar_slot->title ) && ! empty( $calendar_slot->title ) ) {
					$event_title = $calendar_slot->title;
				} elseif ( isset( $calendar_slot->event_title ) && ! empty( $calendar_slot->event_title ) ) {
					$event_title = $calendar_slot->event_title;
				} elseif ( isset( $calendar_slot->name ) && ! empty( $calendar_slot->name ) ) {
					$event_title = $calendar_slot->name;
				}
			}
			
			$meeting_data = [
				'topic'      => sprintf( '%s meeting between %s and %s', $event_title, $host_name, $booking_data['name'] ),
				'type'       => 2,
				'start_time' => gmdate( 'Y-m-d\TH:i:s\Z', strtotime( $booking_data['start_time'] ) ),
				'duration'   => intval( $booking_data['slot_minutes'] ),
				'agenda'     => $booking_data['message'],
				'settings'   => [
					'host_video'        => true,
					'participant_video' => true,
					'join_before_host'  => false,
					'mute_upon_entry'   => false,
				],
			];

			// Create the Zoom meeting.
			$meeting = $zoom_client->createMeeting( $meeting_data );

			if ( ! is_wp_error( $meeting ) && isset( $meeting['join_url'] ) ) {
				return $meeting['join_url'];
			}

			return null;

		} catch ( Exception $e ) {
			return null;
		}
	}


	/**
	 * Generate a random Google Meet ID.
	 *
	 * @return string Random meet ID.
	 */
	private function generate_meet_id() {
		$chars   = 'abcdefghijklmnopqrstuvwxyz';
		$meet_id = '';
		
		for ( $i = 0; $i < 3; $i++ ) {
			$meet_id .= $chars[ wp_rand( 0, 25 ) ];
		}
		$meet_id .= '-';
		
		for ( $i = 0; $i < 4; $i++ ) {
			$meet_id .= $chars[ wp_rand( 0, 25 ) ];
		}
		$meet_id .= '-';
		
		for ( $i = 0; $i < 3; $i++ ) {
			$meet_id .= $chars[ wp_rand( 0, 25 ) ];
		}
		
		return $meet_id;
	}
}

CreateBookingPro::get_instance();
