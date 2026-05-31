<?php
/**
 * GetContactsByList.
 * php version 5.6
 *
 * @category GetContactsByList
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
 * GetContactsByList
 *
 * @category GetContactsByList
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetContactsByList extends AutomateAction {

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
	public $action = 'get_contacts_by_list';

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
			'label'    => __( 'Get contacts by list', 'suretriggers' ),
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
		$list_id = isset( $selected_options['list_id'] ) ? absint( $selected_options['list_id'] ) : 0;
		$limit   = isset( $selected_options['limit'] ) ? absint( $selected_options['limit'] ) : 10;
		
		if ( empty( $list_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'List ID is required.',
			];
		}

		try {
			global $wpdb;

			// Check if list exists.
			$existing_list = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT list_id, name, description FROM {$wpdb->prefix}mailerpress_lists WHERE list_id = %d",
					$list_id
				)
			);

			if ( ! $existing_list ) {
				return [
					'status'  => 'error',
					'message' => 'List not found with the provided ID.',
				];
			}

			$contacts_table      = $wpdb->prefix . 'mailerpress_contact';
			$contact_lists_table = $wpdb->prefix . 'mailerpress_contact_lists';

			// Get contacts by list.
			if ( $limit > 0 ) {
				$contacts = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT DISTINCT c.contact_id, c.email, c.first_name, c.last_name, c.subscription_status, c.created_at, c.updated_at FROM `' . esc_sql( $contacts_table ) . '` c INNER JOIN `' . esc_sql( $contact_lists_table ) . '` cl ON c.contact_id = cl.contact_id WHERE cl.list_id = %d ORDER BY c.email ASC LIMIT %d',
						$list_id,
						$limit
					)
				);
			} else {
				$contacts = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT DISTINCT c.contact_id, c.email, c.first_name, c.last_name, c.subscription_status, c.created_at, c.updated_at FROM `' . esc_sql( $contacts_table ) . '` c INNER JOIN `' . esc_sql( $contact_lists_table ) . '` cl ON c.contact_id = cl.contact_id WHERE cl.list_id = %d ORDER BY c.email ASC',
						$list_id
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
					'SELECT COUNT(DISTINCT c.contact_id) FROM `' . esc_sql( $contacts_table ) . '` c INNER JOIN `' . esc_sql( $contact_lists_table ) . '` cl ON c.contact_id = cl.contact_id WHERE cl.list_id = %d',
					$list_id
				)
			);

			return [
				'list_id'          => $list_id,
				'list_name'        => $existing_list->name,
				'list_description' => $existing_list->description,
				'contacts'         => $formatted_contacts,
				'total_count'      => $total_count,
				'returned'         => count( $formatted_contacts ),
				'limit'            => $limit,
				'success'          => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

GetContactsByList::get_instance();
