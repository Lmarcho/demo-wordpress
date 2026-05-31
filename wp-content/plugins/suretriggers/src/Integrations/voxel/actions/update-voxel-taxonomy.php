<?php
/**
 * UpdateVoxelTaxonomy.
 * php version 5.6
 *
 * @category UpdateVoxelTaxonomy
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

/**
 * UpdateVoxelTaxonomy
 *
 * @category UpdateVoxelTaxonomy
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateVoxelTaxonomy extends AutomateAction {

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
	public $action = 'voxel_update_taxonomy';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Voxel Taxonomy', 'suretriggers' ),
			'action'   => 'voxel_update_taxonomy',
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
	 * 
	 * @throws Exception Exception.
	 * 
	 * @return bool|array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( 'Voxel\Post' ) || ! class_exists( 'Voxel\Post_Type' ) ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Voxel classes not found', 'suretriggers' ),
			];
		}

		$post_id = isset( $selected_options['post_id'] ) ? absint( $selected_options['post_id'] ) : 0;
		if ( ! $post_id ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Post ID is required', 'suretriggers' ),
			];
		}

		$post = \Voxel\Post::force_get( $post_id );
		if ( ! $post ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Post not found', 'suretriggers' ),
			];
		}

		$taxonomy = isset( $selected_options['taxonomy'] ) ? sanitize_text_field( $selected_options['taxonomy'] ) : '';
		if ( empty( $taxonomy ) ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Taxonomy is required', 'suretriggers' ),
			];
		}

		$terms = isset( $selected_options['terms'] ) ? $selected_options['terms'] : [];
		if ( ! is_array( $terms ) ) {
			$terms = array_map( 'trim', explode( ',', $terms ) );
		}

		$post_type_string = get_post_type( $post->get_id() );
		if ( false === $post_type_string ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Invalid post type', 'suretriggers' ),
			];
		}
		
		$wp_taxonomies  = get_object_taxonomies( $post_type_string );
		$is_wp_taxonomy = in_array( $taxonomy, $wp_taxonomies );
		
		if ( $is_wp_taxonomy ) {
			return $this->update_wordpress_taxonomy( $post_id, $taxonomy, $terms );
		}
		
		$post_type_obj = \Voxel\Post_Type::get( $post_type_string );
		if ( ! $post_type_obj ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Post type not found', 'suretriggers' ),
			];
		}
		
		return $this->update_voxel_taxonomy_field( $post, $post_type_obj, $taxonomy, $terms, $post_id );
	}

	/**
	 * Update standard WordPress taxonomy.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $taxonomy Taxonomy name.
	 * @param array  $terms Array of terms.
	 * @return array
	 */
	private function update_wordpress_taxonomy( $post_id, $taxonomy, $terms ) {
		$term_ids      = [];
		$created_terms = [];
		
		foreach ( $terms as $term ) {
			if ( is_numeric( $term ) ) {
				$term_obj = get_term( absint( $term ), $taxonomy );
				if ( $term_obj && ! is_wp_error( $term_obj ) ) {
					$term_ids[] = $term_obj->term_id;
				}
			} else {
				$term_obj = get_term_by( 'slug', sanitize_title( $term ), $taxonomy );
				if ( ! $term_obj ) {
					$term_obj = get_term_by( 'name', sanitize_text_field( $term ), $taxonomy );
				}
				
				if ( $term_obj ) {
					$term_ids[] = $term_obj->term_id;
				} else {
					// Term doesn't exist, create new one.
					$new_term = wp_insert_term( sanitize_text_field( $term ), $taxonomy );
					if ( ! is_wp_error( $new_term ) && isset( $new_term['term_id'] ) ) {
						$term_ids[]      = $new_term['term_id'];
						$created_terms[] = sanitize_text_field( $term );
					}
				}
			}
		}

		if ( empty( $term_ids ) ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'No valid terms found', 'suretriggers' ),
			];
		}

		$result = wp_set_object_terms( $post_id, $term_ids, $taxonomy, false );
		
		if ( is_wp_error( $result ) ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Failed to update taxonomy: ', 'suretriggers' ) . $result->get_error_message(),
			];
		}

		return [
			'success'       => true,
			'message'       => esc_attr__( 'WordPress taxonomy updated successfully', 'suretriggers' ),
			'post_id'       => $post_id,
			'taxonomy'      => $taxonomy,
			'terms_updated' => count( $term_ids ),
			'terms_created' => $created_terms,
		];
	}

	/**
	 * Update Voxel taxonomy field.
	 *
	 * @param object $post Voxel post object.
	 * @param object $post_type_obj Voxel post type object.
	 * @param string $taxonomy Field name.
	 * @param array  $terms Array of terms.
	 * @param int    $post_id Post ID.
	 * @return array
	 */
	private function update_voxel_taxonomy_field( $post, $post_type_obj, $taxonomy, $terms, $post_id ) {
		$post_fields = null;
		if ( is_object( $post_type_obj ) && method_exists( $post_type_obj, 'get_fields' ) ) {
			$post_fields = $post_type_obj->get_fields();
		}
		
		if ( ! is_array( $post_fields ) ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Unable to get post fields', 'suretriggers' ),
			];
		}

		foreach ( $post_fields as $key => $field ) {
			if ( is_object( $field ) && method_exists( $field, 'get_props' ) ) {
				$post_fields[ $key ] = $field->get_props();
			}
		}

		$taxonomy_field     = null;
		$taxonomy_field_key = null;
		
		foreach ( $post_fields as $field_key => $field ) {
			if ( isset( $field['type'] ) && 'taxonomy' === $field['type'] ) {
				if ( $field_key === $taxonomy || 
					( isset( $field['label'] ) && $field['label'] === $taxonomy ) ||
					( isset( $field['taxonomy'] ) && $field['taxonomy'] === $taxonomy ) ) {
					$taxonomy_field     = $field;
					$taxonomy_field_key = $field_key;
					break;
				}
			}
		}

		if ( ! $taxonomy_field ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Voxel taxonomy field not found', 'suretriggers' ),
			];
		}

		$post_field = null;
		if ( is_object( $post ) && method_exists( $post, 'get_field' ) ) {
			$post_field = $post->get_field( $taxonomy_field_key );
		}
		if ( ! $post_field ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Voxel post field not found', 'suretriggers' ),
			];
		}

		$term_ids      = [];
		$created_terms = [];
		foreach ( $terms as $term ) {
			if ( is_numeric( $term ) ) {
				$term_obj = get_term( absint( $term ) );
				if ( $term_obj && ! is_wp_error( $term_obj ) ) {
					$term_ids[] = $term_obj->term_id;
				}
			} else {
				$term_obj = get_term_by( 'slug', sanitize_title( $term ), $taxonomy_field['taxonomy'] );
				if ( ! $term_obj ) {
					$term_obj = get_term_by( 'name', sanitize_text_field( $term ), $taxonomy_field['taxonomy'] );
				}
				
				if ( $term_obj ) {
					$term_ids[] = $term_obj->term_id;
				} else {
					// Term doesn't exist, create new one in the Voxel taxonomy.
					$new_term = wp_insert_term( sanitize_text_field( $term ), $taxonomy_field['taxonomy'] );
					if ( ! is_wp_error( $new_term ) && isset( $new_term['term_id'] ) ) {
						$term_ids[]      = $new_term['term_id'];
						$created_terms[] = sanitize_text_field( $term );
					}
				}
			}
		}

		if ( empty( $term_ids ) ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'No valid terms found', 'suretriggers' ),
			];
		}

		try {
			$post_field->update( $term_ids );
			
			return [
				'success'       => true,
				'message'       => esc_attr__( 'Voxel taxonomy field updated successfully', 'suretriggers' ),
				'post_id'       => $post_id,
				'taxonomy'      => $taxonomy,
				'terms_updated' => count( $term_ids ),
				'terms_created' => $created_terms,
			];
		} catch ( Exception $e ) {
			return [
				'success' => false,
				'message' => esc_attr__( 'Failed to update Voxel taxonomy: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}

}

UpdateVoxelTaxonomy::get_instance();
