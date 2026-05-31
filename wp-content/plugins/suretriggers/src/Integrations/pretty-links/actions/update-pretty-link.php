<?php
/**
 * UpdatePrettyLink.
 * php version 5.6
 *
 * @category UpdatePrettyLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\PrettyLinks\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * UpdatePrettyLink
 *
 * @category UpdatePrettyLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.9
 */
class UpdatePrettyLink extends AutomateAction {

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
	public $action = 'prettylinks_update_pretty_link';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Pretty Link', 'suretriggers' ),
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
		$link_id       = isset( $selected_options['link_id'] ) ? absint( $selected_options['link_id'] ) : 0;
		$name          = isset( $selected_options['name'] ) ? sanitize_text_field( $selected_options['name'] ) : '';
		$url           = isset( $selected_options['url'] ) ? esc_url_raw( $selected_options['url'] ) : '';
		$slug          = isset( $selected_options['slug'] ) ? sanitize_text_field( $selected_options['slug'] ) : '';
		$description   = isset( $selected_options['description'] ) ? sanitize_textarea_field( $selected_options['description'] ) : '';
		$redirect_type = isset( $selected_options['redirect_type'] ) ? absint( $selected_options['redirect_type'] ) : null;
		$tracking      = isset( $selected_options['tracking'] ) ? $selected_options['tracking'] : null;
		$nofollow      = isset( $selected_options['nofollow'] ) ? $selected_options['nofollow'] : null;
		$sponsored     = isset( $selected_options['sponsored'] ) ? $selected_options['sponsored'] : null;

		if ( empty( $link_id ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Link ID is required to update a pretty link.', 'suretriggers' ),
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

		// Check if link exists.
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

		// Prepare update data.
		$update_data = [];
		
		if ( ! empty( $name ) ) {
			$update_data['name'] = $name;
		}
		
		if ( ! empty( $url ) ) {
			$update_data['url'] = $url;
		}
		
		if ( ! empty( $slug ) ) {
			// Check if new slug already exists for another link.
			if ( $this->slug_exists_for_other_link( $slug, $link_id ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'The provided slug already exists for another link. Please choose a different slug.', 'suretriggers' ),
				];
			}
			$update_data['slug'] = $slug;
		}
		
		if ( ! empty( $description ) ) {
			$update_data['description'] = $description;
		}
		
		if ( null !== $redirect_type ) {
			$update_data['redirect_type'] = $redirect_type;
		}
		
		if ( null !== $tracking ) {
			$update_data['track_me'] = ( 'true' === $tracking || true === $tracking ) ? 1 : 0;
		}
		
		if ( null !== $nofollow ) {
			$update_data['nofollow'] = ( 'true' === $nofollow || true === $nofollow ) ? 1 : 0;
		}
		
		if ( null !== $sponsored ) {
			$update_data['sponsored'] = ( 'true' === $sponsored || true === $sponsored ) ? 1 : 0;
		}

		if ( empty( $update_data ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'No fields provided to update.', 'suretriggers' ),
			];
		}

		$result = $wpdb->update(
			$prli_link_table,
			$update_data,
			[ 'id' => $link_id ]
		);

		if ( false === $result ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update Pretty Link in database.', 'suretriggers' ),
			];
		}

		// Get the updated link data.
		$updated_link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}prli_links` WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( ! $updated_link ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to retrieve updated Pretty Link.', 'suretriggers' ),
			];
		}

		// Add pretty URL.
		$updated_link['pretty_url'] = home_url( '/' . $updated_link['slug'] );

		return [
			'status' => 'success',
			'data'   => $updated_link,
		];
	}

	/**
	 * Check if slug exists for another link
	 *
	 * @param string $slug The slug to check.
	 * @param int    $exclude_id The link ID to exclude from check.
	 * @return bool
	 */
	private function slug_exists_for_other_link( $slug, $exclude_id ) {
		global $wpdb;
		$prli_link_table = $wpdb->prefix . 'prli_links';
		
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$wpdb->prefix}prli_links` WHERE slug = %s AND id != %d",
				$slug,
				$exclude_id
			)
		);

		return $existing > 0;
	}
}

UpdatePrettyLink::get_instance();
