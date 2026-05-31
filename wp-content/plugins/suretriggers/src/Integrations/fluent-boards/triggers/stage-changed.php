<?php
/**
 * StageChanged.
 * php version 5.6
 *
 * @category StageChanged
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentBoards\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'StageChanged' ) ) :

	/**
	 * StageChanged
	 *
	 * @category StageChanged
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class StageChanged {


		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'FluentBoards';


		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'fbs_stage_changed';

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
				'label'         => __( 'Stage Changed', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluent_boards/task_stage_updated',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $task Task.
		 * @param int    $old_stage_id Old Status ID.
		 * @return void
		 */
		public function trigger_listener( $task, $old_stage_id ) {
			if ( empty( $task ) || ! is_object( $task ) || empty( $old_stage_id ) ) {
				return;
			}
		   
			
			$context = [
				'task'         => $task,
				'old_stage_id' => $old_stage_id,
				
			];
			
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
	StageChanged::get_instance();

endif;
