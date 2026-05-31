<?php
/**
 * AddContactToList.
 * php version 5.6
 *
 * @category AddContactToList
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
 * AddContactToList
 *
 * @category AddContactToList
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddContactToList extends AutomateAction {

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
	public $action = 'add_contact_to_list';

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
			'label'    => __( 'Add contact to list', 'suretriggers' ),
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
		$email               = isset( $selected_options['email'] ) ? sanitize_email( $selected_options['email'] ) : '';
		$first_name          = isset( $selected_options['first_name'] ) ? sanitize_text_field( $selected_options['first_name'] ) : '';
		$last_name           = isset( $selected_options['last_name'] ) ? sanitize_text_field( $selected_options['last_name'] ) : '';
		$subscription_status = isset( $selected_options['subscription_status'] ) ? sanitize_text_field( $selected_options['subscription_status'] ) : 'subscribed';
		$lists               = isset( $selected_options['lists'] ) ? $selected_options['lists'] : [];
		$tags                = isset( $selected_options['tags'] ) ? $selected_options['tags'] : [];

		// Convert string to array if needed.
		if ( is_string( $lists ) ) {
			$lists = [ $lists ];
		}
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

		try {
			global $wpdb;
			
			// MailerPress table names.
			$contact_table       = $wpdb->prefix . 'mailerpress_contact';
			$contact_lists_table = $wpdb->prefix . 'mailerpress_contact_lists';
			$contact_tags_table  = $wpdb->prefix . 'mailerpress_contact_tags';

			// Check if contact already exists.
			$existing_contact = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}mailerpress_contact WHERE email = %s",
					$email
				)
			);

			if ( $existing_contact ) {
				// Update existing contact.
				$wpdb->update(
					$contact_table,
					[
						'first_name'          => $first_name,
						'last_name'           => $last_name,
						'subscription_status' => $subscription_status,
						'updated_at'          => current_time( 'mysql' ),
					],
					[ 'contact_id' => $existing_contact->contact_id ],
					[ '%s', '%s', '%s', '%s' ],
					[ '%d' ]
				);

				$contact_id = $existing_contact->contact_id;
				
				// Fire action hook.
				do_action( 'mailerpress_contact_updated', $contact_id );
			} else {
				// Create new contact.
				$unsubscribe_token = wp_generate_uuid4();
				
				$result = $wpdb->insert(
					$contact_table,
					[
						'email'               => $email,
						'first_name'          => $first_name,
						'last_name'           => $last_name,
						'subscription_status' => $subscription_status,
						'created_at'          => current_time( 'mysql' ),
						'updated_at'          => current_time( 'mysql' ),
						'unsubscribe_token'   => $unsubscribe_token,
						'opt_in_source'       => '',
						'opt_in_details'      => '',
						'access_token'        => bin2hex( openssl_random_pseudo_bytes( 32 ) ),
					],
					[ '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ]
				);

				if ( false === $result ) {
					return [
						'status'  => 'error',
						'message' => 'Failed to create contact.',
					];
				}

				$contact_id = $wpdb->insert_id;
				
				// Fire action hook.
				do_action( 'mailerpress_contact_created', $contact_id );
			}

			// Add contact to lists.
			if ( ! empty( $lists ) ) {
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
							} else {
								// Create new list.
								$new_list_result = $wpdb->insert(
									$wpdb->prefix . 'mailerpress_lists',
									[
										'name'        => sanitize_text_field( $list['name'] ),
										'description' => '',
										'created_at'  => current_time( 'mysql' ),
										'updated_at'  => current_time( 'mysql' ),
									],
									[ '%s', '%s', '%s', '%s' ]
								);
								
								if ( $new_list_result ) {
									$list_id = $wpdb->insert_id;
								}
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
						} else {
							// Create new list.
							$new_list_result = $wpdb->insert(
								$wpdb->prefix . 'mailerpress_lists',
								[
									'name'        => sanitize_text_field( $list ),
									'description' => '',
									'created_at'  => current_time( 'mysql' ),
									'updated_at'  => current_time( 'mysql' ),
								],
								[ '%s', '%s', '%s', '%s' ]
							);
							
							if ( $new_list_result ) {
								$list_id = $wpdb->insert_id;
							}
						}
					}
					
					if ( $list_id > 0 ) {
						// Check if already in list.
						$existing_contact_list = $wpdb->get_row(
							$wpdb->prepare(
								"SELECT * FROM {$wpdb->prefix}mailerpress_contact_lists WHERE contact_id = %d AND list_id = %d",
								$contact_id,
								$list_id
							)
						);

						if ( ! $existing_contact_list ) {
							$wpdb->insert(
								$contact_lists_table,
								[
									'contact_id' => $contact_id,
									'list_id'    => $list_id,
								],
								[ '%d', '%d' ]
							);
							
							// Fire action hook.
							do_action( 'mailerpress_contact_list_added', $contact_id, $list_id );
						}
					}
				}
			}

			// Add contact to tags.
			if ( ! empty( $tags ) ) {
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
							$wpdb->insert(
								$contact_tags_table,
								[
									'contact_id' => $contact_id,
									'tag_id'     => $tag_id,
								],
								[ '%d', '%d' ]
							);
							
							// Fire action hook.
							do_action( 'mailerpress_contact_tag_added', $contact_id, $tag_id );
						}
					}
				}
			}

			return [
				'contact_id'          => $contact_id,
				'email'               => $email,
				'first_name'          => $first_name,
				'last_name'           => $last_name,
				'subscription_status' => $subscription_status,
				'lists_added'         => $lists,
				'tags_added'          => $tags,
				'success'             => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

AddContactToList::get_instance();
