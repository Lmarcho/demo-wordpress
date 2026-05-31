<?php
/**
 * CreatePost.
 * php version 5.6
 *
 * @category CreatePost
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Wordpress\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;
use WP_Post;

if ( ! class_exists( 'CreatePost' ) ) :

	/**
	 * CreatePost
	 *
	 * @category CreatePost
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class CreatePost {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'WordPress';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'post_created';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'User creates a post', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'transition_post_status',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 3,
			];

			return $triggers;
		}

		/**
		 * Trigger listener.
		 *
		 * @param string  $new_status new status.
		 * @param string  $old_status old status.
		 * @param WP_Post $post post.
		 * @return void
		 */
		public function trigger_listener( $new_status, $old_status, $post ) {
			if ( ! $post instanceof WP_Post ) {
				return;
			}
			
			if ( 'publish' === $new_status && ( 'draft' === $old_status || 'auto-draft' === $old_status || 'new' === $old_status ) ) {
				
				if ( wp_is_post_revision( $post->ID ) || wp_is_post_autosave( $post->ID ) ) {
					return;
				}

				$user_id              = ap_get_current_user_id();
				$context              = WordPress::get_post_context( $post->ID );
				$context['permalink'] = get_permalink( $post->ID );
				$featured_image       = wp_get_attachment_image_src( (int) get_post_thumbnail_id( $post->ID ), 'full' );
				
				if ( ! empty( $featured_image ) && is_array( $featured_image ) ) {
					$context['featured_image'] = $featured_image[0];
				} else {
					$context['featured_image'] = $featured_image;
				}

				$taxonomies = get_object_taxonomies( $post, 'objects' );
				if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
					foreach ( $taxonomies as $taxonomy => $taxonomy_object ) {
						$terms = get_the_terms( $post->ID, $taxonomy );
						if ( ! empty( $terms ) && is_array( $terms ) ) {
							foreach ( $terms as $term ) {
								$context[ $taxonomy ] = $term->name;
							}
						}
					}
				}

				$context                 = array_merge( $context, WordPress::get_user_context( $user_id ) );
				$context['post']         = $post->ID;
				$context['post_type']    = $post->post_type;
				$custom_metas            = get_post_meta( $post->ID );
				$context['custom_metas'] = $custom_metas;

				AutomationController::sure_trigger_handle_trigger(
					[
						'trigger' => $this->trigger,
						'context' => $context,
					]
				);
			}
		}
	}

	CreatePost::get_instance();

endif;
