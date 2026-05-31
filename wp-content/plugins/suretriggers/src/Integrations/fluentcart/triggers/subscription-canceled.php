<?php
/**
 * SubscriptionCanceled.
 * php version 5.6
 *
 * @category SubscriptionCanceled
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCart\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'SubscriptionCanceled' ) ) :

	/**
	 * SubscriptionCanceled
	 *
	 * @category SubscriptionCanceled
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class SubscriptionCanceled {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'FluentCart';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'fluentcart_subscription_canceled';

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
				'label'         => __( 'Subscription Canceled', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => [ 'fluent_cart/subscription_canceled' ],
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 *  Trigger listener
		 *
		 * @param array $data Data from FluentCart subscription cancellation containing subscription, order, customer, reason.
		 *
		 * @return void
		 */
		public function trigger_listener( $data ) {
			if ( empty( $data ) ) {
				return;
			}
			
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $data,
				]
			);
		}

		
	}

		

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	SubscriptionCanceled::get_instance();

endif;
