<?php
/**
 * CreateProduct.
 * php version 5.6
 *
 * @category CreateProduct
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
use WP_Error;
use WC_Product_Simple;

/**
 * CreateProduct
 *
 * @category CreateProduct
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateProduct extends AutomateAction {

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
	public $action = 'wc_create_product';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Product in WooCommerce', 'suretriggers' ),
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
	 * @return array|WP_Error
	 * @throws Exception If WooCommerce or WordPress functions are missing.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wc_get_product' ) || ! function_exists( 'wp_insert_post' ) ) {
			return [
				'message' => 'Required functions not available.',
			];
		}

		$post_id = wp_insert_post(
			[
				'post_title'     => $selected_options['product_name'],
				'post_content'   => $selected_options['product_description'],
				'post_excerpt'   => $selected_options['product_short_description'],
				'post_status'    => $selected_options['product_status'],
				'post_name'      => $selected_options['product_slug'],
				'post_type'      => 'product',
				'comment_status' => $selected_options['enable_reviews'],
			]
		);

		if ( ! $post_id || $post_id <= 0 ) {
			return [
				'message' => 'Failed to create the product.',
			];
		}
		$result = wp_set_object_terms( $post_id, 'simple', 'product_type' );

		if ( ! is_array( $result ) ) {
			return [
				'message' => 'Failed to assign product type.',
			];
		}
		
		update_post_meta( $post_id, '_visibility', $selected_options['catalog_visibility'] );
		update_post_meta( $post_id, '_stock_status', $selected_options['stock_status'] );
		update_post_meta( $post_id, 'catalog_visibility', $selected_options['catalog_visibility'] );
		update_post_meta( $post_id, '_sku', $selected_options['product_sku'] );
		update_post_meta( $post_id, '_manage_stock', $selected_options['manage_stock'] );
		update_post_meta( $post_id, '_stock', $selected_options['stock_quantity'] );
		update_post_meta( $post_id, '_product_url', $selected_options['external_url'] );

		$product = wc_get_product( $post_id );
		
		if ( ! $product ) {
			return new WP_Error( 'product_not_found', __( 'Failed to get the product.', 'suretriggers' ) );
		}
		$product->set_props(
			[
				'regular_price' => wc_format_decimal( $selected_options['regular_price'] ),
				'sale_price'    => isset( $selected_options['sale_price'] ) ? wc_format_decimal( $selected_options['sale_price'] ) : '',
				'length'        => isset( $selected_options['length'] ) ? $selected_options['length'] : '',
				'width'         => isset( $selected_options['width'] ) ? $selected_options['width'] : '',
				'height'        => isset( $selected_options['height'] ) ? $selected_options['height'] : '',
				'weight'        => isset( $selected_options['weight'] ) ? $selected_options['weight'] : '',
			]
		);

		$product->save();

		// Featured image.
		if ( ! empty( $selected_options['product_featured_image'] ) ) {
			$attachment_id = $this->upload_image_from_url( $selected_options['product_featured_image'], $post_id );
			if ( $attachment_id ) {
				set_post_thumbnail( $post_id, $attachment_id );
			}
		}

		// Gallery images.
		if ( ! empty( $selected_options['product_gallery_images'] ) ) {
			$gallery_urls   = explode( ',', $selected_options['product_gallery_images'] );
			$attachment_ids = [];

			foreach ( $gallery_urls as $url ) {
				$attachment_id = $this->upload_image_from_url( trim( $url ), $post_id );
				if ( $attachment_id ) {
					$attachment_ids[] = $attachment_id;
				}
			}

			if ( ! empty( $attachment_ids ) ) {
				update_post_meta( $post_id, '_product_image_gallery', implode( ',', $attachment_ids ) );
			}
		}

		// Categories and tags.
		if ( ! empty( $selected_options['category_ids'] ) ) {
			$category_ids = $selected_options['category_ids'];

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
				wp_set_object_terms( $post_id, $category_ids, 'product_cat' );
			}
		}

		if ( ! empty( $selected_options['tag_ids'] ) ) {
			$tag_ids = $selected_options['tag_ids'];

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
				wp_set_object_terms( $post_id, $tag_ids, 'product_tag' );
			}
		}

		// Brand support.
		if ( ! empty( $selected_options['product_brand'] ) ) {
			$brand = $selected_options['product_brand'];
			wp_set_object_terms( $post_id, [ $brand ], 'product_brand' );
		}

		wc_delete_product_transients( $post_id );
		wc_update_product_lookup_tables();

		return [
			'product_id' => $post_id,
			'title'      => get_the_title( $post_id ),
			'url'        => get_permalink( $post_id ),
		];
	}

	/**
	 * Upload image from URL.
	 *
	 * @param string $image_url Image URL.
	 * @param int    $post_id   Post ID.
	 * @return int|false Attachment ID or false on failure.
	 */
	private function upload_image_from_url( $image_url, $post_id ) {
		$response = wp_remote_get( $image_url );
		$image    = wp_remote_retrieve_body( $response );

		if ( empty( $image ) ) {
			return false;
		}

		$upload = wp_upload_bits( basename( $image_url ), null, $image );

		if ( $upload['error'] ) {
			return false;
		}

		$filename    = $upload['file'];
		$wp_filetype = wp_check_filetype( $filename, null );
		$attachment  = [
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];

		$attachment_id = wp_insert_attachment( $attachment, $filename, $post_id );

		

		require_once ABSPATH . 'wp-admin/includes/image.php';

		$attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		return $attachment_id;
	}
}

CreateProduct::get_instance();
