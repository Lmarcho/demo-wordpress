<?php
/**
 * UpdatePurchase.
 * php version 5.6
 *
 * @category UpdatePurchase
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
 * UpdatePurchase
 *
 * @category UpdatePurchase
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdatePurchase extends AutomateAction {

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
	public $action = 'edd_update_purchase';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Purchase', 'suretriggers' ),
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
				'message' => 'Purchase not found with ID: ' . $payment_id,
			];
		}

		$updated_fields = [];

		if ( isset( $selected_options['status'] ) && ! empty( $selected_options['status'] ) ) {
			$new_status = sanitize_text_field( $selected_options['status'] );
			$old_status = $payment->status;
			
			$payment->status = $new_status;
			$payment->save();
			
			$updated_fields['status'] = [
				'old_value' => $old_status,
				'new_value' => $new_status,
			];
		}

		if ( isset( $selected_options['note'] ) && ! empty( $selected_options['note'] ) ) {
			$note = sanitize_textarea_field( $selected_options['note'] );
			
			if ( function_exists( 'edd_insert_payment_note' ) ) {
				edd_insert_payment_note( $payment_id, $note );
				$updated_fields['note'] = [
					'value' => $note,
					'added' => true,
				];
			} else {
				$updated_fields['note'] = [
					'value' => $note,
					'added' => false,
					'error' => 'EDD function edd_insert_payment_note not found',
				];
			}
		}


		$response_data = [
			'purchase_id'    => $payment_id,
			'purchase_key'   => $payment->key,
			'customer_email' => $payment->email,
			'customer_id'    => $payment->customer_id,
			'updated_fields' => $updated_fields,
			'updated_at'     => gmdate( 'Y-m-d H:i:s' ),
		];

		return [
			'status'  => 'success',
			'message' => 'Purchase updated successfully',
			'data'    => $response_data,
		];
	}
}

UpdatePurchase::get_instance();
