<?php
/**
 * TaxonomyUpdated.
 * php version 5.6
 *
 * @category TaxonomyUpdated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Voxel\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Integrations\Voxel\Voxel;

if ( ! class_exists( 'TaxonomyUpdated' ) ) :

	/**
	 * TaxonomyUpdated
	 *
	 * @category TaxonomyUpdated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class TaxonomyUpdated {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'Voxel';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'voxel_taxonomy_updated';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
			add_action( 'set_object_terms', [ $this, 'trigger_listener' ], 10, 6 );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Taxonomy Updated', 'suretriggers' ),
				'action'        => $this->trigger,
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 6,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int    $object_id Object ID.
		 * @param array  $terms     An array of object terms.
		 * @param array  $tt_ids    An array of term taxonomy IDs.
		 * @param string $taxonomy  Taxonomy slug.
		 * @param bool   $append    Whether to append new terms to the old terms.
		 * @param array  $old_tt_ids Old array of term taxonomy IDs.
		 * @return void
		 */
		public function trigger_listener( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {
			$post_type = get_post_type( $object_id );
			if ( false === $post_type || ( 'post' !== $post_type && ! post_type_exists( $post_type ) ) ) {
				return;
			}

			if ( ! class_exists( 'Voxel\Post' ) || ! class_exists( 'Voxel\Post_Type' ) ) {
				return;
			}

			$post = \Voxel\Post::force_get( $object_id );
			if ( ! $post ) {
				return;
			}

			$post_type_string = get_post_type( $object_id );
			$post_type_obj    = \Voxel\Post_Type::get( $post_type_string );
			if ( ! $post_type_obj ) {
				return;
			}

			if ( $tt_ids === $old_tt_ids ) {
				return;
			}

			$context              = WordPress::get_post_context( $object_id );
			$context['post']      = Voxel::get_post_fields( $object_id );
			$context['post_type'] = $post_type_string;

			$context['taxonomy']       = $taxonomy;
			$context['terms_added']    = array_diff( $tt_ids, $old_tt_ids );
			$context['terms_removed']  = array_diff( $old_tt_ids, $tt_ids );
			$context['current_terms']  = $tt_ids;
			$context['previous_terms'] = $old_tt_ids;

			$added_terms = [];
			foreach ( $context['terms_added'] as $term_id ) {
				$term = get_term( $term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$added_terms[] = [
						'term_id' => $term->term_id,
						'name'    => $term->name,
						'slug'    => $term->slug,
					];
				}
			}
			$context['added_terms_details'] = $added_terms;

			$removed_terms = [];
			foreach ( $context['terms_removed'] as $term_id ) {
				$term = get_term( $term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$removed_terms[] = [
						'term_id' => $term->term_id,
						'name'    => $term->name,
						'slug'    => $term->slug,
					];
				}
			}
			$context['removed_terms_details'] = $removed_terms;

			$current_terms = [];
			foreach ( $context['current_terms'] as $term_id ) {
				$term = get_term( $term_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$current_terms[] = [
						'term_id' => $term->term_id,
						'name'    => $term->name,
						'slug'    => $term->slug,
					];
				}
			}
			$context['current_terms_details'] = $current_terms;

			$post_fields          = $post_type_obj->get_fields();
			$voxel_taxonomy_field = null;
			
			foreach ( $post_fields as $field ) {
				$field_props = $field->get_props();
				if ( isset( $field_props['type'] ) && 'taxonomy' === $field_props['type'] ) {
					if ( isset( $field_props['taxonomy'] ) && $field_props['taxonomy'] === $taxonomy ) {
						$voxel_taxonomy_field = $field_props;
						break;
					}
				}
			}

			$context['is_voxel_taxonomy_field'] = ! is_null( $voxel_taxonomy_field );
			$context['voxel_field_data']        = $voxel_taxonomy_field;

			$update_type = 'updated';
			if ( empty( $context['terms_removed'] ) && ! empty( $context['terms_added'] ) ) {
				$update_type = 'added';
			} elseif ( ! empty( $context['terms_removed'] ) && empty( $context['terms_added'] ) ) {
				$update_type = 'removed';
			}
			$context['update_type'] = $update_type;

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	TaxonomyUpdated::get_instance();

endif;
