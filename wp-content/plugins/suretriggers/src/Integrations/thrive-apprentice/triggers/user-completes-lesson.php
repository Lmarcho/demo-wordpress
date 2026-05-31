<?php
/**
 * UserCompletesLesson.
 * php version 5.6
 *
 * @category UserCompletesLesson
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

if ( ! class_exists( 'UserCompletesLesson' ) ) :

	/**
	 * UserCompletesLesson
	 *
	 * @category UserCompletesLesson
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class UserCompletesLesson {

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
		public $trigger = 'user_completes_lesson';

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
				'label'         => __( 'User Completes Lesson', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'thrive_apprentice_lesson_complete',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $lesson_data Lesson data.
		 * @param array $user_data User data.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $lesson_data, $user_data ) {
			if ( empty( $lesson_data ) || empty( $user_data ) ) {
				return;
			}
			$context              = [];
			$context['course_id'] = $lesson_data['course_id'];
			$context['lesson_id'] = $lesson_data['lesson_id'];
			$context              = [
				'lesson_id'   => $lesson_data['lesson_id'],
				'course_id'   => $lesson_data['course_id'],
				'lesson_data' => $lesson_data,
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

	UserCompletesLesson::get_instance();

endif;
