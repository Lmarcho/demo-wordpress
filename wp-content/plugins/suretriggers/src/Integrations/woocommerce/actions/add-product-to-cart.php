<?php
/**
 * AddProductToCart.
 * php version 5.6
 *
 * @category AddProductToCart
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use WC_Product;
use Exception;

/**
 * AddProductToCart
 *
 * @category AddProductToCart
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddProductToCart extends AutomateAction {

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
	public $action = 'wc_add_product_to_cart';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Product to Cart', 'suretriggers' ),
			'action'   => 'wc_add_product_to_cart',
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
		// Handle user identification from selected options.
		if ( ! empty( $selected_options['user_id'] ) ) {
			$user_id = intval( $selected_options['user_id'] );
		} elseif ( ! empty( $selected_options['customer_email'] ) ) {
			$user = get_user_by( 'email', sanitize_email( $selected_options['customer_email'] ) );
			if ( $user ) {
				$user_id = $user->ID;
			}
		}

		// Check if WooCommerce is active.
		if ( ! function_exists( 'WC' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce not available', 'suretriggers' ),
			];
		}

		// Set the current user context for cart operations.
		wp_set_current_user( $user_id );
		
		// Initialize WooCommerce components properly.
		if ( ! WC()->session ) {
			WC()->session = new \WC_Session_Handler();
			WC()->session->init();
		}
		
		// Initialize customer.
		if ( ! WC()->customer ) {
			WC()->customer = new \WC_Customer( $user_id, true );
		}
		
		// Initialize cart for the user.
		if ( ! WC()->cart ) {
			WC()->cart = new \WC_Cart();
		}
		
		// Ensure cart is properly initialized.
		WC()->cart->get_cart();

		// Validate required fields.
		foreach ( $fields as $field ) {
			if ( array_key_exists( 'validationProps', $field ) && empty( $selected_options[ $field['name'] ] ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Required field is missing: ', 'suretriggers' ) . $field['name'],
				];
			}
		}

		$product_id   = intval( $selected_options['product_id'] );
		$quantity     = ! empty( $selected_options['quantity'] ) ? intval( $selected_options['quantity'] ) : 1;
		$variation_id = ! empty( $selected_options['variation_id'] ) ? intval( $selected_options['variation_id'] ) : 0;
		$variations   = ! empty( $selected_options['variations'] ) ? $selected_options['variations'] : [];

		if ( empty( $product_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product ID is required', 'suretriggers' ),
			];
		}

		// Get the product.
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product instanceof WC_Product ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product not found', 'suretriggers' ),
			];
		}

		// Check if product is purchasable.
		if ( ! $product->is_purchasable() ) {
			return [
				'status'  => 'error',
				'message' => __( 'Product is not purchasable', 'suretriggers' ),
			];
		}

		// Handle variations for variable products.
		$variation_data = [];
		if ( $product->is_type( 'variable' ) ) {
			if ( empty( $variation_id ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Variation ID is required for variable products', 'suretriggers' ),
				];
			}

			// Get variation product.
			$variation = wc_get_product( $variation_id );
			if ( ! $variation || ! $variation->exists() ) {
				return [
					'status'  => 'error',
					'message' => __( 'Product variation not found', 'suretriggers' ),
				];
			}

			// Process variation attributes.
			if ( is_array( $variations ) ) {
				foreach ( $variations as $key => $value ) {
					$variation_data[ $key ] = sanitize_text_field( $value );
				}
			}
		}

		// Check stock availability.
		if ( ! $product->has_enough_stock( $quantity ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Insufficient stock for the requested quantity', 'suretriggers' ),
			];
		}

		// Add product to cart with error handling.
		try {
			$cart_item_key = WC()->cart->add_to_cart( 
				$product_id, 
				$quantity, 
				$variation_id, 
				$variation_data 
			);

			if ( $cart_item_key && is_string( $cart_item_key ) ) {
				// Get cart item details.
				$cart_item = WC()->cart->get_cart_item( $cart_item_key );
				
				if ( ! $cart_item ) {
					return [
						'status'  => 'error',
						'message' => __( 'Failed to retrieve cart item details', 'suretriggers' ),
					];
				}
				
				// Calculate cart totals.
				WC()->cart->calculate_totals();
				
				// Get product details.
				$product_name  = $product->get_name();
				$product_price = $product->get_price();
				
				// Safely get cart totals.
				$cart_total       = method_exists( WC()->cart, 'get_total' ) ? WC()->cart->get_total() : '0';
				$cart_subtotal    = method_exists( WC()->cart, 'get_subtotal' ) ? WC()->cart->get_subtotal() : '0';
				$cart_items_count = method_exists( WC()->cart, 'get_cart_contents_count' ) ? WC()->cart->get_cart_contents_count() : 0;
				
				return [
					'status'           => 'success',
					'message'          => __( 'Product added to cart successfully', 'suretriggers' ),
					'cart_item_key'    => $cart_item_key,
					'product_id'       => $product_id,
					'product_name'     => $product_name,
					'product_price'    => $product_price,
					'variation_id'     => $variation_id,
					'quantity'         => $quantity,
					'line_total'       => isset( $cart_item['line_total'] ) ? $cart_item['line_total'] : '0',
					'cart_total'       => $cart_total,
					'cart_subtotal'    => $cart_subtotal,
					'cart_items_count' => $cart_items_count,
					'user_id'          => $user_id,
				];
			}
		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Error adding product to cart: ', 'suretriggers' ) . $e->getMessage(),
			];
		}

		// If we reach here, the cart_item_key was false/empty.
		// Get WooCommerce notices for error details.
		$notices       = wc_get_notices( 'error' );
		$error_message = ! empty( $notices ) ? $notices[0]['notice'] : __( 'Failed to add product to cart', 'suretriggers' );
		
		// Clear notices to prevent them showing elsewhere.
		wc_clear_notices();
		
		return [
			'status'  => 'error',
			'message' => $error_message,
		];
	}
}

AddProductToCart::get_instance();
