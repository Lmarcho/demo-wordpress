<?php
/**
 * CustomerUpdated.
 * php version 5.6
 *
 * @category CustomerUpdated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */

namespace SureTriggers\Integrations\ProfilePress\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Models\Utilities;

if ( ! class_exists( 'CustomerUpdated' ) ) :

	/**
	 * CustomerUpdated
	 *
	 * @category CustomerUpdated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class CustomerUpdated {

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
		public $trigger = 'profilepress_customer_updated';

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
		 * Register trigger.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'A ProfilePress customer is updated', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'ppress_customer_updated',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1, // Hook passes customer_id.
			];
			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int $customer_id ID of the updated customer.
		 * @since 1.0.0
		 *
		 * @return void|array
		 */
		public function trigger_listener( $customer_id ) {

			$context = [];
			if ( empty( $customer_id ) ) {
				return [
					'status'   => 'error',
					'response' => __( 'No customer ID provided.', 'suretriggers' ), 
					
				];
			}

			if ( class_exists( '\ProfilePress\Core\Membership\Models\Customer\CustomerFactory' ) ) {
				
				$customer = \ProfilePress\Core\Membership\Models\Customer\CustomerFactory::fromId( absint( $customer_id ) );
								
				if ( ! $customer || ! $customer->exists() ) {
					return [
						'status'   => 'error',
						'response' => __( 'Invalid customer ID or customer does not exist.', 'suretriggers' ), 
						
					];
				}

				$customer_data = Utilities::object_to_array( $customer );

				$context = [
					'customer' => $customer_data,
				];
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
	 * Boot class.
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	CustomerUpdated::get_instance();

endif;
