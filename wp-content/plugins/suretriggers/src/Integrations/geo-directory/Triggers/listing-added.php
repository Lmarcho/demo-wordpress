<?php
/**
 * ListingAdded
 *
 * @package  SureTriggers
 * @category Integration
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\GeoDirectory\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;

if ( ! class_exists( 'ListingAdded' ) ) :

	/**
	 * ListingAdded
	 *
	 * @category ListingAdded
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class ListingAdded {

		use SingletonLoader;

		/**
		 * Integration name.
		 *
		 * @var string
		 */
		public $integration = 'GeoDirectory';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'geodir_listing_added';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register trigger.
		 *
		 * @param array $triggers Registered triggers.
		 * @return array Modified triggers.
		 */
		public function register( $triggers ) {
			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'New Place Listing Added', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'geodir_post_published',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener.
		 *
		 * @param object $gd_post GeoDirectory post object.
		 * @param array  $data    Post metadata.
		 * @return void
		 */
		public function trigger_listener( $gd_post, $data ) {
			if ( empty( $gd_post ) || empty( $gd_post->ID ) ) {
				return;
			}

			$post_id = absint( $gd_post->ID );
			if ( ! $post_id ) {
				return;
			}

			$post = get_post( $post_id );
			if ( ! $post ) {
				return;
			}

			$post_type = $post->post_type;

			if ( ! function_exists( 'geodir_is_gd_post_type' ) || ! geodir_is_gd_post_type( $post_type ) ) {
				return;
			}

			$user_id = absint( $post->post_author );
			$user    = WordPress::get_user_context( $user_id );

			$description = $post->post_content;
			
			$categories   = [];
			$cat_taxonomy = $post_type . 'category';
			if ( taxonomy_exists( $cat_taxonomy ) ) {
				$terms = wp_get_post_terms( $post_id, $cat_taxonomy );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$categories[] = $term->name;
					}
				}
			}
			
			$tags         = [];
			$tag_taxonomy = $post_type . '_tags';
			if ( taxonomy_exists( $tag_taxonomy ) ) {
				$terms = wp_get_post_terms( $post_id, $tag_taxonomy );
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					foreach ( $terms as $term ) {
						$tags[] = $term->name;
					}
				}
			}
			
			$attachments = [];
			if ( function_exists( 'geodir_get_images' ) ) {
				$images = geodir_get_images( $post_id );
				if ( ! empty( $images ) ) {
					foreach ( $images as $image ) {
						if ( ! empty( $image->file ) ) {
							$attachments[] = $image->file;
						}
					}
				}
			}
			
			$address     = '';
			$country     = '';
			$region      = '';
			$city        = '';
			$postal_code = '';
			$latitude    = '';
			$longitude   = '';
			
			if ( isset( $gd_post->street ) ) {
				$address = $gd_post->street;
			}
			if ( isset( $gd_post->country ) ) {
				$country = $gd_post->country;
			}
			if ( isset( $gd_post->region ) ) {
				$region = $gd_post->region;
			}
			if ( isset( $gd_post->city ) ) {
				$city = $gd_post->city;
			}
			if ( isset( $gd_post->zip ) ) {
				$postal_code = $gd_post->zip;
			}
			if ( isset( $gd_post->latitude ) ) {
				$latitude = $gd_post->latitude;
			}
			if ( isset( $gd_post->longitude ) ) {
				$longitude = $gd_post->longitude;
			}
			
			$context = array_merge(
				$user,
				[
					'listing_id'    => $post_id,
					'listing_title' => $post->post_title,
					'listing_type'  => $post_type,
					'listing_url'   => get_permalink( $post_id ),
					'description'   => $description,
					'categories'    => $categories,
					'tags'          => $tags,
					'attachments'   => $attachments,
					'address'       => $address,
					'country'       => $country,
					'region'        => $region,
					'city'          => $city,
					'postal_code'   => $postal_code,
					'latitude'      => $latitude,
					'longitude'     => $longitude,
				]
			);

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	ListingAdded::get_instance();

endif;
