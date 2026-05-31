<?php
/**
 * GetContactsWhoOpenedEmail.
 * php version 5.6
 *
 * @category GetContactsWhoOpenedEmail
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
 * GetContactsWhoOpenedEmail
 *
 * @category GetContactsWhoOpenedEmail
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetContactsWhoOpenedEmail extends AutomateAction {

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
	public $action = 'fluentcrm_get_contacts_who_opened_email';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Contacts Who Opened Email', 'suretriggers' ),
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
		
		$campaign_id = isset( $selected_options['campaign_id'] ) ? absint( $selected_options['campaign_id'] ) : 0;
		$username    = isset( $selected_options['api_username'] ) ? sanitize_text_field( $selected_options['api_username'] ) : '';
		$password    = isset( $selected_options['api_password'] ) ? sanitize_text_field( $selected_options['api_password'] ) : '';
		$wp_url      = isset( $selected_options['wordpress_url'] ) ? esc_url_raw( $selected_options['wordpress_url'] ) : '';

		if ( ! $campaign_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Campaign ID is required.', 'suretriggers' ),
			];
		}

		if ( empty( $wp_url ) || empty( $username ) || empty( $password ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WordPress URL, Username, and Password are required.', 'suretriggers' ),
			];
		}

		$args             = [
			'headers'   => [
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
				'Content-Type'  => 'application/json',
			],
			'sslverify' => false,
		];
		$request          = wp_remote_get( $selected_options['wordpress_url'] . '/wp-json/fluent-crm/v2/campaigns/' . $campaign_id . '/emails?filter_type=view', $args );
		$response_code    = wp_remote_retrieve_response_code( $request );
		$response_body    = wp_remote_retrieve_body( $request );
		$response_context = json_decode( $response_body, true );

		if ( 200 !== $response_code ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to fetch opened contacts.', 'suretriggers' ),
			];
		}

		$opened_contacts = [];
		if ( is_array( $response_context ) && isset( $response_context['emails']['data'] ) && is_array( $response_context['emails']['data'] ) ) {
			foreach ( $response_context['emails']['data'] as $email ) {
				if ( isset( $email['subscriber'] ) ) {
					$opened_contacts[] = [
						'id'         => $email['subscriber']['id'],
						'email'      => $email['subscriber']['email'],
						'first_name' => $email['subscriber']['first_name'],
						'last_name'  => $email['subscriber']['last_name'],
						'opened_at'  => $email['updated_at'],
					];
				}
			}
		}

		return [
			'status'   => 'success',
			'contacts' => $opened_contacts,
			'count'    => count( $opened_contacts ),
		];
	}
}

GetContactsWhoOpenedEmail::get_instance();
