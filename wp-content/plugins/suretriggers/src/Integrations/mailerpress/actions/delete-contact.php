<?php
/**
 * DeleteContact.
 * php version 5.6
 *
 * @category DeleteContact
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
 * DeleteContact
 *
 * @category DeleteContact
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteContact extends AutomateAction {

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
	public $action = 'delete_contact';

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
			'label'    => __( 'Delete Contact', 'suretriggers' ),
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

		try {
			global $wpdb;
			
			// MailerPress table names.
			$contact_table       = $wpdb->prefix . 'mailerpress_contact';
			$contact_lists_table = $wpdb->prefix . 'mailerpress_contact_lists';
			$contact_tags_table  = $wpdb->prefix . 'mailerpress_contact_tags';

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

			$contact_id = $existing_contact->contact_id;

			// Delete contact from lists.
			$wpdb->delete(
				$contact_lists_table,
				[ 'contact_id' => $contact_id ],
				[ '%d' ]
			);

			// Delete contact from tags.
			$wpdb->delete(
				$contact_tags_table,
				[ 'contact_id' => $contact_id ],
				[ '%d' ]
			);

			// Delete contact.
			$result = $wpdb->delete(
				$contact_table,
				[ 'contact_id' => $contact_id ],
				[ '%d' ]
			);

			if ( false === $result ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to delete contact.',
				];
			}

			// Fire action hook.
			do_action( 'mailerpress_contact_deleted', $contact_id );

			return [
				'contact_id' => $contact_id,
				'email'      => $existing_contact->email,
				'deleted'    => true,
				'success'    => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

DeleteContact::get_instance();
