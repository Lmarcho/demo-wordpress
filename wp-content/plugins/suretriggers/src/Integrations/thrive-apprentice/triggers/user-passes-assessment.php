<?php
/**
 * UserPassesAssessment.
 * php version 5.6
 *
 * @category UserPassesAssessment
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

if ( ! class_exists( 'UserPassesAssessment' ) ) :

	/**
	 * UserPassesAssessment
	 *
	 * @category UserPassesAssessment
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class UserPassesAssessment {

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
		public $trigger = 'user_passes_assessment';

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
				'label'         => __( 'User Passes Assessment in Course', 'suretriggers' ),
				'action'        => 'user_passes_assessment',
				'common_action' => 'tva_assessment_passed',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Get property value from user assessment object.
		 *
		 * @param object $obj User assessment object.
		 * @param string $property Property name.
		 * @return mixed Property value.
		 */
		private function get_property( $obj, $property ) {
			return $obj->{$property};
		}

		/**
		 * Trigger listener
		 *
		 * @param object $user_assessment {
		 *     User assessment object.
		 *     @type int    $ID                       Assessment submission ID.
		 *     @type int    $post_parent             Assessment ID.
		 *     @type string $post_date               Assessment submission date.
		 *     @type int    $post_author             User ID who submitted.
		 *     @type string $type                    Assessment type.
		 *     @type mixed  $value                   Assessment value.
		 *     @type string $status                  Assessment status.
		 *     @type int    $user_submission_counter Submission counter.
		 * }
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $user_assessment ) {
			$user_id                  = $this->get_property( $user_assessment, 'post_author' );
			$context                  = [];
			$context['assessment_id'] = $this->get_property( $user_assessment, 'post_parent' );
			$context                  = [
				'submission_id'             => $this->get_property( $user_assessment, 'ID' ),
				'assessment_id'             => $this->get_property( $user_assessment, 'post_parent' ),
				'user_assessment_date'      => $this->get_property( $user_assessment, 'post_date' ),
				'user_assessment_author'    => is_numeric( $user_id ) ? get_the_author_meta( 'display_name', (int) $user_id ) : null,
				'user_assessment_author_id' => $this->get_property( $user_assessment, 'post_author' ),
				'user_assessment_type'      => $this->get_property( $user_assessment, 'type' ),
				'user_assessment_value'     => $this->get_property( $user_assessment, 'value' ),
				'user_assessment_status'    => $this->get_property( $user_assessment, 'status' ),
				'user_submission_counter'   => $this->get_property( $user_assessment, 'user_submission_counter' ),
			];
			
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	UserPassesAssessment::get_instance();

endif;
