<?php
/**
 * UpdateBookingStatus.
 * php version 5.6
 *
 * @category UpdateBookingStatus
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
 * UpdateBookingStatus
 *
 * @category UpdateBookingStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */
class UpdateBookingStatus extends AutomateAction {

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
	public $action = 'fluent_booking_update_booking_status';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Booking Status', 'suretriggers' ),
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
		$new_status    = isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : '';
		$cancel_reason = isset( $selected_options['cancel_reason'] ) ? sanitize_textarea_field( $selected_options['cancel_reason'] ) : '';
		$internal_note = isset( $selected_options['internal_note'] ) ? sanitize_textarea_field( $selected_options['internal_note'] ) : '';

		if ( empty( $booking_id ) || empty( $new_status ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Booking ID and Status are required.', 'suretriggers' ),
			];
		}

		$valid_statuses = [ 'scheduled', 'pending', 'cancelled', 'completed', 'rejected' ];
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			return [
				'status'  => 'error',
				'message' => sprintf( __( 'Invalid booking status. Valid statuses are: %s', 'suretriggers' ), implode( ', ', $valid_statuses ) ),
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

			$old_status = $booking->status;

			$update_data = [
				'status' => $new_status,
			];

			if ( $internal_note ) {
				$update_data['internal_note'] = $internal_note;
			}

			if ( 'cancelled' === $new_status ) {
				if ( $cancel_reason ) {
					$booking->updateMeta( 'cancel_reason', $cancel_reason );
				}
				
				if ( ! $booking->cancelled_by ) {
					$update_data['cancelled_by'] = get_current_user_id() ? get_current_user_id() : 'automation';
				}
			}

			$booking->update( $update_data );
			$booking = $booking->fresh();

			return [
				'success'    => true,
				'message'    => 'Booking status updated successfully',
				'booking_id' => $booking->id,
				'old_status' => $old_status,
				'new_status' => $new_status,
				'booking'    => $booking->toArray(),
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update booking status: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

UpdateBookingStatus::get_instance();
