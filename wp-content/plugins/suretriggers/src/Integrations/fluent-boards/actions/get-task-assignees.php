<?php
/**
 * GetTaskAssignees.
 * php version 5.6
 *
 * @category GetTaskAssignees
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

/**
 * GetTaskAssignees
 *
 * @category GetTaskAssignees
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetTaskAssignees extends AutomateAction {

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
	public $action = 'fbs_get_task_assignees';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Task Assignees', 'suretriggers' ),
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
		$task_id = $selected_options['task_id'] ? sanitize_text_field( $selected_options['task_id'] ) : '';

		if ( empty( $task_id ) ) {
			return [ 'error' => 'Task ID is required.' ];
		}

		if ( ! function_exists( 'FluentBoardsApi' ) ) {
			return [ 'error' => 'FluentBoards plugin is not active.' ];
		}

		$task = FluentBoardsApi( 'tasks' )->find( $task_id );
		if ( empty( $task ) ) {
			return [ 'error' => 'Task not found.' ];
		}

		$task->load( 'assignees' );

		$assignee_data = [];
		if ( ! empty( $task->assignees ) ) {
			foreach ( $task->assignees as $assignee ) {
				$assignee_data[] = [
					'id'           => $assignee->ID,
					'display_name' => $assignee->display_name,
					'user_email'   => $assignee->user_email,
					'user_login'   => $assignee->user_login,
				];
			}
		}

		return [
			'task_id'        => $task->id,
			'task_title'     => $task->title,
			'assignees'      => $assignee_data,
			'assignee_count' => count( $assignee_data ),
		];
	}
}

GetTaskAssignees::get_instance();
