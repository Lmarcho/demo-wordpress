<?php
/**
 * PackagePurchaseStatusUpdated.
 * php version 5.6
 *
 * @category PackagePurchaseStatusUpdated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Amelia\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'PackagePurchaseStatusUpdated' ) ) :

	/**
	 * PackagePurchaseStatusUpdated
	 *
	 * @category PackagePurchaseStatusUpdated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class PackagePurchaseStatusUpdated {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'Amelia';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'amelia_package_purchase_status_updated';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Package Purchase Status Updated', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'amelia_before_package_customer_status_updated',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];
			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array  $package_customer Package customer data.
		 * @param string $status New status.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $package_customer, $status ) {
			if ( empty( $package_customer ) || ! is_array( $package_customer ) ) {
				return;
			}

			global $wpdb;

			$package_result = [];
			if ( isset( $package_customer['packageId'] ) ) {
				$package_result = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT * FROM ' . $wpdb->prefix . 'amelia_packages WHERE id = %d',
						[ $package_customer['packageId'] ]
					),
					ARRAY_A
				);
			}

			$customer_result = [];
			if ( isset( $package_customer['customerId'] ) ) {
				$customer_result = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT * FROM ' . $wpdb->prefix . 'amelia_users WHERE id = %d',
						[ $package_customer['customerId'] ]
					),
					ARRAY_A
				);
			}

			$payment_result = [];
			if ( isset( $package_customer['id'] ) && ! empty( $package_customer['id'] ) ) {
				$payment_result = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT * FROM ' . $wpdb->prefix . 'amelia_payments WHERE packageCustomerId = %d',
						[ $package_customer['id'] ]
					),
					ARRAY_A
				);
			}

			$coupon_result = [];
			if ( isset( $package_customer['couponId'] ) && ! empty( $package_customer['couponId'] ) ) {
				$coupon_result = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT code AS couponCode, expirationDate AS couponExpirationDate FROM ' . $wpdb->prefix . 'amelia_coupons WHERE id = %d',
						[ $package_customer['couponId'] ]
					),
					ARRAY_A
				);
			}

			$fields_arr = [];
			if ( ! empty( $package_customer['customFields'] ) ) {
				$custom_fields = is_string( $package_customer['customFields'] ) ? 
					json_decode( $package_customer['customFields'], true ) : 
					$package_customer['customFields'];

				if ( is_array( $custom_fields ) ) {
					foreach ( $custom_fields as $fields ) {
						if ( is_array( $fields ) && isset( $fields['label'], $fields['value'] ) ) {
							$fields_arr[ $fields['label'] ] = $fields['value'];
						}
					}
				}
			}

			$context = array_merge( 
				(array) $package_result, 
				(array) $customer_result, 
				(array) $payment_result, 
				(array) $coupon_result, 
				$fields_arr, 
				$package_customer 
			);

			$json_fields = [ 'limitPerCustomer', 'settings', 'translations', 'customFields' ];
			foreach ( $json_fields as $field ) {
				if ( isset( $context[ $field ] ) && is_string( $context[ $field ] ) && ! empty( $context[ $field ] ) ) {
					$decoded = json_decode( $context[ $field ], true );
					if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
						foreach ( $decoded as $key => $value ) {
							if ( is_array( $value ) ) {
								foreach ( $value as $subkey => $subvalue ) {
									$context[ $field . '_' . $key . '_' . $subkey ] = $subvalue;
								}
							} else {
								$context[ $field . '_' . $key ] = $value;
							}
						}
						$context[ $field . '_original' ] = $decoded;
						unset( $context[ $field ] );
					}
				}
			}
			$context['old_status']          = isset( $package_customer['status'] ) ? $package_customer['status'] : '';
			$context['new_status']          = $status;
			$context['amelia_package_list'] = isset( $package_customer['packageId'] ) ? $package_customer['packageId'] : '';
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	PackagePurchaseStatusUpdated::get_instance();

endif;
