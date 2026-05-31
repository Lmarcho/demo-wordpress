<?php
/**
 * CreateOrder.
 * php version 5.6
 *
 * @category CreateOrder
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
use StoreEngine\Classes\Order;
use StoreEngine\Classes\Price;
use StoreEngine\Classes\Product\SimpleProduct;
use StoreEngine\Classes\Order\OrderItemProduct;
use StoreEngine\Classes\Countries;
use WP_User;
use WP_Error;

/**
 * CreateOrder
 *
 * @category CreateOrder
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 * @psalm-suppress UndefinedTrait
 */
class CreateOrder extends AutomateAction {

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
	public $action = 'se_create_order';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Order', 'suretriggers' ),
			'action'   => 'se_create_order',
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

		$customer_id    = isset( $selected_options['customer_id'] ) ? absint( $selected_options['customer_id'] ) : 0;
		$order_email    = isset( $selected_options['order_email'] ) ? sanitize_email( $selected_options['order_email'] ) : '';
		$order_status   = isset( $selected_options['order_status'] ) ? sanitize_text_field( $selected_options['order_status'] ) : 'pending_payment';
		$payment_method = isset( $selected_options['payment_method'] ) ? sanitize_text_field( $selected_options['payment_method'] ) : '';
		$currency       = isset( $selected_options['currency'] ) ? sanitize_text_field( $selected_options['currency'] ) : 'USD';

		$status_mapping = [
			'pending-payment' => 'pending_payment',
			'pending_payment' => 'pending_payment',
			'processing'      => 'processing',
			'on-hold'         => 'on_hold',
			'on_hold'         => 'on_hold',
			'completed'       => 'completed',
			'cancelled'       => 'cancelled',
			'refunded'        => 'refunded',
			'payment-failed'  => 'payment_failed',
			'payment_failed'  => 'payment_failed',
		];

		if ( isset( $status_mapping[ $order_status ] ) ) {
			$order_status = $status_mapping[ $order_status ];
		} else {
			$order_status = 'pending_payment';
		}

		$billing_country      = isset( $selected_options['billing_country'] ) ? sanitize_text_field( $selected_options['billing_country'] ) : '';
		$billing_country_code = $this->get_country_code( $billing_country );

		$billing_address = [
			'first_name' => isset( $selected_options['billing_first_name'] ) ? sanitize_text_field( $selected_options['billing_first_name'] ) : '',
			'last_name'  => isset( $selected_options['billing_last_name'] ) ? sanitize_text_field( $selected_options['billing_last_name'] ) : '',
			'company'    => isset( $selected_options['billing_company'] ) ? sanitize_text_field( $selected_options['billing_company'] ) : '',
			'address_1'  => isset( $selected_options['billing_address_1'] ) ? sanitize_text_field( $selected_options['billing_address_1'] ) : '',
			'address_2'  => isset( $selected_options['billing_address_2'] ) ? sanitize_text_field( $selected_options['billing_address_2'] ) : '',
			'city'       => isset( $selected_options['billing_city'] ) ? sanitize_text_field( $selected_options['billing_city'] ) : '',
			'state'      => isset( $selected_options['billing_state'] ) ? sanitize_text_field( $selected_options['billing_state'] ) : '',
			'postcode'   => isset( $selected_options['billing_postcode'] ) ? sanitize_text_field( $selected_options['billing_postcode'] ) : '',
			'country'    => $billing_country_code,
			'email'      => $order_email,
			'phone'      => isset( $selected_options['billing_phone'] ) ? sanitize_text_field( $selected_options['billing_phone'] ) : '',
		];

		$shipping_country      = isset( $selected_options['shipping_country'] ) ? sanitize_text_field( $selected_options['shipping_country'] ) : '';
		$shipping_country_code = ! empty( $shipping_country ) ? $this->get_country_code( $shipping_country ) : $billing_country_code;

		$shipping_address = [
			'first_name' => isset( $selected_options['shipping_first_name'] ) ? sanitize_text_field( $selected_options['shipping_first_name'] ) : $billing_address['first_name'],
			'last_name'  => isset( $selected_options['shipping_last_name'] ) ? sanitize_text_field( $selected_options['shipping_last_name'] ) : $billing_address['last_name'],
			'company'    => isset( $selected_options['shipping_company'] ) ? sanitize_text_field( $selected_options['shipping_company'] ) : $billing_address['company'],
			'address_1'  => isset( $selected_options['shipping_address_1'] ) ? sanitize_text_field( $selected_options['shipping_address_1'] ) : $billing_address['address_1'],
			'address_2'  => isset( $selected_options['shipping_address_2'] ) ? sanitize_text_field( $selected_options['shipping_address_2'] ) : $billing_address['address_2'],
			'city'       => isset( $selected_options['shipping_city'] ) ? sanitize_text_field( $selected_options['shipping_city'] ) : $billing_address['city'],
			'state'      => isset( $selected_options['shipping_state'] ) ? sanitize_text_field( $selected_options['shipping_state'] ) : $billing_address['state'],
			'postcode'   => isset( $selected_options['shipping_postcode'] ) ? sanitize_text_field( $selected_options['shipping_postcode'] ) : $billing_address['postcode'],
			'country'    => $shipping_country_code,
			'phone'      => isset( $selected_options['shipping_phone'] ) ? sanitize_text_field( $selected_options['shipping_phone'] ) : $billing_address['phone'],
		];

