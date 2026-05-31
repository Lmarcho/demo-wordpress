<?php
/**
 * PackagePurchase.
 * php version 5.6
 *
 * @category PackagePurchase
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

if ( ! class_exists( 'PackagePurchase' ) ) :

	/**
	 * PackagePurchase
	 *
	 * @category PackagePurchase
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class PackagePurchase {

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
		public $trigger = 'amelia_package_purchase';

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
				'label'         => __( 'Package Purchased', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'amelia_before_package_customer_added',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];
			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $args Package Data.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $args ) {
			if ( empty( $args ) ) {
				return;
			}

			global $wpdb;

			if ( ! isset( $args['packageId'] ) || ! isset( $args['customerId'] ) ) {
				return;
			}

			$package_result = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . $wpdb->prefix . 'amelia_packages WHERE id = %d',
					[ $args['packageId'] ]
				),
				ARRAY_A
			);

			$customer_result = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . $wpdb->prefix . 'amelia_users WHERE id = %d',
					[ $args['customerId'] ]
				),
				ARRAY_A
			);

			$payment_result = [];

			if ( isset( $args['coupon'] ) ) {
				$coupon_result = $wpdb->get_row(
					$wpdb->prepare(
						'SELECT code AS couponCode, expirationDate AS couponExpirationDate FROM ' . $wpdb->prefix . 'amelia_coupons WHERE id = %d',
						[ $args['coupon']['id'] ]
					),
					ARRAY_A
				);
			} else {
				$coupon_result = [];
			}

			$fields_arr = [];
			if ( ! empty( $args['customFields'] ) ) {
				$custom_fields = is_string( $args['customFields'] ) ? 
					json_decode( $args['customFields'], true ) : 
					$args['customFields'];

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
				$args 
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
			$context['amelia_package_list'] = $args['packageId'];
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
	PackagePurchase::get_instance();

endif;
