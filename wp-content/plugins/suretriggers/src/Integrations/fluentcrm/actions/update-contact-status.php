<?php
/**
 * UpdateContactStatus.
 * php version 5.6
 *
 * @category UpdateContactStatus
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

/**
 * UpdateContactStatus
 *
 * @category UpdateContactStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateContactStatus extends AutomateAction {


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
	public $action = 'fluentcrm_update_contact_status';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Contact Status', 'suretriggers' ),
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
				'message' => __( 'FluentCrmApi function not found.', 'suretriggers' ),
			];
		}

		$contact_api = FluentCrmApi( 'contacts' );
		$contact     = $contact_api->getContact( trim( $selected_options['contact_email'] ) );

		if ( is_null( $contact ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid contact.', 'suretriggers' ),
			];
		}

		$previous_status = $contact->status;
		$new_status      = sanitize_text_field( $selected_options['contact_status'] );
		
		$contact->status = $new_status;
		$contact->save();

		return [
			'success'         => true,
			'message'         => __( 'Contact status updated successfully.', 'suretriggers' ),
			'contact_id'      => $contact->id,
			'email'           => $contact->email,
			'full_name'       => $contact->full_name,
			'previous_status' => $previous_status,
			'new_status'      => $contact->status,
		];
	}

}

UpdateContactStatus::get_instance();
