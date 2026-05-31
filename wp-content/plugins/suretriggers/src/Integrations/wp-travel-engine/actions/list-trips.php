<?php
/**
 * ListTrips.
 * php version 5.6
 *
 * @category ListTrips
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
 * ListTrips.
 *
 * @category ListTrips
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListTrips extends AutomateAction {

	use SingletonLoader;

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
	public $action = 'wte_list_trips';

	/**
	 * Register the action.
	 *
	 * @param array $actions List of registered actions.
	 * @return array Modified list of actions.
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'       => __( 'List Trips', 'suretriggers' ),
			'description' => __( 'Get a list of trips with optional filtering', 'suretriggers' ),
			'action'      => $this->action,
			'function'    => [ $this, '_action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener that returns list of trips.
	 *
	 * @param int   $user_id          User ID.
	 * @param int   $automation_id    Automation ID.
	 * @param array $fields           Trigger fields.
	 * @param array $selected_options Selected options for trip filtering.
	 * @return array Result with status and data or error.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'wp_travel_engine_get_settings' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WP Travel Engine plugin is not active.', 'suretriggers' ), 
				
			];
		}
		$limit = ! empty( $selected_options['limit'] ) ? intval( $selected_options['limit'] ) : 20;
		
		$args = [
			'post_type'      => 'trip',
			'post_status'    => isset( $selected_options['status'] ) ? $selected_options['status'] : 'publish',
			'posts_per_page' => $limit,
		];


		$trips_query = new \WP_Query( $args );
		$trips       = [];

		if ( $trips_query->have_posts() ) {
			while ( $trips_query->have_posts() ) {
				$trips_query->the_post();
				$trip_id = get_the_ID();
				
				if ( ! $trip_id ) {
					continue;
				}

				$trip_settings_raw = get_post_meta( $trip_id, 'wp_travel_engine_setting', true );
				$trip_settings     = is_array( $trip_settings_raw ) ? $trip_settings_raw : [];

				$price         = isset( $trip_settings['trip_price'] ) ? floatval( $trip_settings['trip_price'] ) : 0;
				$sale_price    = isset( $trip_settings['sale_price'] ) ? floatval( $trip_settings['sale_price'] ) : 0;
				$duration      = isset( $trip_settings['trip_duration'] ) ? $trip_settings['trip_duration'] : '';
				$duration_unit = isset( $trip_settings['trip_duration_unit'] ) ? $trip_settings['trip_duration_unit'] : 'days';

				$featured_image = get_the_post_thumbnail_url( $trip_id, 'medium' );

				$categories = [];
				$terms      = get_the_terms( $trip_id, 'trip_types' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$categories[] = [
							'id'   => $term->term_id,
							'name' => $term->name,
							'slug' => $term->slug,
						];
					}
				}

				$trips[] = [
					'id'             => $trip_id,
					'title'          => get_the_title(),
					'url'            => get_permalink(),
					'featured_image' => $featured_image,
					'price'          => $price,
					'sale_price'     => $sale_price,
					'duration'       => trim( $duration . ' ' . $duration_unit ),
					'categories'     => $categories,
					'excerpt'        => get_the_excerpt(),
					'date_created'   => get_the_date( 'Y-m-d H:i:s' ),
				];
			}
			wp_reset_postdata();
		}

		return [
			'status' => 'success',
			'data'   => [
				'trips' => $trips,
				'count' => count( $trips ),
			],
		];
	}
}

ListTrips::get_instance();
