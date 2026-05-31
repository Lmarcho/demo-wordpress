<?php
/**
 * GetContactNotes.
 * php version 5.6
 *
 * @category GetContactNotes
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

/**
 * GetContactNotes
 *
 * @category GetContactNotes
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetContactNotes extends AutomateAction {


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
	public $action = 'fluentcrm_get_contact_notes';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Contact Notes', 'suretriggers' ),
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

		$contact_api = FluentCrmApi( 'contacts' );
		$contact     = $contact_api->getContact( trim( $selected_options['contact_email'] ) );

		if ( is_null( $contact ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Contact not found.', 'suretriggers' ),
			];
		}

		$notes = SubscriberNote::where( 'subscriber_id', $contact->id )->get();

		$context = [
			'contact_id'    => $contact->id,
			'contact_email' => $contact->email,
			'notes_count'   => $notes->count(),
			'notes'         => $notes->toArray(),
		];

		return $context;
	}

}

GetContactNotes::get_instance();
