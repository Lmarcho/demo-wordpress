<?php
/**
 * AddAttributeTerms.
 * php version 5.6
 *
 * @category AddAttributeTerms
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;

/**
 * AddAttributeTerms
 *
 * @category AddAttributeTerms
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddAttributeTerms extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WooCommerce';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wc_add_attribute_terms';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Attribute Terms', 'suretriggers' ),
			'action'   => 'wc_add_attribute_terms',
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
	 * @return void|array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		// Check if WooCommerce is active.
		if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_get_attribute' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce not available', 'suretriggers' ),
			];
		}

		// Validate required fields.
		foreach ( $fields as $field ) {
			if ( array_key_exists( 'validationProps', $field ) && empty( $selected_options[ $field['name'] ] ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Required field is missing: ', 'suretriggers' ) . $field['name'],
				];
			}
		}

		// Get parameters.
		$attribute_input   = ! empty( $selected_options['attribute_slug'] ) ? sanitize_text_field( $selected_options['attribute_slug'] ) : '';
		$terms_input       = ! empty( $selected_options['terms'] ) ? $selected_options['terms'] : '';
		$term_descriptions = ! empty( $selected_options['term_descriptions'] ) ? $selected_options['term_descriptions'] : '';
		$term_slugs        = ! empty( $selected_options['term_slugs'] ) ? $selected_options['term_slugs'] : '';
		
		// Backward compatibility - check for legacy fields.
		if ( empty( $terms_input ) && ! empty( $selected_options['term_name'] ) ) {
			$terms_input = $selected_options['term_name'];
		}
		
		// Determine if input is ID or slug.
		$attribute_id   = 0;
		$attribute_slug = '';
		
		if ( is_numeric( $attribute_input ) ) {
			$attribute_id = intval( $attribute_input );
		} else {
			$attribute_slug = $attribute_input;
		}

		// Validate attribute identification.
		if ( empty( $attribute_id ) && empty( $attribute_slug ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Attribute ID or Attribute Slug is required', 'suretriggers' ),
			];
		}

		// Validate terms input.
		if ( empty( $terms_input ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Terms are required', 'suretriggers' ),
			];
		}

		try {
			
			// Get attribute details.
			$attribute     = null;
			$taxonomy_name = '';
			
			if ( ! empty( $attribute_id ) ) {
				$attribute = wc_get_attribute( $attribute_id );
				if ( $attribute && isset( $attribute->name ) ) {
					$taxonomy_name = wc_attribute_taxonomy_name( $attribute->name );
				} elseif ( $attribute && isset( $attribute->attribute_name ) ) {
					$taxonomy_name = wc_attribute_taxonomy_name( $attribute->attribute_name );
				}
			} elseif ( ! empty( $attribute_slug ) ) {
				// Check if it's already a taxonomy name (pa_*).
				if ( strpos( $attribute_slug, 'pa_' ) === 0 ) {
					$taxonomy_name  = $attribute_slug;
					$attribute_name = str_replace( 'pa_', '', $attribute_slug );
					// Find attribute by name.
					$attributes = wc_get_attribute_taxonomies();
					foreach ( $attributes as $attr ) {
						if ( $attr->attribute_name === $attribute_name ) {
							$attribute = $attr;
							break;
						}
					}
				} else {
					// Find attribute by slug.
					$attributes = wc_get_attribute_taxonomies();
					foreach ( $attributes as $attr ) {
						if ( $attr->attribute_name === $attribute_slug ) {
							$attribute     = $attr;
							$taxonomy_name = wc_attribute_taxonomy_name( $attribute_slug );
							break;
						}
					}
				}
			}

			if ( ! $attribute && empty( $taxonomy_name ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Attribute not found', 'suretriggers' ),
				];
			}

			// If we don't have taxonomy name but have attribute, generate it.
			if ( empty( $taxonomy_name ) && $attribute ) {
				if ( isset( $attribute->name ) ) {
					$taxonomy_name = wc_attribute_taxonomy_name( $attribute->name );
				} elseif ( isset( $attribute->attribute_name ) ) {
					$taxonomy_name = wc_attribute_taxonomy_name( $attribute->attribute_name );
				}
			}

			// Ensure taxonomy is registered.
			if ( ! taxonomy_exists( $taxonomy_name ) ) {
				$taxonomy_label = '';
				if ( $attribute && isset( $attribute->label ) ) {
					$taxonomy_label = $attribute->label;
				} elseif ( $attribute && isset( $attribute->attribute_label ) ) {
					$taxonomy_label = $attribute->attribute_label;
				} else {
					$taxonomy_label = ucfirst( str_replace( 'pa_', '', $taxonomy_name ) );
				}
				
				$is_public = false;
				if ( $attribute && isset( $attribute->public ) ) {
					$is_public = (bool) $attribute->public;
				} elseif ( $attribute && isset( $attribute->attribute_public ) ) {
					$is_public = (bool) $attribute->attribute_public;
				}
				
				register_taxonomy(
					$taxonomy_name,
					'product',
					[
						'labels'       => [
							'name' => $taxonomy_label,
						],
						'hierarchical' => false,
						'public'       => $is_public,
						'show_ui'      => false,
						'query_var'    => true,
						'rewrite'      => false,
					]
				);
			}

			// Parse terms input - can be comma-separated string or array.
			$terms = [];
			if ( is_string( $terms_input ) ) {
				$terms = array_map( 'trim', explode( ',', $terms_input ) );
			} elseif ( is_array( $terms_input ) ) {
				$terms = array_map( 'trim', $terms_input );
			}

			// Remove empty terms.
			$terms = array_filter(
				$terms,
				function( $term ) {
					return ! empty( $term );
				}
			);

			// Parse term descriptions - one per line.
			$descriptions = [];
			if ( ! empty( $term_descriptions ) ) {
				$descriptions = array_map( 'trim', explode( "\n", $term_descriptions ) );
			}

			// Parse term slugs - comma-separated.
			$custom_slugs = [];
			if ( ! empty( $term_slugs ) ) {
				if ( is_string( $term_slugs ) ) {
					$custom_slugs = array_map( 'trim', explode( ',', $term_slugs ) );
				} elseif ( is_array( $term_slugs ) ) {
					$custom_slugs = array_map( 'trim', $term_slugs );
				}
			}

			if ( empty( $terms ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'No valid terms provided', 'suretriggers' ),
				];
			}

			$created_terms  = [];
			$existing_terms = [];
			$failed_terms   = [];

			foreach ( $terms as $index => $term_name ) {
				$term_name = sanitize_text_field( $term_name );

				// Check if term already exists.
				$existing_term = get_term_by( 'name', $term_name, $taxonomy_name );
				if ( $existing_term ) {
					$existing_terms[] = [
						'term_id' => $existing_term->term_id,
						'name'    => $existing_term->name,
						'slug'    => $existing_term->slug,
					];
					continue;
				}

				// Prepare term arguments.
				$term_args = [];
				
				// Add description if provided for this specific term.
				if ( ! empty( $descriptions[ $index ] ) ) {
					$term_args['description'] = sanitize_textarea_field( $descriptions[ $index ] );
				} elseif ( ! empty( $selected_options['term_description'] ) ) {
					// Backward compatibility.
					$term_args['description'] = sanitize_textarea_field( $selected_options['term_description'] );
				}
				
				// Add custom slug if provided for this specific term.
				if ( ! empty( $custom_slugs[ $index ] ) ) {
					$term_args['slug'] = sanitize_title( $custom_slugs[ $index ] );
				}

				// Create the term.
				$term_result = wp_insert_term( $term_name, $taxonomy_name, $term_args );

				if ( is_wp_error( $term_result ) ) {
					$failed_terms[] = [
						'name'  => $term_name,
						'error' => $term_result->get_error_message(),
					];
				} else {
					$new_term = get_term( $term_result['term_id'], $taxonomy_name );
					if ( $new_term && ! is_wp_error( $new_term ) ) {
						$created_terms[] = [
							'term_id' => $new_term->term_id,
							'name'    => $new_term->name,
							'slug'    => $new_term->slug,
						];
					}
				}
			}

			// Get all terms for this attribute.
			$all_terms = get_terms(
				[
					'taxonomy'   => $taxonomy_name,
					'hide_empty' => false,
				]
			);

			$all_terms_data = [];
			if ( ! is_wp_error( $all_terms ) && is_array( $all_terms ) ) {
				foreach ( $all_terms as $term ) {
					$all_terms_data[] = [
						'term_id' => $term->term_id,
						'name'    => $term->name,
						'slug'    => $term->slug,
						'count'   => $term->count,
					];
				}
			}

			// Prepare summary.
			$summary = [
				'created_count'  => count( $created_terms ),
				'existing_count' => count( $existing_terms ),
				'failed_count'   => count( $failed_terms ),
				'total_count'    => count( $all_terms_data ),
			];

			return [
				'status'          => 'success',
				'message'         => sprintf( 
					__( 'Processed %1$d terms: %2$d created, %3$d existing, %4$d failed', 'suretriggers' ), 
					count( $terms ), 
					$summary['created_count'], 
					$summary['existing_count'], 
					$summary['failed_count'] 
				),
				'attribute_id'    => $attribute ? ( isset( $attribute->id ) ? $attribute->id : ( isset( $attribute->attribute_id ) ? $attribute->attribute_id : null ) ) : null,
				'attribute_name'  => $attribute ? ( isset( $attribute->name ) ? $attribute->name : ( isset( $attribute->attribute_name ) ? $attribute->attribute_name : str_replace( 'pa_', '', $taxonomy_name ) ) ) : str_replace( 'pa_', '', $taxonomy_name ),
				'attribute_label' => $attribute ? ( isset( $attribute->label ) ? $attribute->label : ( isset( $attribute->attribute_label ) ? $attribute->attribute_label : ucfirst( str_replace( 'pa_', '', $taxonomy_name ) ) ) ) : ucfirst( str_replace( 'pa_', '', $taxonomy_name ) ),
				'taxonomy_name'   => $taxonomy_name,
				'created_terms'   => $created_terms,
				'existing_terms'  => $existing_terms,
				'failed_terms'    => $failed_terms,
				'all_terms'       => $all_terms_data,
				'summary'         => $summary,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Error adding attribute terms: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

AddAttributeTerms::get_instance();
