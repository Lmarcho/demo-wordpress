<?php
/**
 * UserCompletesModule.
 * php version 5.6
 *
 * @category UserCompletesModule
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

if ( ! class_exists( 'UserCompletesModule' ) ) :

	/**
	 * UserCompletesModule
	 *
	 * @category UserCompletesModule
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class UserCompletesModule {

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
		public $trigger = 'user_completes_module';

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
				'label'         => __( 'User Completes Module', 'suretriggers' ),
				'action'        => 'user_completes_module',
				'common_action' => 'thrive_apprentice_module_finish',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $module_details Module details.
		 * @param array $user_details User details.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $module_details, $user_details ) {
			if ( empty( $module_details ) || empty( $user_details ) ) {
				return;
			}
			$context              = [];                                                                                                        
			$context['module_id'] = $module_details['module_id'];                          
			$context['course_id'] = $module_details['course_id']; 
			$context              = [
				'module_id'   => $module_details['module_id'],
				'course_id'   => $module_details['course_id'],
				'module_data' => $module_details,
				'user_data'   => $user_details,
			];
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	UserCompletesModule::get_instance();

endif;
