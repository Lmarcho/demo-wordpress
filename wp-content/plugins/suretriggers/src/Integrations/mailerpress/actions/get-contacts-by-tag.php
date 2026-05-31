<?php
/**
 * GetContactsByTag.
 * php version 5.6
 *
 * @category GetContactsByTag
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
 * GetContactsByTag
 *
 * @category GetContactsByTag
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetContactsByTag extends AutomateAction {

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
	public $action = 'get_contacts_by_tag';

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
			'label'    => __( 'Get contacts by tag', 'suretriggers' ),
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
		// Get required parameters.
		$tag_id = isset( $selected_options['tag_id'] ) ? absint( $selected_options['tag_id'] ) : 0;
		$limit  = isset( $selected_options['limit'] ) ? absint( $selected_options['limit'] ) : 10;
		
		if ( empty( $tag_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Tag ID is required.',
			];
		}

		try {
			global $wpdb;

			// Check if tag exists.
			$existing_tag = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT tag_id, name FROM {$wpdb->prefix}mailerpress_tags WHERE tag_id = %d",
					$tag_id
				)
			);

			if ( ! $existing_tag ) {
				return [
					'status'  => 'error',
					'message' => 'Tag not found with the provided ID.',
				];
			}

			$contacts_table     = $wpdb->prefix . 'mailerpress_contact';
			$contact_tags_table = $wpdb->prefix . 'mailerpress_contact_tags';

			// Get contacts by tag.
			if ( $limit > 0 ) {
				$contacts = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT DISTINCT c.contact_id, c.email, c.first_name, c.last_name, c.subscription_status, c.created_at, c.updated_at FROM `' . esc_sql( $contacts_table ) . '` c INNER JOIN `' . esc_sql( $contact_tags_table ) . '` ct ON c.contact_id = ct.contact_id WHERE ct.tag_id = %d ORDER BY c.email ASC LIMIT %d',
						$tag_id,
						$limit
					)
				);
			} else {
				$contacts = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT DISTINCT c.contact_id, c.email, c.first_name, c.last_name, c.subscription_status, c.created_at, c.updated_at FROM `' . esc_sql( $contacts_table ) . '` c INNER JOIN `' . esc_sql( $contact_tags_table ) . '` ct ON c.contact_id = ct.contact_id WHERE ct.tag_id = %d ORDER BY c.email ASC',
						$tag_id
					)
				);
			}

			if ( $wpdb->last_error ) {
				throw new Exception( 'Database error: ' . $wpdb->last_error );
			}

			// Format contacts data.
			$formatted_contacts = [];
			if ( $contacts ) {
				foreach ( $contacts as $contact ) {
					$formatted_contacts[] = [
						'contact_id'          => (int) $contact->contact_id,
						'email'               => $contact->email,
						'first_name'          => $contact->first_name,
						'last_name'           => $contact->last_name,
						'subscription_status' => $contact->subscription_status,
						'created_at'          => $contact->created_at,
						'updated_at'          => $contact->updated_at,
					];
				}
			}

			// Get total count.
			$total_count = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(DISTINCT c.contact_id) FROM `' . esc_sql( $contacts_table ) . '` c INNER JOIN `' . esc_sql( $contact_tags_table ) . '` ct ON c.contact_id = ct.contact_id WHERE ct.tag_id = %d',
					$tag_id
				)
			);

			return [
				'tag_id'      => $tag_id,
				'tag_name'    => $existing_tag->name,
				'contacts'    => $formatted_contacts,
				'total_count' => $total_count,
				'returned'    => count( $formatted_contacts ),
				'limit'       => $limit,
				'success'     => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

GetContactsByTag::get_instance();
