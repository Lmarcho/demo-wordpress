<?php
/**
 * FetchMembers.
 * php version 5.6
 *
 * @category FetchMembers
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCommunity\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * FetchMembers
 *
 * @category FetchMembers
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class FetchMembers extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCommunity';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fc_fetch_members';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Fetch Members', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;

	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selected_options.
	 *
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! defined( 'FLUENT_COMMUNITY_PLUGIN_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => 'FluentCommunity plugin is not active.',
			];
		}

		$space_id = isset( $selected_options['space_id'] ) ? (int) sanitize_text_field( $selected_options['space_id'] ) : 0;
		$limit    = isset( $selected_options['limit'] ) ? (int) sanitize_text_field( $selected_options['limit'] ) : 50;
	
		if ( $space_id && ! $this->is_valid_space( $space_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid or non-existent Space ID.',
			];
		}

		try {
			$members = $this->get_members( $space_id, $limit );

			if ( empty( $members ) ) {
				return [
					'status'  => 'success',
					'message' => $space_id ? 'No members found in the specified space.' : 'No members found in the community.',
					'members' => [],
				];
			}

			return [
				'status'      => 'success',
				'message'     => 'Members fetched successfully.',
				'members'     => $members,
				'space_id'    => ! empty( $space_id ) ? $space_id : 'all',
				'total_found' => count( $members ),
			];
		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => 'Error fetching members: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Check if space ID is valid.
	 *
	 * @param int $space_id Space ID.
	 *
	 * @return bool
	 */
	private function is_valid_space( $space_id ) {
		global $wpdb;
		$space = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM {$wpdb->prefix}fcom_spaces WHERE ID = %d", $space_id ) );
		return (bool) $space;
	}

	/**
	 * Get members from community or specific space.
	 *
	 * @param int $space_id Space ID (0 for all community members).
	 * @param int $limit    Limit number of members.
	 *
	 * @return array
	 */
	private function get_members( $space_id = 0, $limit = 50 ) {
		global $wpdb;

		$space_id = (int) $space_id;
		$limit    = max( 1, min( 200, (int) $limit ) );

		if ( $space_id ) {
			$results = $this->get_space_members_data( $space_id, $limit );
		} else {
			$results = $this->get_community_members_data( $limit );
		}

		if ( ! $results ) {
			return [];
		}

		$members = [];
		foreach ( $results as $result ) {
			$member = [
				'user_id'      => $result['ID'],
				'display_name' => $result['display_name'],
				'email'        => $result['user_email'],
				'registered'   => $result['user_registered'],
			];

			if ( $space_id ) {
				$member['role']              = ! empty( $result['role'] ) ? $result['role'] : '';
				$member['membership_status'] = $result['membership_status'];
				$member['joined_at']         = $result['joined_at'];
			}

			$member['profile_url'] = $this->get_member_profile_url( $result['ID'] );
			
			$members[] = $member;
		}

		return $members;
	}

	/**
	 * Get member profile URL.
	 *
	 * @param int $user_id User ID.
	 *
	 * @return string
	 */
	private function get_member_profile_url( $user_id ) {
		if ( class_exists( '\FluentCommunity\App\Services\Helper' ) ) {
			try {
				$user = get_user_by( 'ID', $user_id );
				if ( $user ) {
					return \FluentCommunity\App\Services\Helper::baseUrl( 'profile/' . $user->user_nicename );
				}
			} catch ( Exception $e ) {
				// Fallback if FluentCommunity helper is not available.
				unset( $e );
			}
		}

		return home_url( "/community/profile/{$user_id}" );
	}

	/**
	 * Get space members data without direct users table access.
	 *
	 * @param int $space_id Space ID.
	 * @param int $limit    Limit.
	 *
	 * @return array
	 */
	private function get_space_members_data( $space_id, $limit ) {
		global $wpdb;

		
		$space_users = $wpdb->get_results( $wpdb->prepare( "SELECT su.user_id, su.role, su.status as membership_status, su.created_at as joined_at FROM {$wpdb->prefix}fcom_space_user su WHERE su.space_id = %d ORDER BY su.created_at DESC LIMIT %d", $space_id, $limit ), ARRAY_A );
		

		$results = [];
		foreach ( $space_users as $space_user ) {
			$user = get_userdata( $space_user['user_id'] );
			if ( $user ) {
				$results[] = [
					'ID'                => $user->ID,
					'display_name'      => $user->display_name,
					'user_email'        => $user->user_email,
					'user_registered'   => $user->user_registered,
					'role'              => $space_user['role'],
					'membership_status' => $space_user['membership_status'],
					'joined_at'         => $space_user['joined_at'],
				];
			}
		}

		return $results;
	}

	/**
	 * Get community members data without direct users table access.
	 *
	 * @param int $limit  Limit.
	 *
	 * @return array
	 */
	private function get_community_members_data( $limit ) {
		global $wpdb;

		$space_users = $wpdb->get_results( 
			$wpdb->prepare( 
				"SELECT DISTINCT su.user_id 
				FROM {$wpdb->prefix}fcom_space_user su 
				WHERE su.status = 'active' 
				LIMIT %d",  
				$limit 
			), 
			ARRAY_A 
		);

		$results = [];
		foreach ( $space_users as $space_user ) {
			$user = get_userdata( $space_user['user_id'] );
			if ( $user ) {
				$results[] = [
					'ID'              => $user->ID,
					'display_name'    => $user->display_name,
					'user_email'      => $user->user_email,
					'user_registered' => $user->user_registered,
				];
			}
		}

		return $results;
	}

}

FetchMembers::get_instance();
