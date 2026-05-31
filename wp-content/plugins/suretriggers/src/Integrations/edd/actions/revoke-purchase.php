<?php
/**
 * RevokePurchase.
 * php version 5.6
 *
 * @category RevokePurchase
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\EDD\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * RevokePurchase
 *
 * @category RevokePurchase
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RevokePurchase extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'EDD';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'edd_revoked_purchase';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Revoke Purchase', 'suretriggers' ),
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
	 * @return array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'edd_update_payment_status' ) || ! class_exists( 'EDD_Payment' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD plugin is not active.',
			];
		}

		if ( empty( $selected_options['purchase_id'] ) ) {
			return [
				'status'  => 'error',
				'message' => 'Missing required parameter: purchase_id',
			];
		}

		$payment_id = intval( $selected_options['purchase_id'] );

		$payment = new \EDD_Payment( $payment_id );
		if ( ! $payment->ID ) {
			return [
				'status'  => 'error',
				'message' => 'Payment/Order not found with ID: ' . $payment_id,
			];
		}

		$old_status = $payment->status;

		if ( 'revoked' === $old_status ) {
			return [
				'status'  => 'error',
				'message' => 'Purchase is already revoked.',
			];
		}

		if ( ! function_exists( 'edd_update_payment_status' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD function edd_update_payment_status not found',
			];
		}

		$updated = edd_update_payment_status( $payment_id, 'revoked' );

		if ( ! $updated ) {
			return [
				'status'  => 'error',
				'message' => 'Failed to revoke purchase. Please try again.',
			];
		}

		if ( function_exists( 'edd_insert_payment_note' ) ) {
			$note = 'Purchase revoked via SureTriggers automation.';
			edd_insert_payment_note( $payment_id, $note );
		}

		$revoked_licenses = [];
		if ( function_exists( 'edd_software_licensing' ) ) {
			global $wpdb;
			$licenses_table  = $wpdb->prefix . 'edd_licenses';
			$licenses_result = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $licenses_table ) );
			
			if ( $licenses_result == $licenses_table ) {
				$licenses = $wpdb->get_results( 
					$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}edd_licenses WHERE payment_id = %d", $payment_id ),
					ARRAY_A 
				);
				
				if ( ! empty( $licenses ) ) {
					foreach ( $licenses as $license ) {
						if ( 'disabled' !== $license['status'] ) {
							$license_id = $license['ID'];
							
							if ( function_exists( 'edd_software_licensing' ) ) {
								$license_obj = edd_software_licensing()->get_license( $license_id );
								if ( $license_obj && is_object( $license_obj ) && method_exists( $license_obj, 'disable' ) ) {
									$license_obj->disable();
									$revoked_licenses[] = [
										'license_id'  => $license_id,
										'license_key' => $license['license_key'],
										'old_status'  => $license['status'],
										'new_status'  => 'disabled',
									];
								}
							}
						}
					}
				}
			}
		}


		$payment = new \EDD_Payment( $payment_id );

		$order_data = [
			'payment_id'     => $payment->ID,
			'purchase_key'   => $payment->key,
			'customer_email' => $payment->email,
			'customer_id'    => $payment->customer_id,
			'user_id'        => $payment->user_id,
			'first_name'     => $payment->first_name,
			'last_name'      => $payment->last_name,
			'total_amount'   => $payment->total,
			'currency'       => $payment->currency,
			'old_status'     => $old_status,
			'new_status'     => $payment->status,
			'payment_method' => $payment->gateway,
			'date_revoked'   => gmdate( 'Y-m-d H:i:s' ),
			'downloads'      => array_map(
				function( $item ) {
					return [
						'download_id' => $item['id'],
						'name'        => $item['name'],
						'price'       => $item['price'],
						'quantity'    => isset( $item['quantity'] ) ? $item['quantity'] : 1,
					];
				},
				$payment->cart_details 
			),
		];

		if ( ! empty( $revoked_licenses ) ) {
			$order_data['revoked_licenses'] = $revoked_licenses;
		}

		return [
			'status'  => 'success',
			'message' => 'Purchase revoked successfully',
			'order'   => $order_data,
		];
	}
}

RevokePurchase::get_instance();
