<?php
/**
 * LikePost.
 * php version 5.6
 *
 * @category LikePost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCommunity\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * LikePost
 *
 * @category LikePost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class LikePost extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCommunity';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fc_like_post';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Like a Post', 'suretriggers' ),
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
		
		if ( ! defined( 'FLUENT_COMMUNITY_PLUGIN_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => 'FluentCommunity plugin is not active.',
			];
		}

		$post_id    = isset( $selected_options['post_id'] ) ? (int) sanitize_text_field( $selected_options['post_id'] ) : 0;
		$user_email = isset( $selected_options['user_email'] ) ? sanitize_email( $selected_options['user_email'] ) : '';

		if ( ! $post_id ) {
			return [
				'status'  => 'error',
				'message' => 'Post ID is required.',
			];
		}

		$user = $user_email ? get_user_by( 'email', $user_email ) : get_userdata( $user_id );

		if ( ! $user ) {
			return [
				'status'  => 'error',
				'message' => 'User not found.',
			];
		}

		if ( ! $this->is_valid_post( $post_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid or non-existent Post ID.',
			];
		}

		if ( $this->user_already_liked( $post_id, $user->ID ) ) {
			return [
				'status'  => 'success',
				'message' => 'User has already liked this post.',
				'post_id' => $post_id,
				'user_id' => $user->ID,
				'action'  => 'already_liked',
			];
		}

		try {
			$like_id = $this->create_like_reaction( $post_id, $user->ID );

			if ( $like_id ) {
				$this->update_post_reactions_count( $post_id );

				return [
					'status'  => 'success',
					'message' => 'Post liked successfully.',
					'post_id' => $post_id,
					'user_id' => $user->ID,
					'like_id' => $like_id,
					'action'  => 'liked',
				];
			} else {
				return [
					'status'  => 'error',
					'message' => 'Failed to create like reaction.',
				];
			}
		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => 'Error creating like: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Check if post ID is valid.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	private function is_valid_post( $post_id ) {
		global $wpdb;
		$post = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}fcom_posts WHERE id = %d", $post_id ) );
		return (bool) $post;
	}

	/**
	 * Check if user already liked the post.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 *
	 * @return bool
	 */
	private function user_already_liked( $post_id, $user_id ) {
		global $wpdb;
		$existing_like = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT id FROM {$wpdb->prefix}fcom_post_reactions WHERE object_id = %d AND user_id = %d AND object_type = 'feed' AND type = 'like'", 
				$post_id, 
				$user_id 
			) 
		);
		return (bool) $existing_like;
	}

	/**
	 * Create like reaction.
	 *
	 * @param int $post_id Post ID.
	 * @param int $user_id User ID.
	 *
	 * @return int|false
	 */
	private function create_like_reaction( $post_id, $user_id ) {
		global $wpdb;

		$result = $wpdb->insert(
			$wpdb->prefix . 'fcom_post_reactions',
			[
				'user_id'     => $user_id,
				'object_id'   => $post_id,
				'object_type' => 'feed',
				'type'        => 'like',
				'created_at'  => current_time( 'mysql' ),
				'updated_at'  => current_time( 'mysql' ),
			],
			[ '%d', '%d', '%s', '%s', '%s', '%s' ]
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update post reactions count.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return void
	 */
	private function update_post_reactions_count( $post_id ) {
		global $wpdb;

		$count = $wpdb->get_var( 
			$wpdb->prepare( 
				"SELECT COUNT(*) FROM {$wpdb->prefix}fcom_post_reactions WHERE object_id = %d AND object_type = 'feed' AND type = 'like'", 
				$post_id 
			) 
		);

		$wpdb->update(
			$wpdb->prefix . 'fcom_posts',
			[ 'reactions_count' => (int) $count ],
			[ 'id' => $post_id ],
			[ '%d' ],
			[ '%d' ]
		);
	}

}

LikePost::get_instance();
