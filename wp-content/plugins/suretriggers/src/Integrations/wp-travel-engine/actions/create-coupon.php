<?php
/**
 * CreateCoupon.
 * php version 5.6
 *
 * @category CreateCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\WPTravelEngine\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * CreateCoupon
 *
 * @category CreateCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateCoupon extends AutomateAction {

	use SingletonLoader;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'WPTravelEngine';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'wte_create_coupon';

	/**
	 * Register the action.
	 *
	 * @param array $actions List of registered actions.
	 * @return array Modified list of actions.
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'       => __( 'Create a Coupon', 'suretriggers' ),
			'description' => __( 'Create a new coupon for WP Travel Engine', 'suretriggers' ),
			'action'      => $this->action,
			'function'    => [ $this, '_action_listener' ],
		];
		return $actions;
	}

	/**
	 * Action listener that creates a coupon post and meta.
	 *
	 * @param int   $user_id          User ID.
	 * @param int   $automation_id    Automation ID.
	 * @param array $fields           Trigger fields.
	 * @param array $selected_options Selected options for coupon.
	 * @return array Result with status and data or error.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {

		if ( ! function_exists( 'wp_travel_engine_get_settings' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'WP Travel Engine plugin is not active.', 'suretriggers' ), 
				
			];
		}

		if ( empty( $selected_options['coupon_code'] ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon code is required.', 'suretriggers' ), 
				
			];
		}

		$coupon_data = [
			'post_title'  => sanitize_text_field( $selected_options['coupon_title'] ),
			'post_status' => isset( $selected_options['status'] ) ? sanitize_text_field( $selected_options['status'] ) : 'publish',
			'post_type'   => 'wte-coupon',
		];

		$coupon_id = wp_insert_post( $coupon_data, true );

		if ( is_wp_error( $coupon_id ) ) {
			return [
				'status'  => 'error',
				'message' => $coupon_id->get_error_message(),
			];
		}

		update_post_meta( $coupon_id, 'wp_travel_engine_coupon_code', sanitize_text_field( $selected_options['coupon_code'] ) );

		$coupon_metas = [];
		
		$coupon_metas['general'] = [
			'coupon_active' => 'yes',
			'coupon_type'   => isset( $selected_options['discount_type'] ) ? sanitize_text_field( $selected_options['discount_type'] ) : 'fixed',
			'coupon_value'  => isset( $selected_options['discount_value'] ) ? floatval( $selected_options['discount_value'] ) : 0,
		];

		if ( ! empty( $selected_options['start_date'] ) ) {
			$coupon_metas['general']['coupon_start_date'] = sanitize_text_field( $selected_options['start_date'] );
		} else {
			$coupon_metas['general']['coupon_start_date'] = gmdate( 'Y-m-d' );
		}

		if ( ! empty( $selected_options['expiry_date'] ) ) {
			$coupon_metas['general']['coupon_expiry_date'] = sanitize_text_field( $selected_options['expiry_date'] );
		}

		$coupon_metas['restriction'] = [];

		if ( ! empty( $selected_options['usage_limit'] ) ) {
			$coupon_metas['restriction']['coupon_limit_number'] = absint( $selected_options['usage_limit'] );
		}
		
		if ( ! empty( $selected_options['trip_ids'] ) ) {
			if ( is_array( $selected_options['trip_ids'] ) ) {
				if ( isset( $selected_options['trip_ids'][0]['value'] ) ) {
					$trip_ids = array_map(
						function( $item ) {
							return absint( $item['value'] );
						},
						$selected_options['trip_ids']
					);
				} else {
					$trip_ids = array_map( 'absint', $selected_options['trip_ids'] );
				}
			} else {
				$trip_ids = array_map( 'absint', array_map( 'trim', explode( ',', $selected_options['trip_ids'] ) ) );
			}
			$coupon_metas['restriction']['restricted_trips'] = $trip_ids;
		}
		
		update_post_meta( $coupon_id, 'wp_travel_engine_coupon_metas', $coupon_metas );

		return [
			'status' => 'success',
			'data'   => [
				'coupon_id'      => $coupon_id,
				'coupon_code'    => sanitize_text_field( $selected_options['coupon_code'] ),
				'coupon_title'   => html_entity_decode( get_the_title( $coupon_id ) ),
				'discount_type'  => $coupon_metas['general']['coupon_type'],
				'discount_value' => $coupon_metas['general']['coupon_value'],
				'start_date'     => $coupon_metas['general']['coupon_start_date'],
				'expiry_date'    => isset( $coupon_metas['general']['coupon_expiry_date'] ) ? $coupon_metas['general']['coupon_expiry_date'] : '',
				'usage_limit'    => isset( $coupon_metas['restriction']['coupon_limit_number'] ) ? $coupon_metas['restriction']['coupon_limit_number'] : '',
				'trip_ids'       => isset( $coupon_metas['restriction']['restricted_trips'] ) ? $coupon_metas['restriction']['restricted_trips'] : [],
				'status'         => $coupon_data['post_status'],
			],
		];
	}
}

CreateCoupon::get_instance();
