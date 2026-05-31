<?php
/**
 * SendNotificationToAllMembersOfGroup.
 * php version 5.6
 *
 * @category SendNotificationToAllMembersOfGroup
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\BuddyBoss\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * SendNotificationToAllMembersOfGroup
 *
 * @category SendNotificationToAllMembersOfGroup
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SendNotificationToAllMembersOfGroup extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'BuddyBoss';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'bb_send_notification_to_all_members_of_group';

	use SingletonLoader;

	/**
	 * Add Sure Triggers component
	 *
	 * @param array $component_names component names.
	 * @param array $active_components active components.
	 * @return array
	 */
	public function st_bdb_component( $component_names, $active_components ) {
		$component_names = ! is_array( $component_names ) ? [] : $component_names;
		array_push( $component_names, 'suretriggers' );
		return $component_names;
	}

	/**
	 * Update notification Content for SureTrigger Notifications.
	 *
	 * @param string $content content.
	 * @param int    $item_id item id.
	 * @param int    $secondary_item_id secondary item id.
	 * @param int    $action_item_count action item count.
	 * @param string $format format.
	 * @param string $component_action_name component action name.
	 * @param string $component_name component name.
	 * @param int    $id id.
	 * @return array|string
	 */
	public function st_bdb_notification_content( $content, $item_id, $secondary_item_id, $action_item_count, $format, $component_action_name, $component_name, $id ) {
		if ( 'sure-triggers_bb_notification' === $component_action_name && function_exists( 'bp_notifications_get_meta' ) ) {
			$notification_content = bp_notifications_get_meta( $id, 'st_notification_content' );
			$notification_link    = bp_notifications_get_meta( $id, 'st_notification_link' );

			if ( 'string' === $format ) {
				if ( '' !== $notification_link ) {
					$notification_content = '<a href="' . esc_url( $notification_link ) . '">' . $notification_content . '</a>';
				}
				return $notification_content;
			} elseif ( 'object' === $format ) {
				return [
					'text' => $notification_content,
					'link' => $notification_link,
				];
			}
		}

		return $content;
	}

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 *
	 * @return array
	 */
	public function register( $actions ) {
		add_filter(
			'bp_notifications_get_registered_components',
			[
				$this,
				'st_bdb_component', 
			],
			99,
			2
		);

		add_filter(
			'bp_notifications_get_notifications_for_user',
			[
				$this,
				'st_bdb_notification_content', 
			],
			99,
			8
		);
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Send Notification To All Members Of Group', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, '_action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selectedOptions.
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'bp_core_current_time' ) || ! function_exists( 'bp_notifications_update_meta' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'BuddyBoss core functions not found.', 'suretriggers' ), 
			];
		}
		if ( empty( $selected_options['sender_user'] ) || ! is_email( $selected_options['sender_user'] ) ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid email.', 
			];
		}

		$sender_id = email_exists( $selected_options['sender_user'] );

		if ( false === $sender_id ) {
			return [
				'status'  => 'error',
				'message' => 'User with email ' . $selected_options['sender_user'] . ' does not exists .', 
			];
		}
		$group_id             = $selected_options['bb_group']['value'];
		$notification_content = $selected_options['bb_notification_content'];
		$notification_link    = $selected_options['bb_notification_link'];
		$context              = [];
		if ( function_exists( 'groups_get_group_members' ) ) {
			$members = groups_get_group_members(
				[
					'group_id'       => $group_id,
					'page'           => 1,
					'per_page'       => 999999,
					'type'           => 'last_joined',
					'exclude_banned' => true,
				]
			);
			if ( isset( $members['members'] ) ) {

				if ( function_exists( 'bp_notifications_add_notification' ) ) {

					foreach ( $members['members'] as $member ) {
						$context['member_ids'][]    = $member->ID;
						$context['member_emails'][] = $member->user_email;
						$notification_id            = bp_notifications_add_notification(
							[
								'user_id'           => $member->ID,
								'item_id'           => $group_id,
								'secondary_item_id' => $sender_id,
								'component_name'    => 'suretriggers',
								'component_action'  => 'sure-triggers_bb_notification',
								'date_notified'     => bp_core_current_time(),
								'is_new'            => 1,
								'allow_duplicate'   => true,
							]
						);
						// Adding meta for notification display on front-end.
						bp_notifications_update_meta( $notification_id, 'st_notification_content', $notification_content );
						bp_notifications_update_meta( $notification_id, 'st_notification_link', $notification_link );
					}
					return $context;
				}
			}
		}

		return [
			'status'  => 'error',
			'message' => SURE_TRIGGERS_ACTION_ERROR_MESSAGE,
		];
	}
}

SendNotificationToAllMembersOfGroup::get_instance();
