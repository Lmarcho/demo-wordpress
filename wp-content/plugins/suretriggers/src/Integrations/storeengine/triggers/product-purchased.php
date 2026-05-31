<?php
/**
 * ProductPurchased.
 * php version 5.6
 *
 * @category ProductPurchased
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\StoreEngine\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'ProductPurchased' ) ) :

	/**
	 * ProductPurchased
	 *
	 * @category ProductPurchased
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class ProductPurchased {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'StoreEngine';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'se_product_purchased';

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
				'label'         => __( 'Product Purchased', 'suretriggers' ),
				'action'        => 'se_product_purchased',
				'common_action' => 'storeengine/order_status_completed',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 3,
			];

			return $triggers;
		}

		/**
		 * Trigger listener for order status transition.
		 *
		 * @param int    $order_id         The ID of the order.
		 * @param object $order            The StoreEngine order object.
		 * @param array  $status_transition Array containing 'from', 'to', and 'order' status transition data.
		 *
		 * @return void
		 */
		public function trigger_listener( $order_id, $order, $status_transition ) {
			
			if ( empty( $order_id ) || empty( $order ) ) {
				return;
			}

			// Verify order object has required methods.
			if ( ! method_exists( $order, 'get_items' ) || ! method_exists( $order, 'get_customer_id' ) || ! method_exists( $order, 'get_status' ) || ! method_exists( $order, 'get_total' ) || ! method_exists( $order, 'get_currency' ) ) {
				return;
			}

			// Get order items.
			$items = $order->get_items();
			if ( empty( $items ) || ! is_array( $items ) ) {
				return;
			}
			$context = [];

			// Process each product in the order.
			foreach ( $items as $item ) {
				// Verify item has required methods.
				if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) || ! method_exists( $item, 'get_price_id' ) ) {
					continue;
				}

				$product_id = $item->get_product_id();
				
				
				// Get product details.
				$product = get_post( $product_id );
				if ( ! $product ) {
					continue;
				}

				// Get user details.
				$user_id = $order->get_customer_id();
				$user    = get_userdata( $user_id );
				
				// Build context matching search_se_triggers_last_data structure.
				$context = [
					// Product information (flat structure).
					'id'                    => $product_id,
					'post_author'           => $product->post_author,
					'post_date'             => $product->post_date,
					'post_date_gmt'         => $product->post_date_gmt,
					'post_content'          => $product->post_content,
					'post_title'            => $product->post_title,
					'post_excerpt'          => $product->post_excerpt,
					'post_status'           => $product->post_status,
					'comment_status'        => $product->comment_status,
					'ping_status'           => $product->ping_status,
					'post_password'         => $product->post_password,
					'post_name'             => $product->post_name,
					'to_ping'               => $product->to_ping,
					'pinged'                => $product->pinged,
					'post_modified'         => $product->post_modified,
					'post_modified_gmt'     => $product->post_modified_gmt,
					'post_content_filtered' => $product->post_content_filtered,
					'post_parent'           => $product->post_parent,
					'guid'                  => $product->guid,
					'menu_order'            => $product->menu_order,
					'post_type'             => $product->post_type,
					'post_mime_type'        => $product->post_mime_type,
					'comment_count'         => $product->comment_count,
					'filter'                => 'raw',
					'featured_image'        => get_post_thumbnail_id( $product_id ) ? get_post_thumbnail_id( $product_id ) : null,
					'permalink'             => get_permalink( $product_id ),
					
					// User information (flat structure).
					'wp_user_id'            => $user_id,
					'user_login'            => $user ? $user->user_login : '',
					'display_name'          => $user ? $user->display_name : '',
					'user_firstname'        => $user ? get_user_meta( $user_id, 'first_name', true ) : '',
					'user_lastname'         => $user ? get_user_meta( $user_id, 'last_name', true ) : '',
					'user_email'            => $user ? $user->user_email : '',
					'user_registered'       => $user ? $user->user_registered : '',
					'user_role'             => $user ? $user->roles : [],
					
					// Keep post ID for backwards compatibility.
					'post'                  => $product_id,
				];
				
				// Get all custom metas for the product - check with underscore prefix.
				$custom_metas = [];
				$all_meta     = get_post_meta( $product_id );
				
				// Filter StoreEngine specific metas (they start with underscore).
				if ( is_array( $all_meta ) ) {
					foreach ( $all_meta as $meta_key => $meta_value ) {
						if ( is_string( $meta_key ) && strpos( $meta_key, '_storeengine_' ) === 0 ) {
							// Remove underscore prefix for cleaner keys.
							$clean_key = ltrim( $meta_key, '_' );
							if ( is_array( $meta_value ) && isset( $meta_value[0] ) ) {
								$custom_metas[ $clean_key ] = maybe_unserialize( $meta_value[0] );
							}
						}
					}
				}
				
				$context['custom_metas'] = $custom_metas;
				
				// Check if product has membership integration using StoreEngine helper.
				$price_id = $item->get_price_id();
				
				if ( class_exists( '\StoreEngine\Utils\Helper' ) ) {
					// Use StoreEngine's helper function to get integration data.
					$membership_integration = \StoreEngine\Utils\Helper::get_integration_repository_by_price_id( 'storeengine/membership-addon', $price_id );
				} else {
					// Fallback if StoreEngine's helper is not available.
					$membership_integration = null;
				}
				
				// Add membership details if this is a membership product.
				if ( $membership_integration && ! empty( $membership_integration->integration ) ) {
					$membership_plan_id = $membership_integration->integration->get_integration_id();
					$access_group       = get_post( $membership_plan_id );
					
					if ( $access_group && 'storeengine_groups' === $access_group->post_type ) {
							// Add membership information to context - matching search_se_triggers_last_data structure.
							$context['membership_plan_id']   = $membership_plan_id;
							$context['membership_plan_name'] = $access_group->post_title;
							$context['membership_plan_slug'] = $access_group->post_name;
							
							// Get membership meta data.
							$membership_meta = [
								'user_roles'       => get_post_meta( $membership_plan_id, '_storeengine_membership_user_roles', true ),
								'content_protects' => get_post_meta( $membership_plan_id, '_storeengine_membership_content_protect_types', true ),
								'expiration'       => get_post_meta( $membership_plan_id, '_storeengine_membership_expiration', true ),
								'priority'         => get_post_meta( $membership_plan_id, '_storeengine_membership_priority', true ),
								'excluded_items'   => get_post_meta( $membership_plan_id, '_storeengine_membership_content_protect_excluded_items', true ),
							];
							
							$context['membership'] = array_filter( $membership_meta );
							
							// Add user membership data if available.
							if ( $user_id ) {
								$user_membership_data = get_user_meta( $user_id, '_storeengine_user_membership_data', true );
								if ( is_array( $user_membership_data ) && isset( $user_membership_data[ $membership_plan_id ] ) ) {
									$context['user_membership_data'] = $user_membership_data[ $membership_plan_id ];
								}
							}
							
							// Add order data for membership purchases - matching search_se_triggers_last_data.
							$context['order'] = [
								'order_id'     => $order_id,
								'order_status' => $order->get_status(),
								'order_total'  => $order->get_total(),
								'currency'     => $order->get_currency(),
							];
					}
				}
			}

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
	ProductPurchased::get_instance();

endif;
