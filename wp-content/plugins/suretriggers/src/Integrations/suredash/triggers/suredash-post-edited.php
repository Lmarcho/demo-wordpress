<?php
/**
 * SureDashPostEdited trigger for handling post edit events.
 * php version 5.6
 *
 * @category SureDashPostEdited
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
 * SureDashPostEdited
 *
 * @category SureDashPostEdited
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SureDashPostEdited {

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
	public $trigger = 'suredash_post_edited';

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
			'label'         => __( 'Post Edited', 'suretriggers' ),
			'action'        => 'suredash_after_post_edited',
			'function'      => [ $this, 'trigger_listener' ],
			'priority'      => 10,
			'accepted_args' => 2,
		];
		return $triggers;
	}

	/**
	 * Trigger listener for post edit events.
	 *
	 * @param int $post_id Post ID.
	 * @param int $current_user_id Current user ID.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function trigger_listener( $post_id, $current_user_id ) {
		if ( ! $post_id || ! $current_user_id ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$context                   = WordPress::get_user_context( $current_user_id );
		$context['post_id']        = $post_id;
		$context['post_title']     = $post->post_title;
		$context['post_content']   = $post->post_content;
		$context['post_excerpt']   = $post->post_excerpt;
		$context['post_status']    = $post->post_status;
		$context['post_author']    = $post->post_author;
		$context['post_date']      = $post->post_date;
		$context['post_modified']  = $post->post_modified;
		$context['post_type']      = $post->post_type;
		$context['post_name']      = $post->post_name;
		$context['post_permalink'] = get_permalink( $post_id );
		
		$context['editor_user_id']    = $current_user_id;
		$context['is_author_editing'] = ( $post->post_author == $current_user_id );
		
		AutomationController::sure_trigger_handle_trigger(
			[
				'trigger' => $this->trigger,
				'context' => $context,
			]
		);
	}
}

SureDashPostEdited::get_instance();
