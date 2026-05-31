<?php
/**
 * UpdateCommentStatus.
 * php version 5.6
 *
 * @category UpdateCommentStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Wordpress\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * UpdateCommentStatus
 *
 * @category UpdateCommentStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateCommentStatus extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WordPress';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'update_comment_status';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions Action data.
	 * 
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Comment Status', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id          User ID.
	 * @param int   $automation_id    Automation ID.
	 * @param array $fields           Fields.
	 * @param array $selected_options Selected options.
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$comment_id = isset( $selected_options['comment_id'] ) ? (int) $selected_options['comment_id'] : 0;
		$status     = isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : '';

		$comment = get_comment( $comment_id );

		if ( ! $comment ) {
			return [
				'status'  => 'error',
				'message' => 'Comment does not exist.',
			];
		}

		if ( ! in_array( $status, [ 'approve', 'hold', 'spam', 'trash' ], true ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid comment status provided.', 'suretriggers' ), 
				
			];
		}

		$updated = wp_set_comment_status( $comment_id, $status );

		if ( ! $updated ) {
			return [
				'status'  => 'error',
				'message' => 'Failed to update comment status.',
			];
		}
		if ( is_object( $comment ) ) {
			$comment = get_object_vars( $comment );
		}

		return [
			'comment_id'   => $comment_id,
			'new_status'   => $status,
			'comment_data' => $comment,
		];
	}
}

UpdateCommentStatus::get_instance();
