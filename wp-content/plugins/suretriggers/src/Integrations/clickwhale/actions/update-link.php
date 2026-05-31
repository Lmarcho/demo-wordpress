<?php
/**
 * UpdateLink.
 * php version 5.6
 *
 * @category UpdateLink
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
 * UpdateLink
 *
 * @category UpdateLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateLink extends AutomateAction {

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
	public $action = 'cw_update_link';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Link', 'suretriggers' ),
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
		$link_id     = isset( $selected_options['link_id'] ) ? absint( $selected_options['link_id'] ) : 0;
		$title       = isset( $selected_options['title'] ) ? sanitize_text_field( $selected_options['title'] ) : '';
		$url         = isset( $selected_options['url'] ) ? esc_url_raw( $selected_options['url'] ) : '';
		$slug        = isset( $selected_options['slug'] ) ? sanitize_text_field( $selected_options['slug'] ) : '';
		$description = isset( $selected_options['description'] ) ? sanitize_textarea_field( $selected_options['description'] ) : '';
		$redirection = isset( $selected_options['redirection'] ) ? absint( $selected_options['redirection'] ) : 0;
		$link_target = isset( $selected_options['link_target'] ) ? sanitize_text_field( $selected_options['link_target'] ) : '';
		$nofollow    = isset( $selected_options['nofollow'] ) ? $selected_options['nofollow'] : 0;
		$sponsored   = isset( $selected_options['sponsored'] ) ? $selected_options['sponsored'] : 0;
		$categories  = [];

		if ( isset( $selected_options['categories'] ) && is_array( $selected_options['categories'] ) ) {
			foreach ( $selected_options['categories'] as $cat ) {
				if ( isset( $cat['value'] ) ) {
					$categories[] = absint( $cat['value'] );
				}
			}
		}


		if ( empty( $link_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Link ID is required to update a link.', 'suretriggers' ), 
				
			];
		}

		$update_data = [];
		if ( ! empty( $title ) ) {
			$update_data['title'] = $title;
		}
		if ( ! empty( $url ) ) {
			$update_data['url'] = $url;
		}
		if ( ! empty( $slug ) ) {
			if ( class_exists( 'clickwhale\\includes\\helpers\\Links_Helper' ) ) {
				// Check if method exists using is_callable.
				if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Links_Helper', 'sanitize_slug' ] ) ) {
					$slug = \clickwhale\includes\helpers\Links_Helper::sanitize_slug( $slug );
				}
			}
			$update_data['slug'] = $slug;
		}
		if ( ! empty( $description ) ) {
			$update_data['description'] = $description;
		}
		if ( ! empty( $categories ) ) {
			$update_data['categories'] = implode( ',', $categories );
		}

		if ( $redirection > 0 ) {
			$update_data['redirection'] = $redirection;
		}
		if ( ! empty( $link_target ) ) {
			$update_data['link_target'] = $link_target;
		}
		if ( isset( $selected_options['nofollow'] ) ) {
			$update_data['nofollow'] = $nofollow;
		}
		if ( isset( $selected_options['sponsored'] ) ) {
			$update_data['sponsored'] = $sponsored;
		}

		if ( empty( $update_data ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'At least one field is required to update the link.', 'suretriggers' ), 
				
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
		
		$update_data['updated_at'] = current_time( 'mysql' );
		
		$result = $wpdb->update(
			$table_name,
			$update_data,
			[ 'id' => $link_id ],
			null,
			[ '%d' ]
		);

		if ( false === $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update link in ClickWhale.', 'suretriggers' ), 
				
			];
		}

		$link = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . esc_sql( (string) $table_name ) . '` WHERE id = %d', $link_id ), ARRAY_A );
		
		if ( ! $link ) {
			return [
				'status'  => 'error',
				'message' => __( 'Link not found after update.', 'suretriggers' ), 
				
			];
		}
		
		$link['short_url'] = home_url( '/' . $link['slug'] );

		return $link;
	}
}

UpdateLink::get_instance();
