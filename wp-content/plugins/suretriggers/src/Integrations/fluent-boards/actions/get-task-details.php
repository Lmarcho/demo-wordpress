<?php
/**
 * GetTaskDetails.
 * php version 5.6
 *
 * @category GetTaskDetails
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
use FluentBoards\App\Models\Task;
/**
 * GetTaskDetails
 *
 * @category GetTaskDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetTaskDetails extends AutomateAction {


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
	public $action = 'fbs_get_task_details';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Task', 'suretriggers' ),
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

		// Check if FluentBoardsApi function exists, if not, return early.
		if ( ! class_exists( 'FluentBoards\App\Models\Task' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentBoards\App\Models\Task class not found.', 'suretriggers' ), 
				
			];
		}
		$task = \FluentBoards\App\Models\Task::find( $task_id );
		if ( empty( $task ) ) {
			return [
				'status'  => 'error',
				'message' => 'There is error while getting task details.',
			];
		}
		return $task;
	}
}

GetTaskDetails::get_instance();
