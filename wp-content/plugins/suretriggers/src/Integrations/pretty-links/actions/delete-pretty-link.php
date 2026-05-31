<?php
/**
 * DeletePrettyLink.
 * php version 5.6
 *
 * @category DeletePrettyLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.9
 */

namespace SureTriggers\Integrations\PrettyLinks\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * DeletePrettyLink
 *
 * @category DeletePrettyLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeletePrettyLink extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'PrettyLinks';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'prettylinks_delete_pretty_link';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Pretty Link', 'suretriggers' ),
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
				'message' => __( 'Link ID is required to delete a pretty link.', 'suretriggers' ),
			];
		}

		if ( ! defined( 'PRLI_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Pretty Links plugin is not active or not found.', 'suretriggers' ),
			];
		}

		global $wpdb;
		$prli_link_table   = $wpdb->prefix . 'prli_links';
		$prli_clicks_table = $wpdb->prefix . 'prli_clicks';

		// Get link data before deletion for response.
		$existing_link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}prli_links` WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( ! $existing_link ) {
			return [
				'status'  => 'error',
				'message' => __( 'Pretty Link not found with the provided ID.', 'suretriggers' ),
			];
		}

		// Delete associated clicks first.
		$clicks_deleted = $wpdb->delete( $prli_clicks_table, [ 'link_id' => $link_id ] );

		// Delete the link.
		$result = $wpdb->delete( $prli_link_table, [ 'id' => $link_id ] );

		if ( false === $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to delete Pretty Link from database.', 'suretriggers' ),
			];
		}

		if ( 0 === $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'No Pretty Link was deleted. Link may not exist.', 'suretriggers' ),
			];
		}

		return [
			'status'  => 'success',
			'message' => __( 'Pretty Link deleted successfully.', 'suretriggers' ),
			'data'    => [
				'deleted_link_id'   => $link_id,
				'deleted_link_data' => $existing_link,
				'clicks_deleted'    => $clicks_deleted,
			],
		];
	}
}

DeletePrettyLink::get_instance();
