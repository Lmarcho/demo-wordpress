<?php
/**
 * CreateTrip.
 * php version 5.6
 *
 * @category CreateTrip
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPTravelEngine\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * CreateTrip
 *
 * @category CreateTrip
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateTrip extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WPTravelEngine';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wpte_create_trip';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create a Trip', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id user_id.
	 * @param int   $automation_id automation_id.
	 * @param array $fields fields.
	 * @param array $selected_options selectedOptions.
	 * @return array
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! function_exists( 'wp_travel_engine_get_settings' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WP Travel Engine plugin is not active.', 'suretriggers' ), 
				
			];
		}

		$trip_name           = isset( $selected_options['trip_name'] ) ? sanitize_text_field( $selected_options['trip_name'] ) : '';
		$trip_description    = isset( $selected_options['trip_description'] ) ? sanitize_textarea_field( $selected_options['trip_description'] ) : '';
		$trip_duration       = isset( $selected_options['trip_duration'] ) ? intval( $selected_options['trip_duration'] ) : 1;
		$trip_duration_night = isset( $selected_options['trip_duration_night'] ) ? intval( $selected_options['trip_duration_night'] ) : 0;
		$trip_duration_unit  = isset( $selected_options['trip_duration_unit'] ) ? sanitize_text_field( $selected_options['trip_duration_unit'] ) : 'days';
		$enable_cutoff_time  = isset( $selected_options['enable_cutoff_time'] ) ? filter_var( $selected_options['enable_cutoff_time'], FILTER_VALIDATE_BOOLEAN ) : false;
		$cutoff_time_value   = isset( $selected_options['cutoff_time_value'] ) ? intval( $selected_options['cutoff_time_value'] ) : 0;
		$cutoff_time_unit    = isset( $selected_options['cutoff_time_unit'] ) ? sanitize_text_field( $selected_options['cutoff_time_unit'] ) : 'days';
		$min_max_age_enable  = isset( $selected_options['set_minimum_maximum_age'] ) ? filter_var( $selected_options['set_minimum_maximum_age'], FILTER_VALIDATE_BOOLEAN ) : false;
		$min_age             = isset( $selected_options['min_age'] ) ? intval( $selected_options['min_age'] ) : 0;
		$max_age             = isset( $selected_options['max_age'] ) ? intval( $selected_options['max_age'] ) : 0;
		$minmax_pax_enable   = isset( $selected_options['set_minimum_maximum_participants'] ) ? filter_var( $selected_options['set_minimum_maximum_participants'], FILTER_VALIDATE_BOOLEAN ) : false;
		$min_participants    = isset( $selected_options['min_participants'] ) ? intval( $selected_options['min_participants'] ) : 0;
		$max_participants    = isset( $selected_options['max_participants'] ) ? intval( $selected_options['max_participants'] ) : 0;
		$destination         = isset( $selected_options['destination'] ) ? sanitize_text_field( $selected_options['destination'] ) : '';
		$activities          = isset( $selected_options['activities'] ) ? array_filter( array_map( 'sanitize_text_field', explode( ',', $selected_options['activities'] ) ) ) : [];
		$trip_highlights     = isset( $selected_options['trip_highlights'] ) ? array_filter( array_map( 'sanitize_text_field', explode( "\n", $selected_options['trip_highlights'] ) ) ) : [];
		$trip_includes       = isset( $selected_options['trip_includes'] ) ? sanitize_textarea_field( $selected_options['trip_includes'] ) : '';
		$trip_excludes       = isset( $selected_options['trip_excludes'] ) ? sanitize_textarea_field( $selected_options['trip_excludes'] ) : '';
		$featured_image_url  = isset( $selected_options['featured_image_url'] ) ? esc_url_raw( $selected_options['featured_image_url'] ) : '';
		$status              = ( isset( $selected_options['status'] ) && in_array( $selected_options['status'], [ 'publish', 'draft' ] ) ) ? $selected_options['status'] : 'publish';

		if ( empty( $trip_name ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Trip name is required.', 'suretriggers' ), 
				
			];
		}

		$post_id = wp_insert_post(
			[
				'post_title'   => $trip_name,
				'post_content' => $trip_description,
				'post_status'  => $status,
				'post_type'    => defined( 'WP_TRAVEL_ENGINE_POST_TYPE' ) ? WP_TRAVEL_ENGINE_POST_TYPE : 'trip',
				'post_author'  => get_current_user_id(),
				'meta_input'   => [
					'trip_version'                  => '2.0.0',
					'wp_travel_engine_setting_trip_duration' => $trip_duration,
					'_s_duration'                   => $trip_duration * ( 'days' === $trip_duration_unit ? 24 : 1 ),
					'wp_travel_engine_trip_min_age' => $min_age,
					'wp_travel_engine_trip_max_age' => $max_age,
					'wp_travel_engine_setting'      => [
						'trip_duration'          => $trip_duration,
						'trip_duration_unit'     => $trip_duration_unit,
						'trip_duration_nights'   => $trip_duration_night,
						'trip_cutoff_enable'     => $enable_cutoff_time,
						'trip_cut_off_time'      => $cutoff_time_value,
						'trip_cut_off_unit'      => $cutoff_time_unit,
						'min_max_age_enable'     => $min_max_age_enable,
						'trip_minimum_pax'       => $min_participants,
						'trip_maximum_pax'       => $max_participants,
						'minmax_pax_enable'      => $minmax_pax_enable,
						'overview_section_title' => 'Overview',
						'tab_content'            => [ '1_wpeditor' => $trip_description ],
						'trip_highlights_title'  => 'Trip Highlights',
						'trip_highlights'        => array_map(
							function( $h ) {
								return [ 'highlight_text' => $h ]; },
							$trip_highlights 
						),
						'cost_tab_sec_title'     => 'Includes/Excludes',
						'cost'                   => [
							'includes_title' => 'Cost Includes',
							'cost_includes'  => $trip_includes,
							'excludes_title' => 'Cost Excludes',
							'cost_excludes'  => $trip_excludes,
						],
					],
				],
			] 
		);

		if ( empty( $post_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create trip.', 'suretriggers' ), 
				
			];
		}

		if ( ! empty( $destination ) ) {
			$term = term_exists( $destination, 'destination' );
			if ( ! $term ) {
				$term = wp_insert_term( $destination, 'destination' );
			}
			if ( ! is_wp_error( $term ) ) {
				$term_id = 0;
				if ( is_array( $term ) && isset( $term['term_id'] ) ) {
					$term_id = intval( $term['term_id'] );
				}
				if ( $term_id ) {
					wp_set_object_terms( $post_id, $term_id, 'destination' );
				}
			}
		}
		
		if ( ! empty( $activities ) ) {
			foreach ( $activities as $activity ) {
				$term = term_exists( $activity, 'activities' );
				if ( ! $term ) {
					$term = wp_insert_term( $activity, 'activities' );
				}
				if ( ! is_wp_error( $term ) ) {
					$term_id = 0;
					if ( is_array( $term ) && isset( $term['term_id'] ) ) {
						$term_id = intval( $term['term_id'] );
					}
					if ( $term_id ) {
						wp_set_object_terms( $post_id, $term_id, 'activities', true );
					}
				}
			}
		}
		
		if ( ! empty( $featured_image_url ) ) {
			$this->set_featured_image_from_url( $post_id, $featured_image_url );
		}

		return [
			'status'  => 'success',
			'message' => sprintf( __( 'Trip created with ID %d', 'suretriggers' ), $post_id ),
			'trip_id' => $post_id,
		];
	}

	/**
	 * Download image from URL and set as featured image.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $image_url Image URL.
	 * @return void
	 */
	private function set_featured_image_from_url( $post_id, $image_url ) {
		if ( empty( $image_url ) || ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
			return;
		}

		$abspath = realpath( ABSPATH );
		if ( false === $abspath ) {
			return;
		}

		require_once $abspath . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'file.php';
		require_once $abspath . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'media.php';
		require_once $abspath . DIRECTORY_SEPARATOR . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'image.php';

		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			return;
		}

		preg_match( '/[^\?]+\.(jpg|jpeg|png|gif)/i', $image_url, $matches );
		$file_array = [
			'name'     => ! empty( $matches ) ? basename( $matches[0] ) : 'featured-image.jpg',
			'tmp_name' => $tmp,
		];

		$attach_id = media_handle_sideload( $file_array, $post_id );

		if ( is_wp_error( $attach_id ) ) {
			$tmp_file    = $file_array['tmp_name'];
			$uploads_dir = wp_upload_dir();
		
			$tmp_file_realpath     = realpath( $tmp_file );
			$uploads_base_realpath = realpath( $uploads_dir['basedir'] );
		
			if (
				file_exists( $tmp_file ) &&
				false !== $tmp_file_realpath &&
				false !== $uploads_base_realpath &&
				0 === strpos( $tmp_file_realpath, $uploads_base_realpath )
			) {
				wp_delete_file( $tmp_file );
			}           
			return;
		}       
		

		set_post_thumbnail( $post_id, $attach_id );
	}

}

CreateTrip::get_instance();
