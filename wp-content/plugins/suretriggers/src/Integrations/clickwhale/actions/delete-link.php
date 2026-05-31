<?php
/**
 * DeleteLink.
 * php version 5.6
 *
 * @category DeleteLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ClickWhale\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * DeleteLink
 *
 * @category DeleteLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteLink extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'ClickWhale';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'cw_delete_link';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Link', 'suretriggers' ),
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
	 * @param array $selected_options selected_options.
	 *
	 * @return array|void
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		$link_id = isset( $selected_options['link_id'] ) ? absint( $selected_options['link_id'] ) : 0;

		if ( empty( $link_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Link ID is required to delete a link.', 'suretriggers' ), 
				
			];
		}

		// Delete link using ClickWhale database structure.
		global $wpdb;
		$table_name = $wpdb->prefix . 'clickwhale_links';
		if ( class_exists( 'clickwhale\\includes\\helpers\\Helper' ) ) {
			// Check if method exists using is_callable.
			if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Helper', 'get_db_table_name' ] ) ) {
				$table_name = \clickwhale\includes\helpers\Helper::get_db_table_name( 'links' );
			}
		}
		
		// Get link data before deletion.
		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( (string) $table_name ) . '` WHERE id = %d', $link_id ), ARRAY_A );
		
		if ( ! $link ) {
			return [
				'status'  => 'error',
				'message' => __( 'Link not found.', 'suretriggers' ), 
				
			];
		}

		$result = $wpdb->delete(
			$table_name,
			[ 'id' => $link_id ],
			[ '%d' ]
		);

		if ( false === $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to delete link from ClickWhale.', 'suretriggers' ), 
				
			];
		}

		$track_table = $wpdb->prefix . 'clickwhale_track';
		if ( class_exists( 'clickwhale\\includes\\helpers\\Helper' ) ) {
			// Check if method exists using is_callable.
			if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Helper', 'get_db_table_name' ] ) ) {
				$track_table = \clickwhale\includes\helpers\Helper::get_db_table_name( 'track' );
			}
		}
		$wpdb->delete(
			$track_table,
			[ 'link_id' => $link_id ],
			[ '%d' ]
		);

		$meta_table = $wpdb->prefix . 'clickwhale_meta';
		if ( class_exists( 'clickwhale\\includes\\helpers\\Helper' ) ) {
			// Check if method exists using is_callable.
			if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Helper', 'get_db_table_name' ] ) ) {
				$meta_table = \clickwhale\includes\helpers\Helper::get_db_table_name( 'meta' );
			}
		}
		$wpdb->delete(
			$meta_table,
			[ 'link_id' => $link_id ],
			[ '%d' ]
		);

		$result = [
			'deleted'    => true,
			'link_id'    => $link_id,
			'title'      => $link['title'],
			'url'        => $link['url'],
			'slug'       => $link['slug'],
			'deleted_at' => current_time( 'mysql' ),
		];

		return $result;
	}
}

DeleteLink::get_instance();
