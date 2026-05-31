<?php
/**
 * GetSubscriptionDetails.
 * php version 5.6
 *
 * @category GetSubscriptionDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPSubscription\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * GetSubscriptionDetails
 *
 * @category GetSubscriptionDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetSubscriptionDetails extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WPSubscription';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wp_get_subscription_details';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Subscription Details', 'suretriggers' ),
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
	 * @param array $selected_options selected_options.
	 *
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$subscription_id = ( isset( $selected_options['subscription_id'] ) && is_scalar( $selected_options['subscription_id'] ) )
			? absint( $selected_options['subscription_id'] )
			: 0;

		if ( empty( $subscription_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Subscription ID is required.', 'suretriggers' ), 
				
			];
		}

		$subscription = get_post( $subscription_id );
		if ( ! ( $subscription instanceof \WP_Post ) || 'subscrpt_order' !== $subscription->post_type ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid subscription ID.', 'suretriggers' ), 
				
			];
		}

		$subscription_data = [
			'subscription_id' => $subscription_id,
			'status'          => $subscription->post_status,
			'user_id'         => $subscription->post_author,
			'created_date'    => $subscription->post_date,
			'modified_date'   => $subscription->post_modified,
		];

		$meta_keys = [
			'_subscrpt_order_id',
			'_subscrpt_product_id',
			'_subscrpt_price',
			'_subscrpt_period',
			'_subscrpt_period_interval',
			'_subscrpt_trial_period',
			'_subscrpt_trial_period_interval',
			'_subscrpt_next_payment',
			'_subscrpt_end_date',
		];

		foreach ( $meta_keys as $meta_key ) {
			$meta_value                = get_post_meta( $subscription_id, $meta_key, true );
			$key                       = str_replace( '_subscrpt_', '', $meta_key );
			$subscription_data[ $key ] = $meta_value;
		}

		// Get product details.
		$product_id_raw = get_post_meta( $subscription_id, '_subscrpt_product_id', true );
		$product_id     = is_scalar( $product_id_raw ) ? absint( $product_id_raw ) : 0;

		if ( $product_id ) {
			$product = get_post( $product_id );
			if ( $product instanceof \WP_Post ) {
				$subscription_data['product_details'] = [
					'product_id'    => $product_id,
					'product_name'  => $product->post_title,
					'product_price' => get_post_meta( $product_id, '_price', true ),
					'product_sku'   => get_post_meta( $product_id, '_sku', true ),
				];
			}
		}

		// Get customer details.
		$user_id = (int) $subscription->post_author;
		if ( $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user ) {
				$subscription_data['customer_details'] = [
					'customer_id' => $user_id,
					'email'       => $user->user_email,
					'first_name'  => get_user_meta( $user_id, 'first_name', true ),
					'last_name'   => get_user_meta( $user_id, 'last_name', true ),
				];
			}
		}

		// Get billing and shipping addresses from WooCommerce order.
		$order_id_raw = get_post_meta( $subscription_id, '_subscrpt_order_id', true );
		$order_id     = is_scalar( $order_id_raw ) ? absint( $order_id_raw ) : 0;

		if ( $order_id && function_exists( 'wc_get_order' ) ) {
			$order = wc_get_order( $order_id );
			if ( $order instanceof \WC_Order ) {
				$subscription_data['billing_address'] = [
					'first_name' => $order->get_billing_first_name(),
					'last_name'  => $order->get_billing_last_name(),
					'address_1'  => $order->get_billing_address_1(),
					'city'       => $order->get_billing_city(),
					'state'      => $order->get_billing_state(),
					'postcode'   => $order->get_billing_postcode(),
					'country'    => $order->get_billing_country(),
					'email'      => $order->get_billing_email(),
					'phone'      => $order->get_billing_phone(),
				];

				$subscription_data['shipping_address'] = [
					'first_name' => $order->get_shipping_first_name(),
					'last_name'  => $order->get_shipping_last_name(),
					'address_1'  => $order->get_shipping_address_1(),
					'city'       => $order->get_shipping_city(),
					'state'      => $order->get_shipping_state(),
					'postcode'   => $order->get_shipping_postcode(),
					'country'    => $order->get_shipping_country(),
				];
			}
		}

		return $subscription_data;
	}
}

GetSubscriptionDetails::get_instance();
