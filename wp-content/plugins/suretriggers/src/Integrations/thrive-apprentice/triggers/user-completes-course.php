<?php
/**
 * UserCompletesCourse.
 * php version 5.6
 *
 * @category UserCompletesCourse
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

if ( ! class_exists( 'UserCompletesCourse' ) ) :

	/**
	 * UserCompletesCourse
	 *
	 * @category UserCompletesCourse
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class UserCompletesCourse {

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
		public $trigger = 'user_completes_course';

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
				'label'         => __( 'User Completes Course', 'suretriggers' ),
				'action'        => 'user_completes_course',
				'common_action' => 'thrive_apprentice_course_finish',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $course_data Course data.
		 * @param array $user_data User data.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $course_data, $user_data ) {
			if ( empty( $course_data ) || empty( $user_data ) ) {
				return;
			}
			$context              = [];
			$context['course_id'] = $course_data['course_id'];
		
			$context = [
				'course_id'   => $course_data['course_id'],
				'course_data' => $course_data,
				'user_data'   => $user_data,
			];

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	UserCompletesCourse::get_instance();

endif;
