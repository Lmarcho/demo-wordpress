<?php
/**
 * CreateTag.
 * php version 5.6
 *
 * @category CreateTag
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
 * CreateTag
 *
 * @category CreateTag
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateTag extends AutomateAction {

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
	public $action = 'create_tag';

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
			'label'    => __( 'Create tag', 'suretriggers' ),
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
		$name = isset( $selected_options['name'] ) ? sanitize_text_field( $selected_options['name'] ) : '';

		if ( empty( $name ) ) {
			return [
				'status'  => 'error',
				'message' => 'Tag name is required.',
			];
		}

		// Validate tag name length.
		if ( strlen( $name ) > 255 ) {
			return [
				'status'  => 'error',
				'message' => 'Tag name cannot exceed 255 characters.',
			];
		}

		// Validate tag name format (no special characters that could cause issues).
		if ( ! preg_match( '/^[a-zA-Z0-9\s\-_\.]+$/', $name ) ) {
			return [
				'status'  => 'error',
				'message' => 'Tag name can only contain letters, numbers, spaces, hyphens, underscores, and periods.',
			];
		}

		try {
			global $wpdb;
			
			// Check if tag with the same name already exists.
			$existing_tag = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT tag_id FROM {$wpdb->prefix}mailerpress_tags WHERE name = %s",
					$name
				)
			);

			if ( $existing_tag ) {
				return [
					'status'  => 'error',
					'message' => 'A tag with this name already exists.',
					'tag_id'  => (int) $existing_tag->tag_id,
				];
			}

			// Create the new tag.
			$result = $wpdb->insert(
				$wpdb->prefix . 'mailerpress_tags',
				[
					'name' => $name,
				],
				[ '%s' ]
			);

			if ( false === $result ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to create tag. ' . ( $wpdb->last_error ? $wpdb->last_error : '' ),
				];
			}

			$tag_id = $wpdb->insert_id;

			// Get the created tag details.
			$created_tag = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT tag_id, name FROM {$wpdb->prefix}mailerpress_tags WHERE tag_id = %d",
					$tag_id
				)
			);

			if ( ! $created_tag ) {
				return [
					'status'  => 'error',
					'message' => 'Tag created but could not retrieve details.',
				];
			}

			// Fire action hook.
			do_action( 'mailerpress_tag_created', $tag_id, $name );

			return [
				'tag_id'  => (int) $created_tag->tag_id,
				'name'    => $created_tag->name,
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

CreateTag::get_instance();
