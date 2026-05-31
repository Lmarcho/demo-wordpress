<?php
/**
 * ListCourseSections.
 * php version 5.6
 *
 * @category ListCourseSections
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCommunity\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCommunity\Modules\Course\Model\Course;
use FluentCommunity\Modules\Course\Model\CourseTopic;

/**
 * ListCourseSections
 *
 * @category ListCourseSections
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListCourseSections extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCommunity';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fc_list_course_sections';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Course Sections', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id         User ID.
	 * @param int   $automation_id   Automation ID.
	 * @param array $fields          Fields.
	 * @param array $selected_options Selected options.
	 *
	 * @return array|void
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$course_id       = isset( $selected_options['course_id'] ) ? (int) sanitize_text_field( $selected_options['course_id'] ) : 0;
		$include_lessons = isset( $selected_options['include_lessons'] ) ? sanitize_text_field( $selected_options['include_lessons'] ) : 'yes';
		$only_published  = isset( $selected_options['only_published'] ) ? sanitize_text_field( $selected_options['only_published'] ) : 'no';

		// Validate required fields.
		if ( empty( $course_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Course ID is required.',
			];
		}

		// Check if required classes exist.
		if ( ! class_exists( 'FluentCommunity\Modules\Course\Model\Course' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Course class not found.',
			];
		}

		if ( ! class_exists( 'FluentCommunity\Modules\Course\Model\CourseTopic' ) ) {
			return [
				'status'  => 'error',
				'message' => 'CourseTopic class not found.',
			];
		}

		try {
			// Validate that the course exists.
			$course = Course::findOrFail( $course_id );

			// Build the sections query.
			$sections_query = CourseTopic::where( 'space_id', $course_id )
				->orderBy( 'priority', 'ASC' );

			// Filter by published status if requested.
			if ( 'yes' === $only_published ) {
				$sections_query->where( 'status', 'published' );
				
				if ( 'yes' === $include_lessons ) {
					$sections_query->with(
						[
							'lessons' => function ( $q ) {
								$q->where( 'status', 'published' );
							},
						] 
					);
				}
			} elseif ( 'yes' === $include_lessons ) {
				$sections_query->with( [ 'lessons' ] );
			}

			// Get the sections.
			$sections = $sections_query->get();

			// Format the response.
			$formatted_sections = [];
			foreach ( $sections as $section ) {
				$section_data = [
					'id'         => $section->id,
					'title'      => $section->title,
					'slug'       => $section->slug,
					'status'     => $section->status,
					'priority'   => $section->priority,
					'created_at' => $section->created_at,
					'updated_at' => $section->updated_at,
				];

				// Include lessons if requested.
				if ( 'yes' === $include_lessons && isset( $section->lessons ) ) {
					$section_data['lessons'] = [];
					foreach ( $section->lessons as $lesson ) {
						$section_data['lessons'][] = [
							'id'           => $lesson->id,
							'title'        => $lesson->title,
							'slug'         => $lesson->slug,
							'status'       => $lesson->status,
							'content_type' => $lesson->content_type,
							'created_at'   => $lesson->created_at,
							'updated_at'   => $lesson->updated_at,
						];
					}
					$section_data['lessons_count'] = count( $section_data['lessons'] );
				}

				$formatted_sections[] = $section_data;
			}

			return [
				'status'         => 'success',
				'message'        => 'Course sections retrieved successfully',
				'course_id'      => $course_id,
				'course_title'   => $course->title,
				'sections'       => $formatted_sections,
				'sections_count' => count( $formatted_sections ),
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => 'An error occurred while retrieving course sections: ' . $e->getMessage(),
			];
		}
	}
}

ListCourseSections::get_instance();
