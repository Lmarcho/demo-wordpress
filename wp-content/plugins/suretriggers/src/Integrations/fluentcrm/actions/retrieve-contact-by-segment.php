<?php
/**
 * RetrieveContactBySegment.
 * php version 5.6
 *
 * @category RetrieveContactBySegment
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * RetrieveContactBySegment
 *
 * @category RetrieveContactBySegment
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RetrieveContactBySegment extends AutomateAction {


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCRM';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcrm_retrieve_contact_by_segment';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Retrieve Contact By Segment', 'suretriggers' ),
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
	 * @param array $selected_options selectedOptions.
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not active.', 'suretriggers' ), 
				
			];
		}

		$segment_id = isset( $selected_options['segment_id'] ) ? sanitize_text_field( $selected_options['segment_id'] ) : '';
			
		$segments     = apply_filters( 'fluentcrm_dynamic_segments', [] );
		$segment_type = 'custom_segment';
		
		foreach ( $segments as $segment ) {
			if ( isset( $segment['id'] ) && $segment['id'] == $segment_id ) {
				$segment_type = $segment['slug'];
				break;
			}
		}
		
		$segment = apply_filters(
			"fluentcrm_dynamic_segment_{$segment_type}",
			[],
			$segment_id,
			[
				'subscribers' => true,
				'with'        => [ 'tags', 'lists' ],
			]
		);
		
		if ( empty( $segment ) || empty( $segment['subscribers'] ) ) {
			return [
				'message'     => __( 'No contacts found or segment does not exist', 'suretriggers' ),
				'status'      => 'false',
				'user_exists' => 'false',
			];
		}
		
		$contacts = $segment['subscribers'];
		
		if ( is_object( $contacts ) ) {
			if ( method_exists( $contacts, 'toArray' ) ) {
				$contacts = $contacts->toArray();
			} else {
				$contacts = (array) $contacts;
			}
		}
		
		if ( empty( $contacts ) ) {
			return [
				'message'     => __( 'No contacts found', 'suretriggers' ),
				'status'      => 'false',
				'user_exists' => 'false',
			];
		}

		$context = [];

		if ( is_array( $contacts ) ) {
			foreach ( $contacts as $key => $contact ) {
				$standard_fields = [
					'id',
					'user_id',
					'full_name',
					'first_name',
					'last_name',
					'contact_owner',
					'company_id',
					'email',
					'address_line_1',
					'address_line_2',
					'postal_code',
					'city',
					'state',
					'country',
					'phone',
					'status',
					'contact_type',
					'source',
					'date_of_birth',
					'tags',
					'lists',
				];

				foreach ( $standard_fields as $field ) {
					$context['contact'][ $key ][ $field ] = isset( $contact[ $field ] ) ? $contact[ $field ] : '';
				}

				if ( ! empty( $contact['custom_fields'] ) && is_array( $contact['custom_fields'] ) ) {
					foreach ( $contact['custom_fields'] as $custom_key => $custom_value ) {
						$context['contact'][ $key ][ $custom_key ] = is_array( $custom_value )
							? implode( ',', $custom_value )
							: $custom_value;
					}
				}
			}
		}

		return $context;
	}
}

RetrieveContactBySegment::get_instance();
