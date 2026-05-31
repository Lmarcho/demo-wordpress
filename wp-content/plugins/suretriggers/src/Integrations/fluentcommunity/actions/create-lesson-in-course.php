<?php
/**
 * CreateLessonInCourse.
 * php version 5.6
 *
 * @category CreateLessonInCourse
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
use FluentCommunity\Modules\Course\Model\CourseLesson;
use FluentCommunity\Modules\Course\Model\CourseTopic;
use FluentCommunity\Modules\Course\Services\CourseHelper;
use FluentCommunity\App\Services\RemoteUrlParser;

/**
 * CreateLessonInCourse
 *
 * @category CreateLessonInCourse
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateLessonInCourse extends AutomateAction {

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
	public $action = 'fc_create_lesson_in_course';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Lesson in Course', 'suretriggers' ),
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
		$section_id      = isset( $selected_options['section_id'] ) ? (int) sanitize_text_field( $selected_options['section_id'] ) : 0;
		$lesson_title    = isset( $selected_options['lesson_title'] ) ? sanitize_text_field( $selected_options['lesson_title'] ) : '';
		$lesson_content  = isset( $selected_options['lesson_content'] ) ? wp_kses_post( $selected_options['lesson_content'] ) : '';
		$lesson_status   = isset( $selected_options['lesson_status'] ) ? sanitize_text_field( $selected_options['lesson_status'] ) : 'draft';
		$video_url       = isset( $selected_options['video_url'] ) ? esc_url_raw( $selected_options['video_url'] ) : '';
		$content_type    = isset( $selected_options['content_type'] ) ? sanitize_text_field( $selected_options['content_type'] ) : ( ! empty( $video_url ) ? 'video' : 'text' );
		$enable_comments = isset( $selected_options['enable_comments'] ) ? sanitize_text_field( $selected_options['enable_comments'] ) : 'yes';
		$enable_media    = isset( $selected_options['enable_media'] ) ? sanitize_text_field( $selected_options['enable_media'] ) : 'yes';
		$free_preview    = isset( $selected_options['free_preview'] ) ? sanitize_text_field( $selected_options['free_preview'] ) : 'no';

		if ( empty( $lesson_title ) ) {
			return [
				'status'  => 'error',
				'message' => 'Lesson title is required.',
			];
		}

		if ( empty( $course_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Course ID is required.',
			];
		}

		if ( empty( $section_id ) ) {
			return [
				'status'  => 'error',
				'message' => 'Section ID is required.',
			];
		}

		if ( ! class_exists( 'FluentCommunity\Modules\Course\Model\CourseLesson' ) ) {
			return [
				'status'  => 'error',
				'message' => 'CourseLesson class not found.',
			];
		}

		if ( ! class_exists( 'FluentCommunity\Modules\Course\Model\CourseTopic' ) ) {
			return [
				'status'  => 'error',
				'message' => 'CourseTopic class not found.',
			];
		}

		if ( ! class_exists( 'FluentCommunity\Modules\Course\Services\CourseHelper' ) ) {
			return [
				'status'  => 'error',
				'message' => 'CourseHelper class not found.',
			];
		}

		if ( ! class_exists( 'FluentCommunity\App\Services\RemoteUrlParser' ) ) {
			return [
				'status'  => 'error',
				'message' => 'RemoteUrlParser class not found.',
			];
		}

		$validation_result = $this->is_valid_section_in_course( $section_id, $course_id );
		if ( is_array( $validation_result ) ) {
			return $validation_result;
		}
		if ( ! $validation_result ) {
			return [
				'status'  => 'error',
				'message' => 'Invalid Section ID for the specified course.',
			];
		}

		try {
			// First create the lesson with basic data.
			$lesson_data = [
				'title'     => $lesson_title,
				'parent_id' => $section_id,
				'space_id'  => $course_id,
				'status'    => $lesson_status,
			];

			// Create the lesson.
			$lesson = CourseLesson::create( $lesson_data );

			// Now prepare the meta data for the lesson.
			$lesson_meta = [
				'enable_comments' => $enable_comments,
				'enable_media'    => $enable_media,
				'document_lists'  => [],
			];

			// Add video URL if provided.
			if ( ! empty( $video_url ) ) {
				// Parse the video URL to get oembed data.
				$oembed_data = RemoteUrlParser::parse( $video_url );
				
				if ( $oembed_data && ! is_wp_error( $oembed_data ) ) {
					// Use the parsed oembed data which includes HTML embed code.
					$lesson_meta['media']        = $oembed_data;
					$lesson_meta['enable_media'] = 'yes';
				} else {
					// If oembed parsing fails, try WordPress native oembed.
					$wp_oembed   = new \WP_oEmbed();
					$oembed_html = $wp_oembed->get_html( $video_url );
					
					if ( $oembed_html ) {
						$lesson_meta['media']        = [
							'type'         => 'oembed',
							'url'          => $video_url,
							'content_type' => 'video',
							'provider'     => '',
							'title'        => '',
							'html'         => $oembed_html,
						];
						$lesson_meta['enable_media'] = 'yes';
					} else {
						// Final fallback - store URL for manual processing.
						$lesson_meta['media'] = [
							'type'         => 'oembed',
							'url'          => $video_url,
							'content_type' => 'video',
							'provider'     => '',
							'title'        => '',
							'html'         => '',
						];
					}
				}
			}

			// Add free preview setting if specified.
			if ( 'yes' === $free_preview ) {
				$lesson_meta['free_preview_lesson'] = 'yes';
			}

			// Sanitize the lesson meta using CourseHelper.
			$sanitized_meta = CourseHelper::sanitizeLessonMeta( $lesson_meta, $lesson );

			// Update the lesson with sanitized content and meta.
			$update_data = [
				'message'      => CourseHelper::santizeLessonBody( $lesson_content ),
				'content_type' => $content_type,
				'meta'         => $sanitized_meta,
			];

			$lesson->fill( $update_data );
			$lesson->save();

			$lesson = CourseLesson::findOrFail( $lesson->id );

			return [
				'status'     => 'success',
				'message'    => 'Lesson created successfully in course',
				'lesson_id'  => $lesson->id,
				'lesson'     => $lesson,
				'course_id'  => $course_id,
				'section_id' => $section_id,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => 'An error occurred while creating the lesson: ' . $e->getMessage(),
			];
		}
	}

	/**
	 * Check if the section ID is valid for the specified course.
	 *
	 * @param int $section_id Section ID.
	 * @param int $course_id  Course ID.
	 * @return bool|array
	 */
	private function is_valid_section_in_course( $section_id, $course_id ) {
		if ( ! class_exists( 'FluentCommunity\Modules\Course\Model\CourseTopic' ) ) {
			return [
				'status'  => 'error',
				'message' => 'CourseTopic class not found.',
			];
		}
		
		$topic = CourseTopic::where( 'id', $section_id )
			->where( 'space_id', $course_id )
			->first();

		return (bool) $topic;
	}
}

CreateLessonInCourse::get_instance();
