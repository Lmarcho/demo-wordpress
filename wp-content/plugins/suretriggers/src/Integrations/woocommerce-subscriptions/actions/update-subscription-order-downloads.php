<?php
/**
 * UpdateSubscriptionOrderDownloads.
 * php version 5.6
 *
 * @category UpdateSubscriptionOrderDownloads
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WoocommerceSubscriptions\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;
use WC_Order;
use WC_Subscription;

/**
 * UpdateSubscriptionOrderDownloads
 *
 * @category UpdateSubscriptionOrderDownloads
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateSubscriptionOrderDownloads extends AutomateAction {

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
	public $action = 'wc_update_subscription_order_downloads';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Subscription Order Download Permissions', 'suretriggers' ),
			'action'   => 'wc_update_subscription_order_downloads',
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
		if ( ! function_exists( 'wcs_get_subscription' ) || ! class_exists( 'WC_Subscriptions' ) ) {
			return [
				'status'  => 'error',
				'message' => 'WooCommerce Subscriptions plugin is not active or functions are missing.',
			];
		}

		if ( ! class_exists( 'WC_Order' ) || ! class_exists( 'WC_Subscription' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WC_Order or WC_Subscription class not found.', 'suretriggers' ),
			];
		}

		$subscription_id = isset( $selected_options['subscription_id'] ) ? intval( $selected_options['subscription_id'] ) : 0;

		if ( $subscription_id <= 0 ) {
			return [
				'status'  => 'error',
				'message' => __( 'Subscription ID is required.', 'suretriggers' ),
			];
		}

		$subscription = wcs_get_subscription( $subscription_id );
		if ( ! $subscription || ! $subscription instanceof WC_Subscription ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid subscription ID provided.', 'suretriggers' ),
			];
		}

		$result = $this->wc_update_subscription_order_downloads( $subscription_id );

		if ( isset( $result['status'] ) && 'error' === $result['status'] ) {
			return $result;
		}

		return [
			'status'            => 'success',
			'message'           => __( 'Download permissions updated successfully.', 'suretriggers' ),
			'subscription_id'   => $subscription_id,
			'downloads_updated' => $result['downloads_updated'],
			'permissions_count' => $result['permissions_count'],
		];
	}

	/**
	 * Update subscription order downloads.
	 *
	 * @param int $subscription_id Subscription ID.
	 * @return array
	 */
	private function wc_update_subscription_order_downloads( $subscription_id ) {
		if ( ! function_exists( 'wcs_get_subscription' ) ) {
			return [
				'status'  => 'error',
				'message' => 'WooCommerce Subscriptions functions not available.',
			];
		}

		$subscription = wcs_get_subscription( $subscription_id );
		
		if ( ! $subscription ) {
			return [
				'status'  => 'error',
				'message' => 'Subscription not found.',
			];
		}

		$data_store = \WC_Data_Store::load( 'customer-download' );
		if ( method_exists( $data_store, 'delete_by_order_id' ) ) {
			$data_store->delete_by_order_id( $subscription_id );
		}

		$downloads_updated    = wc_downloadable_product_permissions( $subscription_id, true );
		$user_id              = $subscription->get_user_id();
		$download_permissions = wc_get_customer_download_permissions( $user_id );
		$permissions_count    = 0;
		
		foreach ( $download_permissions as $permission ) {
			if ( $permission->order_id == $subscription_id ) {
				$permissions_count++;
			}
		}

		do_action( 'wc_subscription_downloads_updated', $subscription_id, $downloads_updated );

		return [
			'downloads_updated' => $downloads_updated ? $downloads_updated : 0,
			'permissions_count' => $permissions_count,
		];
	}
}

UpdateSubscriptionOrderDownloads::get_instance();
