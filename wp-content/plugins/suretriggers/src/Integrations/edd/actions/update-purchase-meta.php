<?php
/**
 * UpdatePurchaseMeta.
 * php version 5.6
 *
 * @category UpdatePurchaseMeta
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
 * UpdatePurchaseMeta
 *
 * @category UpdatePurchaseMeta
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdatePurchaseMeta extends AutomateAction {

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
	public $action = 'edd_update_purchase_meta';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Purchase Meta', 'suretriggers' ),
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
		if ( ! class_exists( 'EDD_Payment' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD plugin is not active.',
			];
		}

		if ( empty( $selected_options['purchase_id'] ) || empty( $selected_options['purchase_meta'] ) ) {
			return [
				'status'  => 'error',
				'message' => 'Missing required parameters (purchase_id, purchase_meta)',
			];
		}

		$payment_id = intval( $selected_options['purchase_id'] );

		$payment = new \EDD_Payment( $payment_id );
		if ( ! $payment->ID ) {
			return [
				'status'  => 'error',
				'message' => 'Purchase not found with ID: ' . $payment_id,
			];
		}

		$updated_meta = [];

		foreach ( $selected_options['purchase_meta'] as $meta ) {
			$meta_key   = sanitize_text_field( $meta['meta_key'] );
			$meta_value = isset( $meta['meta_value'] ) ? $meta['meta_value'] : '';

			if ( empty( $meta_key ) ) {
				continue;
			}

			$old_value = '';
			if ( function_exists( 'edd_get_payment_meta' ) ) {
				$old_value = edd_get_payment_meta( $payment_id, $meta_key, true );
			} else {
				$old_value = get_post_meta( $payment_id, $meta_key, true );
			}

			$updated = false;
			if ( function_exists( 'edd_update_payment_meta' ) ) {
				$updated = edd_update_payment_meta( $payment_id, $meta_key, $meta_value );
			} else {
				$updated = update_post_meta( $payment_id, $meta_key, $meta_value );
			}

			if ( false !== $updated ) {
				$new_value = '';
				if ( function_exists( 'edd_get_payment_meta' ) ) {
					$new_value = edd_get_payment_meta( $payment_id, $meta_key, true );
				} else {
					$new_value = get_post_meta( $payment_id, $meta_key, true );
				}

				$updated_meta[] = [
					'meta_key'  => $meta_key,
					'old_value' => $old_value,
					'new_value' => $new_value,
					'status'    => 'updated',
				];
			} else {
				$updated_meta[] = [
					'meta_key'  => $meta_key,
					'old_value' => $old_value,
					'new_value' => $meta_value,
					'status'    => 'failed',
				];
			}
		}

		$response_data = [
			'purchase_id'    => $payment_id,
			'purchase_key'   => $payment->key,
			'customer_email' => $payment->email,
			'customer_id'    => $payment->customer_id,
			'updated_meta'   => $updated_meta,
			'updated_at'     => gmdate( 'Y-m-d H:i:s' ),
		];

		return [
			'status'  => 'success',
			'message' => 'Purchase meta operations completed',
			'data'    => $response_data,
		];
	}
}

UpdatePurchaseMeta::get_instance();
