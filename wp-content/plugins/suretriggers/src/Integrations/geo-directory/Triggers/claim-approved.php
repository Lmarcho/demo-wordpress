<?php
/**
 * ClaimApproved
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

if ( ! class_exists( 'ClaimApproved' ) ) :

	/**
	 * ClaimApproved
	 *
	 * @category ClaimApproved
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class ClaimApproved {

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
		public $trigger = 'geodir_claim_approved';

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
				'label'         => __( 'Listing Claim Approved', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'geodir_claim_approved',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener.
		 *
		 * @param object $claim Claim object.
		 * @return void
		 */
		public function trigger_listener( $claim ) {
			if ( empty( $claim ) || empty( $claim->post_id ) ) {
				return;
			}
	
			$post_id = absint( $claim->post_id );
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

			$user_id = isset( $claim->user_id ) ? absint( $claim->user_id ) : 0;
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
			
			$gd_post = null;
			if ( function_exists( 'geodir_get_post_info' ) ) {
				$gd_post = geodir_get_post_info( $post_id );
			}
			
			$address     = '';
			$country     = '';
			$region      = '';
			$city        = '';
			$postal_code = '';
			$latitude    = '';
			$longitude   = '';
			
			if ( $gd_post ) {
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
			}
			
			$context = array_merge(
				$user,
				[
					'claim'         => array_merge(
						(array) $claim,
						[
							'status' => 'approved',
						]
					),
					'listing_id'    => $post_id,
					'listing_title' => $post->post_title,
					'listing_type'  => $post_type,
					'listing_url'   => get_permalink( $post_id ),
					'description'   => $description,
					'categories'    => $categories,
					'tags'          => $tags,
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

	ClaimApproved::get_instance();

endif;
