<?php
/**
 * GetAllTags.
 * php version 5.6
 *
 * @category GetAllTags
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
 * GetAllTags
 *
 * @category GetAllTags
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetAllTags extends AutomateAction {

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
	public $action = 'get_all_tags';

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
			'label'    => __( 'Get all tags', 'suretriggers' ),
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
		$allowed_order_by = [ 'tag_id', 'name' ];
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
			
			$tags_table = $wpdb->prefix . 'mailerpress_tags';
			
			// Check if table exists.
			$table_exists = $wpdb->get_var( 
				$wpdb->prepare( 
					'SHOW TABLES LIKE %s', 
					$tags_table 
				)
			);
			if ( ! $table_exists ) {
				throw new Exception( 'MailerPress tags table not found' );
			}

			// Main tag query.
			if ( ! empty( $search ) ) {
				$search_val = '%' . $wpdb->esc_like( $search ) . '%';
				if ( $limit > 0 ) {
					$tags = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT tag_id, name FROM `' . esc_sql( $tags_table ) . '` WHERE name LIKE %s ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ) . ' LIMIT %d',
							$search_val,
							$limit
						),
						ARRAY_A
					);
				} else {
					$tags = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT tag_id, name FROM `' . esc_sql( $tags_table ) . '` WHERE name LIKE %s ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ),
							$search_val
						),
						ARRAY_A
					);
				}
			} else {
				if ( $limit > 0 ) {
					$tags = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT tag_id, name FROM `' . esc_sql( $tags_table ) . '` ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ) . ' LIMIT %d',
							$limit
						),
						ARRAY_A
					);
				} else {
					$tags = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT tag_id, name FROM `' . esc_sql( $tags_table ) . '` ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order )
						),
						ARRAY_A
					);
				}
			}

			if ( $wpdb->last_error ) {
				throw new Exception( 'Database error: ' . $wpdb->last_error );
			}

			// Format the results.
			$formatted_tags = [];
			if ( $tags ) {
				foreach ( $tags as $tag ) {
					// Get contact count for each tag.
					$contact_count = $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'mailerpress_contact_tags WHERE tag_id = %d',
							$tag['tag_id']
						)
					);

					$formatted_tags[] = [
						'tag_id'        => (int) $tag['tag_id'],
						'name'          => $tag['name'],
						'contact_count' => (int) $contact_count,
					];
				}
			}

			// Get total count for pagination info.
			if ( ! empty( $search ) ) {
				$search_val  = '%' . $wpdb->esc_like( $search ) . '%';
				$total_count = $wpdb->get_var( 
					$wpdb->prepare( 
						'SELECT COUNT(*) FROM `' . esc_sql( $tags_table ) . '` WHERE name LIKE %s',
						$search_val
					)
				);
			} else {
				$total_count = $wpdb->get_var( 
					$wpdb->prepare( 
						'SELECT COUNT(*) FROM `' . esc_sql( $tags_table ) . '`'
					)
				);
			}

			return [
				'tags'        => $formatted_tags,
				'total_count' => (int) $total_count,
				'returned'    => count( $formatted_tags ),
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

GetAllTags::get_instance();
