<?php
/**
 * UserReceivesPrivateMessage.
 * php version 5.6
 *
 * @category UserReceivesPrivateMessage
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\BuddyBoss\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'UserReceivesPrivateMessage' ) ) :
	/**
	 * UserReceivesPrivateMessage
	 *
	 * @category UserReceivesPrivateMessage
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class UserReceivesPrivateMessage {

		use SingletonLoader;

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'BuddyBoss';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'user_receives_private_message';

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
		 * @param array $triggers triggers.
		 *
		 * @return array
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'User Receives Private Message', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'messages_message_sent',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 *  Trigger listener
		 *
		 * @param object $message message object.
		 *
		 * @return void
		 */
		public function trigger_listener( $message ) {
			if ( ! is_object( $message ) || ! property_exists( $message, 'id' ) ) {
				return;
			}

			$sender_id  = property_exists( $message, 'sender_id' ) ? $message->sender_id : 0;
			$thread_id  = property_exists( $message, 'thread_id' ) ? $message->thread_id : 0;
			$message_id = $message->id;
			$recipients = property_exists( $message, 'recipients' ) ? $message->recipients : [];

			if ( empty( $recipients ) || ! $sender_id ) {
				return;
			}

			foreach ( $recipients as $recipient ) {
				if ( ! is_object( $recipient ) || ! property_exists( $recipient, 'user_id' ) || $recipient->user_id == $sender_id ) {
					continue;
				}

				$recipient_id = $recipient->user_id;
				$context      = WordPress::get_user_context( $recipient_id );

				$sender_context                 = WordPress::get_user_context( $sender_id );
				$context['sender_id']           = $sender_id;
				$context['sender_first_name']   = $sender_context['user_firstname'];
				$context['sender_last_name']    = $sender_context['user_lastname'];
				$context['sender_email']        = $sender_context['user_email'];
				$context['sender_display_name'] = $sender_context['display_name'];
				$context['sender_avatar_url']   = get_avatar_url( $sender_id );

				$context['message_id']           = $message_id;
				$context['thread_id']            = $thread_id;
				$context['message_subject']      = property_exists( $message, 'subject' ) ? $message->subject : '';
				$message_content                 = property_exists( $message, 'message' ) ? $message->message : '';
				$context['message_content']      = wp_strip_all_tags( $message_content );
				$context['message_content_html'] = $message_content;
				$context['message_date']         = property_exists( $message, 'date_sent' ) ? $message->date_sent : '';

				AutomationController::sure_trigger_handle_trigger(
					[
						'trigger' => $this->trigger,
						'user_id' => $recipient_id,
						'context' => $context,
					]
				);
			}
		}
	}


	UserReceivesPrivateMessage::get_instance();
endif;
