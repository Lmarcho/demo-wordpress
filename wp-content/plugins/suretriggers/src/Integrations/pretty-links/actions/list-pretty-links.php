<?php
/**
 * ListPrettyLinks.
 * php version 5.6
 *
 * @category ListPrettyLinks
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
 * ListPrettyLinks
 *
 * @category ListPrettyLinks
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListPrettyLinks extends AutomateAction {

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
	public $action = 'prettylinks_list_pretty_links';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Pretty Links', 'suretriggers' ),
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
		$limit = isset( $selected_options['limit'] ) ? absint( $selected_options['limit'] ) : 10;

		if ( ! defined( 'PRLI_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Pretty Links plugin is not active or not found.', 'suretriggers' ),
			];
		}

		// Validate limit.
		if ( $limit > 100 ) {
			$limit = 100; // Maximum 100 links per request.
		}
		if ( $limit <= 0 ) {
			$limit = 10;
		}

		global $wpdb;
		$prli_link_table = $wpdb->prefix . 'prli_links';

		// Get total count first.
		$total_count = $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}prli_links`" );

		// Get the links.
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}prli_links` ORDER BY id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		if ( ! $links ) {
			$links = [];
		}

		// Enhance each link with additional data.
		foreach ( $links as &$link ) {
			$link['pretty_url'] = home_url( '/' . $link['slug'] );

			// Get click statistics for each link.
			$prli_clicks_table = $wpdb->prefix . 'prli_clicks';
			$click_stats       = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT 
						COUNT(*) as total_clicks,
						COUNT(DISTINCT ip) as unique_clicks,
						MIN(created_at) as first_click,
						MAX(created_at) as last_click
					FROM `{$wpdb->prefix}prli_clicks` 
					WHERE link_id = %d",
					$link['id']
				),
				ARRAY_A
			);

			if ( $click_stats ) {
				$link['click_stats'] = $click_stats;
			} else {
				$link['click_stats'] = [
					'total_clicks'  => 0,
					'unique_clicks' => 0,
					'first_click'   => null,
					'last_click'    => null,
				];
			}
		}

		return [
			'status' => 'success',
			'data'   => [
				'links'       => $links,
				'total_count' => $total_count,
				'limit'       => $limit,
				'returned'    => count( $links ),
			],
		];
	}
}

ListPrettyLinks::get_instance();
