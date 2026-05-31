<?php
/**
 * SubscribeUserToForum.
 * php version 5.6
 *
 * @category SubscribeUserToForum
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
use SureTriggers\Integrations\WordPress\WordPress;

/**
 * SubscribeUserToForum
 * 
 * @category SubscribeUserToForum
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SubscribeUserToForum extends AutomateAction {

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
	public $action = 'bb_subscribe_user_to_forum';

	use SingletonLoader;

	/**
	 * Register the action.
	 *
	 * @param array $actions Registered actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Subscribe User to Forum', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Subscribe user to a forum.
	 *
	 * @param int   $user_id User ID.
	 * @param int   $automation_id Automation ID.
	 * @param array $fields Fields.
	 * @param array $selected_options Selected options.
	 * @return array
	 * @throws Exception If required data is missing or subscription fails.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		if ( ! function_exists( 'bbp_is_user_subscribed' ) || ! function_exists( 'bbp_add_user_subscription' ) || ! function_exists( 'bbp_get_forum' ) || ! function_exists( 'bbp_is_subscriptions_active' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Required BuddyBoss functions are not available.',
			];
		}

		if ( empty( $selected_options['user'] ) ) {
			return [
				'success' => false,
				'message' => 'User email is required.',
			];
		}

		$user_email = sanitize_email( $selected_options['user'] );

		if ( ! is_email( $user_email ) ) {
			return [
				'success' => false,
				'message' => 'Invalid email address.',
			];
		}

		$user = get_user_by( 'email', $user_email );

		if ( ! $user ) {
			return [
				'success' => false,
				'message' => 'User with email ' . $user_email . ' does not exist.',
			];
		}

		$user_id = $user->ID;

		if ( bbp_is_subscriptions_active() === false ) {
			return [
				'success' => false,
				'message' => 'Forum subscriptions are currently disabled on this site.',
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

		if ( bbp_is_user_subscribed( $user_id, $forum_id ) ) {
			return [
				'success'    => true,
				'message'    => 'User is already subscribed to the forum.',
				'user_email' => $user_email,
				'forum'      => $forum,
			];
		}

		$subscribed = bbp_add_user_subscription( $user_id, $forum_id );

		if ( ! $subscribed ) {
			return [
				'success' => false,
				'message' => 'Failed to subscribe user to the forum. Please try again later.',
			];
		}

		return [
			'success'    => true,
			'message'    => 'User subscribed to the forum successfully.',
			'user_email' => $user_email,
			'forum'      => $forum,
		];
	}
}

SubscribeUserToForum::get_instance();
