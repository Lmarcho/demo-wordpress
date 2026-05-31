<?php
/**
 * GetCrmContactForTask.
 * php version 5.6
 *
 * @category GetCrmContactForTask
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentBoards\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * GetCrmContactForTask
 *
 * @category GetCrmContactForTask
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetCrmContactForTask extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentBoards';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fbs_get_crm_contact_for_task';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get CRM Contact for Task', 'suretriggers' ),
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
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$task_id  = $selected_options['task_id'] ? sanitize_text_field( $selected_options['task_id'] ) : '';
		$board_id = $selected_options['board_id'] ? sanitize_text_field( $selected_options['board_id'] ) : '';

		if ( empty( $task_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Task ID is required.', 'suretriggers' ), 
				
			];
		}

		if ( ! function_exists( 'FluentBoardsApi' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBoards plugin is not active.', 'suretriggers' ), 
				
			];
		}

		$task = FluentBoardsApi( 'tasks' )->find( $task_id );
		if ( empty( $task ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Task not found.', 'suretriggers' ), 
				
			];
		}
		
		if ( ! empty( $board_id ) && $task->board_id != $board_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Task does not belong to the specified board.', 'suretriggers' ), 
				
			];
		}

		global $wpdb;
		$task_data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}fbs_tasks WHERE id = %d",
				$task_id
			)
		);

		$crm_contact_data = [];
		$crm_contact_id   = isset( $task_data->crm_contact_id ) ? $task_data->crm_contact_id : null;

		if ( $crm_contact_id ) {
			// Get CRM contact details using the contact ID from task.
			if ( class_exists( 'FluentCrm\App\Models\Subscriber' ) ) {
				$contact = \FluentCrm\App\Models\Subscriber::find( $crm_contact_id );
			} elseif ( function_exists( 'FluentCrmApi' ) ) {
				$contact_api = FluentCrmApi( 'contacts' );
				$contact     = $contact_api->getContact( $crm_contact_id );
			} else {
				$contact = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}fc_subscribers WHERE id = %d",
						$crm_contact_id
					)
				);
			}

			if ( $contact ) {
				$crm_contact_data = [
					'contact_id'   => $contact->id,
					'email'        => $contact->email,
					'first_name'   => isset( $contact->first_name ) ? $contact->first_name : '',
					'last_name'    => isset( $contact->last_name ) ? $contact->last_name : '',
					'full_name'    => isset( $contact->full_name ) ? $contact->full_name : '',
					'status'       => isset( $contact->status ) ? $contact->status : '',
					'contact_type' => isset( $contact->contact_type ) ? $contact->contact_type : '',
					'crm_found'    => true,
				];
			} else {
				$crm_contact_data = [
					'contact_id' => $crm_contact_id,
					'crm_status' => 'Contact ID found but contact details not available',
				];
			}
		} else {
			$crm_contact_data = [
				'crm_status' => 'No CRM contact assigned to this task',
			];
		}

		return [
			'status'         => 'success',
			'task_id'        => $task->id,
			'task_title'     => $task->title,
			'board_id'       => $task->board_id,
			'board_title'    => $task->board ? $task->board->title : '',
			'crm_contact'    => $crm_contact_data,
			'crm_contact_id' => $crm_contact_id,
		];
	}
}

GetCrmContactForTask::get_instance();
