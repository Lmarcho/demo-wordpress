<?php
/**
 * ListMembers.
 *
 * @category ListMembers
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\PaidMembershipsPro\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * ListMembers
 *
 * @category ListMembers
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListMembers extends AutomateAction {

	use SingletonLoader;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'PaidMembershipsPro';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'list_members_by_membership_level';

	/**
	 * Register the action.
	 *
	 * @param array $actions Available actions.
	 *
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List all users in a membership level', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id User ID.
	 * @param int   $automation_id Automation ID.
	 * @param array $fields Fields.
	 * @param array $selected_options Selected Options.
	 *
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		global $wpdb;
	
		$level_id = isset( $selected_options['membership_id'] ) ? $selected_options['membership_id'] : -1;
	
		if ( -1 !== $level_id ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT mu.user_id, mu.membership_id, ml.name AS membership_name
                     FROM {$wpdb->prefix}pmpro_memberships_users AS mu
                     LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml
                     ON mu.membership_id = ml.id
                     WHERE mu.membership_id = %d AND mu.status = 'active'",
					$level_id
				)
			);
		} else {
			$results = $wpdb->get_results(
				"SELECT mu.user_id, mu.membership_id, ml.name AS membership_name
                 FROM {$wpdb->prefix}pmpro_memberships_users AS mu
                 LEFT JOIN {$wpdb->prefix}pmpro_membership_levels AS ml
                 ON mu.membership_id = ml.id
                 WHERE mu.status = 'active'"
			);
		}
	
		if ( empty( $results ) ) {
			return [
				'status'   => esc_attr__( 'Success', 'suretriggers' ),
				'response' => esc_attr__( 'No active members found for the selected level.', 'suretriggers' ), 
				
			];
		}
	
		$members = array_filter(
			array_map(
				function ( $row ) {
					$user = get_userdata( $row->user_id );
					if ( ! $user ) {
						return null;
					}
					return [
						'ID'              => $user->ID,
						'name'            => $user->display_name,
						'email'           => $user->user_email,
						'membership_id'   => $row->membership_id,
						'membership_name' => $row->membership_name,
					];
				},
				$results
			)
		);
	
		return [
			'status'  => esc_attr__( 'Success', 'suretriggers' ),
			'members' => $members,
		];
	}
	
	
}

ListMembers::get_instance();
