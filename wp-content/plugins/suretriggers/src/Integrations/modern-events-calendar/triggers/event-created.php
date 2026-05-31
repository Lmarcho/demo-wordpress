<?php
/**
 * EventCreated.
 * php version 7.0+
 *
 * @category EventCreated
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ModernEventsCalendar\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;



if ( ! class_exists( 'EventCreated' ) ) :

	/**
	 * EventCreated Class
	 *
	 * Handles the trigger when a new event is created in ModernEventsCalendar.
	 *
	 * @since 1.0.0
	 */
	class EventCreated {

		use SingletonLoader;

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'ModernEventsCalendar';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'mec_event_created';

		/**
		 * Constructor
		 *
		 * Initializes the EventCreated class.
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register the trigger.
		 *
		 * @param array $triggers Existing triggers.
		 * @return array Modified triggers with the new trigger added.
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'New Event Created', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'mec_save_event_data',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * Listens for the `mec_save_event_data` action and triggers automation.
		 *
		 * @param int    $post_id The post ID.
		 * @param object $_mec  The event object.
		 * @return void
		 */
		public function trigger_listener( $post_id, $_mec ) {
			if ( empty( $post_id ) || empty( $_mec ) ) {
				return;
			}
		   
			$context = [
				'post_id' => $post_id,
				'event'   => get_post_meta( $post_id ),
				'post'    => get_post( $post_id ),
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
	 * Initialize the singleton instance of EventCreated.
	 */
	EventCreated::get_instance();

endif;
