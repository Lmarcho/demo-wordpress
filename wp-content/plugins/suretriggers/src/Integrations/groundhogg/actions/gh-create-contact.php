<?php
/**
 * GhCreateContact.
 * php version 5.6
 *
 * @category GhCreateContact
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Groundhogg\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * GhCreateContact
 *
 * @category GhCreateContact
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GhCreateContact extends AutomateAction {


	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'Groundhogg';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'gh_create_contact';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create/Update Contact', 'suretriggers' ),
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
		$email         = sanitize_email( $selected_options['email'] );
		$api_key       = $selected_options['token'];
		$public_key    = $selected_options['public_key'];
		$first_name    = $selected_options['first_name'];
		$last_name     = $selected_options['last_name'];
		$optin_status  = $selected_options['optin_status'];
		$custom_fields = $selected_options['custom_fields'];
		// Extract new fields from selected_options or meta object.
		$primary_phone           = isset( $selected_options['primary_phone'] ) ? $selected_options['primary_phone'] : '';
		$primary_phone_extension = isset( $selected_options['primary_phone_extension'] ) ? $selected_options['primary_phone_extension'] : '';
		$street_address_1        = isset( $selected_options['street_address_1'] ) ? $selected_options['street_address_1'] : '';
		$street_address_2        = isset( $selected_options['street_address_2'] ) ? $selected_options['street_address_2'] : '';
		$city                    = isset( $selected_options['city'] ) ? $selected_options['city'] : '';
		$postal_zip              = isset( $selected_options['postal_zip'] ) ? $selected_options['postal_zip'] : '';
		$region                  = isset( $selected_options['region'] ) ? $selected_options['region'] : '';
		$country                 = isset( $selected_options['country'] ) ? $selected_options['country'] : '';
		$lead_source             = isset( $selected_options['lead_source'] ) ? $selected_options['lead_source'] : '';
		$source_page             = isset( $selected_options['source_page'] ) ? $selected_options['source_page'] : '';
		$custom_meta             = isset( $selected_options['custom_meta'] ) ? $selected_options['custom_meta'] : '';
		$birthday                = isset( $selected_options['birthday'] ) ? $selected_options['birthday'] : '';
		$ip_address              = isset( $selected_options['ip_address'] ) ? $selected_options['ip_address'] : '';
		$mobile_phone            = isset( $selected_options['mobile_phone'] ) ? $selected_options['mobile_phone'] : '';
		$gravatar                = isset( $selected_options['gravatar'] ) ? $selected_options['gravatar'] : '';
		$age                     = isset( $selected_options['age'] ) ? $selected_options['age'] : '';
		
		
		// Check if fields exist in meta object from custom_fields if not found directly.
		if ( ! empty( $custom_fields ) ) {
			foreach ( $custom_fields as $field ) {
				if ( isset( $field['custom_field_key'] ) && isset( $field['custom_field_value'] ) ) {
					$key   = $field['custom_field_key'];
					$value = $field['custom_field_value'];
					
					// Map meta fields to direct fields if direct fields are empty.
					switch ( $key ) {
						case 'primary_phone':
							if ( empty( $primary_phone ) ) {
								$primary_phone = $value;
							}
							break;
						case 'primary_phone_extension':
							if ( empty( $primary_phone_extension ) ) {
								$primary_phone_extension = $value;
							}
							break;
						case 'street_address_1':
							if ( empty( $street_address_1 ) ) {
								$street_address_1 = $value;
							}
							break;
						case 'street_address_2':
							if ( empty( $street_address_2 ) ) {
								$street_address_2 = $value;
							}
							break;
						case 'city':
							if ( empty( $city ) ) {
								$city = $value;
							}
							break;
						case 'postal_zip':
							if ( empty( $postal_zip ) ) {
								$postal_zip = $value;
							}
							break;
						case 'region':
							if ( empty( $region ) ) {
								$region = $value;
							}
							break;
						case 'country':
							if ( empty( $country ) ) {
								$country = $value;
							}
							break;
						case 'lead_source':
							if ( empty( $lead_source ) ) {
								$lead_source = $value;
							}
							break;
						case 'source_page':
							if ( empty( $source_page ) ) {
								$source_page = $value;
							}
							break;
						case 'custom_meta':
							if ( empty( $custom_meta ) ) {
								$custom_meta = $value;
							}
							break;
						case 'birthday':
							if ( empty( $birthday ) ) {
								$birthday = $value;
							}
							break;
						case 'ip_address':
							if ( empty( $ip_address ) ) {
								$ip_address = $value;
							}
							break;
						case 'mobile_phone':
							if ( empty( $mobile_phone ) ) {
								$mobile_phone = $value;
							}
							break;
						case 'gravatar':
							if ( empty( $gravatar ) ) {
								$gravatar = $value;
							}
							break;
						case 'age':
							if ( empty( $age ) ) {
								$age = $value;
							}
							break;
					}
				}
			}
		}
		
		if ( is_email( $email ) ) {
			// Make a single response array.
			$response_array = [];

			// Build http request param.
			$request_args = [
				'data' => [
					'email'        => $email,
					'first_name'   => $first_name,
					'last_name'    => $last_name,
					'optin_status' => $optin_status,
				],
			];
			
			// Add gravatar and age to data section if provided.
			if ( ! empty( $gravatar ) ) {
				$request_args['data']['gravatar'] = $gravatar;
			}
			if ( ! empty( $age ) ) {
				$request_args['data']['age'] = $age;
			}
			
			// Prepare meta fields array.
			$meta_fields = [];
			
			// Add custom_fields to meta.
			if ( ! empty( $custom_fields ) ) {
				foreach ( $custom_fields as $key => $value ) {
					$meta_fields[ $value['custom_field_key'] ] = $value['custom_field_value'];
				}
			}
			
			// Add direct fields to meta if provided.
			if ( ! empty( $primary_phone ) ) {
				$meta_fields['primary_phone'] = $primary_phone;
			}
			if ( ! empty( $primary_phone_extension ) ) {
				$meta_fields['primary_phone_extension'] = $primary_phone_extension;
			}
			if ( ! empty( $street_address_1 ) ) {
				$meta_fields['street_address_1'] = $street_address_1;
			}
			if ( ! empty( $street_address_2 ) ) {
				$meta_fields['street_address_2'] = $street_address_2;
			}
			if ( ! empty( $city ) ) {
				$meta_fields['city'] = $city;
			}
			if ( ! empty( $postal_zip ) ) {
				$meta_fields['postal_zip'] = $postal_zip;
			}
			if ( ! empty( $region ) ) {
				$meta_fields['region'] = $region;
			}
			if ( ! empty( $country ) ) {
				$meta_fields['country'] = $country;
			}
			if ( ! empty( $lead_source ) ) {
				$meta_fields['lead_source'] = $lead_source;
			}
			if ( ! empty( $source_page ) ) {
				$meta_fields['source_page'] = $source_page;
			}
			if ( ! empty( $custom_meta ) ) {
				$meta_fields['custom_meta'] = $custom_meta;
			}
			if ( ! empty( $birthday ) ) {
				$meta_fields['birthday'] = $birthday;
			}
			if ( ! empty( $ip_address ) ) {
				$meta_fields['ip_address'] = $ip_address;
			}
			if ( ! empty( $mobile_phone ) ) {
				$meta_fields['mobile_phone'] = $mobile_phone;
			}
			
			// Add meta to request if we have any fields.
			if ( ! empty( $meta_fields ) ) {
				$request_args['meta'] = $meta_fields;
			}

			
			$args = [
				'headers'     => [
					'Content-Type'  => 'application/json',
					'Gh-Token'      => $api_key,
					'Gh-Public-Key' => $public_key,
				],
				'sslverify'   => false,
				'data_format' => 'body',
				'body'        => wp_json_encode( $request_args ),
			];

			/**
			 *
			 * Ignore line
			 *
			 * @phpstan-ignore-next-line
			 */
			$request       = wp_remote_post( get_rest_url() . 'gh/v4/contacts/', $args );
			$response_code = wp_remote_retrieve_response_code( $request );
			$response_body = wp_remote_retrieve_body( $request );
			$response      = $response_body;

			if ( 200 !== $response_code ) {
				$response = json_decode( $response_body );
				if ( is_object( $response ) ) {
					if ( property_exists( $response, 'code' ) && property_exists( $response, 'message' ) ) {
						$error_code     = $response->code;
						$error_message  = $response->message;
						$response_array = [
							'status'  => 'error',
							'code'    => $error_code,
							'message' => $error_message,
						];
					}
				}
			} else {
				$response       = json_decode( $response, true );
				$response_array = (array) $response;
			}

			return $response_array;
		} else {
			return [
				'status'  => 'error',
				'message' => 'Enter valid email',
			];
		}
	}

}

GhCreateContact::get_instance();
