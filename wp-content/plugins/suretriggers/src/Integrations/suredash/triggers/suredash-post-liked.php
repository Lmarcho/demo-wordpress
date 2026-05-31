<?php
/**
 * SureDashPostLiked trigger for handling post like events.
 * php version 5.6
 *
 * @category SureDashPostLiked
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
use WP_REST_Request;
use WP_REST_Server;

/**
 * SureDashPostLiked
 *
 * @category SureDashPostLiked
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SureDashPostLiked {

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
	public $trigger = 'suredash_post_liked';

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
			'label'         => __( 'User likes a post', 'suretriggers' ),
			'action'        => 'rest_pre_dispatch',
			'function'      => [ $this, 'trigger_listener' ],
			'priority'      => 10,
			'accepted_args' => 3,
		];
		return $triggers;
	}

	/**
	 * Trigger listener for post like events.
	 *
	 * @param mixed           $result Response to replace the requested version with.
	 * @param WP_REST_Server  $server Server instance.
	 * @param WP_REST_Request $request Request used to generate the response.
	 * @since 1.0.0
	 *
	 * @return mixed
	 */
	public function trigger_listener( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		// Check if this is a SureDash entity-reaction endpoint.
		if ( false === strpos( $request->get_route(), 'entity-reaction' ) ) {
			return $result;
		}
		
		$params = $request->get_params();
		
		// Check if this is a post like action.
		if ( empty( $params['entity'] ) || 'post' !== $params['entity'] ) {
			return $result;
		}
		
		$entity_id = ! empty( $params['entity_id'] ) ? absint( $params['entity_id'] ) : 0;
		$user_id   = get_current_user_id();
		
		if ( ! $entity_id || ! $user_id ) {
			return $result;
		}
		
		// Get current likes before the action.
		$current_likes      = get_post_meta( $entity_id, 'portal_post_likes', true );
		$current_likes      = is_array( $current_likes ) ? $current_likes : [];
		$user_already_liked = in_array( $user_id, $current_likes, true );
		
		// Use shutdown hook to check final like status after processing.
		add_action(
			'shutdown',
			function() use ( $entity_id, $user_id, $user_already_liked ) {
				$new_likes      = get_post_meta( $entity_id, 'portal_post_likes', true );
				$new_likes      = is_array( $new_likes ) ? $new_likes : [];
				$user_now_likes = in_array( $user_id, $new_likes, true );
			
				// If user didn't like before but likes now, trigger the event.
				if ( ! $user_already_liked && $user_now_likes ) {
					$post = get_post( $entity_id );
					if ( ! $post instanceof \WP_Post ) {
						return;
					}
				
					$context                  = WordPress::get_user_context( $user_id );
					$context['post_id']       = $entity_id;
					$context['post_title']    = $post->post_title;
					$context['post_author']   = $post->post_author;
					$context['suredash_post'] = $entity_id;
				
					AutomationController::sure_trigger_handle_trigger(
						[
							'trigger' => $this->trigger,
							'context' => $context,
						]
					);
				}
			} 
		);
		
		return $result;
	}
}

SureDashPostLiked::get_instance();
