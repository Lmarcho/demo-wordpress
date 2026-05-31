<?php
/**
 * UserReceivesProductAccess.
 * php version 5.6
 *
 * @category UserReceivesProductAccess
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ThriveApprentice\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\ThriveApprentice\ThriveApprentice;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'UserReceivesProductAccess' ) ) :

	/**
	 * UserReceivesProductAccess
	 *
	 * @category UserReceivesProductAccess
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class UserReceivesProductAccess {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'ThriveApprentice';

		/**
		 * Action name.
		 *
		 * @var string
		 */
		public $trigger = 'user_receives_product_access';

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
		 * Register a trigger.
		 *
		 * @param array $triggers triggers.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'User Receives Access to Product', 'suretriggers' ),
				'action'        => 'user_receives_product_access',
				'common_action' => 'tva_user_receives_product_access',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $user User object.
		 * @param int    $product_id Product ID.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $user, $product_id ) {
			if ( empty( $user ) || empty( $product_id ) ) {
				return;
			}
			$context['product_id'] = $product_id;
			$context               = [
				'user'       => $user,
				'product_id' => $product_id,
			];
	
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	UserReceivesProductAccess::get_instance();

endif;
