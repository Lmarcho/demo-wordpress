<?php
/**
 * FetchPrettyLink.
 * php version 5.6
 *
 * @category FetchPrettyLink
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
 * FetchPrettyLink
 *
 * @category FetchPrettyLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class FetchPrettyLink extends AutomateAction {

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
	public $action = 'prettylinks_fetch_pretty_link';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Fetch Pretty Link', 'suretriggers' ),
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
		$link_id = isset( $selected_options['link_id'] ) ? sanitize_text_field( $selected_options['link_id'] ) : '';

		if ( empty( $link_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Link ID or Slug is required to fetch a pretty link.', 'suretriggers' ),
			];
		}

		if ( ! defined( 'PRLI_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Pretty Links plugin is not active or not found.', 'suretriggers' ),
			];
		}

		global $wpdb;
		$prli_link_table = $wpdb->prefix . 'prli_links';

		// Determine if link_id is numeric (ID) or string (slug).
		if ( is_numeric( $link_id ) ) {
			$link = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}prli_links` WHERE id = %d",
					absint( $link_id )
				),
				ARRAY_A
			);
		} else {
			$link = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM `{$wpdb->prefix}prli_links` WHERE slug = %s",
					$link_id
				),
				ARRAY_A
			);
		}

		if ( ! $link ) {
			return [
				'status'  => 'error',
				'message' => __( 'Pretty Link not found with the provided criteria.', 'suretriggers' ),
			];
		}

		$link['pretty_url'] = home_url( '/' . $link['slug'] );

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

		return [
			'status' => 'success',
			'data'   => $link,
		];
	}
}

FetchPrettyLink::get_instance();
