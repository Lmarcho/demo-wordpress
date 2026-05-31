<?php
/**
 * CreateLink.
 * php version 5.6
 *
 * @category CreateLink
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
 * CreateLink
 *
 * @category CreateLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateLink extends AutomateAction {

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
	public $action = 'cw_create_link';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Link', 'suretriggers' ),
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
		$title       = isset( $selected_options['title'] ) ? sanitize_text_field( $selected_options['title'] ) : '';
		$url         = isset( $selected_options['url'] ) ? esc_url_raw( $selected_options['url'] ) : '';
		$slug        = isset( $selected_options['slug'] ) ? sanitize_text_field( $selected_options['slug'] ) : '';
		$description = isset( $selected_options['description'] ) ? sanitize_textarea_field( $selected_options['description'] ) : '';
		$redirection = isset( $selected_options['redirection'] ) ? absint( $selected_options['redirection'] ) : 301;
		$link_target = isset( $selected_options['link_target'] ) ? sanitize_text_field( $selected_options['link_target'] ) : '';
		$nofollow    = isset( $selected_options['nofollow'] ) ? $selected_options['nofollow'] : 0;
		$sponsored   = isset( $selected_options['sponsored'] ) ? $selected_options['sponsored'] : 0;
		
		if ( empty( $title ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Title is required to create a link.', 'suretriggers' ), 
				
			];
		}

		if ( empty( $url ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'URL is required to create a link.', 'suretriggers' ), 
				
			];
		}

		if ( empty( $slug ) ) {
			$slug = sanitize_title( $title );
			if ( class_exists( 'clickwhale\\includes\\helpers\\Links_Helper' ) ) {
				// Check if method exists using reflection.
				if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Links_Helper', 'generate_random_slug' ] ) ) {
					$slug = \clickwhale\includes\helpers\Links_Helper::generate_random_slug();
				}
			}
		}

		$categories = [];

		if ( isset( $selected_options['categories'] ) && is_array( $selected_options['categories'] ) ) {
			foreach ( $selected_options['categories'] as $cat ) {
				if ( isset( $cat['value'] ) ) {
					$categories[] = absint( $cat['value'] );
				}
			}
		}

		if ( class_exists( 'clickwhale\\includes\\helpers\\Links_Helper' ) ) {
			// Check if method exists using is_callable.
			if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Links_Helper', 'sanitize_slug' ] ) ) {
				$slug = \clickwhale\includes\helpers\Links_Helper::sanitize_slug( $slug );
			}
		}

		$link_data = [
			'title'       => $title,
			'url'         => $url,
			'slug'        => $slug,
			'description' => $description,
			'categories'  => implode( ',', $categories ),
			'redirection' => $redirection,
			'link_target' => $link_target,
			'nofollow'    => $nofollow,
			'sponsored'   => $sponsored,
			'author'      => get_current_user_id(),
			'created_at'  => current_time( 'mysql' ),
			'updated_at'  => current_time( 'mysql' ),
		];

		global $wpdb;
		$table_name = $wpdb->prefix . 'clickwhale_links';
		if ( class_exists( 'clickwhale\\includes\\helpers\\Helper' ) ) {
			// Check if method exists using is_callable.
			if ( is_callable( [ '\\clickwhale\\includes\\helpers\\Helper', 'get_db_table_name' ] ) ) {
				$table_name = \clickwhale\includes\helpers\Helper::get_db_table_name( 'links' );
			}
		}
		$result = $wpdb->insert(
			$table_name,
			$link_data,
			[
				'%s', // title.
				'%s', // url.
				'%s', // slug.
				'%s', // description.
				'%s', // categories.
				'%d', // redirection.
				'%s', // link_target.
				'%d', // nofollow.
				'%d', // sponsored.
				'%d', // author.
				'%s', // created_at.
				'%s', // updated_at.
			]
		);

		if ( false === $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create link in ClickWhale.', 'suretriggers' ), 
				
			];
		}

		$link_id = $wpdb->insert_id;

		$link = array_merge(
			$link_data,
			[
				'id'        => $link_id,
				'short_url' => home_url( '/' . $slug ),
			] 
		);

		return $link;
	}
}

CreateLink::get_instance();
