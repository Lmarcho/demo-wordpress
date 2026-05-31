<?php
/**
 * UpdateProduct.
 * php version 5.6
 *
 * @category UpdateProduct
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\StoreEngine\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * UpdateProduct
 *
 * @category UpdateProduct
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateProduct extends AutomateAction { 


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'StoreEngine';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'se_update_product';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Product', 'suretriggers' ),
			'action'   => 'se_update_product',
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id         user_id.
	 * @param int   $automation_id   automation_id.
	 * @param array $fields          fields.
	 * @param array $selected_options selectedOptions.
	 * @return array
	 * @throws Exception Error.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( 'StoreEngine' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'StoreEngine plugin is not active.', 'suretriggers' ), 
				
			];
		}

		$product_id = isset( $selected_options['product_id'] ) ? absint( $selected_options['product_id'] ) : 0;
		if ( empty( $product_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Product ID is required.',
			];
		}

		$product = get_post( $product_id );
		if ( ! $product || 'storeengine_product' !== $product->post_type ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid product ID or product does not exist.',
			];
		}

		$product_name           = isset( $selected_options['product_name'] ) ? sanitize_text_field( $selected_options['product_name'] ) : '';
		$product_description    = isset( $selected_options['product_description'] ) ? wp_kses_post( $selected_options['product_description'] ) : '';
		$product_status         = isset( $selected_options['product_status'] ) ? sanitize_text_field( $selected_options['product_status'] ) : 'publish';
		$product_price          = isset( $selected_options['regular_price'] ) ? floatval( $selected_options['regular_price'] ) : ( isset( $selected_options['product_price'] ) ? floatval( $selected_options['product_price'] ) : 0 );
		$sale_price             = isset( $selected_options['sale_price'] ) ? floatval( $selected_options['sale_price'] ) : '';
		$product_type           = isset( $selected_options['product_type'] ) ? sanitize_text_field( $selected_options['product_type'] ) : 'simple';
		$product_sku            = isset( $selected_options['product_sku'] ) ? sanitize_text_field( $selected_options['product_sku'] ) : '';
		$product_slug           = isset( $selected_options['product_slug'] ) ? sanitize_title( $selected_options['product_slug'] ) : '';
		$downloadable_file_name = isset( $selected_options['downloadable_file_name'] ) ? $selected_options['downloadable_file_name'] : '';
		$downloadable_file_url  = isset( $selected_options['downloadable_file_url'] ) ? esc_url_raw( $selected_options['downloadable_file_url'] ) : '';

		$valid_types = [ 'simple', 'variable', 'grouped', 'external', 'virtual', 'downloadable' ];
		if ( empty( $product_type ) || ! in_array( $product_type, $valid_types ) ) {
			$product_type = 'simple';
		}

		if ( ! is_numeric( $product_price ) || $product_price < 0 ) {
			$product_price = 0;
		}

		if ( ! empty( $sale_price ) && ( ! is_numeric( $sale_price ) || $sale_price < 0 ) ) {
			$sale_price = '';
		}

		if ( empty( $product_status ) || ! in_array( $product_status, [ 'publish', 'draft', 'pending', 'private' ] ) ) {
			$product_status = 'publish';
		}

		$downloadable_files = [];

		if ( ! empty( $downloadable_file_name ) && ! empty( $downloadable_file_url ) ) {
			$downloadable_files[] = [
				'id'      => md5( $downloadable_file_name . $downloadable_file_url ),
				'name'    => sanitize_text_field( $downloadable_file_name ),
				'file'    => $downloadable_file_url,
				'enabled' => true,
			];
		}

		$product_data = [
			'ID'           => $product_id,
			'post_title'   => ! empty( $product_name ) ? $product_name : $product->post_title,
			'post_content' => '' !== $product_description ? $product_description : $product->post_content,
			'post_status'  => $product_status,
			'post_type'    => 'storeengine_product',
		];

		if ( ! empty( $product_slug ) ) {
			$product_data['post_name'] = $product_slug;
		}

		/**
		 * Update the product post.
		 * 
		 * @var int|\WP_Error $updated_product_id
		 */
		$updated_product_id = wp_update_post( $product_data );

		if ( is_wp_error( $updated_product_id ) ) {
			return [
				'status'  => 'error',
				'message' => sprintf(
					__( 'Failed to update product: %s', 'suretriggers' ),
					$updated_product_id->get_error_message()
				),
			];
		}

		if ( ! $updated_product_id || ! is_numeric( $updated_product_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update product: Invalid product ID returned', 'suretriggers' ), 
				
			];
		}

		if ( $product_price > 0 ) {
			update_post_meta( $product_id, '_storeengine_product_price', $product_price );
			update_post_meta( $product_id, '_storeengine_regular_price', $product_price );
		}

		if ( class_exists( '\\StoreEngine\\Classes\\Price' ) ) {
			global $wpdb;
			$price_data = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}storeengine_product_price WHERE product_id = %d ORDER BY id ASC LIMIT 1",
					$product_id
				)
			);

			if ( $price_data ) {
				$price = new \StoreEngine\Classes\Price( $price_data->id );
			} else {
				$price = new \StoreEngine\Classes\Price();
				$price->set_product_id( $product_id );
			}
			
			if ( ! empty( $sale_price ) && $sale_price < $product_price ) {
				$price->set_price( $sale_price );
				$price->set_compare_price( $product_price );
				update_post_meta( $product_id, '_storeengine_sale_price', $sale_price );
				update_post_meta( $product_id, '_storeengine_product_price', $sale_price );
			} elseif ( $product_price > 0 ) {
				$price->set_price( $product_price );
				$price->set_compare_price( $product_price );
			}
			
			if ( $product_price > 0 ) {
				$price->set_price_name( 'Regular Price' );
				$price->set_price_type( 'onetime' );
				$price->set_order( 0 );
				
				if ( ! empty( $selected_options['product_expiry'] ) && is_numeric( $selected_options['product_expiry'] ) ) {
					$expire_days = absint( $selected_options['product_expiry'] );
					if ( $expire_days > 0 ) {
						$price->set_expire( true );
						$price->set_expire_days( $expire_days );
					}
				}
				
				$price->save();
			}
		}

		update_post_meta( $product_id, '_storeengine_product_type', $product_type );
		
		$shipping_type = isset( $selected_options['shipping_type'] ) ? sanitize_text_field( $selected_options['shipping_type'] ) : 'physical';
		if ( ! in_array( $shipping_type, [ 'physical', 'digital' ] ) ) {
			$shipping_type = 'physical';
		}
		update_post_meta( $product_id, '_storeengine_product_shipping_type', $shipping_type );
		
		if ( 'physical' === $shipping_type && ! empty( $selected_options['product_weight'] ) ) {
			$weight = floatval( $selected_options['product_weight'] );
			if ( $weight >= 0 ) {
				update_post_meta( $product_id, '_storeengine_product_physical_weight', $weight );
				
				$weight_unit = isset( $selected_options['weight_unit'] ) ? sanitize_text_field( $selected_options['weight_unit'] ) : 'kg';
				if ( ! in_array( $weight_unit, [ 'kg', 'g' ] ) ) {
					$weight_unit = 'kg';
				}
				update_post_meta( $product_id, '_storeengine_product_physical_weight_unit', $weight_unit );
				
				if ( class_exists( '\\StoreEngine\\Classes\\Product' ) ) {
					$product_obj = new \StoreEngine\Classes\Product( $product_id );
					$product_obj->set_weight( $weight );
					$product_obj->set_weight_unit( $weight_unit );
					$product_obj->save();
				}
			}
		}

		if ( ! empty( $product_sku ) ) {
			update_post_meta( $product_id, '_storeengine_sku', $product_sku );
		}

		if ( ! empty( $downloadable_files ) ) {
			update_post_meta( $product_id, '_storeengine_product_downloadable_files', $downloadable_files );
		}

		if ( isset( $selected_options['manage_stock'] ) && 'yes' === sanitize_key( $selected_options['manage_stock'] ) ) {
			update_post_meta( $product_id, '_storeengine_manage_stock', 'yes' );

			$stock_quantity = isset( $selected_options['stock_quantity'] ) ? absint( $selected_options['stock_quantity'] ) : 0;
			update_post_meta( $product_id, '_storeengine_stock_quantity', $stock_quantity );

			$stock_status = $stock_quantity > 0 ? 'instock' : 'outofstock';
			update_post_meta( $product_id, '_storeengine_stock_status', $stock_status );
		} else {
			update_post_meta( $product_id, '_storeengine_manage_stock', 'no' );
			$stock_status = isset( $selected_options['stock_status'] ) ? sanitize_text_field( $selected_options['stock_status'] ) : 'instock';
			update_post_meta( $product_id, '_storeengine_stock_status', $stock_status );
		}

		if ( ! empty( $selected_options['product_categories'] ) ) {
			$categories = is_array( $selected_options['product_categories'] ) 
				? $selected_options['product_categories'] 
				: array_map( 'trim', explode( ',', $selected_options['product_categories'] ) );
			
			$category_ids = [];
			foreach ( $categories as $category ) {
				if ( is_numeric( $category ) ) {
					$category_ids[] = absint( $category );
				} else {
					$term = get_term_by( 'name', $category, 'storeengine_product_category' );
					if ( $term ) {
						$category_ids[] = $term->term_id;
					} else {
						$new_term = wp_insert_term( $category, 'storeengine_product_category' );
						if ( ! is_wp_error( $new_term ) ) {
							$category_ids[] = $new_term['term_id'];
						}
					}
				}
			}
			
			if ( ! empty( $category_ids ) ) {
				wp_set_object_terms( $product_id, $category_ids, 'storeengine_product_category' );
			}
		}

		if ( ! empty( $selected_options['product_tags'] ) ) {
			$tags = is_array( $selected_options['product_tags'] ) 
				? $selected_options['product_tags'] 
				: array_map( 'trim', explode( ',', $selected_options['product_tags'] ) );
			
			$tag_ids = [];
			foreach ( $tags as $tag ) {
				if ( is_numeric( $tag ) ) {
					$tag_ids[] = absint( $tag );
				} else {
					$term = get_term_by( 'name', $tag, 'storeengine_product_tag' );
					if ( $term ) {
						$tag_ids[] = $term->term_id;
					} else {
						$new_term = wp_insert_term( $tag, 'storeengine_product_tag' );
						if ( ! is_wp_error( $new_term ) ) {
							$tag_ids[] = $new_term['term_id'];
						}
					}
				}
			}
			
			if ( ! empty( $tag_ids ) ) {
				wp_set_object_terms( $product_id, $tag_ids, 'storeengine_product_tag' );
			}
		}

		$updated_product = get_post( $product_id );

		if ( ! $updated_product ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to retrieve updated product.', 'suretriggers' ), 
				
			];
		}

		$response = [
			'product_id'         => $product_id,
			'product_name'       => $updated_product->post_title,
			'product_slug'       => $updated_product->post_name,
			'product_url'        => get_permalink( $product_id ),
			'product_status'     => $updated_product->post_status,
			'product_type'       => get_post_meta( $product_id, '_storeengine_product_type', true ),
			'regular_price'      => get_post_meta( $product_id, '_storeengine_regular_price', true ),
			'sale_price'         => get_post_meta( $product_id, '_storeengine_sale_price', true ),
			'current_price'      => get_post_meta( $product_id, '_storeengine_product_price', true ),
			'sku'                => get_post_meta( $product_id, '_storeengine_sku', true ),
			'stock_status'       => get_post_meta( $product_id, '_storeengine_stock_status', true ),
			'stock_quantity'     => get_post_meta( $product_id, '_storeengine_stock_quantity', true ),
			'manage_stock'       => get_post_meta( $product_id, '_storeengine_manage_stock', true ),
			'shipping_type'      => get_post_meta( $product_id, '_storeengine_product_shipping_type', true ),
			'weight'             => get_post_meta( $product_id, '_storeengine_product_physical_weight', true ),
			'weight_unit'        => get_post_meta( $product_id, '_storeengine_product_physical_weight_unit', true ),
			'downloadable_files' => get_post_meta( $product_id, '_storeengine_product_downloadable_files', true ),
			'updated_date'       => current_time( 'mysql' ),
			'updated_date_gmt'   => current_time( 'mysql', 1 ),
			'author_id'          => $updated_product->post_author,
		];

		$categories = wp_get_object_terms( $product_id, 'storeengine_product_category', [ 'fields' => 'all' ] );
		if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
			$response['categories'] = array_map(
				function ( $cat ) {
					return [
						'id'   => $cat->term_id,
						'name' => $cat->name,
						'slug' => $cat->slug,
					];
				},
				$categories
			);
		}

		$tags = wp_get_object_terms( $product_id, 'storeengine_product_tag', [ 'fields' => 'all' ] );
		if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) {
			$response['tags'] = array_map(
				function ( $tag ) {
					return [
						'id'   => $tag->term_id,
						'name' => $tag->name,
						'slug' => $tag->slug,
					];
				},
				$tags
			);
		}

		return $response;
	}
}

UpdateProduct::get_instance();
