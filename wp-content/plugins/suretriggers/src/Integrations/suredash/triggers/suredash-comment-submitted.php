<?php
/**
 * SureDashCommentSubmitted trigger for handling comment submission events.
 * php version 5.6
 *
 * @category SureDashCommentSubmitted
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
 * SureDashCommentSubmitted
 *
 * @category SureDashCommentSubmitted
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SureDashCommentSubmitted {

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
	public $trigger = 'suredash_comment_submitted';

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
			'label'         => __( 'Comment Submitted', 'suretriggers' ),
			'action'        => 'suredash_after_comment_submit',
			'function'      => [ $this, 'trigger_listener' ],
			'priority'      => 10,
			'accepted_args' => 2,
		];
		return $triggers;
	}

	/**
	 * Trigger listener for comment submission events.
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

		$comment = get_comment( $comment_id );
		if ( ! $comment instanceof \WP_Comment ) {
			return;
		}

		$context                         = WordPress::get_user_context( $current_user_id );
		$context['comment_id']           = $comment_id;
		$context['comment_content']      = $comment->comment_content;
		$context['comment_author']       = $comment->comment_author;
		$context['comment_author_email'] = $comment->comment_author_email;
		$context['comment_date']         = $comment->comment_date;
		$context['comment_approved']     = $comment->comment_approved;
		$context['comment_type']         = $comment->comment_type;
		$context['comment_parent']       = $comment->comment_parent;

		$post_id = (int) $comment->comment_post_ID;
		if ( $post_id ) {
			$post = get_post( $post_id );
			if ( $post instanceof \WP_Post ) {
				$context['post_id']        = $post_id;
				$context['post_title']     = $post->post_title;
				$context['post_content']   = $post->post_content;
				$context['post_author']    = $post->post_author;
				$context['post_type']      = $post->post_type;
				$context['post_status']    = $post->post_status;
				$context['post_permalink'] = get_permalink( $post_id );
				$context['suredash_post']  = $post_id;
			}
		}

		$context['suredash_post'] = $post_id;
		AutomationController::sure_trigger_handle_trigger(
			[
				'trigger' => $this->trigger,
				'context' => $context,
			]
		);
	}
}

SureDashCommentSubmitted::get_instance();
