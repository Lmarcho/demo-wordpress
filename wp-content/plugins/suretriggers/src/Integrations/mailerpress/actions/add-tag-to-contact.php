<?php
/**
 * AddTagToContact.
 * php version 5.6
 *
 * @category AddTagToContact
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
 * AddTagToContact
 *
 * @category AddTagToContact
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddTagToContact extends AutomateAction {

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
	public $action = 'add_tag_to_contact';

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
			'label'    => __( 'Add tag to contact', 'suretriggers' ),
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
		$tags  = isset( $selected_options['tags'] ) ? $selected_options['tags'] : [];

		// Convert string to array if needed.
		if ( is_string( $tags ) ) {
			$tags = [ $tags ];
		}

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

		if ( empty( $tags ) ) {
			return [
				'status'  => 'error',
				'message' => 'At least one tag is required.',
			];
		}

		try {
			global $wpdb;
			
			// MailerPress table names.
			$contact_tags_table = $wpdb->prefix . 'mailerpress_contact_tags';

			// Check if contact exists.
			$existing_contact = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT contact_id FROM {$wpdb->prefix}mailerpress_contact WHERE email = %s",
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
			$added_tags = [];

			// Add contact to tags.
			foreach ( $tags as $tag ) {
				$tag_id = 0;
				
				// Check if tag is an ID or name.
				if ( is_array( $tag ) ) {
					if ( isset( $tag['value'] ) && ! empty( $tag['value'] ) ) {
						$tag_id = absint( $tag['value'] );
					} elseif ( isset( $tag['id'] ) && ! empty( $tag['id'] ) ) {
						$tag_id = absint( $tag['id'] );
					} elseif ( isset( $tag['name'] ) && ! empty( $tag['name'] ) ) {
						// Search for tag by name.
						$existing_tag = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT tag_id FROM {$wpdb->prefix}mailerpress_tags WHERE name = %s",
								sanitize_text_field( $tag['name'] )
							)
						);
						
						if ( $existing_tag ) {
							$tag_id = $existing_tag->tag_id;
						} else {
							// Create new tag.
							$new_tag_result = $wpdb->insert(
								$wpdb->prefix . 'mailerpress_tags',
								[
									'name' => sanitize_text_field( $tag['name'] ),
								],
								[ '%s' ]
							);
							
							if ( $new_tag_result ) {
								$tag_id = $wpdb->insert_id;
							}
						}
					}
				} elseif ( is_numeric( $tag ) ) {
					$tag_id = absint( $tag );
				} elseif ( is_string( $tag ) ) {
					// Search for tag by name.
					$existing_tag = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT tag_id FROM {$wpdb->prefix}mailerpress_tags WHERE name = %s",
							sanitize_text_field( $tag )
						)
					);
					
					if ( $existing_tag ) {
						$tag_id = $existing_tag->tag_id;
					} else {
						// Create new tag.
						$new_tag_result = $wpdb->insert(
							$wpdb->prefix . 'mailerpress_tags',
							[
								'name' => sanitize_text_field( $tag ),
							],
							[ '%s' ]
						);
						
						if ( $new_tag_result ) {
							$tag_id = $wpdb->insert_id;
						}
					}
				}
				
				if ( $tag_id > 0 ) {
					// Check if already has tag.
					$existing_contact_tag = $wpdb->get_row(
						$wpdb->prepare(
							"SELECT * FROM {$wpdb->prefix}mailerpress_contact_tags WHERE contact_id = %d AND tag_id = %d",
							$contact_id,
							$tag_id
						)
					);

					if ( ! $existing_contact_tag ) {
						$insert_result = $wpdb->insert(
							$contact_tags_table,
							[
								'contact_id' => $contact_id,
								'tag_id'     => $tag_id,
							],
							[ '%d', '%d' ]
						);
						
						if ( $insert_result ) {
							$added_tags[] = $tag_id;
							
							// Fire action hook.
							do_action( 'mailerpress_contact_tag_added', $contact_id, $tag_id );
						}
					} else {
						// Tag already exists for this contact.
						$added_tags[] = $tag_id;
					}
				}
			}

			return [
				'contact_id' => $contact_id,
				'email'      => $email,
				'tags_added' => $added_tags,
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

AddTagToContact::get_instance();
