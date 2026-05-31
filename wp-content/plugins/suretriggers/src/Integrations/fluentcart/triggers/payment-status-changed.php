<?php
/**
 * PaymentStatusChanged.
 * php version 5.6
 *
 * @category PaymentStatusChanged
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

if ( ! class_exists( 'PaymentStatusChanged' ) ) :

	/**
	 * PaymentStatusChanged
	 *
	 * @category PaymentStatusChanged
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class PaymentStatusChanged {

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
		public $trigger = 'fluentcart_payment_status_changed';

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
				'label'         => __( 'Payment Status Changed', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => [ 'fluent_cart/payment_status_changed' ],
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array{order: object, old_payment_status?: string, new_payment_status?: string, customer?: object} $data Context data from FluentCart.
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
	PaymentStatusChanged::get_instance();

endif;
