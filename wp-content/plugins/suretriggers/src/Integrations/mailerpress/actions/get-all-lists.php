<?php
/**
 * GetAllLists.
 * php version 5.6
 *
 * @category GetAllLists
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
 * GetAllLists
 *
 * @category GetAllLists
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetAllLists extends AutomateAction {

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
	public $action = 'get_all_lists';

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
			'label'    => __( 'Get all lists', 'suretriggers' ),
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

		global $wpdb;

		$limit    = isset( $selected_options['limit'] ) ? absint( $selected_options['limit'] ) : 0;
		$order_by = isset( $selected_options['order_by'] ) ? sanitize_text_field( $selected_options['order_by'] ) : 'name';
		$order    = isset( $selected_options['order'] ) ? sanitize_text_field( $selected_options['order'] ) : 'ASC';
		$search   = isset( $selected_options['search'] ) ? sanitize_text_field( $selected_options['search'] ) : '';

		$allowed_order_by = [ 'list_id', 'name', 'description', 'created_at', 'updated_at' ];
		if ( ! in_array( $order_by, $allowed_order_by, true ) ) {
			$order_by = 'name';
		}

		$order = strtoupper( $order );
		if ( ! in_array( $order, [ 'ASC', 'DESC' ], true ) ) {
			$order = 'ASC';
		}

		try {

			$lists_table         = $wpdb->prefix . 'mailerpress_lists';
			$contact_lists_table = $wpdb->prefix . 'mailerpress_contact_lists';

			$where_sql  = '';
			$where_args = [];

			if ( ! empty( $search ) ) {
				$where_sql  = ' WHERE (name LIKE %s OR description LIKE %s)';
				$search_val = '%' . $wpdb->esc_like( $search ) . '%';
				$where_args = [ $search_val, $search_val ];
			}

			// Main list query.
			if ( ! empty( $search ) ) {
				$search_val = '%' . $wpdb->esc_like( $search ) . '%';
				if ( $limit > 0 ) {
					$lists = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT list_id, name, description, created_at, updated_at FROM `' . esc_sql( $lists_table ) . '` WHERE (name LIKE %s OR description LIKE %s) ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ) . ' LIMIT %d',
							$search_val,
							$search_val,
							$limit
						)
					);
				} else {
					$lists = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT list_id, name, description, created_at, updated_at FROM `' . esc_sql( $lists_table ) . '` WHERE (name LIKE %s OR description LIKE %s) ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ),
							$search_val,
							$search_val
						)
					);
				}
			} else {
				if ( $limit > 0 ) {
					$lists = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT list_id, name, description, created_at, updated_at FROM `' . esc_sql( $lists_table ) . '` ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order ) . ' LIMIT %d',
							$limit
						)
					);
				} else {
					$lists = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT list_id, name, description, created_at, updated_at FROM `' . esc_sql( $lists_table ) . '` ORDER BY ' . esc_sql( $order_by ) . ' ' . esc_sql( $order )
						)
					);
				}
			}

			if ( $wpdb->last_error ) {
				throw new Exception( 'Database error: ' . $wpdb->last_error );
			}

			$formatted_lists = [];

			if ( $lists ) {
				foreach ( $lists as $list ) {

					// Safe contact count query.
					$contact_count = (int) $wpdb->get_var(
						$wpdb->prepare(
							'SELECT COUNT(*) FROM `' . esc_sql( $contact_lists_table ) . '` WHERE list_id = %d',
							$list->list_id
						)
					);

					$formatted_lists[] = [
						'list_id'       => (int) $list->list_id,
						'name'          => $list->name,
						'description'   => $list->description,
						'contact_count' => $contact_count,
						'created_at'    => $list->created_at,
						'updated_at'    => $list->updated_at,
					];
				}
			}

			// Safe total count query.
			if ( ! empty( $search ) ) {
				$search_val  = '%' . $wpdb->esc_like( $search ) . '%';
				$total_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM `' . esc_sql( $lists_table ) . '` WHERE (name LIKE %s OR description LIKE %s)',
						$search_val,
						$search_val
					)
				);
			} else {
				$total_count = (int) $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(*) FROM `' . esc_sql( $lists_table ) . '`'
					)
				);
			}

			return [
				'lists'       => $formatted_lists,
				'total_count' => $total_count,
				'returned'    => count( $formatted_lists ),
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

GetAllLists::get_instance();
