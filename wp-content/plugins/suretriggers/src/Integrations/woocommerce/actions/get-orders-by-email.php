<?php
/**
 * GetOrdersByEmail.
 * php version 5.6
 *
 * @category GetOrdersByEmail
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Woocommerce\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use WC_Order;
use Exception;

/**
 * GetOrdersByEmail
 *
 * @category GetOrdersByEmail
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetOrdersByEmail extends AutomateAction {

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
	public $action = 'wc_get_orders_by_email';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Orders by Email', 'suretriggers' ),
			'action'   => 'wc_get_orders_by_email',
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
		if ( ! function_exists( 'WC' ) || ! function_exists( 'wc_get_orders' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WooCommerce not available', 'suretriggers' ),
			];
		}

		// Validate required fields.
		foreach ( $fields as $field ) {
			if ( array_key_exists( 'validationProps', $field ) && empty( $selected_options[ $field['name'] ] ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'Required field is missing: ', 'suretriggers' ) . $field['name'],
				];
			}
		}

		// Get parameters.
		$customer_email = ! empty( $selected_options['customer_email'] ) ? sanitize_email( $selected_options['customer_email'] ) : '';
		$limit          = ! empty( $selected_options['limit'] ) ? intval( $selected_options['limit'] ) : 50; // Default limit.
		$status         = ! empty( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : '';
		$date_from      = ! empty( $selected_options['date_from'] ) ? sanitize_text_field( $selected_options['date_from'] ) : '';
		$date_to        = ! empty( $selected_options['date_to'] ) ? sanitize_text_field( $selected_options['date_to'] ) : '';

		if ( empty( $customer_email ) || ! is_email( $customer_email ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Valid customer email is required', 'suretriggers' ),
			];
		}

		// Ensure limit is reasonable.
		if ( $limit > 100 ) {
			$limit = 100; // Max 100 orders per request.
		}

		try {
			// Build query arguments.
			$args = [
				'customer' => $customer_email,
				'limit'    => $limit,
				'orderby'  => 'date',
				'order'    => 'DESC',
			];

			// Add status filter if provided.
			if ( ! empty( $status ) ) {
				$args['status'] = $status;
			}

			// Add date range if provided.
			if ( ! empty( $date_from ) ) {
				$args['date_created'] = '>=' . $date_from;
			}
			if ( ! empty( $date_to ) ) {
				if ( ! empty( $date_from ) ) {
					$args['date_created'] = $date_from . '...' . $date_to;
				} else {
					$args['date_created'] = '<=' . $date_to;
				}
			}

			// Get orders.
			$orders = wc_get_orders( $args );

			if ( empty( $orders ) ) {
				return [
					'status'        => 'success',
					'message'       => __( 'No orders found for this email', 'suretriggers' ),
					'orders'        => [],
					'orders_count'  => 0,
					'search_email'  => $customer_email,
					'search_params' => [
						'limit'     => $limit,
						'status'    => $status,
						'date_from' => $date_from,
						'date_to'   => $date_to,
					],
				];
			}

			$orders_data = [];
			$total_value = 0;

			if ( is_array( $orders ) ) {
				foreach ( $orders as $order ) {
					if ( ! $order instanceof WC_Order ) {
						continue;
					}

					// Get order items.
					$items       = [];
					$order_items = $order->get_items();
					foreach ( $order_items as $item ) {
						if ( $item instanceof \WC_Order_Item_Product ) {
							$product     = $item->get_product();
							$product_sku = '';
							if ( $product && $product instanceof \WC_Product ) {
								$product_sku = $product->get_sku();
							}
							$items[] = [
								'id'           => $item->get_id(),
								'name'         => $item->get_name(),
								'product_id'   => $item->get_product_id(),
								'variation_id' => $item->get_variation_id(),
								'quantity'     => $item->get_quantity(),
								'total'        => $item->get_total(),
								'sku'          => $product_sku,
							];
						}
					}

					// Get coupons used.
					$coupons = [];
					foreach ( $order->get_coupon_codes() as $coupon_code ) {
						$coupon_discount = 0;
						
						// Get discount amount from coupon items.
						$used_coupons = $order->get_items( 'coupon' );
						foreach ( $used_coupons as $coupon_item ) {
							if ( $coupon_item instanceof \WC_Order_Item_Coupon && $coupon_item->get_name() === $coupon_code ) {
								$coupon_discount = abs( floatval( $coupon_item->get_discount() ) );
								break;
							}
						}
						
						$coupons[] = [
							'code'   => $coupon_code,
							'amount' => $coupon_discount,
						];
					}

					// Get order meta.
					$order_meta = [];
					$meta_data  = $order->get_meta_data();
					if ( is_array( $meta_data ) ) {
						foreach ( $meta_data as $meta ) {
							$meta_array = $meta->get_data();
							if ( is_array( $meta_array ) && isset( $meta_array['key'] ) && is_string( $meta_array['key'] ) && ! str_starts_with( $meta_array['key'], '_' ) ) { // Skip private meta.
								$order_meta[ $meta_array['key'] ] = $meta_array['value'];
							}
						}
					}

					$order_data = [
						'id'                   => $order->get_id(),
						'order_number'         => $order->get_order_number(),
						'status'               => $order->get_status(),
						'currency'             => $order->get_currency(),
						'total'                => $order->get_total(),
						'subtotal'             => $order->get_subtotal(),
						'tax_total'            => $order->get_total_tax(),
						'shipping_total'       => $order->get_shipping_total(),
						'discount_total'       => $order->get_discount_total(),
						'payment_method'       => $order->get_payment_method(),
						'payment_method_title' => $order->get_payment_method_title(),
						'transaction_id'       => $order->get_transaction_id(),
						'date_created'         => $order->get_date_created() ? $order->get_date_created()->getTimestamp() : null,
						'date_modified'        => $order->get_date_modified() ? $order->get_date_modified()->getTimestamp() : null,
						'date_completed'       => $order->get_date_completed() ? $order->get_date_completed()->getTimestamp() : null,
						'customer_note'        => $order->get_customer_note(),
						'billing_address'      => [
							'first_name' => $order->get_billing_first_name(),
							'last_name'  => $order->get_billing_last_name(),
							'company'    => $order->get_billing_company(),
							'address_1'  => $order->get_billing_address_1(),
							'address_2'  => $order->get_billing_address_2(),
							'city'       => $order->get_billing_city(),
							'state'      => $order->get_billing_state(),
							'postcode'   => $order->get_billing_postcode(),
							'country'    => $order->get_billing_country(),
							'email'      => $order->get_billing_email(),
							'phone'      => $order->get_billing_phone(),
						],
						'shipping_address'     => [
							'first_name' => $order->get_shipping_first_name(),
							'last_name'  => $order->get_shipping_last_name(),
							'company'    => $order->get_shipping_company(),
							'address_1'  => $order->get_shipping_address_1(),
							'address_2'  => $order->get_shipping_address_2(),
							'city'       => $order->get_shipping_city(),
							'state'      => $order->get_shipping_state(),
							'postcode'   => $order->get_shipping_postcode(),
							'country'    => $order->get_shipping_country(),
						],
						'items'                => $items,
						'items_count'          => count( $items ),
						'coupons'              => $coupons,
						'order_meta'           => $order_meta,
					];

					$orders_data[] = $order_data;
					$total_value  += floatval( $order->get_total() );
				}
			}

			// Calculate summary statistics.
			$statuses    = array_count_values( array_column( $orders_data, 'status' ) );
			$order_count = count( $orders_data );

			return [
				'status'        => 'success',
				'message'       => sprintf( __( 'Found %d orders for this email', 'suretriggers' ), $order_count ),
				'orders'        => $orders_data,
				'orders_count'  => $order_count,
				'total_value'   => wc_format_decimal( $total_value, 2 ),
				'search_email'  => $customer_email,
				'search_params' => [
					'limit'     => $limit,
					'status'    => $status,
					'date_from' => $date_from,
					'date_to'   => $date_to,
				],
				'summary'       => [
					'total_orders' => $order_count,
					'total_value'  => wc_format_decimal( $total_value, 2 ),
					'statuses'     => $statuses,
					'date_range'   => [
						'from' => $date_from,
						'to'   => $date_to,
					],
				],
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => __( 'Error retrieving orders: ', 'suretriggers' ) . $e->getMessage(),
			];
		}
	}
}

GetOrdersByEmail::get_instance();
