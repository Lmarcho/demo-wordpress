<?php
/**
 * SendEmailByTemplate.
 * php version 5.6
 *
 * @category SendEmailByTemplate
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
 * SendEmailByTemplate
 *
 * @category SendEmailByTemplate
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SendEmailByTemplate extends AutomateAction {

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
	public $action = 'fluentcrm_send_email_by_template';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Send Email By Template', 'suretriggers' ),
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
	 *
	 * @return array|void|mixed
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		// Check if FluentCRM is active.
		if ( ! class_exists( '\FluentCrm\App\Models\Template' ) || ! class_exists( '\FluentCrm\App\Models\Campaign' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not installed or activated.', 'suretriggers' ), 
				
			];
		}

		$template_id   = isset( $selected_options['template_id'] ) ? absint( $selected_options['template_id'] ) : 0;
		$selected_list = $selected_options['list_id'];
		$selected_tag  = $selected_options['tag_id'];
		
		if ( ! $template_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Template ID is required.', 'suretriggers' ), 
				
			];
		}

		// Get the template.
		$template = \FluentCrm\App\Models\Template::find( $template_id );
		
		if ( ! $template ) {
			return [
				'status'  => 'error',
				'message' => __( 'Template not found.', 'suretriggers' ), 
				
			];
		}

		$tags_lists = [];

		// Initialize with original values.
		$tags_lists[] = [
			'list' => $selected_list,
			'tag'  => $selected_tag,
		];

		// Process arrays if needed.
		if ( is_array( $selected_list ) && is_array( $selected_tag ) ) {
			$max_count  = max( count( $selected_list ), count( $selected_tag ) );
			$tags_lists = [];
			for ( $i = 0; $i < $max_count; $i++ ) {
				$tags_lists[] = [
					'list' => isset( $selected_list[ $i ]['value'] ) ? $selected_list[ $i ]['value'] : '',
					'tag'  => isset( $selected_tag[ $i ]['value'] ) ? $selected_tag[ $i ]['value'] : '',
				];
			}
		}

		$campaign_title = isset( $selected_options['campaign_title'] ) ? sanitize_text_field( $selected_options['campaign_title'] ) : 'Campaign from template ' . $template->post_title . ' - ' . gmdate( 'Y-m-d H:i:s' );
		
		$email_subject = isset( $selected_options['email_subject'] ) && ! empty( $selected_options['email_subject'] ) ? 
			sanitize_text_field( $selected_options['email_subject'] ) : get_post_meta( $template->ID, '_email_subject', true );
		
		$from_name      = isset( $selected_options['from_name'] ) ? sanitize_text_field( $selected_options['from_name'] ) : '';
		$from_email     = isset( $selected_options['from_email'] ) ? sanitize_email( $selected_options['from_email'] ) : '';
		$reply_to_name  = isset( $selected_options['reply_to_name'] ) ? sanitize_text_field( $selected_options['reply_to_name'] ) : '';
		$reply_to_email = isset( $selected_options['reply_to_email'] ) ? sanitize_email( $selected_options['reply_to_email'] ) : '';
		
		$campaign_data = [
			'title'            => $campaign_title,
			'status'           => 'draft',
			'template_id'      => $template_id,
			'email_subject'    => $email_subject,
			'email_pre_header' => '',
			'email_body'       => $template->post_content,
			'settings'         => [
				'mailer_settings'     => [
					'from_name'      => $from_name,
					'from_email'     => $from_email,
					'reply_to_name'  => $reply_to_name,
					'reply_to_email' => $reply_to_email,
					'is_custom'      => 'yes',
				],
				'subscribers'         => $tags_lists,
				'excludedSubscribers' => null,
				'sending_filter'      => 'list_tag',
				'dynamic_segment'     => [
					'id'   => '',
					'slug' => '',
				],
				'advanced_filters'    => [
					[],
				],
			],
			'design_template'  => get_post_meta( $template->ID, '_design_template', true ),
		];

		$api_username  = isset( $selected_options['api_username'] ) ? $selected_options['api_username'] : '';
		$api_password  = isset( $selected_options['api_password'] ) ? $selected_options['api_password'] : '';
		$wordpress_url = isset( $selected_options['wordpress_url'] ) ? $selected_options['wordpress_url'] : site_url();

		$header_data = [
			'Content-Type'  => 'application/json',
			'Authorization' => 'Basic ' . base64_encode( $api_username . ':' . $api_password ),
		];

		$body_data = wp_json_encode( [ 'title' => $campaign_title ] );
		if ( false === $body_data ) {
			$body_data = '{"title":"' . esc_js( $campaign_title ) . '"}';
		}

		$args = [
			'headers'   => $header_data,
			'sslverify' => false,
			'body'      => $body_data,
		];

		$new_campaign_request       = wp_remote_post( $wordpress_url . '/wp-json/fluent-crm/v2/campaigns', $args );
		$response_code              = wp_remote_retrieve_response_code( $new_campaign_request );
		$new_campaign_response_body = wp_remote_retrieve_body( $new_campaign_request );
		$response_context           = json_decode( $new_campaign_response_body, true );

		if ( 200 !== $response_code ) {

			$error_message = __( 'Failed to create campaign.', 'suretriggers' );

			switch ( $response_code ) {
				case 422:
					$messages = [];
					if ( is_array( $response_context ) ) {
						foreach ( $response_context as $field_errors ) {
							foreach ( $field_errors as $msg ) {
								$messages[] = $msg;
							}
						}
						$error_message = implode( ', ', $messages );
					}
					break;
				default:
					$error_message = __( 'Failed to create campaign.', 'suretriggers' );
					break;
			}

			return [
				'status'  => 'error',
				'message' => __( 'Failed to create campaign.', 'suretriggers' ), 
				
			];
		}

		$settings_data = wp_json_encode(
			[
				'title'         => $campaign_title,
				'email_body'    => $template->post_content,
				'email_subject' => $email_subject,
				'settings'      => [
					'mailer_settings'     => [
						'from_name'      => $from_name,
						'is_custom'      => 'yes',
						'from_email'     => $from_email,
						'reply_to_name'  => $reply_to_name,
						'reply_to_email' => $reply_to_email,
					],
					'subscribers'         => $tags_lists,
					'excludedSubscribers' => null,
					'sending_filter'      => 'list_tag',
					'dynamic_segment'     => [
						'id'   => '',
						'slug' => '',
					],
					'advanced_filters'    => [
						[],
					],
				],
			]
		);
		
		if ( false === $settings_data ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to encode campaign settings data.', 'suretriggers' ), 
				
			];
		}

		$args = [
			'headers'   => $header_data,
			'sslverify' => false,
			'method'    => 'PUT',
			'body'      => $settings_data,
		];

		if ( ! is_array( $response_context ) || ! isset( $response_context['id'] ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid campaign response: missing ID.', 'suretriggers' ), 
				
			];
		}

		$settings_request       = wp_remote_request( $wordpress_url . '/wp-json/fluent-crm/v2/campaigns/' . $response_context['id'], $args );
		$settings_response_code = wp_remote_retrieve_response_code( $settings_request );
		$settings_response_body = wp_remote_retrieve_body( $settings_request );
		$settings_context       = json_decode( $settings_response_body, true );

		if ( 200 !== $settings_response_code ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update campaign settings.', 'suretriggers' ), 
				
			];
		}

		$contact_body_data = [
			'subscribers'    => $tags_lists,
			'sending_filter' => 'list_tag',
		];
		$contact_body      = wp_json_encode( $contact_body_data );
		
		if ( false === $contact_body ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to encode contact data.', 'suretriggers' ), 
				
			];
		}

		$check_estimated_contacts = wp_remote_post(
			$wordpress_url . '/wp-json/fluent-crm/v2/campaigns/estimated-contacts',
			[
				'headers'   => $header_data,
				'sslverify' => false,
				'body'      => $contact_body,
			]
		);
		$contacts                 = wp_remote_retrieve_body( $check_estimated_contacts );
		$contacts_context         = json_decode( $contacts, true );

		if ( is_array( $contacts_context ) && 0 == $contacts_context['count'] ) {
			return [
				'status'  => 'error',
				'message' => __( 'No contacts found based on your selection.', 'suretriggers' ), 
				
			];
		}

		$scheduled_at = isset( $selected_options['scheduled_at'] ) ? sanitize_text_field( $selected_options['scheduled_at'] ) : gmdate( 'Y-m-d H:i:s' );
		
		$schedule_data = wp_json_encode( [ 'scheduled_at' => $scheduled_at ] );
		if ( false === $schedule_data ) {
			$schedule_data = '{"scheduled_at":"' . esc_js( $scheduled_at ) . '"}';
		}

		if ( ! is_array( $settings_context ) || ! isset( $settings_context['campaign'] ) || ! isset( $settings_context['campaign']['id'] ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid campaign ID.', 'suretriggers' ), 
				
			];
		}

		$final_request = wp_remote_post( 
			$wordpress_url . '/wp-json/fluent-crm/v2/campaigns/' . $settings_context['campaign']['id'] . '/schedule', 
			[
				'headers'   => $header_data,
				'sslverify' => false,
				'body'      => $schedule_data,
			]
		);
		
		$final_response_body = wp_remote_retrieve_body( $final_request );
		$final_context       = json_decode( $final_response_body, true );
		
		if ( is_wp_error( $final_request ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to schedule campaign.', 'suretriggers' ), 
				
			];
		}

		if ( is_array( $final_context ) ) {
			$final_context['template_id'] = $template_id;
		}

		return $final_context;
	}
}

SendEmailByTemplate::get_instance();
