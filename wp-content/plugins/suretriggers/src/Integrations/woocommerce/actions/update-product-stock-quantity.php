<?php
/**
 * UpdateProductStockQuantity.
 * php version 5.6
 *
 * @category UpdateProductStockQuantity
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WooCommerce\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;
use WP_Error;

/**
 * UpdateProductStockQuantity
 *
 * @category UpdateProductStockQuantity
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateProductStockQuantity extends AutomateAction {

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
	public $action = 'wc_update_product_stock_quantity';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Product Stock Quantity', 'suretriggers' ),
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
	 * @throws Exception If WooCommerce functions are missing.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return new WP_Error( 'woocommerce_not_available', __( 'WooCommerce functions not available.', 'suretriggers' ) );
		}

		$product_identifier = isset( $selected_options['product_identifier'] ) ? trim( $selected_options['product_identifier'] ) : '';
		$identifier_type    = isset( $selected_options['identifier_type'] ) ? $selected_options['identifier_type'] : 'id';
		$stock_quantity     = isset( $selected_options['stock_quantity'] ) ? intval( $selected_options['stock_quantity'] ) : 0;
		$stock_status       = isset( $selected_options['stock_status'] ) ? $selected_options['stock_status'] : '';
		$manage_stock       = isset( $selected_options['manage_stock'] ) ? $selected_options['manage_stock'] : 'yes';

		if ( empty( $product_identifier ) ) {
			return new WP_Error( 'missing_product_identifier', __( 'Product identifier is required.', 'suretriggers' ) );
		}

		$product = null;

		// Find product by identifier type.
		switch ( $identifier_type ) {
			case 'id':
				$product = wc_get_product( intval( $product_identifier ) );
				break;
			case 'sku':
				$product_id = wc_get_product_id_by_sku( $product_identifier );
				if ( $product_id ) {
					$product = wc_get_product( $product_id );
				}
				break;
			default:
				return new WP_Error( 'invalid_identifier_type', __( 'Invalid identifier type. Use "id" or "sku".', 'suretriggers' ) );
		}

		if ( ! $product || ! is_object( $product ) ) {
			return new WP_Error( 'product_not_found', __( 'Product not found.', 'suretriggers' ) );
		}

		// Handle variations for variable products.
		if ( $product->is_type( 'variable' ) ) {
			return new WP_Error( 'variable_product', __( 'For variable products, please use the variation ID or SKU instead of the parent product.', 'suretriggers' ) );
		}

		try {
			// Set stock management.
			$product->set_manage_stock( 'yes' === $manage_stock );

			// Set stock quantity if managing stock.
			if ( 'yes' === $manage_stock ) {
				$product->set_stock_quantity( $stock_quantity );
			}

			// Set stock status if provided.
			if ( ! empty( $stock_status ) ) {
				$valid_statuses = [ 'instock', 'outofstock', 'onbackorder' ];
				if ( in_array( $stock_status, $valid_statuses, true ) ) {
					$product->set_stock_status( $stock_status );
				} else {
					return new WP_Error( 'invalid_stock_status', __( 'Invalid stock status. Use "instock", "outofstock", or "onbackorder".', 'suretriggers' ) );
				}
			}

			// Save the product.
			$product->save();

			// Clear cache.
			wc_delete_product_transients( $product->get_id() );

			$response_data = [
				'success'        => true,
				'product_id'     => $product->get_id(),
				'product_name'   => $product->get_name(),
				'product_sku'    => $product->get_sku(),
				'stock_quantity' => $product->get_stock_quantity(),
				'stock_status'   => $product->get_stock_status(),
				'manage_stock'   => $product->get_manage_stock() ? 'yes' : 'no',
				'message'        => __( 'Product stock updated successfully.', 'suretriggers' ),
			];

			return $response_data;

		} catch ( Exception $e ) {
			return new WP_Error( 'update_failed', sprintf( __( 'Failed to update product stock: %s', 'suretriggers' ), $e->getMessage() ) );
		}
	}
}

UpdateProductStockQuantity::get_instance();
