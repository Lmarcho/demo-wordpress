<?php
/**
 * GetLinkDetails.
 * php version 5.6
 *
 * @category GetLinkDetails
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
 * GetLinkDetails
 *
 * @category GetLinkDetails
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetLinkDetails extends AutomateAction {

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
	public $action = 'cw_get_link_details';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Link Details', 'suretriggers' ),
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
		$slug    = isset( $selected_options['slug'] ) ? sanitize_text_field( $selected_options['slug'] ) : '';

		if ( empty( $link_id ) && empty( $slug ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Either Link ID or Slug is required to get link details.', 'suretriggers' ), 
				
			];
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'clickwhale_links';
		if ( class_exists( 'clickwhale\\includes\\helpers\\Helper' ) ) {
			// Check if method exists using is_callable.
			if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Helper', 'get_db_table_name' ] ) ) {
				$table_name = \clickwhale\includes\helpers\Helper::get_db_table_name( 'links' );
			}
		}

		$link = null;

		if ( $link_id ) {
			$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( (string) $table_name ) . '` WHERE id = %d', $link_id ), ARRAY_A );
			if ( ! $link ) {
				return [
					'status'  => 'error',
					'message' => __( 'Invalid Link ID.', 'suretriggers' ), 
					
				];
			}
		} elseif ( $slug ) {
			$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( (string) $table_name ) . '` WHERE slug = %s', $slug ), ARRAY_A );
			if ( ! $link ) {
				return [
					'status'  => 'error',
					'message' => __( 'Invalid Slug.', 'suretriggers' ), 
					
				];
			}
		}

		$link['short_url'] = home_url( '/' . $link['slug'] );

		return $link;
	}

}

GetLinkDetails::get_instance();
