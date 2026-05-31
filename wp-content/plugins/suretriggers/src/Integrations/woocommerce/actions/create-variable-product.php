<?php
/**
 * CreateVariableProduct.
 * php version 5.6
 *
 * @category CreateVariableProduct
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
 * CreateVariableProduct
 *
 * @category CreateVariableProduct
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateVariableProduct extends AutomateAction {

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
	public $action = 'wc_create_variable_product';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Variable Product', 'suretriggers' ),
			'action'   => 'wc_create_variable_product',
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

		// Get basic product parameters.
		$product_name               = ! empty( $selected_options['product_name'] ) ? sanitize_text_field( $selected_options['product_name'] ) : '';
		$product_description        = ! empty( $selected_options['product_description'] ) ? wp_kses_post( $selected_options['product_description'] ) : '';
		$product_short_description  = ! empty( $selected_options['product_short_description'] ) ? wp_kses_post( $selected_options['product_short_description'] ) : '';
		$product_sku                = ! empty( $selected_options['product_sku'] ) ? sanitize_text_field( $selected_options['product_sku'] ) : '';
		$product_status             = ! empty( $selected_options['product_status'] ) ? sanitize_text_field( $selected_options['product_status'] ) : 'publish';
		$product_catalog_visibility = ! empty( $selected_options['product_catalog_visibility'] ) ? sanitize_text_field( $selected_options['product_catalog_visibility'] ) : 'visible';
		$product_featured           = ! empty( $selected_options['product_featured'] ) ? (bool) $selected_options['product_featured'] : false;
		$tax_status                 = ! empty( $selected_options['tax_status'] ) ? sanitize_text_field( $selected_options['tax_status'] ) : 'taxable';
		$tax_class                  = ! empty( $selected_options['tax_class'] ) ? sanitize_text_field( $selected_options['tax_class'] ) : '';
		
		// Categories and tags.
		$product_categories = ! empty( $selected_options['product_categories'] ) ? $selected_options['product_categories'] : [];
		$product_tags       = ! empty( $selected_options['product_tags'] ) ? $selected_options['product_tags'] : [];
		
		// Images.
		$featured_image = ! empty( $selected_options['featured_image'] ) ? $selected_options['featured_image'] : '';
		$product_images = ! empty( $selected_options['product_images'] ) ? $selected_options['product_images'] : [];
		
		// Attributes for variations.
		$product_attributes = ! empty( $selected_options['product_attributes'] ) ? $selected_options['product_attributes'] : [];
		
		// Variations data.
		$variations_data = ! empty( $selected_options['variations'] ) ? $selected_options['variations'] : [];
		
		// Legacy field support for variations.
		if ( empty( $variations_data ) && ! empty( $selected_options['product_variations'] ) ) {
			$variations_data = $selected_options['product_variations'];
		}

		if ( empty( $product_name ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product name is required', 'suretriggers' ),
			];
		}

		try {
			// Create the variable product.
			$product = new \WC_Product_Variable();
			
			// Set basic product data.
			$product->set_name( $product_name );
			$product->set_description( $product_description );
			$product->set_short_description( $product_short_description );
			
			// Set SKU if provided.
			if ( ! empty( $product_sku ) ) {
				$product->set_sku( $product_sku );
			}
			
			$product->set_status( $product_status );
			$product->set_catalog_visibility( $product_catalog_visibility );
			$product->set_featured( $product_featured );
			
			// Set tax settings.
			$product->set_tax_status( $tax_status );
			if ( ! empty( $tax_class ) ) {
				$product->set_tax_class( $tax_class );
			}
			
			// Set manage stock to false for variable product (variations will manage their own stock).
			$product->set_manage_stock( false );
			
			// Save the product first to get an ID.
			$product_id = $product->save();

			// Set categories.
			if ( ! empty( $product_categories ) ) {
				$category_ids = $product_categories;
				
				if ( is_string( $category_ids ) ) {
					$category_ids = array_map( 'intval', explode( ',', $category_ids ) );
				} elseif ( is_array( $category_ids ) ) {
					$category_ids = array_map(
						function( $item ) {
							return isset( $item['value'] ) ? intval( $item['value'] ) : 0;
						},
						$category_ids
					);
					$category_ids = array_filter( $category_ids );
				}
				
				if ( ! empty( $category_ids ) ) {
					wp_set_object_terms( $product_id, $category_ids, 'product_cat' );
				}
			}

			// Set tags.
			if ( ! empty( $product_tags ) ) {
				$tag_ids = $product_tags;
				
				if ( is_string( $tag_ids ) ) {
					$tag_ids = array_map( 'intval', explode( ',', $tag_ids ) );
				} elseif ( is_array( $tag_ids ) ) {
					$tag_ids = array_map(
						function( $item ) {
							return isset( $item['value'] ) ? intval( $item['value'] ) : 0;
						},
						$tag_ids
					);
					$tag_ids = array_filter( $tag_ids );
				}
				
				if ( ! empty( $tag_ids ) ) {
					wp_set_object_terms( $product_id, $tag_ids, 'product_tag' );
				}
			}

			if ( ! $product_id ) {
					return [
						'status'  => 'error',
						'message' => __( 'Failed to create product', 'suretriggers' ),
					];
			}

			// Handle product images.
			$this->handle_product_images( $product_id, $featured_image, $product_images );

			// Process attributes for variations.
			$processed_attributes = [];
			$created_attributes   = [];
			$failed_attributes    = [];

			if ( ! empty( $product_attributes ) ) {
					
				foreach ( $product_attributes as $index => $attribute_data ) {
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
					$is_visible       = isset( $attribute_data['visible'] ) ? (bool) $attribute_data['visible'] : true;
					$is_variation     = isset( $attribute_data['variation'] ) ? (bool) $attribute_data['variation'] : true; // Default true for variable products.
					$is_taxonomy      = isset( $attribute_data['taxonomy'] ) ? (bool) $attribute_data['taxonomy'] : false;

					// Legacy field support.
					if ( empty( $attribute_name ) && ! empty( $attribute_data['attribute_name'] ) ) {
						$attribute_name = sanitize_text_field( $attribute_data['attribute_name'] );
					}
					if ( empty( $attribute_slug ) && ! empty( $attribute_data['attribute_slug'] ) ) {
						$attribute_slug = sanitize_text_field( $attribute_data['attribute_slug'] );
					}
					if ( empty( $attribute_values ) && ! empty( $attribute_data['attribute_values'] ) ) {
						$attribute_values = $attribute_data['attribute_values'];
					}
					if ( ! isset( $attribute_data['visible'] ) && isset( $attribute_data['is_visible'] ) ) {
						$is_visible = (bool) $attribute_data['is_visible'];
					}
					if ( ! isset( $attribute_data['variation'] ) && isset( $attribute_data['is_variation'] ) ) {
						$is_variation = (bool) $attribute_data['is_variation'];
					}
					if ( ! isset( $attribute_data['taxonomy'] ) && isset( $attribute_data['is_taxonomy'] ) ) {
						$is_taxonomy = (bool) $attribute_data['is_taxonomy'];
					}

					if ( empty( $attribute_name ) || empty( $attribute_values ) ) {
						$failed_attributes[] = [
							'data'  => $attribute_data,
							'error' => __( 'Attribute name and values are required', 'suretriggers' ),
						];
						continue;
					}

					// Generate slug if not provided.
					if ( empty( $attribute_slug ) ) {
						$attribute_slug = sanitize_title( $attribute_name );
					}

					// Prepare values array.
					if ( is_string( $attribute_values ) ) {
						if ( strpos( $attribute_values, '|' ) !== false ) {
							$values_array = array_map( 'trim', explode( '|', $attribute_values ) );
						} elseif ( strpos( $attribute_values, ',' ) !== false ) {
							$values_array = array_map( 'trim', explode( ',', $attribute_values ) );
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
							if ( strpos( $attribute_slug, 'pa_' ) === 0 ) {
								$taxonomy_name = $attribute_slug;
							} else {
								$taxonomy_name = 'pa_' . $attribute_slug;
							}

							// Ensure taxonomy exists.
							if ( ! taxonomy_exists( $taxonomy_name ) ) {
								// Create the attribute in WooCommerce.
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

							// Create terms and get term IDs.
							$term_ids = [];
							foreach ( $values_array as $value ) {
								$term = get_term_by( 'name', $value, $taxonomy_name );
								if ( ! $term ) {
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

							$processed_attributes[ $taxonomy_name ] = $attribute_object;

						} else {
							// Handle custom (non-taxonomy) attributes.

							// Create WC_Product_Attribute object.
							$attribute_object = new \WC_Product_Attribute();
							$attribute_object->set_name( $attribute_name );
							$attribute_object->set_options( $values_array );
							$attribute_object->set_visible( $is_visible );
							$attribute_object->set_variation( $is_variation );

							$processed_attributes[ $attribute_key ] = $attribute_object;
						}

						$created_attributes[] = [
							'name'        => $attribute_name,
							'slug'        => $is_taxonomy ? $taxonomy_name : $attribute_key,
							'values'      => $values_array,
							'is_taxonomy' => $is_taxonomy,
							'visible'     => $is_visible,
							'variation'   => $is_variation,
						];

	
					} catch ( Exception $e ) {
							$failed_attributes[] = [
								'data'  => $attribute_data,
								'error' => $e->getMessage(),
							];
					}
				}

				// Set attributes on the product.
				if ( ! empty( $processed_attributes ) ) {
					$product->set_attributes( $processed_attributes );
					$product->save();
				}
			}

			// Create variations.
			$created_variations = [];
			$failed_variations  = [];

			if ( ! empty( $variations_data ) ) {
					
				foreach ( $variations_data as $index => $variation_data ) {
					if ( ! is_array( $variation_data ) ) {
						$failed_variations[] = [
							'data'  => $variation_data,
							'error' => __( 'Invalid variation data format', 'suretriggers' ),
						];
						continue;
					}

					try {
						// Create variation.
						$variation = new \WC_Product_Variation();
						$variation->set_parent_id( $product_id );

						// Set variation attributes.
						$variation_attributes = [];
						if ( ! empty( $variation_data['attributes'] ) ) {
							foreach ( $variation_data['attributes'] as $attr_name => $attr_value ) {
								$variation_attributes[ $attr_name ] = sanitize_text_field( $attr_value );
							}
						}
						
						if ( empty( $variation_attributes ) && ! empty( $variation_data['variation_attributes'] ) ) {
							$attr_data = $variation_data['variation_attributes'];
							if ( is_string( $attr_data ) ) {
								$decoded = json_decode( $attr_data, true );
								if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
									foreach ( $decoded as $attr_name => $value ) {
										$found_match = false;
										
										if ( ! empty( $processed_attributes ) ) {
											foreach ( $processed_attributes as $attr_key => $attr_obj ) {
												$clean_name       = str_replace( 'pa_', '', $attr_key );
												$normalized_input = strtolower( str_replace( ' ', '_', $attr_name ) );
												
												if ( $normalized_input === $clean_name || strtolower( $attr_name ) === $clean_name ) {
													$variation_attributes[ $attr_key ] = sanitize_text_field( $value );
													$found_match                       = true;
													break;
												}
											}
										}
										
										if ( ! $found_match ) {
											$slug                          = 'pa_' . strtolower( str_replace( ' ', '_', $attr_name ) );
											$variation_attributes[ $slug ] = sanitize_text_field( $value );
										}
									}
								}
							}
						}
						
						if ( ! empty( $variation_attributes ) ) {
							$variation->set_attributes( $variation_attributes );
						}

						$sku = ! empty( $variation_data['sku'] ) ? $variation_data['sku'] : 
							( ! empty( $variation_data['variation_sku'] ) ? $variation_data['variation_sku'] : '' );
						if ( ! empty( $sku ) ) {
							$variation->set_sku( sanitize_text_field( $sku ) );
						}
						
						if ( ! empty( $variation_data['regular_price'] ) ) {
							$variation->set_regular_price( (string) floatval( $variation_data['regular_price'] ) );
						}
						
						if ( ! empty( $variation_data['sale_price'] ) ) {
							$variation->set_sale_price( (string) floatval( $variation_data['sale_price'] ) );
						}
						
						$manage_stock = isset( $variation_data['manage_stock'] ) ? $variation_data['manage_stock'] : false;
						if ( $manage_stock ) {
							$variation->set_manage_stock( true );
							if ( ! empty( $variation_data['stock_quantity'] ) ) {
								$variation->set_stock_quantity( intval( $variation_data['stock_quantity'] ) );
							}
						}
						
						if ( ! empty( $variation_data['weight'] ) ) {
							$variation->set_weight( floatval( $variation_data['weight'] ) );
						}
						
						// Separate dimension fields.
						if ( ! empty( $variation_data['length'] ) ) {
							$variation->set_length( floatval( $variation_data['length'] ) );
						}
						if ( ! empty( $variation_data['width'] ) ) {
							$variation->set_width( floatval( $variation_data['width'] ) );
						}
						if ( ! empty( $variation_data['height'] ) ) {
							$variation->set_height( floatval( $variation_data['height'] ) );
						}
						
						// Set tax settings for variation.
						if ( ! empty( $variation_data['tax_status'] ) ) {
							$variation->set_tax_status( sanitize_text_field( $variation_data['tax_status'] ) );
						}
						if ( ! empty( $variation_data['tax_class'] ) ) {
							$variation->set_tax_class( sanitize_text_field( $variation_data['tax_class'] ) );
						}

						// Save variation.
						$variation_id = $variation->save();

						if ( $variation_id ) {
							// Handle variation image.
							if ( ! empty( $variation_data['variation_image'] ) ) {
								$this->handle_variation_image( $variation_id, $variation_data['variation_image'] );
							}
							
							$created_variations[] = [
								'id'         => $variation_id,
								'sku'        => $variation->get_sku(),
								'price'      => $variation->get_price(),
								'attributes' => $variation->get_attributes(),
							];
						} else {
							$failed_variations[] = [
								'data'  => $variation_data,
								'error' => __( 'Failed to create variation', 'suretriggers' ),
							];
						}                   
					} catch ( Exception $e ) {
								$failed_variations[] = [
									'data'  => $variation_data,
									'error' => $e->getMessage(),
								];
					}
				}
			}

			// Sync variable product with its variations.
			\WC_Product_Variable::sync( $product_id );
			
			wc_delete_product_transients( $product_id );
			wp_cache_delete( 'wc_product_' . $product_id, 'products' );

			// Get final product data.
			$final_product = wc_get_product( $product_id );
			if ( ! $final_product ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to retrieve created product', 'suretriggers' ),
				];
			}
			
			$response = [
				'status'             => 'success',
				'message'            => sprintf(
					__( 'Variable product created successfully with %1$d attributes and %2$d variations', 'suretriggers' ),
					count( $created_attributes ),
					count( $created_variations )
				),
				'product_id'         => $product_id,
				'product_name'       => $final_product->get_name(),
				'product_type'       => $final_product->get_type(),
				'product_status'     => $final_product->get_status(),
				'product_sku'        => $final_product->get_sku(),
				'product_permalink'  => $final_product->get_permalink(),
				'created_attributes' => $created_attributes,
				'failed_attributes'  => $failed_attributes,
				'created_variations' => $created_variations,
				'failed_variations'  => $failed_variations,
				'summary'            => [
					'attributes_created' => count( $created_attributes ),
					'attributes_failed'  => count( $failed_attributes ),
					'variations_created' => count( $created_variations ),
					'variations_failed'  => count( $failed_variations ),
				],
			];

			return $response;

		} catch ( Exception $e ) {
				return [
					'status'  => 'error',
					'message' => __( 'Error creating variable product: ', 'suretriggers' ) . $e->getMessage(),
				];
		}
	}

	/**
	 * Handle product images - featured and gallery images.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $featured_image Featured image URL.
	 * @param mixed  $product_images Gallery images URLs.
	 * @return void
	 */
	private function handle_product_images( $product_id, $featured_image, $product_images ) {
		try {
			// Handle featured image.
			if ( ! empty( $featured_image ) ) {
				$featured_attachment_id = $this->upload_image_from_url( $featured_image, $product_id );
				if ( $featured_attachment_id ) {
					set_post_thumbnail( $product_id, $featured_attachment_id );
				}
			}

			// Handle gallery images.
			if ( ! empty( $product_images ) ) {
				$gallery_ids = [];
				
				if ( is_string( $product_images ) ) {
					$gallery_urls = explode( ',', $product_images );
					foreach ( $gallery_urls as $url ) {
						$url = trim( $url );
						if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
							$attachment_id = $this->upload_image_from_url( $url, $product_id );
							if ( $attachment_id ) {
								$gallery_ids[] = $attachment_id;
							}
						}
					}
				} elseif ( is_array( $product_images ) ) {
					foreach ( $product_images as $image_url ) {
						if ( ! is_string( $image_url ) ) {
							continue;
						}
						$image_url = trim( $image_url );
						if ( ! empty( $image_url ) && filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
							$attachment_id = $this->upload_image_from_url( $image_url, $product_id );
							if ( $attachment_id ) {
								$gallery_ids[] = $attachment_id;
							}
						}
					}
				}
				
				if ( ! empty( $gallery_ids ) ) {
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$product->set_gallery_image_ids( $gallery_ids );
						$product->save();
					}
				}
			}
		} catch ( Exception $e ) {
			// Silently ignore image handling errors to prevent disrupting product creation.
			return;
		}
	}

	/**
	 * Upload image from URL to WordPress media library.
	 *
	 * @param string $image_url Image URL.
	 * @param int    $product_id Product ID for attachment.
	 * @return int|false Attachment ID or false on failure.
	 */
	private function upload_image_from_url( $image_url, $product_id = 0 ) {
		try {
			// Validate URL.
			if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
				return false;
			}

			// Get image data.
			$response = wp_remote_get( $image_url, [ 'timeout' => 3 ] );
			
			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return false;
			}

			$image_data = wp_remote_retrieve_body( $response );
			if ( empty( $image_data ) ) {
				return false;
			}

			// Get filename from URL.
			$parsed_path = wp_parse_url( $image_url, PHP_URL_PATH );
			$filename    = $parsed_path ? basename( $parsed_path ) : '';
			if ( empty( $filename ) || strpos( $filename, '.' ) === false ) {
				$filename = 'product-image-' . time() . '.jpg';
			}

			// Upload to WordPress.
			$upload = wp_upload_bits( $filename, null, $image_data );
			
			if ( $upload['error'] ) {
				return false;
			}

			// Create attachment.
			$attachment = [
				'post_mime_type' => $upload['type'],
				'post_title'     => sanitize_file_name( $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
				'post_parent'    => $product_id,
			];

			$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $product_id );
			
			if ( $attachment_id > 0 ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
				$attachment_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
				wp_update_attachment_metadata( $attachment_id, $attachment_data );
				return $attachment_id;
			}

			return false;
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * Handle variation image.
	 *
	 * @param int    $variation_id Variation ID.
	 * @param string $image_url Image URL.
	 * @return void
	 */
	private function handle_variation_image( $variation_id, $image_url ) {
		try {
			if ( ! empty( $image_url ) && filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
				$attachment_id = $this->upload_image_from_url( $image_url, $variation_id );
				if ( $attachment_id ) {
					set_post_thumbnail( $variation_id, $attachment_id );
				}
			}
		} catch ( Exception $e ) {
			// Silently ignore variation image errors.
			return;
		}
	}
}

CreateVariableProduct::get_instance();
