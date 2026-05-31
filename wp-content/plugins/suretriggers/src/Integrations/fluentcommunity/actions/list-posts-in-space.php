<?php
/**
 * ListPostsInSpace.
 * php version 5.6
 *
 * @category ListPostsInSpace
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
 * ListPostsInSpace
 *
 * @category ListPostsInSpace
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListPostsInSpace extends AutomateAction {

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
	public $action = 'fc_list_posts_in_space';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Posts in a Specific Space', 'suretriggers' ),
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

		$space_id = isset( $selected_options['space_id'] ) ? (int) sanitize_text_field( $selected_options['space_id'] ) : 0;
		$limit    = isset( $selected_options['limit'] ) ? (int) sanitize_text_field( $selected_options['limit'] ) : 10;

		if ( ! $this->is_valid_space( $space_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid or non-existent Space ID.',
			];
		}

		try {
			$posts = $this->get_space_posts( $space_id, $limit );
			if ( empty( $posts ) ) {
				return [
					'status'  => 'success',
					'message' => 'No posts found in the specified space.',
					'posts'   => [],
				];
			}

			return [
				'status'      => 'success',
				'message'     => 'Posts fetched successfully from space.',
				'posts'       => $posts,
				'space_id'    => $space_id,
				'total_found' => count( $posts ),
			];
		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => 'Error fetching posts: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Check if space ID is valid.
	 *
	 * @param int $space_id Space ID.
	 *
	 * @return bool
	 */
	private function is_valid_space( $space_id ) {
		global $wpdb;
		$space = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}fcom_spaces WHERE ID = %d", $space_id ) );
		return (bool) $space;
	}

	/**
	 * Get posts from a specific space.
	 *
	 * @param int $space_id Space ID.
	 * @param int $limit    Limit number of posts.
	 *
	 * @return array
	 */
	private function get_space_posts( $space_id, $limit = 10 ) {
		global $wpdb;

		$space_id = (int) $space_id;
		$limit    = max( 1, min( 100, (int) $limit ) );

		$feeds = $wpdb->get_results( $wpdb->prepare( "SELECT f.* FROM {$wpdb->prefix}fcom_posts f WHERE f.space_id = %d AND f.status = 'published' ORDER BY f.created_at DESC LIMIT %d", $space_id, $limit ), ARRAY_A );

		if ( ! $feeds ) {
			return [];
		}

		$posts = [];
		foreach ( $feeds as $feed ) {
			$user    = get_userdata( $feed['user_id'] );
			$posts[] = [
				'id'           => $feed['id'],
				'title'        => ! empty( $feed['title'] ) ? $feed['title'] : '',
				'message'      => ! empty( $feed['message'] ) ? $feed['message'] : '',
				'type'         => ! empty( $feed['type'] ) ? $feed['type'] : 'feed',
				'space_id'     => $feed['space_id'],
				'user_id'      => $feed['user_id'],
				'author_name'  => $user ? $user->display_name : '',
				'author_email' => $user ? $user->user_email : '',
				'status'       => $feed['status'],
				'created_at'   => $feed['created_at'],
				'updated_at'   => $feed['updated_at'],
				'permalink'    => $this->get_post_permalink( $feed['id'] ),
			];
		}

		return $posts;
	}

	/**
	 * Get post permalink.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	private function get_post_permalink( $post_id ) {
		if ( class_exists( '\FluentCommunity\App\Models\Feed' ) ) {
			try {
				$feed = \FluentCommunity\App\Models\Feed::find( $post_id );
				if ( $feed && is_object( $feed ) && method_exists( $feed, 'getPermalink' ) ) {
					return $feed->getPermalink();
				}
			} catch ( Exception $e ) {
				// Fallback if FluentCommunity model is not available.
				unset( $e );
			}
		}

		return home_url( "/community/feed/{$post_id}" );
	}

}

ListPostsInSpace::get_instance();
