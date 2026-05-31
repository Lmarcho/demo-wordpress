<?php
/**
 * SureDashCourseComplete trigger for handling course completion events.
 * php version 5.6
 *
 * @category SureDashCourseComplete
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\SureDash\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

/**
 * SureDashCourseComplete
 *
 * @category SureDashCourseComplete
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SureDashCourseComplete {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'SureDash';

	/**
	 * Trigger name.
	 *
	 * @var string
	 */
	public $trigger = 'suredash_course_completed';

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
			'label'         => __( 'User complete Course', 'suretriggers' ),
			'action'        => 'updated_user_meta',
			'function'      => [ $this, 'trigger_listener' ],
			'priority'      => 10,
			'accepted_args' => 4,
		];
		return $triggers;
	}

	/**
	 * Trigger listener for course completion events.
	 *
	 * @param int    $meta_id Meta ID.
	 * @param int    $user_id User ID.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function trigger_listener( $meta_id, $user_id, $meta_key, $meta_value ) {
		// Check if this is a SureDash course completion meta update.
		if ( ! preg_match( '/^portal_course_(\d+)_completed_lessons$/', $meta_key, $matches ) ) {
			return;
		}
		
		$course_id = (int) $matches[1];
		
		// Check if course is now complete.
		$course_sections = get_post_meta( $course_id, 'pp_course_section_loop', true );
		$all_lesson_ids  = [];
		
		if ( is_array( $course_sections ) ) {
			foreach ( $course_sections as $section ) {
				if ( ! empty( $section['section_medias'] ) ) {
					foreach ( $section['section_medias'] as $media_data ) {
						$lesson_id = absint( ! empty( $media_data['value'] ) ? $media_data['value'] : 0 );
						if ( $lesson_id ) {
							$all_lesson_ids[] = $lesson_id;
						}
					}
				}
			}
		}
		
		// If all lessons are completed, trigger the automation.
		if ( is_array( $meta_value ) && count( $meta_value ) >= count( $all_lesson_ids ) && count( $all_lesson_ids ) > 0 ) {
			$course                      = get_post( $course_id );
			$context                     = WordPress::get_user_context( $user_id );
			$context['suredash_courses'] = $course_id;
			if ( $course instanceof \WP_Post ) {
				$context['course_title'] = $course->post_title;
			}
			
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}
}

SureDashCourseComplete::get_instance();
