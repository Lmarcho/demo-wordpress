<?php
/**
 * ReplyToTicketAsAgent.
 * php version 5.6
 *
 * @category ReplyToTicketAsAgent
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentSupport\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Models\Conversation;
use FluentSupport\App\Models\Agent;

/**
 * ReplyToTicketAsAgent
 */
class ReplyToTicketAsAgent extends AutomateAction {

	use SingletonLoader;
	
	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentSupport';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'reply_to_ticket_as_agent_fluent_support';

	/**
	 * Register the action.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Reply to Ticket as Agent', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id User ID.
	 * @param int   $automation_id Automation ID.
	 * @param array $fields Field values.
	 * @param array $selected_options Selected options.
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( 'FluentSupport\App\Models\Ticket' ) || ! class_exists( 'FluentSupport\App\Models\Conversation' ) || ! class_exists( 'FluentSupport\App\Models\Agent' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Error: FluentSupport plugin is not installed correctly. Required classes are missing.',
			];
		}

		$ticket_id         = isset( $selected_options['ticket_id'] ) ? (int) $selected_options['ticket_id'] : 0;
		$reply_content     = isset( $selected_options['reply_content'] ) ? wp_kses_post( $selected_options['reply_content'] ) : '';
		$person_id         = isset( $selected_options['person_id'] ) ? (int) $selected_options['person_id'] : 0;
		$conversation_type = isset( $selected_options['conversation_type'] ) ? $selected_options['conversation_type'] : 'response';

		if ( ! $person_id ) {
			return [
				'success' => false,
				'message' => 'Agent (person_id) is required.',
			];
		}

		if ( ! $ticket_id || empty( $reply_content ) ) {
			return [
				'success' => false,
				'message' => 'Ticket ID and reply content are required.',
			];
		}

		$ticket = Ticket::find( $ticket_id );

		if ( ! $ticket ) {
			return [
				'success' => false,
				'message' => 'Ticket not found.',
			];
		}

		$agent = Agent::where( 'id', $person_id )->first();
		if ( ! $agent ) {
			return [
				'success' => false,
				'message' => 'Agent not found or is not a valid FluentSupport agent.',
			];
		}

		try {
			$conversation = Conversation::create(
				[
					'ticket_id'         => $ticket_id,
					'content'           => $reply_content,
					'person_id'         => $person_id,
					'person_type'       => 'agent',
					'conversation_type' => $conversation_type,
					'source'            => 'web',
					'created_at'        => current_time( 'mysql' ),
					'updated_at'        => current_time( 'mysql' ),
				] 
			);
		} catch ( Exception $e ) {
			return [
				'success' => false,
				'message' => 'Error while adding reply to ticket: ' . $e->getMessage(),
			];
		}


		return [
			'success' => true,
			'message' => 'Reply added to ticket successfully as agent.',
			'data'    => $conversation,
		];        
	}
}

ReplyToTicketAsAgent::get_instance();
