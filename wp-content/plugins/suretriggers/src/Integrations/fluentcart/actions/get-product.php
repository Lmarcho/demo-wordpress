<?php
/**
 * GetProduct.
 * php version 5.6
 *
 * @category GetProduct
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCart\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCart\App\Models\Product;
use FluentCart\App\Models\ProductVariation;

/**
 * GetProduct
 *
 * @category GetProduct
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetProduct extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCart';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcart_get_product';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Product', 'suretriggers' ),
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
	 * @param array $selected_options selectedOptions.
	 *
	 * @return array|void
	 *
	 * @throws \Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( '\FluentCart\App\Models\Product' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCart is not installed or activated.', 'suretriggers' ),
			];
		}

		$product_id   = isset( $selected_options['product_id'] ) ? $selected_options['product_id'] : '';
		$product_slug = isset( $selected_options['product_slug'] ) ? $selected_options['product_slug'] : '';
		$product_sku  = isset( $selected_options['product_sku'] ) ? $selected_options['product_sku'] : '';
		
		if ( empty( $product_id ) && empty( $product_slug ) && empty( $product_sku ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product ID, slug, or SKU is required.', 'suretriggers' ),
			];
		}

		try {
			$product = null;

			// Find product by ID first (most specific).
			if ( ! empty( $product_id ) ) {
				$product = Product::find( $product_id );
			} elseif ( ! empty( $product_slug ) ) {
				// Then by slug.
				$product = Product::where( 'post_name', $product_slug )->first();
			} elseif ( ! empty( $product_sku ) ) {
				// Finally by SKU.
				$product = Product::whereHas(
					'meta',
					function( $q ) use ( $product_sku ) {
						$q->where( 'meta_key', '_fct_sku' )->where( 'meta_value', $product_sku );
					}
				)->first();
			}

			if ( ! $product ) {
				return [
					'status'  => 'error',
					'message' => __( 'Product not found.', 'suretriggers' ),
				];
			}

			$product_meta = get_post_meta( $product->ID );
			if ( ! is_array( $product_meta ) ) {
				$product_meta = [];
			}

			$context = [
				'product_id' => $product->ID,
				'title'      => $product->post_title,
				'content'    => $product->post_content,
				'excerpt'    => $product->post_excerpt,
				'status'     => $product->post_status,
				'slug'       => $product->post_name,
				'created_at' => $product->post_date,
				'updated_at' => $product->post_modified,
				'permalink'  => get_permalink( $product->ID ),
				'edit_link'  => admin_url( 'post.php?post=' . $product->ID . '&action=edit' ),
			];

			// Add product meta data with proper type checking.
			$context['price']          = ( isset( $product_meta['_fct_price'] ) && is_array( $product_meta['_fct_price'] ) && isset( $product_meta['_fct_price'][0] ) ) ? $product_meta['_fct_price'][0] : '';
			$context['sale_price']     = ( isset( $product_meta['_fct_sale_price'] ) && is_array( $product_meta['_fct_sale_price'] ) && isset( $product_meta['_fct_sale_price'][0] ) ) ? $product_meta['_fct_sale_price'][0] : '';
			$context['regular_price']  = ( isset( $product_meta['_fct_regular_price'] ) && is_array( $product_meta['_fct_regular_price'] ) && isset( $product_meta['_fct_regular_price'][0] ) ) ? $product_meta['_fct_regular_price'][0] : $context['price'];
			$context['product_type']   = ( isset( $product_meta['_fct_product_type'] ) && is_array( $product_meta['_fct_product_type'] ) && isset( $product_meta['_fct_product_type'][0] ) ) ? $product_meta['_fct_product_type'][0] : 'simple';
			$context['sku']            = ( isset( $product_meta['_fct_sku'] ) && is_array( $product_meta['_fct_sku'] ) && isset( $product_meta['_fct_sku'][0] ) ) ? $product_meta['_fct_sku'][0] : '';
			$context['stock_quantity'] = ( isset( $product_meta['_fct_stock_quantity'] ) && is_array( $product_meta['_fct_stock_quantity'] ) && isset( $product_meta['_fct_stock_quantity'][0] ) ) ? $product_meta['_fct_stock_quantity'][0] : '';
			$context['manage_stock']   = ( isset( $product_meta['_fct_manage_stock'] ) && is_array( $product_meta['_fct_manage_stock'] ) && isset( $product_meta['_fct_manage_stock'][0] ) ) ? $product_meta['_fct_manage_stock'][0] : 'no';
			$context['stock_status']   = ( isset( $product_meta['_fct_stock_status'] ) && is_array( $product_meta['_fct_stock_status'] ) && isset( $product_meta['_fct_stock_status'][0] ) ) ? $product_meta['_fct_stock_status'][0] : 'instock';
			$context['backorders']     = ( isset( $product_meta['_fct_backorders'] ) && is_array( $product_meta['_fct_backorders'] ) && isset( $product_meta['_fct_backorders'][0] ) ) ? $product_meta['_fct_backorders'][0] : 'no';
			$context['featured']       = ( isset( $product_meta['_fct_featured'] ) && is_array( $product_meta['_fct_featured'] ) && isset( $product_meta['_fct_featured'][0] ) ) ? $product_meta['_fct_featured'][0] : 'no';
			$context['virtual']        = ( isset( $product_meta['_fct_virtual'] ) && is_array( $product_meta['_fct_virtual'] ) && isset( $product_meta['_fct_virtual'][0] ) ) ? $product_meta['_fct_virtual'][0] : 'no';
			$context['downloadable']   = ( isset( $product_meta['_fct_downloadable'] ) && is_array( $product_meta['_fct_downloadable'] ) && isset( $product_meta['_fct_downloadable'][0] ) ) ? $product_meta['_fct_downloadable'][0] : 'no';
			$context['weight']         = ( isset( $product_meta['_fct_weight'] ) && is_array( $product_meta['_fct_weight'] ) && isset( $product_meta['_fct_weight'][0] ) ) ? $product_meta['_fct_weight'][0] : '';
			$context['length']         = ( isset( $product_meta['_fct_length'] ) && is_array( $product_meta['_fct_length'] ) && isset( $product_meta['_fct_length'][0] ) ) ? $product_meta['_fct_length'][0] : '';
			$context['width']          = ( isset( $product_meta['_fct_width'] ) && is_array( $product_meta['_fct_width'] ) && isset( $product_meta['_fct_width'][0] ) ) ? $product_meta['_fct_width'][0] : '';
			$context['height']         = ( isset( $product_meta['_fct_height'] ) && is_array( $product_meta['_fct_height'] ) && isset( $product_meta['_fct_height'][0] ) ) ? $product_meta['_fct_height'][0] : '';

			// Pricing calculations.
			$regular_price_value = ! empty( $context['regular_price'] ) ? $context['regular_price'] : $context['price'];
			$regular_price       = is_numeric( $regular_price_value ) ? floatval( $regular_price_value ) : 0.0;
			$sale_price          = is_numeric( $context['sale_price'] ) ? floatval( $context['sale_price'] ) : 0.0;
			
			if ( $sale_price > 0 && $sale_price < $regular_price ) {
				$context['on_sale']             = true;
				$context['discount_amount']     = $regular_price - $sale_price;
				$context['discount_percentage'] = $regular_price > 0 ? round( ( $context['discount_amount'] / $regular_price ) * 100, 2 ) : 0;
				$context['effective_price']     = $sale_price;
			} else {
				$context['on_sale']             = false;
				$context['discount_amount']     = 0;
				$context['discount_percentage'] = 0;
				$context['effective_price']     = $regular_price;
			}

			// Get featured image.
			$thumbnail_id = get_post_thumbnail_id( $product->ID );
			if ( $thumbnail_id ) {
				$context['featured_image'] = [
					'id'        => $thumbnail_id,
					'url'       => wp_get_attachment_url( $thumbnail_id ),
					'alt'       => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
					'thumbnail' => wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ),
					'medium'    => wp_get_attachment_image_url( $thumbnail_id, 'medium' ),
					'large'     => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
					'full'      => wp_get_attachment_image_url( $thumbnail_id, 'full' ),
				];
			} else {
				$context['featured_image'] = null;
			}



			// Get categories and tags.
			$categories = wp_get_post_terms( $product->ID, 'fct_product_category' );
			$tags       = wp_get_post_terms( $product->ID, 'fct_product_tag' );
			
			$context['categories']     = [];
			$context['category_names'] = [];
			if ( ! is_wp_error( $categories ) && is_array( $categories ) ) {
				foreach ( $categories as $category ) {
					$context['categories'][]     = [
						'id'   => $category->term_id,
						'name' => $category->name,
						'slug' => $category->slug,
					];
					$context['category_names'][] = $category->name;
				}
			}

			$context['tags']      = [];
			$context['tag_names'] = [];
			if ( ! is_wp_error( $tags ) && is_array( $tags ) ) {
				foreach ( $tags as $tag ) {
					$context['tags'][]      = [
						'id'   => $tag->term_id,
						'name' => $tag->name,
						'slug' => $tag->slug,
					];
					$context['tag_names'][] = $tag->name;
				}
			}


			// Add product attributes for variable products.
			if ( 'variable' === $context['product_type'] ) {
				$product_attributes_raw = ( isset( $product_meta['_fct_product_attributes'] ) && is_array( $product_meta['_fct_product_attributes'] ) && isset( $product_meta['_fct_product_attributes'][0] ) ) ? $product_meta['_fct_product_attributes'][0] : '';
				$attributes_data        = [];
				
				if ( ! empty( $product_attributes_raw ) && is_string( $product_attributes_raw ) ) {
					$product_attributes = maybe_unserialize( $product_attributes_raw );
					if ( is_array( $product_attributes ) ) {
						foreach ( $product_attributes as $attr_name => $attr_data ) {
							$attributes_data[] = [
								'name'      => $attr_name,
								'label'     => isset( $attr_data['name'] ) ? $attr_data['name'] : $attr_name,
								'values'    => isset( $attr_data['value'] ) ? $attr_data['value'] : [],
								'visible'   => isset( $attr_data['is_visible'] ) ? $attr_data['is_visible'] : false,
								'variation' => isset( $attr_data['is_variation'] ) ? $attr_data['is_variation'] : false,
							];
						}
					}
				}
				$context['product_attributes'] = $attributes_data;
			}

			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

GetProduct::get_instance();
