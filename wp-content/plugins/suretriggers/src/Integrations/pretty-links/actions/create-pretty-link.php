<?php
/**
 * CreatePrettyLink.
 * php version 5.6
 *
 * @category CreatePrettyLink
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
 * CreatePrettyLink
 *
 * @category CreatePrettyLink
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreatePrettyLink extends AutomateAction {

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
	public $action = 'prettylinks_create_pretty_link';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Pretty Link', 'suretriggers' ),
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
		$name          = isset( $selected_options['name'] ) ? sanitize_text_field( $selected_options['name'] ) : '';
		$url           = isset( $selected_options['url'] ) ? esc_url_raw( $selected_options['url'] ) : '';
		$slug          = isset( $selected_options['slug'] ) ? sanitize_text_field( $selected_options['slug'] ) : '';
		$description   = isset( $selected_options['description'] ) ? sanitize_textarea_field( $selected_options['description'] ) : '';
		$redirect_type = isset( $selected_options['redirect_type'] ) ? absint( $selected_options['redirect_type'] ) : 307;
		$tracking      = $selected_options['tracking'];
		$nofollow      = $selected_options['nofollow'];
		$sponsored     = $selected_options['sponsored'];
		if ( empty( $name ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Name is required to create a pretty link.', 'suretriggers' ),
			];
		}

		if ( empty( $url ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Target URL is required to create a pretty link.', 'suretriggers' ),
			];
		}

		if ( ! defined( 'PRLI_VERSION' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Pretty Links plugin is not active or not found.', 'suretriggers' ),
			];
		}

		// Generate slug if not provided.
		if ( empty( $slug ) ) {
			$slug = sanitize_title( $name );
			// Ensure unique slug.
			$original_slug = $slug;
			$counter       = 1;
			while ( $this->slug_exists( $slug ) ) {
				$slug = $original_slug . '-' . $counter;
				$counter++;
			}
		} else {
			// Check if provided slug already exists.
			if ( $this->slug_exists( $slug ) ) {
				return [
					'status'  => 'error',
					'message' => __( 'The provided slug already exists. Please choose a different slug.', 'suretriggers' ),
				];
			}
		}

		if ( ! class_exists( '\PrliLink' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Pretty Links plugin not properly initialized.', 'suretriggers' ),
			];
		}

		$prli      = new \PrliLink();
		$link_data = [
			'name'          => $name,
			'url'           => $url,
			'slug'          => $slug,
			'description'   => $description,
			'redirect_type' => (string) $redirect_type,
		];

		if ( 'true' === $tracking || true === $tracking ) {
			$link_data['track_me'] = 1;
		}
		if ( 'true' === $nofollow || true === $nofollow ) {
			$link_data['nofollow'] = 1;
		}
		if ( 'true' === $sponsored || true === $sponsored ) {
			$link_data['sponsored'] = 1;
		}

		$link_id = $prli->create( $link_data );
		if ( false === $link_id || ! $link_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create Pretty Link.', 'suretriggers' ),
			];
		}

		// Get the created link data.
		global $wpdb;
		$created_link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$wpdb->prefix}prli_links` WHERE id = %d",
				$link_id
			),
			ARRAY_A
		);

		if ( ! $created_link ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to retrieve created Pretty Link.', 'suretriggers' ),
			];
		}

		// Add pretty URL.
		$created_link['pretty_url'] = home_url( '/' . $slug );
		return [
			'status' => 'success',
			'data'   => $created_link,
		];
	}

	/**
	 * Check if slug exists
	 *
	 * @param string $slug The slug to check.
	 * @return bool
	 */
	private function slug_exists( $slug ) {
		global $wpdb;
		$prli_link_table = $wpdb->prefix . 'prli_links';
		
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM `{$wpdb->prefix}prli_links` WHERE slug = %s",
				$slug
			)
		);

		return $existing > 0;
	}
}

CreatePrettyLink::get_instance();
