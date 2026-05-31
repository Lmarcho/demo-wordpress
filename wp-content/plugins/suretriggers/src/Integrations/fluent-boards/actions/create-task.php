<?php
/**
 * CreateTask.
 * php version 5.6
 *
 * @category CreateTask
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentBoards\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentBoardsPro\App\Services\AttachmentService;

/**
 * CreateTask
 *
 * @category CreateTask
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateTask extends AutomateAction {


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
	public $action = 'fbs_create_task';

	use SingletonLoader;
	

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Task', 'suretriggers' ),
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
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$title           = $selected_options['title'] ? sanitize_text_field( $selected_options['title'] ) : '';
		$description     = $selected_options['description'] ? sanitize_text_field( $selected_options['description'] ) : '';
		$board_id        = $selected_options['board_id'] ? sanitize_text_field( $selected_options['board_id'] ) : '';
		$stage_id        = $selected_options['stage_id'] ? sanitize_text_field( $selected_options['stage_id'] ) : '';
		$priority        = $selected_options['priority'] ? sanitize_text_field( $selected_options['priority'] ) : '';
		$status          = $selected_options['status'] ? sanitize_text_field( $selected_options['status'] ) : '';
		$due_at          = $selected_options['due_date'] ? sanitize_text_field( $selected_options['due_date'] ) : '';
		$labels          = $selected_options['labels'] ? explode( ',', sanitize_text_field( $selected_options['labels'] ) ) : '';
		$crm_contact_id  = $selected_options['crm_contact_id'] ? sanitize_text_field( $selected_options['crm_contact_id'] ) : '';
		$created_by      = $selected_options['created_by'] ? sanitize_text_field( $selected_options['created_by'] ) : '';
		$attachment_url  = isset( $selected_options['attachment_url'] ) ? esc_url_raw( $selected_options['attachment_url'] ) : '';
		$attachment_name = $selected_options['attachment_name'] ? sanitize_text_field( $selected_options['attachment_name'] ) : '';
		$assignees       = isset( $selected_options['assignees'] ) ? sanitize_text_field( $selected_options['assignees'] ) : '';
		
		$task_data = array_filter(
			[
				'title'          => $title,
				'description'    => $description,
				'board_id'       => $board_id,
				'stage_id'       => $stage_id,
				'priority'       => $priority,
				'status'         => $status,
				'due_at'         => $due_at,
				'labels'         => $labels,
				'crm_contact_id' => $crm_contact_id,
				'created_by'     => $created_by,
			],
			function( $value ) {
				return '' !== $value; }
		);
		if ( ! function_exists( 'FluentBoardsApi' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBoardsApi function not found.', 'suretriggers' ), 
					
			];
		}
			
			$task = FluentBoardsApi( 'tasks' )->create( $task_data );
		if ( empty( $task ) ) {
			return [
				'status'  => 'error',
				'message' => 'There is error while creating a Task.',
			];
		}
			
		if ( ! empty( $assignees ) ) {
			if ( ! class_exists( 'FluentBoards\\App\\Services\\TaskService' ) ) {
				throw new Exception( __( 'FluentBoards TaskService not found.', 'suretriggers' ) );
			}
				
			$assignee_ids = array_map( 'intval', explode( ',', $assignees ) );
			$task_service = new \FluentBoards\App\Services\TaskService();
				
			foreach ( $assignee_ids as $assignee_id ) {
				$task_service->updateAssignee( $assignee_id, $task );
			}
				
			$task->load( 'assignees' );
		}
	
		if ( ! empty( $attachment_url ) ) {
			if ( ! class_exists( '\FluentBoardsPro\App\Services\AttachmentService' ) ) {
				return [ 'error' => 'AttachmentService class is not available.' ];
			}
			
			$urls  = array_map( 'trim', explode( ',', $attachment_url ) );
			$names = array_map( 'trim', explode( ',', $attachment_name ) );
			
			$attachment_service = new \FluentBoardsPro\App\Services\AttachmentService();
			
			foreach ( $urls as $index => $url ) {
				if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
					$title = ! empty( $names[ $index ] ) ? $names[ $index ] : 'Attachment ' . ( $index + 1 );
			
					$attachment_service->handleAttachment(
						$task->id,
						[
							'type'  => 'url',
							'url'   => $url,
							'title' => $title,
						]
					);
				}
			}
		}
			
			return $task;
	}
}

CreateTask::get_instance();
