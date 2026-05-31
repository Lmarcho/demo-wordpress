<?php
/**
 * CreateList.
 * php version 5.6
 *
 * @category CreateList
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
 * CreateList
 *
 * @category CreateList
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateList extends AutomateAction {

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
	public $action = 'create_list';

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
			'label'    => __( 'Create list', 'suretriggers' ),
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
		$name        = isset( $selected_options['name'] ) ? sanitize_text_field( $selected_options['name'] ) : '';
		$description = isset( $selected_options['description'] ) ? sanitize_textarea_field( $selected_options['description'] ) : '';

		if ( empty( $name ) ) {
			return [
				'status'  => 'error',
				'message' => 'List name is required.',
			];
		}

		// Validate list name length.
		if ( strlen( $name ) > 255 ) {
			return [
				'status'  => 'error',
				'message' => 'List name cannot exceed 255 characters.',
			];
		}

		try {
			global $wpdb;
			
			// Check if list with the same name already exists.
			$existing_list = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT list_id FROM {$wpdb->prefix}mailerpress_lists WHERE name = %s",
					$name
				)
			);

			if ( $existing_list ) {
				return [
					'status'  => 'error',
					'message' => 'A list with this name already exists.',
				];
			}

			// Create the new list.
			$result = $wpdb->insert(
				$wpdb->prefix . 'mailerpress_lists',
				[
					'name'        => $name,
					'description' => $description,
					'created_at'  => current_time( 'mysql' ),
					'updated_at'  => current_time( 'mysql' ),
				],
				[ '%s', '%s', '%s', '%s' ]
			);

			if ( false === $result ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to create list. ' . ( $wpdb->last_error ? $wpdb->last_error : '' ),
				];
			}

			$list_id = $wpdb->insert_id;

			// Get the created list details.
			$created_list = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT list_id, name, description, created_at, updated_at FROM {$wpdb->prefix}mailerpress_lists WHERE list_id = %d",
					$list_id
				)
			);

			if ( ! $created_list ) {
				return [
					'status'  => 'error',
					'message' => 'List created but could not retrieve details.',
				];
			}

			// Fire action hook.
			do_action( 'mailerpress_list_created', $list_id, $name, $description );

			return [
				'list_id'     => (int) $created_list->list_id,
				'name'        => $created_list->name,
				'description' => $created_list->description,
				'created_at'  => $created_list->created_at,
				'updated_at'  => $created_list->updated_at,
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

CreateList::get_instance();
