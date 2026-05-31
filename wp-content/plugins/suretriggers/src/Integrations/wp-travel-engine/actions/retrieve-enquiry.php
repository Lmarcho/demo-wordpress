<?php
/**
 * RetrieveEnquiry.
 * php version 5.6
 *
 * @category RetrieveEnquiry
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPTravelEngine\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * RetrieveEnquiry
 *
 * @category RetrieveEnquiry
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveEnquiry extends AutomateAction {

	use SingletonLoader;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WPTravelEngine';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wte_retrieve_enquiry';

	/**
	 * Register the action.
	 *
	 * @param array $actions Actions array.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve an Enquiry', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, '_action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id User ID.
	 * @param int   $automation_id Automation ID.
	 * @param array $fields Fields.
	 * @param array $selected_options Selected options.
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( empty( $selected_options['enquiry_id'] ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Enquiry ID is required.', 'suretriggers' ), 
				
			];
		}
	
		$enquiry_id = absint( $selected_options['enquiry_id'] );
		$post       = get_post( $enquiry_id );
	
		if ( ! $post || 'enquiry' !== $post->post_type ) {
			return [
				'status'  => 'error',
				'message' => __( 'Enquiry not found.', 'suretriggers' ), 
				
			];
		}
	
		$form_data_raw = get_post_meta( $enquiry_id, 'wp_travel_engine_enquiry_formdata', true );

		if ( is_string( $form_data_raw ) ) {
			$form_data = maybe_unserialize( $form_data_raw );

			if ( ! is_array( $form_data ) ) {
				$form_data = [];
			}
		} else {
			$form_data = is_array( $form_data_raw ) ? $form_data_raw : [];
		}

		$enquiry_details = [
			'enquiry_id'  => $enquiry_id,
			'post_title'  => $post->post_title,
			'post_status' => $post->post_status,
			'form_data'   => $form_data,
		];
	
		return [
			'status' => 'success',
			'data'   => $enquiry_details,
		];
	}
}

RetrieveEnquiry::get_instance();
