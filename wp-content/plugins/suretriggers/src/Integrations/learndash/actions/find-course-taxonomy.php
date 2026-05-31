<?php
/**
 * FindCourseTaxonomies.
 * php version 5.6
 *
 * @category FindCourseTaxonomies
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\LearnDash\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * FindCourseTaxonomies
 *
 * @category FindCourseTaxonomies
 * @package  SureTriggers
 */
class FindCourseTaxonomies extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'LearnDash';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'ld_find_course_taxonomies';

	use SingletonLoader;

	/**
	 * Register an action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Find Course Taxonomies', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user ID.
	 * @param int   $automation_id automation ID.
	 * @param array $fields fields.
	 * @param array $selected_options selected options.
	 *
	 * @return array|false
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		$course_id = isset( $selected_options['sfwd-courses'] ) ? intval( $selected_options['sfwd-courses'] ) : 0;

		if ( empty( $course_id ) || get_post_type( $course_id ) !== 'sfwd-courses' ) {
			return [
				'message' => __( 'Invalid Course ID.', 'suretriggers' ), 
				
			];
		}


		global $sfwd_lms;
		$courses_taxonomies = $sfwd_lms->get_post_args_section( 'sfwd-courses', 'taxonomies' );

		$course_taxonomies = [];

		if ( ! empty( $courses_taxonomies ) ) {
			foreach ( $courses_taxonomies as $taxonomy_slug => $taxonomy ) {
				if ( ! empty( $taxonomy['public'] ) ) {
					$terms = get_the_terms( $course_id, $taxonomy_slug );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						$course_taxonomies[ $taxonomy_slug ] = wp_list_pluck( $terms, 'name' );
					}
				}
			}
		}

		return [
			'course_id'  => $course_id,
			'taxonomies' => ! empty( $course_taxonomies ) ? $course_taxonomies : __( 'No taxonomies found.', 'suretriggers' ), 
			
		];
	}
}

FindCourseTaxonomies::get_instance();
