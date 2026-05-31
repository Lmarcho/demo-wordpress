<?php
/**
 * SubscriptionStatusChanged.
 * php version 5.6
 *
 * @category SubscriptionStatusChanged
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPSubscription\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'SubscriptionStatusChanged' ) ) :

	/**
	 * SubscriptionStatusChanged
	 *
	 * @category SubscriptionStatusChanged
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class SubscriptionStatusChanged {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'WPSubscription';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'wp_subscription_status_changed';

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
				'label'         => __( 'Subscription Status Changed', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'subscrpt_subscription_status_changed',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 3,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int    $subscription_id Subscription ID.
		 * @param string $old_status Old status.
		 * @param string $new_status New status.
		 * @return void
		 */
		public function trigger_listener( $subscription_id, $old_status, $new_status ) {
			if ( empty( $subscription_id ) ) {
				return;
			}

			$subscription = get_post( $subscription_id );
			if ( ! $subscription || 'subscrpt_order' !== $subscription->post_type ) {
				return;
			}

			$context = [
				'subscription_id' => $subscription_id,
				'subscription'    => $subscription,
				'old_status'      => $old_status,
				'new_status'      => $new_status,
				'user_id'         => $subscription->post_author,
			];

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	SubscriptionStatusChanged::get_instance();

endif;
