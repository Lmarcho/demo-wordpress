<?php
/**
 * RemoveProductFromCart.
 * php version 5.6
 *
 * @category RemoveProductFromCart
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
 * RemoveProductFromCart
 *
 * @category RemoveProductFromCart
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RemoveProductFromCart extends AutomateAction {

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
	public $action = 'wc_remove_product_from_cart';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Remove Product from Cart', 'suretriggers' ),
			'action'   => 'wc_remove_product_from_cart',
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

		// Get removal parameters.
		$product_id    = ! empty( $selected_options['product_id'] ) ? intval( $selected_options['product_id'] ) : 0;
		$cart_item_key = ! empty( $selected_options['cart_item_key'] ) ? sanitize_text_field( $selected_options['cart_item_key'] ) : '';
		$variation_id  = ! empty( $selected_options['variation_id'] ) ? intval( $selected_options['variation_id'] ) : 0;
		$remove_all    = ! empty( $selected_options['remove_all'] ) ? (bool) $selected_options['remove_all'] : false;

		// Check if cart is empty.
		if ( WC()->cart->is_empty() ) {
			return [
				'status'  => 'error',
				'message' => __( 'Cart is empty', 'suretriggers' ),
			];
		}

		$removed_items = [];
		$cart_contents = WC()->cart->get_cart();

		try {
			// If cart_item_key is provided, remove that specific item.
			if ( ! empty( $cart_item_key ) ) {
				if ( isset( $cart_contents[ $cart_item_key ] ) ) {
					$cart_item       = $cart_contents[ $cart_item_key ];
					$removed_items[] = [
						'cart_item_key' => $cart_item_key,
						'product_id'    => $cart_item['product_id'],
						'variation_id'  => $cart_item['variation_id'],
						'quantity'      => $cart_item['quantity'],
						'product_name'  => $cart_item['data']->get_name(),
					];
					
					WC()->cart->remove_cart_item( $cart_item_key );
				} else {
					return [
						'status'  => 'error',
						'message' => __( 'Cart item not found', 'suretriggers' ),
					];
				}
			} else {
				// Remove by product ID (and optionally variation ID).
				if ( empty( $product_id ) ) {
					return [
						'status'  => 'error',
						'message' => __( 'Product ID or Cart Item Key is required', 'suretriggers' ),
					];
				}

				// Find matching cart items.
				foreach ( $cart_contents as $key => $cart_item ) {
					$matches = false;
					
					// Check if product matches.
					if ( $cart_item['product_id'] == $product_id ) {
						// If variation_id is specified, it must match too.
						if ( $variation_id > 0 ) {
							$matches = ( $cart_item['variation_id'] == $variation_id );
						} else {
							$matches = true;
						}
					}

					if ( $matches ) {
						$removed_items[] = [
							'cart_item_key' => $key,
							'product_id'    => $cart_item['product_id'],
							'variation_id'  => $cart_item['variation_id'],
							'quantity'      => $cart_item['quantity'],
							'product_name'  => $cart_item['data']->get_name(),
						];
						
						WC()->cart->remove_cart_item( $key );
						
						// If not removing all matches, break after first match.
						if ( ! $remove_all ) {
							break;
						}
					}
				}
			}

			// Check if any items were removed.
			if ( empty( $removed_items ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'No matching products found in cart', 'suretriggers' ),
				];
			}

			// Calculate cart totals after removal.
			WC()->cart->calculate_totals();
			
			// Safely get cart totals.
			$cart_total       = method_exists( WC()->cart, 'get_total' ) ? WC()->cart->get_total() : '0';
			$cart_subtotal    = method_exists( WC()->cart, 'get_subtotal' ) ? WC()->cart->get_subtotal() : '0';
			$cart_items_count = method_exists( WC()->cart, 'get_cart_contents_count' ) ? WC()->cart->get_cart_contents_count() : 0;

			return [
				'status'           => 'success',
				'message'          => __( 'Product(s) removed from cart successfully', 'suretriggers' ),
				'removed_items'    => $removed_items,
				'removed_count'    => count( $removed_items ),
				'cart_total'       => $cart_total,
				'cart_subtotal'    => $cart_subtotal,
				'cart_items_count' => $cart_items_count,
				'cart_is_empty'    => WC()->cart->is_empty(),
				'user_id'          => $user_id,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Error removing product from cart: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

RemoveProductFromCart::get_instance();
