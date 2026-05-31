<?php
/**
 * SubscriptionActivated.
 *
 * @category SubscriptionActivated
 * @package  SureTriggers
 * @since    1.1.5
 */

namespace SureTriggers\Integrations\ProfilePress\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Models\Utilities;

if ( ! class_exists( 'SubscriptionActivated' ) ) :

	/**
	 * SubscriptionActivated
	 */
	class SubscriptionActivated {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'ProfilePress';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'profilepress_subscription_activated';

		use SingletonLoader;

		/**
		 * Constructor
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register trigger.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'A ProfilePress subscription is activated', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'ppress_subscription_activated',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1, // Hook passes SubscriptionEntity.
			];
			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $subscription Subscription.
		 * @return void|array
		 */
		public function trigger_listener( $subscription ) {
			if ( ! class_exists( '\ProfilePress\Core\Membership\Models\Subscription\SubscriptionEntity' ) ) {
				return [
					'status'   => 'error',
					'response' => __( 'ProfilePress Subscription Entity class not found. Please ensure ProfilePress is properly installed.', 'suretriggers' ), 
					
				];
			}

			$subscription_data = Utilities::object_to_array( $subscription );

			// Pass subscription object directly.
			$context = [
				'subscription' => $subscription_data,
			];

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	SubscriptionActivated::get_instance();

endif;
