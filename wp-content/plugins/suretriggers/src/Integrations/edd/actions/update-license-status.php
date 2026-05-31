<?php
/**
 * UpdateLicenseStatus.
 * php version 5.6
 *
 * @category UpdateLicenseStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\EDD\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * UpdateLicenseStatus
 *
 * @category UpdateLicenseStatus
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateLicenseStatus extends AutomateAction {

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
	public $action = 'edd_update_license_status';

	use SingletonLoader;

	/**
	 * Register action.
	 *
	 * @param array $actions action data.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update License Status', 'suretriggers' ),
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
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		// Check if EDD Software Licensing is available.
		if ( ! function_exists( 'edd_software_licensing' ) || ! class_exists( 'EDD_SL_License' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD Software Licensing plugin is not active.',
			];
		}

		// Validate required parameters.
		if ( empty( $selected_options['license_key'] ) || empty( $selected_options['status'] ) ) {
			return [
				'status'  => 'error',
				'message' => 'Missing required parameters (license_key, status)',
			];
		}

		$license_key = sanitize_text_field( $selected_options['license_key'] );
		$new_status  = sanitize_text_field( $selected_options['status'] );

		// Validate status.
		$valid_statuses = [ 'active', 'inactive', 'expired', 'disabled', 'site_inactive' ];
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid license status. Valid statuses: ' . implode( ', ', $valid_statuses ),
			];
		}

		// Find license by key.
		$license_id = edd_software_licensing()->get_license_by_key( $license_key );
		
		if ( ! $license_id ) {
			return [
				'status'  => 'error',
				'message' => 'License not found with key: ' . $license_key,
			];
		}

		/**
		 * EDD Software License instance.
		 *
		 * @var \EDD_SL_License $license
		 */
		$license = new \EDD_SL_License( $license_id );

		if ( ! $license || ! $license->ID ) {
			return [
				'status'  => 'error',
				'message' => 'Failed to load license with ID: ' . $license_id,
			];
		}

		$old_status = $license->status;

		// Update license status.
		$license->update_meta( 'status', $new_status );
		$license->status = $new_status;

		// Trigger actions for other plugins to hook into.
		do_action( 'edd_sl_license_status_updated', $license->ID, $new_status, $old_status );
		
		// Trigger specific status actions.
		switch ( $new_status ) {
			case 'active':
				do_action( 'edd_sl_license_activated', $license->ID );
				break;
			case 'inactive':
				do_action( 'edd_sl_license_deactivated', $license->ID );
				break;
			case 'expired':
				do_action( 'edd_sl_license_expired', $license->ID );
				break;
			case 'disabled':
				do_action( 'edd_sl_license_disabled', $license->ID );
				break;
		}

		$license_data = [
			'license_id'       => $license->ID,
			'license_key'      => $license->key,
			'status'           => $license->status,
			'old_status'       => $old_status,
			'download_id'      => $license->download_id,
			'payment_id'       => $license->payment_id,
			'user_id'          => $license->user_id,
			'customer_email'   => $license->customer->email,
			'expiration_date'  => $license->expiration ? gmdate( 'Y-m-d H:i:s', $license->expiration ) : '',
			'activation_count' => $license->activation_count,
			'activation_limit' => $license->activation_limit,
		];

		return [
			'status'  => 'success',
			'message' => 'License status updated successfully',
			'license' => $license_data,
		];
	}
}

UpdateLicenseStatus::get_instance();
