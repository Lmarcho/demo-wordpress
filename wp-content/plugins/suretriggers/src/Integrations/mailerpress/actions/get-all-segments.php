<?php
/**
 * GetAllSegments.
 * php version 5.6
 *
 * @category GetAllSegments
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\MailerPress\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * GetAllSegments
 *
 * @category GetAllSegments
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetAllSegments extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'MailerPress';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'get_all_segments';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 *
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get all segments', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id          user_id.
	 * @param int   $automation_id    automation_id.
	 * @param array $fields           fields.
	 * @param array $selected_options selectedOptions.
	 * @return array|void
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		// Get optional parameters.
		$limit    = isset( $selected_options['limit'] ) ? absint( $selected_options['limit'] ) : 0;
		$order_by = isset( $selected_options['order_by'] ) ? sanitize_text_field( $selected_options['order_by'] ) : 'name';
		$order    = isset( $selected_options['order'] ) ? sanitize_text_field( $selected_options['order'] ) : 'ASC';
		$search   = isset( $selected_options['search'] ) ? sanitize_text_field( $selected_options['search'] ) : '';

		// Validate order_by field.
		$allowed_order_by = [ 'id', 'name', 'created_at', 'updated_at' ];
		if ( ! in_array( $order_by, $allowed_order_by, true ) ) {
			$order_by = 'name';
		}

		// Validate order direction.
		$order = strtoupper( $order );
		if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$order = 'ASC';
		}

		try {
			global $wpdb;
			
			// Check if segments table exists.
			$table_name   = $wpdb->prefix . 'mailerpress_segments';
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
			
			if ( ! $table_exists ) {
				return [
					'status'  => 'error',
					'message' => 'Segments feature is not available or table does not exist.',
				];
			}

			// Build query.
			$segments_table = $wpdb->prefix . 'mailerpress_segments';

			if ( ! empty( $search ) ) {
				$search_term = '%' . $wpdb->esc_like( $search ) . '%';
				if ( $limit > 0 ) {
					$segments = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT id, name, conditions, created_at, updated_at FROM `' . esc_sql( $segments_table ) . '` WHERE name LIKE %s ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ) . ' LIMIT %d',
							$search_term,
							$limit
						)
					);
				} else {
					$segments = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT id, name, conditions, created_at, updated_at FROM `' . esc_sql( $segments_table ) . '` WHERE name LIKE %s ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ),
							$search_term
						)
					);
				}
			} else {
				if ( $limit > 0 ) {
					$segments = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT id, name, conditions, created_at, updated_at FROM `' . esc_sql( $segments_table ) . '` ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ) . ' LIMIT %d',
							$limit
						)
					);
				} else {
					$segments = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT id, name, conditions, created_at, updated_at FROM `' . esc_sql( $segments_table ) . '` ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order )
						)
					);
				}
			}

			if ( $wpdb->last_error ) {
				throw new Exception( 'Database error: ' . $wpdb->last_error );
			}

			// Format the results.
			$formatted_segments = [];
			if ( $segments ) {
				foreach ( $segments as $segment ) {
					// Parse conditions JSON if it exists.
					$conditions = null;
					if ( ! empty( $segment->conditions ) ) {
						$decoded_conditions = json_decode( $segment->conditions, true );
						if ( json_last_error() === JSON_ERROR_NONE ) {
							$conditions = $decoded_conditions;
						} else {
							$conditions = $segment->conditions; // Return raw if JSON decode fails.
						}
					}

					$formatted_segments[] = [
						'segment_id' => (int) $segment->id,
						'name'       => $segment->name,
						'conditions' => $conditions,
						'created_at' => $segment->created_at,
						'updated_at' => $segment->updated_at,
					];
				}
			}

			// Get total count for pagination info.
			if ( ! empty( $search ) ) {
				$search_term = '%' . $wpdb->esc_like( $search ) . '%';
				$total_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM `' . esc_sql( $segments_table ) . '` WHERE name LIKE %s',
						$search_term
					)
				);
			} else {
				$total_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM `' . esc_sql( $segments_table ) . '`'
					)
				);
			}

			return [
				'segments'    => $formatted_segments,
				'total_count' => (int) $total_count,
				'returned'    => count( $formatted_segments ),
				'search'      => $search,
				'order_by'    => $order_by,
				'order'       => $order,
				'limit'       => $limit,
				'success'     => true,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

GetAllSegments::get_instance();
