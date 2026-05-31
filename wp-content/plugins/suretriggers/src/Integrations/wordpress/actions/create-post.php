<?php
/**
 * CreatePost.
 * php version 5.6
 *
 * @category CreatePost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Wordpress\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * CreatePost
 *
 * @category CreatePost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreatePost extends AutomateAction {

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
	public $action = 'create_update_post';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Post: Create a Post', 'suretriggers' ),
			'action'   => 'create_update_post',
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
	 * @return array|bool|object
	 * @throws Exception Error.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$result_arr     = [];
		$is_post_update = false;
		foreach ( $fields as $field ) {
			if ( isset( $field['name'] ) && isset( $selected_options[ $field['name'] ] ) && ( trim( wp_strip_all_tags( $selected_options[ $field['name'] ] ) ) !== '' ) ) {
				if ( 'post_content' === $field['name'] ) {
					$html_content                 = $selected_options[ $field['name'] ];
					$patterns                     = [
						'/<head\b[^>]*>.*?<\/head>/is',
						'/<script\b[^>]*>.*?<\/script>/is',
						'/<style\b[^>]*>.*?<\/style>/is', 
					];
					$html_content                 = preg_replace( $patterns, '', $html_content );
					$result_arr[ $field['name'] ] = $html_content;
				} elseif ( 'post_date' === $field['name'] ) {
					// Handle post_date field for scheduling.
					$result_arr[ $field['name'] ] = $selected_options[ $field['name'] ];
				} else {
					$result_arr[ $field['name'] ] = $selected_options[ $field['name'] ];
				}
			}           
		}

		$meta_array = [];

		if ( ! empty( $selected_options['post_meta'] ) ) {
			foreach ( $selected_options['post_meta'] as $meta ) {
				$meta_key                = $meta['metaKey'];
				$meta_value              = $meta['metaValue'];
				$meta_array[ $meta_key ] = $meta_value;
			}
			$result_arr['meta_input'] = $meta_array;
		}
		
		if ( ! empty( $selected_options['post_url'] ) ) {
			$url   = $selected_options['post_url'];
			$parts = explode( '/', $url );
			$parts = array_values( array_filter( $parts ) );
			$slug  = $parts[ count( $parts ) - 1 ]; 
		
			$post_exists = get_page_by_path( $slug, OBJECT, $selected_options['post_type'] );
		
			if ( $post_exists ) {
				$result_arr['ID'] = $post_exists->ID;
				wp_update_post( $result_arr );
				$last_response  = get_post( $post_exists->ID );
				$post_id        = $post_exists->ID;
				$is_post_update = true;
			} else {
				return [
					'status'  => 'error',
					'message' => 'The URL entered is incorrect. Please provide the correct URL for the post.', 
				];
			}
		} elseif ( ! empty( $selected_options['post_id'] ) ) {
			$post_id     = absint( $selected_options['post_id'] );
			$post_exists = get_post( $post_id );
		
			if ( $post_exists && $post_exists instanceof \WP_Post ) {
				$result_arr['ID'] = $post_id;
				wp_update_post( $result_arr );
				$last_response  = get_post( $post_id );
				$is_post_update = true;
			} else {
				return [
					'status'  => 'error',
					'message' => 'Invalid Post ID provided. No post found with that ID.', 
				];
			}
		} else {
			/**
			 * Post ID.
			 *
			 * @var int|\WP_Error $post_id
			 */
			$post_id = wp_insert_post( $result_arr );
			if ( is_wp_error( $post_id ) || 0 === $post_id ) {
				$this->set_error(
					[
						'post_data' => $result_arr,
						'msg'       => __( 'Failed to insert post!', 'suretriggers' ), 
					]
				);
				return false;
			}
		}

		// Handle scheduled posts.
		if ( isset( $selected_options['post_status'] ) && 'future' === $selected_options['post_status'] ) {
			if ( ! empty( $selected_options['post_date'] ) ) {
				// Validate date is in future.
				$schedule_date = strtotime( $selected_options['post_date'] );
				$current_time  = time();
				
				if ( $schedule_date > $current_time ) {
					// Update post with scheduled date.
					wp_update_post(
						[
							'ID'          => $post_id,
							'post_date'   => $selected_options['post_date'],
							'post_status' => 'future',
						]
					);
					// Post scheduled successfully.
				} else {
					// Date is in past, publish immediately.
					wp_update_post(
						[
							'ID'          => $post_id,
							'post_status' => 'publish',
						]
					);
					// Schedule date in past, post published immediately.
				}
			} else {
				// If no date provided, change status to draft.
				wp_update_post(
					[
						'ID'          => $post_id,
						'post_status' => 'draft',
					]
				);
				// No schedule date provided, post saved as draft.
			}
		}       

		$last_response     = get_post( $post_id );
		$response_taxonomy = '';
		$taxonomy_terms    = [];

		// Set taxonomy terms for new post.
		if ( isset( $selected_options['taxonomy'] ) && isset( $selected_options['taxonomy_term'] ) ) {

			$terms                = [];
			$taxonomy             = $selected_options['taxonomy'];
			$taxonomy_terms_input = $selected_options['taxonomy_term'];

			// Handle different input formats.
			if ( is_string( $taxonomy_terms_input ) ) {
				$taxonomy_terms_input = array_map( 'trim', explode( ',', $taxonomy_terms_input ) );
			} elseif ( ! is_array( $taxonomy_terms_input ) ) {
				// Convert any other format to array.
				$taxonomy_terms_input = [ $taxonomy_terms_input ];
			}

			foreach ( $taxonomy_terms_input as $term ) {
				if ( is_array( $term ) && isset( $term['value'] ) ) {
					// If term value is numeric, it's an existing term ID.
					if ( is_numeric( $term['value'] ) ) {
						$terms[] = (int) $term['value'];
					} else {
						// If term value is text, check if term exists or create new one.
						$term_name     = sanitize_text_field( $term['value'] );
						$existing_term = get_term_by( 'name', $term_name, $taxonomy );
						
						if ( $existing_term ) {
							// Term exists, use its ID.
							$terms[] = (int) $existing_term->term_id;
						} else {
							// Term doesn't exist, create new term.
							$new_term = wp_insert_term( $term_name, $taxonomy );
							if ( ! is_wp_error( $new_term ) ) {
								$terms[] = (int) $new_term['term_id'];
							}
						}
					}
				} elseif ( is_string( $term ) || is_numeric( $term ) ) {
					// Handle direct string/numeric values.
					if ( is_numeric( $term ) ) {
						$terms[] = (int) $term;
					} else {
						$term_name     = sanitize_text_field( $term );
						$existing_term = get_term_by( 'name', $term_name, $taxonomy );
						
						if ( $existing_term ) {
							$terms[] = (int) $existing_term->term_id;
						} else {
							$new_term = wp_insert_term( $term_name, $taxonomy );
							if ( ! is_wp_error( $new_term ) ) {
								$terms[] = (int) $new_term['term_id'];
							}
						}
					}
				}
			}
			
			if ( ! empty( $terms ) ) {
				if ( $is_post_update ) {
					// If is post update then append the terms to the existing terms.
					wp_set_object_terms( $post_id, $terms, $taxonomy, true );
				} else {
					// If is post create then set the terms.
					wp_set_object_terms( $post_id, $terms, $taxonomy, false );
				}
			}
			
			$response_taxonomy = get_object_taxonomies( (string) get_post_type( $post_id ) );
			foreach ( $response_taxonomy as $taxonomy_name ) {
				$terms = wp_get_post_terms( $post_id, $taxonomy_name );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$taxonomy_terms[] = $term;
					}
				}           
			}
		}

		// Handle multiple taxonomy terms mapping - enhanced map button functionality.
		if ( isset( $selected_options['taxonomy_terms_map'] ) && is_array( $selected_options['taxonomy_terms_map'] ) ) {
			foreach ( $selected_options['taxonomy_terms_map'] as $taxonomy_mapping ) {
				if ( isset( $taxonomy_mapping['taxonomy'] ) && isset( $taxonomy_mapping['terms'] ) ) {
					$taxonomy_name = $taxonomy_mapping['taxonomy'];
					$mapped_terms  = [];
					
					// Process mapped terms - support creating new terms if they don't exist.
					if ( is_array( $taxonomy_mapping['terms'] ) ) {
						foreach ( $taxonomy_mapping['terms'] as $term_data ) {
							if ( is_array( $term_data ) && isset( $term_data['value'] ) ) {
								$term_value = $term_data['value'];
							} elseif ( is_string( $term_data ) || is_numeric( $term_data ) ) {
								$term_value = $term_data;
							} else {
								continue;
							}
							
							if ( is_numeric( $term_value ) ) {
								// Existing term ID.
								$mapped_terms[] = (int) $term_value;
							} else {
								// Term name - check if exists or create new.
								$term_name     = sanitize_text_field( $term_value );
								$existing_term = get_term_by( 'name', $term_name, $taxonomy_name );
								
								if ( $existing_term ) {
									$mapped_terms[] = (int) $existing_term->term_id;
								} else {
									// Create new term.
									$new_term = wp_insert_term( $term_name, $taxonomy_name );
									if ( ! is_wp_error( $new_term ) ) {
										$mapped_terms[] = (int) $new_term['term_id'];
									}
								}
							}
						}
					}
					
					if ( ! empty( $mapped_terms ) ) {
						// Set or append terms based on configuration.
						$append_terms = isset( $taxonomy_mapping['append'] ) ? $taxonomy_mapping['append'] : false;
						if ( $is_post_update && $append_terms ) {
							wp_set_object_terms( $post_id, $mapped_terms, $taxonomy_name, true );
						} else {
							wp_set_object_terms( $post_id, $mapped_terms, $taxonomy_name, false );
						}
					}
				}
			}
			
			// Update taxonomy terms response for mapped taxonomies.
			$response_taxonomy = get_object_taxonomies( (string) get_post_type( $post_id ) );
			foreach ( $response_taxonomy as $taxonomy_name ) {
				$terms = wp_get_post_terms( $post_id, $taxonomy_name );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$taxonomy_terms[] = $term;
					}
				}           
			}
		}

		if ( ! empty( $selected_options['featured_image'] ) ) {
			$image_url = $selected_options['featured_image'];
			require_once ABSPATH . 'wp-admin/includes/media.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/image.php';

			$existing_media_id = absint( attachment_url_to_postid( $image_url ) ); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.attachment_url_to_postid_attachment_url_to_postid

			if ( 0 !== $existing_media_id ) {
				$attachment_id = $existing_media_id;
			} else {
				$attachment_id = media_sideload_image( $image_url, $post_id, null, 'id' );
			}
			if ( isset( $selected_options['featured_image'] ) && ! $attachment_id || is_wp_error( $attachment_id ) ) {

				return (object) [
					$last_response,
					'taxonomy_term'      => $taxonomy_terms,
					'featured_image_url' => 'Failed to set featured image', 
				];
			}
			
			set_post_thumbnail( $post_id, (int) $attachment_id );
		}
		$featured_image_url = get_the_post_thumbnail_url( $post_id, 'full' );

		return (object) [
			$last_response,
			'taxonomy_term'      => $taxonomy_terms,
			'featured_image_url' => $featured_image_url,
		];

	}
}

CreatePost::get_instance();
