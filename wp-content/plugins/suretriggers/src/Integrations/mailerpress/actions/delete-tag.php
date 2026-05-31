<?php
/**
 * DeleteTag.
 * php version 5.6
 *
 * @category DeleteTag
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
 * DeleteTag
 *
 * @category DeleteTag
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteTag extends AutomateAction {

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
	public $action = 'delete_tag';

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
			'label'    => __( 'Delete tag', 'suretriggers' ),
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
		$tag_id   = isset( $selected_options['tag_id'] ) ? absint( $selected_options['tag_id'] ) : 0;
		$tag_name = isset( $selected_options['tag_name'] ) ? sanitize_text_field( $selected_options['tag_name'] ) : '';

		// Validate input - either tag_id or tag_name is required.
		if ( empty( $tag_id ) && empty( $tag_name ) ) {
			return [
				'status'  => 'error',
				'message' => 'Either tag ID or tag name is required.',
			];
		}

		try {
			global $wpdb;
			
			// If tag_name is provided instead of tag_id, find the tag by name.
			if ( empty( $tag_id ) && ! empty( $tag_name ) ) {
				$existing_tag = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT tag_id, name FROM {$wpdb->prefix}mailerpress_tags WHERE name = %s",
						$tag_name
					)
				);

				if ( ! $existing_tag ) {
					return [
						'status'  => 'error',
						'message' => 'Tag not found with the provided name.',
					];
				}

				$tag_id = $existing_tag->tag_id;
			} else {
				// Verify the tag exists by ID.
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
			}

			// Store tag details before deletion.
			$deleted_tag_name = $existing_tag->name;
			$deleted_tag_id   = $existing_tag->tag_id;

			// Get count of contacts with this tag before deletion.
			$contact_count = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}mailerpress_contact_tags WHERE tag_id = %d",
					$tag_id
				)
			);

			// Delete contact-tag relationships first.
			$relationships_deleted = $wpdb->delete(
				$wpdb->prefix . 'mailerpress_contact_tags',
				[ 'tag_id' => $tag_id ],
				[ '%d' ]
			);

			if ( false === $relationships_deleted ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to remove contact relationships for the tag.',
				];
			}

			// Delete the tag.
			$tag_deleted = $wpdb->delete(
				$wpdb->prefix . 'mailerpress_tags',
				[ 'tag_id' => $tag_id ],
				[ '%d' ]
			);

			if ( false === $tag_deleted ) {
				return [
					'status'  => 'error',
					'message' => 'Failed to delete the tag.',
				];
			}

			if ( 0 === $tag_deleted ) {
				return [
					'status'  => 'error',
					'message' => 'Tag was not found or already deleted.',
				];
			}

			// Fire action hook.
			do_action( 'mailerpress_tag_deleted', $deleted_tag_id, $deleted_tag_name, $contact_count );

			return [
				'tag_id'                => $deleted_tag_id,
				'tag_name'              => $deleted_tag_name,
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

DeleteTag::get_instance();
