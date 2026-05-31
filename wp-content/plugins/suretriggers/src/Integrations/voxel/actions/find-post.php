<?php
/**
 * FindPost.
 * php version 5.6
 *
 * @category FindPost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Voxel\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;
use SureTriggers\Integrations\Voxel\Voxel;

/**
 * FindPost
 *
 * @category FindPost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class FindPost extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'Voxel';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'voxel_find_posts';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'       => __( 'Find Post', 'suretriggers' ),
			'description' => __( 'Searches for Voxel posts based on multiple criteria including search terms, post type, status, and date ranges.', 'suretriggers' ),
			'action'      => $this->action,
			'function'    => [ $this, 'action_listener' ],
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
	 * 
	 * @throws Exception Exception.
	 * 
	 * @return bool|array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$post_type   = isset( $selected_options['post_type'] ) ? $selected_options['post_type'] : 'post';
		$search_term = isset( $selected_options['search_term'] ) ? $selected_options['search_term'] : '';
		
		if ( ! class_exists( 'Voxel\Post' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Voxel plugin not found',
			];
		}

		if ( empty( $search_term ) ) {
			return [
				'status'  => 'error',
				'message' => 'Search term is required',
			];
		}

		$query_args = [
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'orderby'     => 'relevance',
			'order'       => 'DESC',
			's'           => $search_term,
		];

		$posts = get_posts( $query_args );

		if ( empty( $posts ) ) {
			return [
				'status'  => 'error',
				'message' => 'No posts found matching the criteria',
			];
		}

		$result = [
			'status'      => 'success',
			'total_found' => count( $posts ),
			'posts'       => [],
		];

		foreach ( $posts as $post ) {
			$post_data = [
				'post_id'        => $post->ID,
				'post_title'     => $post->post_title,
				'post_content'   => $post->post_content,
				'post_excerpt'   => $post->post_excerpt,
				'post_status'    => $post->post_status,
				'post_type'      => $post->post_type,
				'post_slug'      => $post->post_name,
				'post_author_id' => $post->post_author,
				'post_date'      => $post->post_date,
				'post_modified'  => $post->post_modified,
				'post_permalink' => get_permalink( $post->ID ),
				'featured_image' => get_the_post_thumbnail_url( $post->ID, 'full' ),
			];

			$author = get_user_by( 'id', $post->post_author );
			if ( $author ) {
				$post_data['author_name']  = $author->display_name;
				$post_data['author_email'] = $author->user_email;
				$post_data['author_login'] = $author->user_login;
			}

			$voxel_post = \Voxel\Post::force_get( $post->ID );
			if ( $voxel_post ) {
				$voxel_fields = [];
				foreach ( $voxel_post->get_fields() as $field ) {
					$key         = $field->get_key();
					$field_type  = $field->get_type();
					$field_value = $field->get_value();

					if ( 'taxonomy' === $field_type ) {
						if ( is_array( $field_value ) && ! empty( $field_value ) ) {
							$content = join(
								', ',
								array_map(
									function( $term ) {
										return is_object( $term ) && method_exists( $term, 'get_label' ) ? $term->get_label() : (string) $term;
									},
									$field_value
								)
							);
						} else {
							$content = (string) $field_value;
						}
					} elseif ( 'location' === $field_type ) {
						$content = ( is_array( $field_value ) && isset( $field_value['address'] ) ) ? $field_value['address'] : '';
					} else {
						$content = $field_value;
					}

					$voxel_fields[ $key ] = is_array( $content ) ? wp_json_encode( $content ) : $content;
				}

				if ( ! empty( $voxel_fields ) ) {
					$post_data['voxel_fields'] = $voxel_fields;
					foreach ( $voxel_fields as $key => $value ) {
						$post_data[ 'field_' . $key ] = $value;
					}
				}
			}

			$result['posts'][] = $post_data;
		}


		return $result;
	}

}

FindPost::get_instance();
