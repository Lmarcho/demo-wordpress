<?php
/**
 * UpdateProductPrice.
 * php version 5.6
 *
 * @category UpdateProductPrice
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
 * UpdateProductPrice
 *
 * @category UpdateProductPrice
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateProductPrice extends AutomateAction {

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
	public $action = 'wc_update_product_price';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Product Price', 'suretriggers' ),
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
		$regular_price      = isset( $selected_options['regular_price'] ) ? trim( $selected_options['regular_price'] ) : '';
		$sale_price         = isset( $selected_options['sale_price'] ) ? trim( $selected_options['sale_price'] ) : '';
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
			$updated_fields = [];

			// Validate and update regular price.
			if ( ! empty( $regular_price ) ) {
				$regular_price_validated = $this->validate_price( $regular_price );
				if ( is_wp_error( $regular_price_validated ) ) {
					return $regular_price_validated;
				}

				$product->set_regular_price( (string) $regular_price_validated );
				$updated_fields[] = 'regular_price';
			}

			// Validate and update sale price.
			if ( ! empty( $sale_price ) ) {
				$sale_price_validated = $this->validate_price( $sale_price );
				if ( is_wp_error( $sale_price_validated ) ) {
					return $sale_price_validated;
				}

				// Validate sale price is not higher than regular price.
				$current_regular_price = $product->get_regular_price( 'edit' );
				if ( ! empty( $regular_price ) ) {
					$current_regular_price = $regular_price_validated;
				}

				if ( ! empty( $current_regular_price ) && $sale_price_validated >= $current_regular_price ) {
					return new WP_Error( 'invalid_sale_price', __( 'Sale price must be lower than the regular price.', 'suretriggers' ) );
				}

				$product->set_sale_price( (string) $sale_price_validated );
				$updated_fields[] = 'sale_price';
			}

			// Save the product.
			$product->save();

			// Clear cache.
			wc_delete_product_transients( $product->get_id() );

			$response_data = [
				'success'         => true,
				'product_id'      => $product->get_id(),
				'product_name'    => $product->get_name(),
				'product_sku'     => $product->get_sku(),
				'regular_price'   => $product->get_regular_price(),
				'sale_price'      => $product->get_sale_price(),
				'price'           => $product->get_price(),
				'updated_fields'  => $updated_fields,
				'currency'        => get_woocommerce_currency(),
				'currency_symbol' => get_woocommerce_currency_symbol(),
				'message'         => __( 'Product price updated successfully.', 'suretriggers' ),
			];

			return $response_data;

		} catch ( Exception $e ) {
			return new WP_Error( 'update_failed', sprintf( __( 'Failed to update product price: %s', 'suretriggers' ), $e->getMessage() ) );
		}
	}

	/**
	 * Validate price value.
	 *
	 * @param string $price Price to validate.
	 * @return string|WP_Error
	 */
	private function validate_price( $price ) {
		// Remove currency symbols and whitespace.
		$price = trim( str_replace( [ '$', '€', '£', '¥', get_woocommerce_currency_symbol() ], '', $price ) );

		// Check if price is numeric.
		if ( ! is_numeric( $price ) ) {
			return new WP_Error( 'invalid_price_format', __( 'Price must be a valid number.', 'suretriggers' ) );
		}

		$price_float = floatval( $price );

		// Check if price is not negative.
		if ( $price_float < 0 ) {
			return new WP_Error( 'negative_price', __( 'Price cannot be negative.', 'suretriggers' ) );
		}

		// Format price according to WooCommerce settings.
		return (string) wc_format_decimal( $price_float );
	}
}

UpdateProductPrice::get_instance();
