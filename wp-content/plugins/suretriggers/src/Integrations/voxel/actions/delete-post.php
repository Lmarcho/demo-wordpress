<?php
/**
 * DeletePost.
 * php version 5.6
 *
 * @category DeletePost
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
 * DeletePost
 *
 * @category DeletePost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeletePost extends AutomateAction {

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
	public $action = 'voxel_delete_post';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'       => __( 'Delete Post', 'suretriggers' ),
			'description' => __( 'Permanently delete a Voxel post by ID. This action cannot be undone.', 'suretriggers' ),
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
		$post_id      = isset( $selected_options['post_id'] ) ? (int) $selected_options['post_id'] : 0;
		$force_delete = isset( $selected_options['force_delete'] ) && ( true === $selected_options['force_delete'] || 'true' === $selected_options['force_delete'] );
		if ( ! class_exists( 'Voxel\Post' ) ) {
			return [
				'success' => false,
				'message' => 'Voxel plugin not found',
			];
		}

		if ( empty( $post_id ) ) {
			return [
				'success' => false,
				'message' => 'Post ID is required',
			];
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [
				'success' => false,
				'message' => 'Post not found with ID: ' . $post_id,
			];
		}

		$voxel_post = \Voxel\Post::force_get( $post_id );
		if ( ! $voxel_post ) {
			return [
				'success' => false,
				'message' => 'Voxel post not found with ID: ' . $post_id,
			];
		}

		$post_data = [
			'post_id'        => $post->ID,
			'post_title'     => $post->post_title,
			'post_type'      => $post->post_type,
			'post_status'    => $post->post_status,
			'post_author_id' => $post->post_author,
			'post_date'      => $post->post_date,
		];

		$author = get_user_by( 'id', $post->post_author );
		if ( $author ) {
			$post_data['author_name']  = $author->display_name;
			$post_data['author_email'] = $author->user_email;
			$post_data['author_login'] = $author->user_login;
		}

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
		}

		$deleted = wp_delete_post( $post_id, $force_delete );

		if ( ! $deleted ) {
			return [
				'success' => false,
				'message' => 'Failed to delete post with ID: ' . $post_id,
			];
		}

		$deletion_type = $force_delete ? 'permanently_deleted' : 'moved_to_trash';

		return [
			'success'       => true,
			'message'       => sprintf( 
				'Post "%s" (ID: %d) has been %s successfully', 
				$post_data['post_title'], 
				$post_id, 
				$force_delete ? 'permanently deleted' : 'moved to trash'
			),
			'deletion_type' => $deletion_type,
			'deleted_post'  => $post_data,
			'post_id'       => $post_id,
			'post_title'    => $post_data['post_title'],
			'post_type'     => $post_data['post_type'],
			'was_permanent' => $force_delete,
		];
	}
}

DeletePost::get_instance();
