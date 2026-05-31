<?php
/**
 * GetContactByEmail.
 * php version 5.6
 *
 * @category GetContactByEmail
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\MailerPress\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * GetContactByEmail
 *
 * @category GetContactByEmail
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetContactByEmail extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'MailerPress';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'get_contact_by_email';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 *
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get contact by email', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id          user_id.
	 * @param int   $automation_id    automation_id.
	 * @param array $fields           fields.
	 * @param array $selected_options selectedOptions.
	 * @return array|void
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		// Get form data.
		$email                 = isset( $selected_options['email'] ) ? sanitize_email( $selected_options['email'] ) : '';
		$include_lists         = isset( $selected_options['include_lists'] ) ? (bool) $selected_options['include_lists'] : false;
		$include_tags          = isset( $selected_options['include_tags'] ) ? (bool) $selected_options['include_tags'] : false;
		$include_custom_fields = isset( $selected_options['include_custom_fields'] ) ? (bool) $selected_options['include_custom_fields'] : false;

		if ( empty( $email ) ) {
			return [
				'status'  => 'error',
				'message' => 'Email address is required.',
			];
		}

		if ( ! is_email( $email ) ) {
			return [
				'status'  => 'error',
				'message' => 'Please enter a valid email address.',
			];
		}

		try {
			global $wpdb;
			
			// Get contact by email.
			$contact = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT contact_id, email, first_name, last_name, subscription_status, opt_in_source, opt_in_details, unsubscribe_token, access_token, created_at, updated_at 
					 FROM {$wpdb->prefix}mailerpress_contact 
					 WHERE email = %s",
					$email
				)
			);

			if ( ! $contact ) {
				return [
					'status'  => 'error',
					'message' => 'Contact not found with the provided email address.',
				];
			}

			// Format basic contact data.
			$contact_data = [
				'contact_id'          => (int) $contact->contact_id,
				'email'               => $contact->email,
				'first_name'          => $contact->first_name,
				'last_name'           => $contact->last_name,
				'subscription_status' => $contact->subscription_status,
				'opt_in_source'       => $contact->opt_in_source,
				'opt_in_details'      => $contact->opt_in_details,
				'unsubscribe_token'   => $contact->unsubscribe_token,
				'access_token'        => $contact->access_token,
				'created_at'          => $contact->created_at,
				'updated_at'          => $contact->updated_at,
			];

			// Get lists if requested.
			if ( $include_lists ) {
				$lists = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT l.list_id, l.name, l.description, l.created_at, l.updated_at
						 FROM {$wpdb->prefix}mailerpress_lists l
						 INNER JOIN {$wpdb->prefix}mailerpress_contact_lists cl ON l.list_id = cl.list_id
						 WHERE cl.contact_id = %d
						 ORDER BY l.name ASC",
						$contact->contact_id
					)
				);

				$formatted_lists = [];
				if ( $lists ) {
					foreach ( $lists as $list ) {
						$formatted_lists[] = [
							'list_id'     => (int) $list->list_id,
							'name'        => $list->name,
							'description' => $list->description,
							'created_at'  => $list->created_at,
							'updated_at'  => $list->updated_at,
						];
					}
				}

				$contact_data['lists'] = $formatted_lists;
			}

			// Get tags if requested.
			if ( $include_tags ) {
				$tags = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT t.tag_id, t.name
						 FROM {$wpdb->prefix}mailerpress_tags t
						 INNER JOIN {$wpdb->prefix}mailerpress_contact_tags ct ON t.tag_id = ct.tag_id
						 WHERE ct.contact_id = %d
						 ORDER BY t.name ASC",
						$contact->contact_id
					)
				);

				$formatted_tags = [];
				if ( $tags ) {
					foreach ( $tags as $tag ) {
						$formatted_tags[] = [
							'tag_id' => (int) $tag->tag_id,
							'name'   => $tag->name,
						];
					}
				}

				$contact_data['tags'] = $formatted_tags;
			}

			// Get custom fields if requested.
			if ( $include_custom_fields ) {
				$custom_fields_table = $wpdb->prefix . 'mailerpress_contact_custom_fields';
				$table_exists        = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $custom_fields_table ) );
				
				if ( $table_exists ) {
					$custom_fields = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT field_key, field_value
							 FROM {$wpdb->prefix}mailerpress_contact_custom_fields
							 WHERE contact_id = %d
							 ORDER BY field_key ASC",
							$contact->contact_id
						)
					);

					$formatted_custom_fields = [];
					if ( $custom_fields ) {
						foreach ( $custom_fields as $field ) {
							$formatted_custom_fields[ $field->field_key ] = $field->field_value;
						}
					}

					$contact_data['custom_fields'] = $formatted_custom_fields;
				} else {
					$contact_data['custom_fields'] = [];
				}
			}

			// Get some additional stats.
			$stats = [];

			// Count lists.
			$list_count          = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mailerpress_contact_lists WHERE contact_id = %d",
					$contact->contact_id
				)
			);
			$stats['list_count'] = (int) $list_count;

			// Count tags.
			$tag_count          = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mailerpress_contact_tags WHERE contact_id = %d",
					$contact->contact_id
				)
			);
			$stats['tag_count'] = (int) $tag_count;

			$contact_data['stats'] = $stats;

			return [
				'contact' => $contact_data,
				'success' => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

GetContactByEmail::get_instance();
