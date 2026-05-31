<?php
/**
 * NewMessagePostCreated.
 * php version 5.6
 *
 * @category NewMessagePostCreated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Voxel\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;

if ( ! class_exists( 'NewMessagePostCreated' ) ) :

	/**
	 * NewMessagePostCreated
	 *
	 * @category NewMessagePostCreated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class NewMessagePostCreated {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'Voxel';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'voxel_new_message_post_created';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Voxel Message Posted', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'voxel/app-events/messages/user:received_message',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $event Event.
		 * @return void
		 */
		public function trigger_listener( $event ) {
			if ( ! property_exists( $event, 'message' ) ) {
				return;
			}
			
			$context['sender']     = WordPress::get_user_context( $event->message->get_sender_id() );
			$context['receiver']   = WordPress::get_user_context( $event->message->get_receiver_id() );
			$context['content']    = $event->message->get_content();
			$context['message_id'] = $event->message->get_id();
			$context['created_at'] = $event->message->get_created_at();
			
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
	NewMessagePostCreated::get_instance();

endif;
