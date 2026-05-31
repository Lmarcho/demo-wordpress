<?php
/**
 * SubscriptionExpired.
 * php version 5.6
 *
 * @category SubscriptionExpired
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPSubscription\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'SubscriptionExpired' ) ) :

	/**
	 * SubscriptionExpired
	 *
	 * @category SubscriptionExpired
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class SubscriptionExpired {

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
		public $trigger = 'wp_subscription_expired';

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
				'label'         => __( 'Subscription Expired', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'subscrpt_subscription_expired',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int $subscription_id Subscription ID.
		 * @return void
		 */
		public function trigger_listener( $subscription_id ) {
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

	SubscriptionExpired::get_instance();

endif;