		if ( empty( $customer_id ) && ! empty( $order_email ) ) {
			/**
			 * Get existing user by email.
			 *
			 * @var WP_User|false $existing_user
			 */
			$existing_user = get_user_by( 'email', $order_email );
			if ( $existing_user instanceof WP_User ) {
				$customer_id = $existing_user->ID;
			} else {
				$username = strstr( $order_email, '@', true );
				if ( false === $username ) {
					$username = $order_email;
				}
				$username = sanitize_user( $username );
				
				$base_username = $username;
				$suffix        = 1;
				while ( username_exists( $username ) ) {
					$username = $base_username . $suffix;
					$suffix++;
				}
				
				$customer_data = [
					'user_login' => $username,
					'user_email' => $order_email,
					'user_pass'  => wp_generate_password(),
					'role'       => 'customer',
				];
				
				if ( ! empty( $billing_address['first_name'] ) ) {
					$customer_data['first_name'] = $billing_address['first_name'];
				}
				if ( ! empty( $billing_address['last_name'] ) ) {
					$customer_data['last_name'] = $billing_address['last_name'];
				}
				
				/**
				 * Create new customer user.
				 *
				 * @var int|WP_Error $new_customer_id
				 */
				$new_customer_id = wp_insert_user( $customer_data );
				if ( ! is_wp_error( $new_customer_id ) ) {
					$customer_id = (int) $new_customer_id;
					
					update_user_meta( $customer_id, 'billing_first_name', $billing_address['first_name'] );
					update_user_meta( $customer_id, 'billing_last_name', $billing_address['last_name'] );
					update_user_meta( $customer_id, 'billing_company', $billing_address['company'] );
					update_user_meta( $customer_id, 'billing_address_1', $billing_address['address_1'] );
					update_user_meta( $customer_id, 'billing_address_2', $billing_address['address_2'] );
					update_user_meta( $customer_id, 'billing_city', $billing_address['city'] );
					update_user_meta( $customer_id, 'billing_state', $billing_address['state'] );
					update_user_meta( $customer_id, 'billing_postcode', $billing_address['postcode'] );
					update_user_meta( $customer_id, 'billing_country', $billing_address['country'] );
					update_user_meta( $customer_id, 'billing_email', $order_email );
					update_user_meta( $customer_id, 'billing_phone', $billing_address['phone'] );
				}
			}
		}

