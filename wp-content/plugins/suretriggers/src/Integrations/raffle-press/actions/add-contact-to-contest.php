<?php
/**
 * AddContactToContest.
 * php version 5.6
 *
 * @category AddContactToContest
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */

namespace SureTriggers\Integrations\RafflePress\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * AddContactToContest
 *
 * @category AddContactToContest
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */
class AddContactToContest extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'RafflePress';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'rafflepress_add_contact_to_contest';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Contact to Contest', 'suretriggers' ),
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
	 * @param array $selected_options selectedOptions.
	 *
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! defined( 'RAFFLEPRESS_BUILD' ) && ! defined( 'RAFFLEPRESS_PRO_BUILD' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'RafflePress plugin is not installed or activated.', 'suretriggers' ),
			];
		}

		global $wpdb;

		$contest_id = isset( $selected_options['contest_id'] ) ? absint( $selected_options['contest_id'] ) : 0;
		$email      = isset( $selected_options['email'] ) ? sanitize_email( $selected_options['email'] ) : '';
		$first_name = isset( $selected_options['first_name'] ) ? sanitize_text_field( $selected_options['first_name'] ) : '';
		$last_name  = isset( $selected_options['last_name'] ) ? sanitize_text_field( $selected_options['last_name'] ) : '';

		if ( empty( $contest_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Contest ID is required.', 'suretriggers' ),
			];
		}

		if ( empty( $email ) || ! is_email( $email ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Valid email address is required.', 'suretriggers' ),
			];
		}

		// Check if contest exists.
		$contest = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT id, name, active FROM {$wpdb->prefix}rafflepress_giveaways WHERE id = %d", 
				$contest_id 
			), 
			ARRAY_A 
		);
		
		if ( ! $contest ) {
			return [
				'status'  => 'error',
				'message' => __( 'Contest not found.', 'suretriggers' ),
			];
		}

		if ( ! $contest['active'] ) {
			return [
				'status'  => 'error',
				'message' => __( 'Contest is not active.', 'suretriggers' ),
			];
		}

		// Check if contestant already exists.
		$existing_contestant = $wpdb->get_row( 
			$wpdb->prepare( 
				"SELECT id FROM {$wpdb->prefix}rafflepress_contestants WHERE giveaway_id = %d AND email = %s", 
				$contest_id, 
				$email 
			), 
			ARRAY_A 
		);

		if ( $existing_contestant ) {
			return [
				'status'  => 'error',
				'message' => __( 'Contact already exists in this contest.', 'suretriggers' ),
			];
		}

		// Insert contestant.
		$contestant_data = [
			'giveaway_id' => $contest_id,
			'email'       => $email,
			'fname'       => $first_name,
			'lname'       => $last_name,
			'status'      => 'confirmed',
			'created_at'  => current_time( 'mysql' ),
		];

		$inserted = $wpdb->insert(
			$wpdb->prefix . 'rafflepress_contestants',
			$contestant_data,
			[ '%d', '%s', '%s', '%s', '%s', '%s' ]
		);
		
		if ( false === $inserted ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to add contact to contest.', 'suretriggers' ),
			];
		}

		$contestant_id = $wpdb->insert_id;

		$entry_data = [
			'giveaway_id'   => $contest_id,
			'contestant_id' => $contestant_id,
			'created'       => current_time( 'mysql' ),
		];

		$wpdb->insert(
			$wpdb->prefix . 'rafflepress_entries',
			$entry_data,
			[ '%d', '%d', '%s' ]
		);

		$context = [
			'contest_id'    => $contest_id,
			'contest_name'  => $contest['name'],
			'contestant_id' => $contestant_id,
			'email'         => $email,
			'first_name'    => $first_name,
			'last_name'     => $last_name,
			'full_name'     => trim( $first_name . ' ' . $last_name ),
			'status'        => 'confirmed',
			'entry_id'      => $wpdb->insert_id,
			'date_added'    => current_time( 'mysql' ),
		];

		return $context;
	}
}

AddContactToContest::get_instance();
