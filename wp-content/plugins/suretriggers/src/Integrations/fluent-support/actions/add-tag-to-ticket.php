<?php
/**
 * AddTagToTicket.
 * php version 5.6
 *
 * @category AddTagToTicket
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
use FluentSupport\App\Models\TicketTag;

/**
 * AddTagToTicket
 *
 * @category AddTagToTicket
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddTagToTicket extends AutomateAction {

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
	public $action = 'add_tag_to_ticket_fluent_support';

	/**
	 * Register action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Tag to Ticket', 'suretriggers' ),
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
	 * @throws Exception If FluentSupport classes are not available or data fetch fails.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( 'FluentSupport\App\Models\Ticket' ) || ! class_exists( 'FluentSupport\App\Models\TicketTag' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Error: FluentSupport plugin is not installed correctly. Required classes are missing.',
			];
		}

		$ticket_id = isset( $selected_options['ticket_id'] ) ? (int) $selected_options['ticket_id'] : 0;
		$tags_raw  = isset( $selected_options['tags'] ) ? sanitize_text_field( $selected_options['tags'] ) : '';

		if ( ! $ticket_id || empty( $tags_raw ) ) {
			return [
				'success' => false,
				'message' => 'Ticket ID and Tag(s) are required.',
			];
		}

		$tags = array_map( 'trim', explode( ',', $tags_raw ) );

		$ticket = Ticket::find( $ticket_id );

		if ( ! $ticket ) {
			return [
				'success' => false,
				'message' => 'Ticket not found.',
			];
		}

		$tag_ids = [];

		foreach ( $tags as $tag_title ) {
			$tag = TicketTag::firstOrCreate(
				[ 'slug' => sanitize_title( $tag_title ) ],
				[ 'title' => $tag_title ]
			);

			$tag_ids[] = $tag->id;
		}

		try {
			$ticket->applyTags( $tag_ids );

			return [
				'success' => true,
				'message' => 'Tag(s) added and attached to the ticket successfully.',
				'data'    => [
					'ticket_id' => $ticket_id,
					'tag_ids'   => $tag_ids,
					'tags'      => $tags,
				],
			];
		} catch ( Exception $e ) {
			return [
				'success' => false,
				'message' => 'Failed to apply tags to the ticket: ' . $e->getMessage(),
			];
		}
	}
}

AddTagToTicket::get_instance();
