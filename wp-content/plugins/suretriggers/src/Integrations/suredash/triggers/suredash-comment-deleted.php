<?php
/**
 * SureDashCommentDeleted trigger for handling comment deletion events.
 * php version 5.6
 *
 * @category SureDashCommentDeleted
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\SureDash\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

/**
 * SureDashCommentDeleted
 *
 * @category SureDashCommentDeleted
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SureDashCommentDeleted {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'SureDash';

	/**
	 * Trigger name.
	 *
	 * @var string
	 */
	public $trigger = 'suredash_comment_deleted';

	use SingletonLoader;

	/**
	 * Constructor
	 *
	 * @since  1.0.0
	 */
	public function __construct() {
		add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
	}

	/**
	 * Register a trigger.
	 *
	 * @param array $triggers triggers.
	 * @return array
	 */
	public function register( $triggers ) {
		$triggers[ $this->integration ][ $this->trigger ] = [
			'label'         => __( 'Comment Deleted', 'suretriggers' ),
			'action'        => 'suredash_after_comment_deleted',
			'function'      => [ $this, 'trigger_listener' ],
			'priority'      => 10,
			'accepted_args' => 2,
		];
		return $triggers;
	}

	/**
	 * Trigger listener for comment deletion events.
	 *
	 * @param int $comment_id Comment ID.
	 * @param int $current_user_id Current user ID.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function trigger_listener( $comment_id, $current_user_id ) {
		if ( ! $comment_id || ! $current_user_id ) {
			return;
		}

		$context               = WordPress::get_user_context( $current_user_id );
		$context['comment_id'] = $comment_id;
		
		AutomationController::sure_trigger_handle_trigger(
			[
				'trigger' => $this->trigger,
				'context' => $context,
			]
		);
	}
}

SureDashCommentDeleted::get_instance();
