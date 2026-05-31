<?php
/**
 * GetForumSubscribers.
 * php version 5.6
 *
 * @category GetForumSubscribers
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
 * GetForumSubscribers
 *
 * @category GetForumSubscribers
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetForumSubscribers extends AutomateAction {

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
	public $action = 'get_forum_subscribers';

	use SingletonLoader;

	/**
	 * Register.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Forum Subscribers', 'suretriggers' ),
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
	 * @param array $selected_options selected options.
	 * @return mixed
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		if ( ! function_exists( 'bbp_get_forum' ) || ! function_exists( 'bbp_get_forum_subscribers' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Required BuddyBoss functions do not exist.',
			];
		}

		if ( empty( $selected_options['bb_forum'] ) ) {
			return [
				'success' => false,
				'message' => 'Forum ID is required.',
			];
		}

		$forum_id = $selected_options['bb_forum'];
		$forum    = bbp_get_forum( $forum_id );

		if ( empty( $forum ) ) {
			return [
				'success' => false,
				'message' => 'Invalid forum selected.',
			];
		}

		$user_ids = bbp_get_forum_subscribers( $forum_id );

		if ( empty( $user_ids ) || ! is_array( $user_ids ) ) {
			return [
				'success' => false,
				'message' => 'No subscribers found for the selected forum.',
			];
		}

		$subscribers = [];

		foreach ( $user_ids as $id ) {
			$user = get_userdata( $id );
			if ( $user ) {
				$subscribers[] = [
					'user_id' => $id,
					'email'   => $user->user_email,
				];
			}
		}

		if ( empty( $subscribers ) ) {
			return [
				'success' => false,
				'message' => 'No valid subscriber data found.',
			];
		}

		return [
			'success'     => true,
			'message'     => 'Subscribers fetched successfully.',
			'subscribers' => $subscribers,
		];
	}
}
GetForumSubscribers::get_instance();