		if ( ! class_exists( '\\StoreEngine\\Classes\\Order' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'StoreEngine Order class not found. Please ensure StoreEngine is properly installed.', 'suretriggers' ), 
				
			];
		}

		$order = new Order();
		$order->set_status( $order_status );
		$order->set_created_via( 'suretriggers' );
		$order->set_currency( $currency );

		if ( $customer_id > 0 ) {
			/**
			 * Get customer by ID.
			 *
			 * @var WP_User|false $customer
			 */
			$customer = get_user_by( 'ID', $customer_id );
			if ( $customer instanceof WP_User ) {
				$order->set_customer_id( $customer_id );
				if ( empty( $order_email ) ) {
					$order_email = $customer->user_email;
				}
			}
		}

		if ( ! empty( $order_email ) ) {
			$order->set_order_email( $order_email );
		}

		if ( ! empty( $payment_method ) ) {
			$order->set_payment_method( $payment_method );
			
			$payment_method_titles = [
				'cod'   => 'Cash on delivery',
				'bacs'  => 'Direct bank transfer',
				'check' => 'Check payments',
			];
			
			if ( isset( $payment_method_titles[ $payment_method ] ) ) {
				$order->set_payment_method_title( $payment_method_titles[ $payment_method ] );
			}
		}

		$order->set_billing_address( $billing_address );
		$order->set_shipping_address( $shipping_address );

		$order->save();

		if ( ! $order->get_id() ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create the order.', 'suretriggers' ), 
				
			];
		}

		if ( ! empty( $selected_options['order_items'] ) && is_array( $selected_options['order_items'] ) ) {
			foreach ( $selected_options['order_items'] as $item ) {
				if ( empty( $item['product_id'] ) ) {
					continue;
				}

				$product_id = absint( $item['product_id'] );
				$quantity   = isset( $item['quantity'] ) ? absint( $item['quantity'] ) : 1;
				$price_id   = isset( $item['price_id'] ) ? absint( $item['price_id'] ) : 0;

				if ( $quantity < 1 ) {
					$quantity = 1;
				}

				$product = get_post( $product_id );
				if ( ! $product || 'storeengine_product' !== $product->post_type ) {
					continue;
				}

				if ( $price_id > 0 ) {
					if ( class_exists( '\\StoreEngine\\Classes\\Price' ) ) {
						$price_obj = new Price( $price_id );
						$order->add_product( $price_obj, $quantity );
					}
				} else {
					if ( class_exists( '\\StoreEngine\\Classes\\Product\\SimpleProduct' ) ) {
						$product_obj = new SimpleProduct( $product_id );
						$prices      = $product_obj->get_prices();
						
						if ( ! empty( $prices ) ) {
							$default_price = reset( $prices );
							$order->add_product( $default_price, $quantity );
						} else {
							if ( class_exists( '\\StoreEngine\\Classes\\Order\\OrderItemProduct' ) ) {
								$order_item = new OrderItemProduct();
								$order_item->set_order_id( $order->get_id() );
								$order_item->set_product_id( $product_id );
								$order_item->set_quantity( $quantity );
								$order_item->set_name( $product->post_title );
								
								$product_price = get_post_meta( $product_id, '_storeengine_product_price', true );
								if ( $product_price && is_numeric( $product_price ) ) {
									$price = floatval( $product_price );
									$total = $price * $quantity;
									$order_item->set_price( $price );
									$order_item->set_total( $total );
									$order_item->set_subtotal( $total );
								}

								$order_item->save();
								$order->add_item( $order_item );
							}
						}
					}
				}
			}
		}

		$order->calculate_totals();
		$order->set_status( $order_status );
		$order->save();

		if ( ! empty( $selected_options['order_notes'] ) ) {
			$order_notes = sanitize_textarea_field( $selected_options['order_notes'] );
			if ( ! empty( $order_notes ) ) {
				$order->add_order_note( $order_notes, false, true );
			}
		}

		$response = [
			'order_id'         => $order->get_id(),
			'order_number'     => $order->get_order_number(),
			'order_status'     => $order->get_status(),
			'order_total'      => $order->get_total(),
			'order_subtotal'   => $order->get_subtotal(),
			'order_tax_total'  => $order->get_total_tax(),
			'order_shipping'   => $order->get_shipping_total(),
			'currency'         => $order->get_currency(),
			'payment_method'   => $order->get_payment_method(),
			'customer_id'      => $order->get_customer_id(),
			'order_email'      => $order->get_order_email(),
			'order_date'       => '',
			'order_date_gmt'   => '',
			'billing_address'  => $order->get_address( 'billing' ),
			'shipping_address' => $order->get_address( 'shipping' ),
			'order_url'        => admin_url( 'admin.php?page=storeengine-orders&action=edit&order_id=' . $order->get_id() ),
		];

		$date_created = $order->get_date_created_gmt();
		if ( $date_created ) {
			$response['order_date']     = $date_created->format( 'Y-m-d H:i:s' );
			$response['order_date_gmt'] = $date_created->format( 'Y-m-d H:i:s' );
		}

		$order_items = $order->get_items();
		if ( ! empty( $order_items ) ) {
			$response['order_items'] = [];
			foreach ( $order_items as $item ) {
				if ( is_object( $item ) && method_exists( $item, 'get_product_id' ) ) {
					$response['order_items'][] = [
						'item_id'    => method_exists( $item, 'get_id' ) ? $item->get_id() : 0,
						'product_id' => $item->get_product_id(),
						'price_id'   => method_exists( $item, 'get_price_id' ) ? $item->get_price_id() : 0,
						'quantity'   => method_exists( $item, 'get_quantity' ) ? $item->get_quantity() : 0,
						'total'      => method_exists( $item, 'get_total' ) ? $item->get_total() : 0,
						'subtotal'   => method_exists( $item, 'get_subtotal' ) ? $item->get_subtotal() : 0,
					];
				}
			}
		}

		return $response;
	}

	/**
	 * Get country code from country name or code.
	 *
	 * @param string $country Country name or code.
	 * @return string Country ISO code.
	 */
	private function get_country_code( $country ) {
		if ( empty( $country ) ) {
			return '';
		}

		if ( ! class_exists( '\\StoreEngine\\Classes\\Countries' ) ) {
			return '';
		}

		if ( strlen( $country ) === 2 ) {
			$country_code = strtoupper( $country );
			$countries    = Countries::get_instance();
			if ( $countries->country_exists( $country_code ) ) {
				return $country_code;
			}
		}

		$countries_obj  = Countries::get_instance();
		$countries_list = $countries_obj->get_countries();
		
		$country_lower = strtolower( $country );
		
		foreach ( $countries_list as $code => $name ) {
			if ( strtolower( $name ) === $country_lower ) {
				return $code;
			}
		}
		
		$common_mappings = [
			'usa' => 'US',
			'uk'  => 'GB',
			'uae' => 'AE',
		];
		
		if ( isset( $common_mappings[ $country_lower ] ) ) {
			return $common_mappings[ $country_lower ];
		}
		
		return $country;
	}
}

CreateOrder::get_instance();
