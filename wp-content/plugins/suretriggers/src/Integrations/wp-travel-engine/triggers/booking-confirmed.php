<?php
/**
 * BookingConfirmed.
 * php version 5.6
 *
 * @category BookingConfirmed
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPTravelEngine\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'BookingConfirmed' ) ) :

	/**
	 * BookingConfirmed
	 *
	 * @category BookingConfirmed
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class BookingConfirmed {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'WPTravelEngine';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'wte_booking_confirmed';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
			add_action( 'updated_post_meta', [ $this, 'meta_updated_listener' ], 10, 4 );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Booking Confirmed', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'wte_booking_status_confirmed',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Listen for meta updates on booking posts
		 *
		 * @param int    $meta_id    ID of the updated metadata entry.
		 * @param int    $post_id    Post ID.
		 * @param string $meta_key   Meta key.
		 * @param mixed  $meta_value Meta value.
		 * @return void
		 */
		public function meta_updated_listener( $meta_id, $post_id, $meta_key, $meta_value ) {
			if ( 'wp_travel_engine_booking_status' !== $meta_key ) {
				return;
			}

			$post = get_post( $post_id );
			if ( empty( $post ) || 'booking' !== $post->post_type ) {
				return;
			}

			if ( in_array( $meta_value, [ 'booked', 'confirmed', 'completed' ] ) ) {
				do_action( 'wte_booking_status_confirmed', $post_id );
			}
		}

		/**
		 * Trigger listener
		 *
		 * @param int $booking_id Booking ID.
		 * @return void
		 */
		public function trigger_listener( $booking_id ) {
			if ( empty( $booking_id ) ) {
				return;
			}
			
			$booking_data   = get_post( $booking_id );
			$booking_meta   = get_post_meta( $booking_id, 'wp_travel_engine_booking_setting', true );
			$booking_status = get_post_meta( $booking_id, 'wp_travel_engine_booking_status', true );
			
			$context = [
				'booking_data'   => $booking_data,
				'booking_meta'   => $booking_meta,
				'booking_status' => $booking_status,
			];
			
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	BookingConfirmed::get_instance();

endif;
