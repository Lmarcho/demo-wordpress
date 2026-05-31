<?php
/**
 * ListProducts.
 * php version 5.6
 *
 * @category ListProducts
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
 * ListProducts
 *
 * @category ListProducts
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListProducts extends AutomateAction {

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
	public $action = 'fluentcart_list_products';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Products', 'suretriggers' ),
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

		$limit        = isset( $selected_options['limit'] ) ? $selected_options['limit'] : 50;
		$search       = isset( $selected_options['search'] ) ? $selected_options['search'] : '';
		$product_type = isset( $selected_options['product_type'] ) ? $selected_options['product_type'] : '';
		
		// Validate limit.
		$limit = min( max( intval( $limit ), 1 ), 500 ); // Between 1 and 500.

		try {
			// Build query.
			$query = Product::where( 'post_status', 'publish' );

			if ( ! empty( $search ) ) {
				$query->where( 'post_title', 'like', '%' . $search . '%' );
			}
			
			// Get total count before applying limit.
			$total_count = $query->count();

			// Apply limit.
			$query->limit( $limit );

			$products = $query->get();

			// Format product data.
			$products_data = [];
			foreach ( $products as $product ) {
				$product_meta = get_post_meta( $product->ID );
				if ( ! is_array( $product_meta ) ) {
					$product_meta = [];
				}
				
				$product_data = [
					'product_id' => $product->ID,
					'title'      => $product->post_title,
					'content'    => $product->post_content,
					'excerpt'    => $product->post_excerpt,
					'status'     => $product->post_status,
					'slug'       => $product->post_name,
					'created_at' => $product->post_date,
					'updated_at' => $product->post_modified,
					'permalink'  => get_permalink( $product->ID ),
				];

				// Add meta data.
				$product_data['price']          = isset( $product_meta['_fct_price'] ) && is_array( $product_meta['_fct_price'] ) && isset( $product_meta['_fct_price'][0] ) ? $product_meta['_fct_price'][0] : '';
				$product_data['sale_price']     = isset( $product_meta['_fct_sale_price'] ) && is_array( $product_meta['_fct_sale_price'] ) && isset( $product_meta['_fct_sale_price'][0] ) ? $product_meta['_fct_sale_price'][0] : '';
				$product_data['product_type']   = isset( $product_meta['_fct_product_type'] ) && is_array( $product_meta['_fct_product_type'] ) && isset( $product_meta['_fct_product_type'][0] ) ? $product_meta['_fct_product_type'][0] : 'simple';
				$product_data['sku']            = isset( $product_meta['_fct_sku'] ) && is_array( $product_meta['_fct_sku'] ) && isset( $product_meta['_fct_sku'][0] ) ? $product_meta['_fct_sku'][0] : '';
				$product_data['stock_quantity'] = isset( $product_meta['_fct_stock_quantity'] ) && is_array( $product_meta['_fct_stock_quantity'] ) && isset( $product_meta['_fct_stock_quantity'][0] ) ? $product_meta['_fct_stock_quantity'][0] : '';
				$product_data['manage_stock']   = isset( $product_meta['_fct_manage_stock'] ) && is_array( $product_meta['_fct_manage_stock'] ) && isset( $product_meta['_fct_manage_stock'][0] ) ? $product_meta['_fct_manage_stock'][0] : 'no';
				$product_data['stock_status']   = isset( $product_meta['_fct_stock_status'] ) && is_array( $product_meta['_fct_stock_status'] ) && isset( $product_meta['_fct_stock_status'][0] ) ? $product_meta['_fct_stock_status'][0] : 'instock';
				$product_data['featured']       = isset( $product_meta['_fct_featured'] ) && is_array( $product_meta['_fct_featured'] ) && isset( $product_meta['_fct_featured'][0] ) ? $product_meta['_fct_featured'][0] : 'no';
				$product_data['virtual']        = isset( $product_meta['_fct_virtual'] ) && is_array( $product_meta['_fct_virtual'] ) && isset( $product_meta['_fct_virtual'][0] ) ? $product_meta['_fct_virtual'][0] : 'no';
				$product_data['downloadable']   = isset( $product_meta['_fct_downloadable'] ) && is_array( $product_meta['_fct_downloadable'] ) && isset( $product_meta['_fct_downloadable'][0] ) ? $product_meta['_fct_downloadable'][0] : 'no';

				// Get featured image.
				$thumbnail_id = get_post_thumbnail_id( $product->ID );
				if ( $thumbnail_id ) {
					$product_data['featured_image'] = [
						'id'        => $thumbnail_id,
						'url'       => wp_get_attachment_url( $thumbnail_id ),
						'thumbnail' => wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ),
						'medium'    => wp_get_attachment_image_url( $thumbnail_id, 'medium' ),
						'large'     => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
					];
				} else {
					$product_data['featured_image'] = null;
				}


				// Get categories/tags if they exist.
				$categories = wp_get_post_terms( $product->ID, 'product-categories', [ 'fields' => 'names' ] );
				$tags       = wp_get_post_terms( $product->ID, 'product-brands', [ 'fields' => 'names' ] );
				
				$product_data['categories'] = is_array( $categories ) ? $categories : [];
				$product_data['tags']       = is_array( $tags ) ? $tags : [];

				$products_data[] = $product_data;
			}


			$context = [
				'products'        => $products_data,
				'total_count'     => $total_count,
				'returned_count'  => count( $products_data ),
				'limit'           => $limit,
				'has_more'        => count( $products_data ) >= $limit,
				'filters_applied' => [
					'search' => $search,
					'limit'  => $limit,
				],
			];

			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

ListProducts::get_instance();
