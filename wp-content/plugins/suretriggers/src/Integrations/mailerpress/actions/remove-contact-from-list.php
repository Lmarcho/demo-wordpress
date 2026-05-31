<?php
/**
 * RemoveContactFromList.
 * php version 5.6
 *
 * @category RemoveContactFromList
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
 * RemoveContactFromList
 *
 * @category RemoveContactFromList
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RemoveContactFromList extends AutomateAction {

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
	public $action = 'remove_contact_from_list';

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
			'label'    => __( 'Remove contact from list', 'suretriggers' ),
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
		$email = isset( $selected_options['email'] ) ? sanitize_email( $selected_options['email'] ) : '';
		$lists = isset( $selected_options['lists'] ) ? $selected_options['lists'] : [];

		if ( empty( $email ) ) {
			return [
				'status'  => 'error',
				'message' => 'Email is required.',
			];
		}

		if ( ! is_email( $email ) ) {
			return [
				'status'  => 'error',
				'message' => 'Please enter a valid email address.',
			];
		}

		if ( empty( $lists ) ) {
			return [
				'status'  => 'error',
				'message' => 'At least one list is required.',
			];
		}

		try {
			global $wpdb;
			
			// MailerPress table names.
			$contact_table       = $wpdb->prefix . 'mailerpress_contact';
			$contact_lists_table = $wpdb->prefix . 'mailerpress_contact_lists';

			// Find contact by email.
			$existing_contact = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}mailerpress_contact WHERE email = %s",
					$email
				)
			);

			if ( ! $existing_contact ) {
				return [
					'status'  => 'error',
					'message' => 'Contact not found.',
				];
			}

			$contact_id    = $existing_contact->contact_id;
			$removed_lists = [];

			// Remove contact from lists.
			foreach ( $lists as $list ) {
				$list_id = 0;
				
				// Check if list is an ID or name.
				if ( is_array( $list ) ) {
					if ( isset( $list['value'] ) && ! empty( $list['value'] ) ) {
						$list_id = absint( $list['value'] );
					} elseif ( isset( $list['id'] ) && ! empty( $list['id'] ) ) {
						$list_id = absint( $list['id'] );
					} elseif ( isset( $list['name'] ) && ! empty( $list['name'] ) ) {
						// Search for list by name.
						$existing_list = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT list_id FROM {$wpdb->prefix}mailerpress_lists WHERE name = %s",
								sanitize_text_field( $list['name'] )
							)
						);
						
						if ( $existing_list ) {
							$list_id = $existing_list->list_id;
						}
					}
				} elseif ( is_numeric( $list ) ) {
					$list_id = absint( $list );
				} elseif ( is_string( $list ) ) {
					// Search for list by name.
					$existing_list = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT list_id FROM {$wpdb->prefix}mailerpress_lists WHERE name = %s",
							sanitize_text_field( $list )
						)
					);
					
					if ( $existing_list ) {
						$list_id = $existing_list->list_id;
					}
				}
				
				if ( $list_id > 0 ) {
					// Check if contact is in list.
					$existing_contact_list = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}mailerpress_contact_lists WHERE contact_id = %d AND list_id = %d",
							$contact_id,
							$list_id
						)
					);

					if ( $existing_contact_list ) {
						$result = $wpdb->delete(
							$contact_lists_table,
							[
								'contact_id' => $contact_id,
								'list_id'    => $list_id,
							],
							[ '%d', '%d' ]
						);
						
						if ( false !== $result ) {
							$removed_lists[] = $list_id;
							
							// Fire action hook.
							do_action( 'mailerpress_contact_list_removed', $contact_id, $list_id );
						}
					}
				}
			}

			return [
				'contact_id'    => $contact_id,
				'email'         => $email,
				'lists_removed' => $removed_lists,
				'lists_count'   => count( $removed_lists ),
				'success'       => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

RemoveContactFromList::get_instance();
