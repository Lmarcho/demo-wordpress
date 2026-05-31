<?php
/**
 * DeleteList.
 * php version 5.6
 *
 * @category DeleteList
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
 * DeleteList
 *
 * @category DeleteList
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteList extends AutomateAction {

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
	public $action = 'delete_list';

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
			'label'    => __( 'Delete list', 'suretriggers' ),
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
		$list_id   = isset( $selected_options['list_id'] ) ? absint( $selected_options['list_id'] ) : 0;
		$list_name = isset( $selected_options['list_name'] ) ? sanitize_text_field( $selected_options['list_name'] ) : '';

		// Validate input - either list_id or list_name is required.
		if ( empty( $list_id ) && empty( $list_name ) ) {
			return [
				'status'  => 'error',
				'message' => 'Either list ID or list name is required.',
			];
		}

		try {
			global $wpdb;
			
			// If list_name is provided instead of list_id, find the list by name.
			if ( empty( $list_id ) && ! empty( $list_name ) ) {
				$existing_list = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT list_id, name FROM {$wpdb->prefix}mailerpress_lists WHERE name = %s",
						$list_name
					)
				);

				if ( ! $existing_list ) {
					return [
						'status'  => 'error',
						'message' => 'List not found with the provided name.',
					];
				}

				$list_id = $existing_list->list_id;
			} else {
				// Verify the list exists by ID.
				$existing_list = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT list_id, name FROM {$wpdb->prefix}mailerpress_lists WHERE list_id = %d",
						$list_id
					)
				);

				if ( ! $existing_list ) {
					return [
						'status'  => 'error',
						'message' => 'List not found with the provided ID.',
					];
				}
			}

			// Store list details before deletion.
			$deleted_list_name = $existing_list->name;
			$deleted_list_id   = $existing_list->list_id;

			// Get count of contacts in this list before deletion.
			$contact_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mailerpress_contact_lists WHERE list_id = %d",
					$list_id
				)
			);

			// Delete contact-list relationships first.
			$relationships_deleted = $wpdb->delete(
				$wpdb->prefix . 'mailerpress_contact_lists',
				[ 'list_id' => $list_id ],
				[ '%d' ]
			);

			if ( false === $relationships_deleted ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to remove contact relationships for the list.',
				];
			}

			// Delete the list.
			$list_deleted = $wpdb->delete(
				$wpdb->prefix . 'mailerpress_lists',
				[ 'list_id' => $list_id ],
				[ '%d' ]
			);

			if ( false === $list_deleted ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to delete the list.',
				];
			}

			if ( 0 === $list_deleted ) {
				return [
					'status'  => 'error',
					'message' => 'List was not found or already deleted.',
				];
			}

			// Fire action hook.
			do_action( 'mailerpress_list_deleted', $deleted_list_id, $deleted_list_name, $contact_count );

			return [
				'list_id'               => $deleted_list_id,
				'list_name'             => $deleted_list_name,
				'contacts_affected'     => (int) $contact_count,
				'relationships_removed' => (int) $relationships_deleted,
				'success'               => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

DeleteList::get_instance();
