<?php
/**
 * CreateSubscriptionOrderParent.
 * php version 5.6
 *
 * @category CreateSubscriptionOrderParent
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WoocommerceSubscriptions\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Integrations\WooCommerce\WooCommerce;
use SureTriggers\Traits\SingletonLoader;
use Exception;
use WC_Subscriptions_Product;
use WC_Order;

/**
 * CreateSubscriptionOrderParent
 *
 * @category CreateSubscriptionOrderParent
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateSubscriptionOrderParent extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WoocommerceSubscriptions';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wc_create_subscription_order_parent';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create a subscription order with parent order id', 'suretriggers' ),
			'action'   => 'wc_create_subscription_order_parent',
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param mixed $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selectedOptions.
	 * @throws Exception Exception.
	 *
	 * @return object|array|null|void
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wc_create_order' ) ) {
			return [
				'status'  => 'error',
				'message' => 'WooCommerce function `wc_create_order` is missing.',
			];
		}
		
		if ( ! function_exists( 'wcs_create_subscription' ) ) {
			return [
				'status'  => 'error',
				'message' => 'WooCommerce Subscriptions function `wcs_create_subscription` is missing.',
			];
		}
		
		if ( ! class_exists( 'WC_Subscriptions' ) ) {
			return [
				'status'  => 'error',
				'message' => 'WooCommerce Subscriptions plugin is not active.',
			];
		}

		if ( ! class_exists( '\WC_Order' ) ) {
			return [
				'status'  => 'error',
				'message' => __( '\WC_Order class not found.', 'suretriggers' ), 
				
			];
		}

		if ( ! class_exists( 'WC_Subscriptions_Product' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WC_Subscriptions_Product class not found.', 'suretriggers' ), 
				
			];
		}

		$parent_order_id = isset( $selected_options['parent_order_id'] ) ? intval( $selected_options['parent_order_id'] ) : 0;

		if ( $parent_order_id <= 0 ) {
			return [
				'status'  => 'error',
				'message' => __( 'Parent order ID is required.', 'suretriggers' ),
			];
		}

		$parent_order = wc_get_order( $parent_order_id );
		if ( ! $parent_order || ! $parent_order instanceof \WC_Order ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid parent order ID provided.', 'suretriggers' ),
			];
		}

		$user_id     = $parent_order->get_user_id();
		$order_items = $parent_order->get_items();

		if ( empty( $order_items ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Parent order has no products to create subscription.', 'suretriggers' ),
			];
		}

		$bundle_containers    = [];
		$subscription_product = null;
		$product_id           = 0;
		
		foreach ( $order_items as $item ) {
			if ( ! method_exists( $item, 'get_product' ) || ! method_exists( $item, 'get_product_id' ) ) {
				continue;
			}
			
			$bundled_by = $item->get_meta( '_bundled_by' );
			if ( ! empty( $bundled_by ) ) {
				continue;
			}
			
			$product    = $item->get_product();
			$product_id = $item->get_product_id();
			
			if ( $product ) {
				$bundled_items_meta = $item->get_meta( '_bundled_items' );
				if ( ! empty( $bundled_items_meta ) ) {
					$bundle_containers[] = $item;
				}
				
				if ( ! $subscription_product ) {
					$subscription_product = $product;
				}
			}
		}

		if ( ! $subscription_product ) {
			return [
				'status'  => 'error',
				'message' => __( 'No product found in parent order.', 'suretriggers' ),
			];
		}

		if ( ! class_exists( 'WC_Subscriptions_Product' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WC_Subscriptions_Product class not found.', 'suretriggers' ),
			];
		}

		$billing_period   = WC_Subscriptions_Product::get_period( $product_id );
		$billing_interval = WC_Subscriptions_Product::get_interval( $product_id );
		
		if ( empty( $billing_period ) ) {
			$billing_period = 'year';
		}
		
		if ( empty( $billing_interval ) || $billing_interval <= 0 ) {
			$billing_interval = 1;
		}
		
		$sub_args = [
			'order_id'         => $parent_order->get_id(),
			'customer_id'      => $user_id,
			'billing_period'   => $billing_period,
			'billing_interval' => $billing_interval,
		];

		$sub = wcs_create_subscription( $sub_args );
		
		if ( is_wp_error( $sub ) ) {
			$error_message = $sub->get_error_message();
			return [
				'status'  => 'error',
				'message' => 'Failed to create a subscription: ' . $error_message,
			];
		}
		
		if ( ! $sub ) {
			return [
				'status'  => 'error',
				'message' => 'Failed to create a subscription: No subscription object returned.',
			];
		}

		foreach ( $order_items as $item ) {
			if ( ! method_exists( $item, 'get_product' ) || ! method_exists( $item, 'get_quantity' ) ) {
				continue;
			}
			
			$bundled_by = $item->get_meta( '_bundled_by' );
			if ( ! empty( $bundled_by ) ) {
				continue;
			}
			
			$product = $item->get_product();
			if ( $product ) {
				$bundled_items_meta = $item->get_meta( '_bundled_items' );
				if ( ! empty( $bundled_items_meta ) ) {
					$this->add_bundle_product_to_subscription( $sub, $product, $item, $parent_order );
				} else {
					$sub->add_product( $product, $item->get_quantity() );
				}
			}
		}
		
		$billing_address = [
			'first_name' => $parent_order->get_billing_first_name(),
			'last_name'  => $parent_order->get_billing_last_name(),
			'company'    => $parent_order->get_billing_company(),
			'country'    => $parent_order->get_billing_country(),
			'address_1'  => $parent_order->get_billing_address_1(),
			'address_2'  => $parent_order->get_billing_address_2(),
			'city'       => $parent_order->get_billing_city(),
			'state'      => $parent_order->get_billing_state(),
			'postcode'   => $parent_order->get_billing_postcode(),
			'phone'      => $parent_order->get_billing_phone(),
			'email'      => $parent_order->get_billing_email(),
		];

		$shipping_address = [
			'first_name' => $parent_order->get_shipping_first_name() ? $parent_order->get_shipping_first_name() : $parent_order->get_billing_first_name(),
			'last_name'  => $parent_order->get_shipping_last_name() ? $parent_order->get_shipping_last_name() : $parent_order->get_billing_last_name(),
			'company'    => $parent_order->get_shipping_company() ? $parent_order->get_shipping_company() : $parent_order->get_billing_company(),
			'country'    => $parent_order->get_shipping_country() ? $parent_order->get_shipping_country() : $parent_order->get_billing_country(),
			'address_1'  => $parent_order->get_shipping_address_1() ? $parent_order->get_shipping_address_1() : $parent_order->get_billing_address_1(),
			'address_2'  => $parent_order->get_shipping_address_2() ? $parent_order->get_shipping_address_2() : $parent_order->get_billing_address_2(),
			'city'       => $parent_order->get_shipping_city() ? $parent_order->get_shipping_city() : $parent_order->get_billing_city(),
			'state'      => $parent_order->get_shipping_state() ? $parent_order->get_shipping_state() : $parent_order->get_billing_state(),
			'postcode'   => $parent_order->get_shipping_postcode() ? $parent_order->get_shipping_postcode() : $parent_order->get_billing_postcode(),
		];
		
		$sub->set_address( $billing_address, 'billing' );
		$sub->set_address( $shipping_address, 'shipping' );
		
		$start_date = gmdate( 'Y-m-d H:i:s' );

		$trial_end_days = isset( $selected_options['trial_end_days'] ) ? $selected_options['trial_end_days'] : '';
		
		if ( '' != $trial_end_days ) {
			$now                 = strtotime( 'now' );
			$trial_end_timestamp = strtotime( "+$trial_end_days days", $now );

			if ( false !== $trial_end_timestamp ) {
				$trial_end_date     = gmdate( 'Y-m-d H:i:s', $trial_end_timestamp );
				$dates['trial_end'] = $trial_end_date;

				$trial_end_timestamp = strtotime( $trial_end_date );
				if ( false !== $trial_end_timestamp ) {
					$next_payment_date     = gmdate( 'Y-m-d H:i:s', strtotime( '+1 day', $trial_end_timestamp ) );
					$dates['next_payment'] = WC_Subscriptions_Product::get_expiration_date( $product_id, $next_payment_date );
				}
			}

			$start_date = $sub->get_date_created();
			$end_date   = WC_Subscriptions_Product::get_expiration_date( $product_id, $start_date );

			$dates['end'] = $end_date;
		} else {
			$dates = [
				'trial_end'    => WC_Subscriptions_Product::get_trial_expiration_date( $product_id, $start_date ),
				'next_payment' => WC_Subscriptions_Product::get_first_renewal_payment_date( $product_id, $start_date ),
				'end'          => WC_Subscriptions_Product::get_expiration_date( $product_id, $start_date ),
			];
		}

		$sub->update_dates( $dates );
		$status = isset( $selected_options['status'] ) ? $selected_options['status'] : 'active';
		$sub->update_status( $status );

		if ( ! empty( $sub ) ) {
			$context['subscription'] = [
				'id'                => $sub->get_id(),
				'status'            => $sub->get_status(),
				'start_date'        => $sub->get_date_created(),
				'next_payment_date' => $sub->get_date( 'next_payment' ),
				'trial_end_date'    => $sub->get_date( 'trial_end' ),
				'end_date'          => $sub->get_date( 'end' ),
			];
			
			$context['parent_order'] = [
				'id'     => $parent_order->get_id(),
				'status' => $parent_order->get_status(),
				'total'  => $parent_order->get_total(),
			];
			
			$order_details = WooCommerce::get_order_context( $parent_order->get_id() );
			if ( is_array( $order_details ) ) {
				return array_merge( $context, $order_details );
			}
			
			return $context;
		}
	}
	
	/**
	 * Add bundle product to subscription using WooCommerce Product Bundles integration
	 *
	 * @param \WC_Order      $subscription The subscription object.
	 * @param \WC_Product    $bundle_product The bundle product.
	 * @param \WC_Order_Item $parent_item The parent order item.
	 * @param \WC_Order      $parent_order The parent order.
	 * @return void
	 */
	private function add_bundle_product_to_subscription( $subscription, $bundle_product, $parent_item, $parent_order ) {
		
		if ( class_exists( '\WC_PB_Order' ) && function_exists( 'WC_PB' ) ) {
			$pb_order_instance = \WC_PB_Order::instance();
			if ( is_object( $pb_order_instance ) && method_exists( $pb_order_instance, 'add_bundle_to_order' ) ) {
				
				// Use WooCommerce Product Bundles built-in configuration generator.
				$configuration = WC_PB()->cart->get_posted_bundle_configuration( $bundle_product );
				
				$args = [
					'configuration' => $configuration,
					'silent'        => true,
				];
				
				$result = $pb_order_instance->add_bundle_to_order( $bundle_product, $subscription, $parent_item->get_quantity(), $args );
				
				if ( ! is_wp_error( $result ) ) {
					return;
				}
			}
		}
		
		// Fallback: Add as simple product if bundle integration fails.
		if ( method_exists( $subscription, 'add_product' ) ) {
			$subscription->add_product( $bundle_product, $parent_item->get_quantity() );
		}
	}
	
	
}

CreateSubscriptionOrderParent::get_instance();
