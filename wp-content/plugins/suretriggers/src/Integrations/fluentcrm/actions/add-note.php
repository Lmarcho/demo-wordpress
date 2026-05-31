<?php
/**
 * AddNote.
 * php version 5.6
 *
 * @category AddNote
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCrm\App\Models\SubscriberNote;
use FluentCrm\App\Services\Sanitize;

/**
 * AddNote
 *
 * @category AddNote
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddNote extends AutomateAction {


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCRM';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcrm_add_note';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Note', 'suretriggers' ),
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
	 * @param array $selected_options selectedOptions.
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not active.', 'suretriggers' ), 
				
			];
		}

		if ( ! class_exists( 'FluentCrm\App\Models\SubscriberNote' ) ) {
			return [
				'status'  => 'error',
				'message' => 'SubscriberNote class not found.',
			];
		}

		if ( ! class_exists( 'FluentCrm\App\Services\Sanitize' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Sanitize class not found.',
			];
		}
		
		$contact_api = FluentCrmApi( 'contacts' );

		$contact = $contact_api->getContact( trim( $selected_options['contact_email'] ) );

		if ( is_null( $contact ) ) {
			return [
				'message'     => __( 'Can not find the contact with the email.', 'suretriggers' ),
				'status'      => 'false',
				'user_exists' => 'false',
			];
		}


		$contact_id = $contact->id;

		$note = [
			'title'         => $selected_options['title'],
			'description'   => $selected_options['description'],
			'type'          => $selected_options['type'],
			'created_at'    => isset( $selected_options['created_at'] ) ? $selected_options['created_at'] : current_time( 'mysql' ),
			'subscriber_id' => $contact_id,
		];

		$note            = Sanitize::contactNote( $note );
		$subscriber_note = SubscriberNote::create( wp_unslash( (array) $note ) );

		if ( ! $subscriber_note ) {
			return [
				'success' => false,
				'message' => __( 'Failed to add note. Please try again.', 'suretriggers' ), 
				
			];
		}

		return [
			'success' => true,
			'message' => __( 'Note has been successfully added.', 'suretriggers' ),
			'note'    => $subscriber_note,
		];
	}

}

AddNote::get_instance();
