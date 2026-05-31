<?php
/**
 * MembershipPlanUpdated.
 * php version 5.6
 *
 * @category MembershipPlanUpdated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Voxel\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;

if ( ! class_exists( 'MembershipPlanUpdated' ) ) :

	/**
	 * MembershipPlanUpdated
	 *
	 * @category MembershipPlanUpdated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class MembershipPlanUpdated {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'Voxel';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'voxel_membership_plan_updated';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Membership Plan Updated', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'voxel/membership/pricing-plan-updated',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 3,
			];
			
			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $user User object.
		 * @param object $old_plan Old plan.
		 * @param object $new_plan New plan.
		 * @return void
		 */
		public function trigger_listener( $user, $old_plan, $new_plan ) {
			
			$user_id = method_exists( $user, 'get_id' ) ? $user->get_id() : ( isset( $user->ID ) ? $user->ID : 0 );
			
			if ( 0 === $user_id ) {
				return;
			}
			
			global $wpdb;
			$context  = WordPress::get_user_context( $user_id );
			$meta_key = ( function_exists( '\Voxel\is_test_mode' ) && \Voxel\is_test_mode() ) ? 'voxel:test_plan' : 'voxel:plan';
			
			$sql     = "SELECT
				m.user_id AS id,
				m.meta_value AS details
			FROM wp_usermeta as m
			LEFT JOIN wp_users AS u ON m.user_id = u.ID
			WHERE m.meta_key = %s AND m.user_id = %d AND JSON_UNQUOTE( JSON_EXTRACT( m.meta_value, '$.plan' ) ) != 'default'
			ORDER BY m.user_id DESC
			LIMIT 25 OFFSET 0";
			$results = $wpdb->get_results( $wpdb->prepare( $sql, $meta_key, $user_id ), ARRAY_A );// @phpcs:ignore
			
			if ( ! empty( $results[0]['details'] ) ) {
				$context['details'] = json_decode( $results[0]['details'], true );
			}
			
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	MembershipPlanUpdated::get_instance();

endif;
