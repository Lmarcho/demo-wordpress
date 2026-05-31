<?php
/**
 * AddTagToPost.
 * php version 5.6
 *
 * @category AddTagToPost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Wordpress\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use WP_User;
use Exception;

/**
 * AddTagToPost
 *
 * @category AddTagToPost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddTagToPost extends AutomateAction {

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
	public $action = 'add_tag_to_post';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Tag To Post', 'suretriggers' ),
			'action'   => 'add_tag_to_post',
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
	 * @param array $selected_options selectedOptions.
	 * @return array|string
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$tags    = $selected_options['tag'];
		$post_id = $selected_options['post_id'];

		$last_response = get_post( $post_id );
		if ( ! $last_response ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid post ID or post not found.',
			];
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid post ID or post type not found.',
			];
		}

		$tag_ids = [];
		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag ) {
				if ( isset( $tag['value'] ) ) {
					$tag_ids[] = (int) $tag['value'];
				}
			}
		} else {
			$tag_ids[] = (int) $tags;
		}

		if ( ! empty( $tag_ids ) ) {
			$result = wp_set_object_terms( $post_id, $tag_ids, 'post_tag', true );
			if ( is_wp_error( $result ) ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to add tags: ' . $result->get_error_message(),
				];
			}
		}

		$response_taxonomy = get_object_taxonomies( $post_type );
		$taxonomy_terms    = [];
		foreach ( $response_taxonomy as $taxonomy_name ) {
			$terms = wp_get_post_terms( $post_id, $taxonomy_name );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$taxonomy_terms[] = $term;
				}
			}           
		}

		return [
			'last_response'  => $last_response,
			'taxonomy_terms' => $taxonomy_terms,
		];
	}
}

AddTagToPost::get_instance();
