<?php
/**
 * CancelBooking.
 * php version 5.6
 *
 * @category CancelBooking
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
 * CancelBooking
 *
 * @category CancelBooking
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */
class CancelBooking extends AutomateAction {

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
	public $action = 'fluent_booking_cancel_booking';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Cancel Booking', 'suretriggers' ),
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

		$booking_id    = isset( $selected_options['booking_id'] ) ? intval( $selected_options['booking_id'] ) : 0;
		$cancel_reason = isset( $selected_options['cancel_reason'] ) ? sanitize_textarea_field( $selected_options['cancel_reason'] ) : '';
		$internal_note = isset( $selected_options['internal_note'] ) ? sanitize_textarea_field( $selected_options['internal_note'] ) : '';

		if ( empty( $booking_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Booking ID is required.', 'suretriggers' ),
			];
		}

		try {
			$booking = \FluentBooking\App\Models\Booking::find( $booking_id );
			
			if ( ! $booking ) {
				return [
					'status'  => 'error',
					'message' => sprintf( __( 'Booking not found with ID: %d', 'suretriggers' ), $booking_id ),
				];
			}

			if ( 'cancelled' === $booking->status ) {
				return [
					'status'  => 'error',
					'message' => __( 'Booking is already cancelled.', 'suretriggers' ),
				];
			}

			$old_status = $booking->status;

			$update_data = [
				'status'       => 'cancelled',
				'cancelled_by' => get_current_user_id() ? get_current_user_id() : 'automation',
			];

			if ( $internal_note ) {
				$update_data['internal_note'] = $internal_note;
			}

			$booking->update( $update_data );

			if ( $cancel_reason ) {
				$booking->updateMeta( 'cancel_reason', $cancel_reason );
			}

			if ( class_exists( 'FluentBooking\App\Models\BookingActivity' ) ) {
				\FluentBooking\App\Models\BookingActivity::create(
					[
						'booking_id'  => $booking->id,
						'status'      => 'cancelled',
						'type'        => 'activity',
						'title'       => __( 'Booking Cancelled via Automation', 'suretriggers' ),
						'description' => $cancel_reason ? __( 'Reason: ', 'suretriggers' ) . $cancel_reason : __( 'Booking was cancelled through automation.', 'suretriggers' ),
						'created_by'  => get_current_user_id() ? get_current_user_id() : 0,
					]
				);
			}

			$booking = $booking->fresh();

			return [
				'success'       => true,
				'message'       => 'Booking cancelled successfully',
				'booking_id'    => $booking->id,
				'old_status'    => $old_status,
				'new_status'    => 'cancelled',
				'cancel_reason' => $cancel_reason,
				'booking'       => $booking->toArray(),
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to cancel booking: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

CancelBooking::get_instance();
