<?php
/**
 * GetAllProducts.
 * php version 5.6
 *
 * @category GetAllProducts
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WooCommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;

/**
 * GetAllProducts
 *
 * @category GetAllProducts
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetAllProducts extends AutomateAction {

	use SingletonLoader;

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
	public $action = 'wc_get_all_products';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get All Products', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id          User ID.
	 * @param int   $automation_id    Automation ID.
	 * @param array $fields           Fields.
	 * @param array $selected_options Selected options.
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return [
				'success' => false,
				'message' => __( 'WooCommerce functions not available.', 'suretriggers' ),
			];
		}

		// Get parameters with defaults.
		$limit  = isset( $selected_options['limit'] ) ? intval( $selected_options['limit'] ) : 10;
		$status = isset( $selected_options['status'] ) ? $selected_options['status'] : 'publish';
		
		// Validate limit.
		if ( $limit <= 0 || $limit > 100 ) {
			$limit = 10;
		}

		// Prepare query arguments.
		$args = [
			'limit'   => $limit,
			'status'  => $status,
			'orderby' => 'date',
			'order'   => 'DESC',
			'return'  => 'objects',
		];

		// Get products - try alternative approach if wc_get_products fails.
		$products = wc_get_products( $args );

		// If we didn't get the expected count, try with WP_Query.
		if ( is_array( $products ) && count( $products ) < $limit && $limit > 1 ) {
			$wp_args = [
				'post_type'      => 'product',
				'post_status'    => $status,
				'posts_per_page' => $limit,
				'orderby'        => 'date',
				'order'          => 'DESC',
			];
			
			$query    = new \WP_Query( $wp_args );
			$products = [];
			
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();
					$product = wc_get_product( get_the_ID() );
					if ( $product ) {
						$products[] = $product;
					}
				}
				wp_reset_postdata();
			}
		}

		if ( empty( $products ) ) {
			return [
				'success'        => true,
				'products'       => [],
				'products_count' => 0,
				'message'        => __( 'No products found.', 'suretriggers' ),
			];
		}

		// Format products data.
		$formatted_products = [];
		if ( is_array( $products ) ) {
			foreach ( $products as $product ) {
				$formatted_products[] = $this->format_product_data( $product );
			}
		}

		$response_data = [
			'success'         => true,
			'products'        => $formatted_products,
			'products_count'  => count( $formatted_products ),
			'filters_applied' => [
				'limit'  => $limit,
				'status' => $status,
			],
			'message'         => sprintf( __( 'Retrieved %d products.', 'suretriggers' ), count( $formatted_products ) ),
		];

		return $response_data;
	}

	/**
	 * Format product data.
	 *
	 * @param \WC_Product $product WooCommerce product object.
	 * @return array
	 */
	private function format_product_data( $product ) {
		// Type check to ensure we have a WC_Product object.
		if ( ! is_a( $product, 'WC_Product' ) ) {
			return [];
		}
		$product_data = [
			'id'                => $product->get_id(),
			'name'              => $product->get_name(),
			'slug'              => $product->get_slug(),
			'sku'               => $product->get_sku(),
			'type'              => $product->get_type(),
			'status'            => $product->get_status(),
			'description'       => $product->get_description(),
			'short_description' => $product->get_short_description(),
			'regular_price'     => $product->get_regular_price(),
			'sale_price'        => $product->get_sale_price(),
			'price'             => $product->get_price(),
			'stock_status'      => $product->get_stock_status(),
			'stock_quantity'    => $product->get_stock_quantity(),
			'manage_stock'      => $product->get_manage_stock(),
			'weight'            => $product->get_weight(),
			'length'            => $product->get_length(),
			'width'             => $product->get_width(),
			'height'            => $product->get_height(),
			'date_created'      => $product->get_date_created() ? $product->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
			'date_modified'     => $product->get_date_modified() ? $product->get_date_modified()->date( 'Y-m-d H:i:s' ) : '',
			'permalink'         => $product->get_permalink(),
		];

		// Get product images.
		$image_id    = $product->get_image_id();
		$gallery_ids = $product->get_gallery_image_ids();

		$product_data['images'] = [
			'featured_image' => $image_id ? wp_get_attachment_image_url( (int) $image_id, 'full' ) : '',
			'gallery_images' => [],
		];

		foreach ( $gallery_ids as $gallery_id ) {
			$product_data['images']['gallery_images'][] = wp_get_attachment_image_url( (int) $gallery_id, 'full' );
		}

		// Get product categories.
		$categories                 = get_the_terms( $product->get_id(), 'product_cat' );
		$product_data['categories'] = [];
		if ( $categories && ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				$product_data['categories'][] = [
					'id'   => $category->term_id,
					'name' => $category->name,
					'slug' => $category->slug,
				];
			}
		}

		// Get product tags.
		$tags                 = get_the_terms( $product->get_id(), 'product_tag' );
		$product_data['tags'] = [];
		if ( $tags && ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				$product_data['tags'][] = [
					'id'   => $tag->term_id,
					'name' => $tag->name,
					'slug' => $tag->slug,
				];
			}
		}

		// Get product attributes.
		$attributes                 = $product->get_attributes();
		$product_data['attributes'] = [];
		foreach ( $attributes as $attribute ) {
			$product_data['attributes'][] = [
				'name'    => $attribute->get_name(),
				'options' => $attribute->get_options(),
				'visible' => $attribute->get_visible(),
			];
		}

		// Add variation info for variable products.
		if ( $product->is_type( 'variable' ) ) {
			$variations                 = $product->get_children();
			$product_data['variations'] = [];
			foreach ( $variations as $variation_id ) {
				$variation = wc_get_product( $variation_id );
				if ( $variation && is_a( $variation, 'WC_Product' ) ) {
					$variation_attributes         = method_exists( $variation, 'get_variation_attributes' ) ? $variation->get_variation_attributes() : [];
					$product_data['variations'][] = [
						'id'             => $variation->get_id(),
						'sku'            => $variation->get_sku(),
						'price'          => $variation->get_price(),
						'regular_price'  => $variation->get_regular_price(),
						'sale_price'     => $variation->get_sale_price(),
						'stock_status'   => $variation->get_stock_status(),
						'stock_quantity' => $variation->get_stock_quantity(),
						'attributes'     => $variation_attributes,
					];
				}
			}
		}

		return $product_data;
	}

}

GetAllProducts::get_instance();
