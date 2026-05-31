<?php
/**
 * OrderStatusChanged.
 * php version 5.6
 *
 * @category OrderStatusChanged
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

if ( ! class_exists( 'OrderStatusChanged' ) ) :

	/**
	 * OrderStatusChanged
	 *
	 * @category OrderStatusChanged
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class OrderStatusChanged {

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
		public $trigger = 'fluentcart_order_status_changed';

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
				'label'         => __( 'Order Status Changed', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => [ 'fluent_cart/order_status_changed' ],
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $data Data from FluentCart order status change containing order, customer, old_status, new_status, activity.
		 *
		 * @return void
		 */
		public function trigger_listener( $data ) {
			if ( empty( $data ) ) {
				return;
			}
			$context                 = $data;
			$context['order_status'] = $data['new_status'];
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
	OrderStatusChanged::get_instance();

endif;
