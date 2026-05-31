<?php
/**
 * AddProductAttributes.
 * php version 5.6
 *
 * @category AddProductAttributes
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * AddProductAttributes
 *
 * @category AddProductAttributes
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddProductAttributes extends AutomateAction {

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
	public $action = 'wc_add_product_attributes';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Product Attributes', 'suretriggers' ),
			'action'   => 'wc_add_product_attribute',
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
		if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_get_product' ) ) {
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
		$product_id = ! empty( $selected_options['product_id'] ) ? intval( $selected_options['product_id'] ) : 0;
		$attributes = ! empty( $selected_options['attributes'] ) ? $selected_options['attributes'] : '';
		
		// Legacy support.
		if ( empty( $attributes ) && ! empty( $selected_options['product_attributes'] ) ) {
			$attributes = $selected_options['product_attributes'];
		}

		if ( empty( $product_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product ID is required', 'suretriggers' ),
			];
		}

		if ( empty( $attributes ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Attributes are required', 'suretriggers' ),
			];
		}

		try {
			// Get the product.
			$product = wc_get_product( $product_id );

			if ( ! $product instanceof \WC_Product ) {
				return [
					'status'  => 'error',
					'message' => __( 'Invalid product ID provided', 'suretriggers' ),
				];
			}

			// Get existing product attributes.
			$existing_attributes = $product->get_attributes();
			$updated_attributes  = $existing_attributes;
			$added_attributes    = [];
			$updated_existing    = [];
			$failed_attributes   = [];

			// Parse attributes input.
			if ( is_string( $attributes ) ) {
				// Try to decode JSON if it's a string.
				$decoded = json_decode( $attributes, true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$attributes = $decoded;
				} else {
					return [
						'status'  => 'error',
						'message' => __( 'Invalid attributes format. Expected JSON array or object.', 'suretriggers' ),
					];
				}
			}

			if ( ! is_array( $attributes ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Attributes must be an array', 'suretriggers' ),
				];
			}

			foreach ( $attributes as $index => $attribute_data ) {
				// Validate attribute data structure.
				if ( ! is_array( $attribute_data ) ) {
					$failed_attributes[] = [
						'data'  => $attribute_data,
						'error' => __( 'Invalid attribute data format', 'suretriggers' ),
					];
					continue;
				}

				// Extract attribute information.
				$attribute_name   = ! empty( $attribute_data['name'] ) ? sanitize_text_field( $attribute_data['name'] ) : '';
				$attribute_slug   = ! empty( $attribute_data['slug'] ) ? sanitize_text_field( $attribute_data['slug'] ) : '';
				$attribute_values = ! empty( $attribute_data['values'] ) ? $attribute_data['values'] : '';
				$is_visible       = isset( $attribute_data['visible'] ) ? (bool) $attribute_data['visible'] : false;
				$is_variation     = isset( $attribute_data['variation'] ) ? (bool) $attribute_data['variation'] : false;
				$is_taxonomy      = isset( $attribute_data['taxonomy'] ) ? (bool) $attribute_data['taxonomy'] : false;

				// Legacy field support - check all possible field name variations.
				if ( empty( $attribute_name ) && ! empty( $attribute_data['attribute'] ) ) {
					$attribute_name = sanitize_text_field( $attribute_data['attribute'] );
				}
				if ( empty( $attribute_name ) && ! empty( $attribute_data['attribute_name'] ) ) {
					$attribute_name = sanitize_text_field( $attribute_data['attribute_name'] );
				}
				
				if ( empty( $attribute_slug ) && ! empty( $attribute_data['attribute_slug'] ) ) {
					$attribute_slug = sanitize_text_field( $attribute_data['attribute_slug'] );
				}
				
				if ( empty( $attribute_values ) && ! empty( $attribute_data['attribute_value'] ) ) {
					$attribute_values = $attribute_data['attribute_value'];
				}
				if ( empty( $attribute_values ) && ! empty( $attribute_data['attribute_values'] ) ) {
					$attribute_values = $attribute_data['attribute_values'];
				}
				
				// Check for other visibility field variations.
				if ( ! isset( $attribute_data['visible'] ) && isset( $attribute_data['is_visible'] ) ) {
					$is_visible = (bool) $attribute_data['is_visible'];
				}
				
				// Check for other variation field variations.
				if ( ! isset( $attribute_data['variation'] ) && isset( $attribute_data['is_variation'] ) ) {
					$is_variation = (bool) $attribute_data['is_variation'];
				}
				
				// Check for other taxonomy field variations.
				if ( ! isset( $attribute_data['taxonomy'] ) && isset( $attribute_data['is_taxonomy'] ) ) {
					$is_taxonomy = (bool) $attribute_data['is_taxonomy'];
				}

				if ( empty( $attribute_name ) ) {
					$failed_attributes[] = [
						'data'  => $attribute_data,
						'error' => __( 'Attribute name is required', 'suretriggers' ),
					];
					continue;
				}

				if ( empty( $attribute_values ) ) {
					$failed_attributes[] = [
						'data'  => $attribute_data,
						'error' => __( 'Attribute values are required', 'suretriggers' ),
					];
					continue;
				}

				// Generate slug if not provided.
				if ( empty( $attribute_slug ) ) {
					$attribute_slug = sanitize_title( $attribute_name );
				}

				// Prepare values array.
				if ( is_string( $attribute_values ) ) {
					// Try multiple separators - pipe, comma, semicolon.
					if ( strpos( $attribute_values, '|' ) !== false ) {
						$values_array = array_map( 'trim', explode( '|', $attribute_values ) );
					} elseif ( strpos( $attribute_values, ',' ) !== false ) {
						$values_array = array_map( 'trim', explode( ',', $attribute_values ) );
					} elseif ( strpos( $attribute_values, ';' ) !== false ) {
						$values_array = array_map( 'trim', explode( ';', $attribute_values ) );
					} else {
						$values_array = [ trim( $attribute_values ) ];
					}
				} elseif ( is_array( $attribute_values ) ) {
					$values_array = array_map( 'trim', $attribute_values );
				} else {
					$values_array = [ trim( $attribute_values ) ];
				}

				// Remove empty values.
				$values_array = array_filter(
					$values_array,
					function( $value ) {
						return ! empty( $value );
					}
				);

				if ( empty( $values_array ) ) {
					$failed_attributes[] = [
						'data'  => $attribute_data,
						'error' => __( 'No valid attribute values provided', 'suretriggers' ),
					];
					continue;
				}

				try {
					$attribute_key = sanitize_title( $attribute_name );
					$taxonomy_name = '';
					if ( $is_taxonomy ) {
						// Handle taxonomy-based attributes.
						
						// Check if it's a global attribute.
						if ( strpos( $attribute_slug, 'pa_' ) === 0 ) {
							$taxonomy_name = $attribute_slug;
						} else {
							$taxonomy_name = 'pa_' . $attribute_slug;
						}

						// Ensure taxonomy exists - create if it doesn't exist.
						if ( ! taxonomy_exists( $taxonomy_name ) ) {
							
							// First create the attribute in WooCommerce.
							$attribute_args = [
								'name'         => $attribute_name,
								'slug'         => str_replace( 'pa_', '', $taxonomy_name ),
								'type'         => 'select',
								'order_by'     => 'menu_order',
								'has_archives' => false,
							];
							
							$attribute_id = wc_create_attribute( $attribute_args );
							
							if ( is_wp_error( $attribute_id ) ) {
								$failed_attributes[] = [
									'data'  => $attribute_data,
									'error' => sprintf( __( 'Failed to create attribute: %s', 'suretriggers' ), $attribute_id->get_error_message() ),
								];
								continue;
							}
							
							// Register the taxonomy.
							register_taxonomy(
								$taxonomy_name,
								'product',
								[
									'labels'       => [
										'name' => $attribute_name,
									],
									'hierarchical' => false,
									'public'       => false,
									'show_ui'      => false,
									'query_var'    => true,
									'rewrite'      => false,
								]
							);
						}

						// Set terms for the product.
						$term_ids = [];
						foreach ( $values_array as $value ) {
							$term = get_term_by( 'name', $value, $taxonomy_name );
							if ( ! $term ) {
								// Try to create the term if it doesn't exist.
								$term_result = wp_insert_term( $value, $taxonomy_name );
								if ( ! is_wp_error( $term_result ) ) {
									$term_ids[] = $term_result['term_id'];
								}
							} else {
								$term_ids[] = $term->term_id;
							}
						}

						if ( ! empty( $term_ids ) ) {
							wp_set_object_terms( $product_id, $term_ids, $taxonomy_name );
						}

						// Create WC_Product_Attribute object.
						$attribute_object = new \WC_Product_Attribute();
						$attribute_object->set_name( $taxonomy_name );
						$attribute_object->set_options( $term_ids );
						$attribute_object->set_visible( $is_visible );
						$attribute_object->set_variation( $is_variation );

						$updated_attributes[ $taxonomy_name ] = $attribute_object;

					} else {
						// Handle custom (non-taxonomy) attributes.

						// Create WC_Product_Attribute object.
						$attribute_object = new \WC_Product_Attribute();
						$attribute_object->set_name( $attribute_name );
						$attribute_object->set_options( $values_array );
						$attribute_object->set_visible( $is_visible );
						$attribute_object->set_variation( $is_variation );

						$updated_attributes[ $attribute_key ] = $attribute_object;
					}

					// Track what was added/updated.
					if ( isset( $existing_attributes[ $is_taxonomy ? $taxonomy_name : $attribute_key ] ) ) {
						$updated_existing[] = [
							'name'        => $attribute_name,
							'slug'        => $is_taxonomy ? $taxonomy_name : $attribute_key,
							'values'      => $values_array,
							'is_taxonomy' => $is_taxonomy,
							'visible'     => $is_visible,
							'variation'   => $is_variation,
						];
					} else {
						$added_attributes[] = [
							'name'        => $attribute_name,
							'slug'        => $is_taxonomy ? $taxonomy_name : $attribute_key,
							'values'      => $values_array,
							'is_taxonomy' => $is_taxonomy,
							'visible'     => $is_visible,
							'variation'   => $is_variation,
						];
					}               
				} catch ( Exception $e ) {
					$failed_attributes[] = [
						'data'  => $attribute_data,
						'error' => $e->getMessage(),
					];
				}
			}

			// Update product attributes.
			$product->set_attributes( $updated_attributes );
			$product->save();

			// Clear caches.
			wc_delete_product_transients( $product_id );
			wp_cache_delete( 'wc_product_' . $product_id, 'products' );

			// Get updated product data.
			$product = wc_get_product( $product_id ); // Refresh product data.
			if ( ! $product ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to retrieve updated product', 'suretriggers' ),
				];
			}
			$final_attributes = $product->get_attributes();

			$formatted_attributes = [];
			foreach ( $final_attributes as $key => $attribute ) {
				if ( $attribute instanceof \WC_Product_Attribute ) {
					$formatted_attributes[] = [
						'name'        => $attribute->get_name(),
						'slug'        => $key,
						'values'      => $attribute->get_options(),
						'is_taxonomy' => $attribute->is_taxonomy(),
						'visible'     => $attribute->get_visible(),
						'variation'   => $attribute->get_variation(),
					];
				}
			}

			$response = [
				'status'             => 'success',
				'message'            => sprintf(
					__( 'Processed %1$d attributes: %2$d added, %3$d updated, %4$d failed', 'suretriggers' ),
					count( $attributes ),
					count( $added_attributes ),
					count( $updated_existing ),
					count( $failed_attributes )
				),
				'product_id'         => $product_id,
				'product_name'       => $product->get_name(),
				'product_type'       => $product->get_type(),
				'added_attributes'   => $added_attributes,
				'updated_attributes' => $updated_existing,
				'failed_attributes'  => $failed_attributes,
				'all_attributes'     => $formatted_attributes,
				'attributes_count'   => count( $formatted_attributes ),
				'summary'            => [
					'total_processed' => count( $attributes ),
					'added'           => count( $added_attributes ),
					'updated'         => count( $updated_existing ),
					'failed'          => count( $failed_attributes ),
					'final_count'     => count( $formatted_attributes ),
				],
			];

			return $response;

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Error adding product attributes: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

AddProductAttributes::get_instance();
