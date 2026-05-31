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

namespace SureTriggers\Integrations\FluentCart\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentCart\App\Models\Coupon;

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

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCart';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcart_create_coupon';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Coupon', 'suretriggers' ),
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
	 * @param array $selected_options selectedOptions.
	 *
	 * @return array|void
	 *
	 * @throws \Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( '\FluentCart\App\Models\Coupon' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCart is not installed or activated.', 'suretriggers' ),
			];
		}

		$title         = isset( $selected_options['title'] ) ? $selected_options['title'] : '';
		$coupon_code   = isset( $selected_options['coupon_code'] ) ? $selected_options['coupon_code'] : '';
		$discount_type = isset( $selected_options['discount_type'] ) ? $selected_options['discount_type'] : 'percentage';
		$amount        = isset( $selected_options['discount_value'] ) ? $selected_options['discount_value'] : '';
		$status        = isset( $selected_options['status'] ) ? $selected_options['status'] : 'active';
		$start_date    = isset( $selected_options['start_date'] ) ? $selected_options['start_date'] : '';
		$end_date      = isset( $selected_options['end_date'] ) ? $selected_options['end_date'] : '';
		$stackable     = isset( $selected_options['stackable'] ) ? $selected_options['stackable'] : 0;
		$notes         = isset( $selected_options['notes'] ) ? $selected_options['notes'] : '';
		
		// Validation.
		if ( empty( $title ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon title is required.', 'suretriggers' ),
			];
		}

		if ( empty( $coupon_code ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon coupon_code is required.', 'suretriggers' ),
			];
		}

		if ( empty( $amount ) || ! is_numeric( $amount ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Valid discount amount is required.', 'suretriggers' ),
			];
		}
		
		// Validate percentage amount.
		if ( 'percentage' === $discount_type && floatval( $amount ) > 100 ) {
			return [
				'status'  => 'error',
				'message' => __( 'Percentage discount cannot exceed 100%.', 'suretriggers' ),
			];
		}

		// Check if coupon code already exists.
		$existing_coupon = Coupon::where( 'code', $coupon_code )->first();
		if ( $existing_coupon ) {
			return [
				'status'  => 'error',
				'message' => sprintf( __( 'Coupon with coupon_code "%s" already exists.', 'suretriggers' ), $coupon_code ),
			];
		}

		try {
			// Prepare coupon data.
			$coupon_data = [
				'title'     => $title,
				'code'      => $coupon_code,
				'type'      => $discount_type,
				'amount'    => floatval( $amount ),
				'status'    => $status,
				'stackable' => ( 1 == $stackable ) ? 'yes' : 'no',
				'use_count' => 0,
				'notes'     => $notes,
			];

			// Add dates if provided.
			if ( ! empty( $start_date ) ) {
				$start_timestamp = strtotime( $start_date );
				if ( $start_timestamp ) {
					$coupon_data['start_date'] = gmdate( 'Y-m-d H:i:s', $start_timestamp );
				}
			}

			if ( ! empty( $end_date ) ) {
				$end_timestamp = strtotime( $end_date );
				if ( $end_timestamp ) {
					$coupon_data['end_date'] = gmdate( 'Y-m-d H:i:s', $end_timestamp );
				}
			}


			// Create the coupon.
			$coupon = Coupon::create( $coupon_data );

			if ( ! $coupon ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to create coupon.', 'suretriggers' ),
				];
			}

			$context = [
				'coupon_id'        => $coupon->id,
				'title'            => $coupon->title,
				'code'             => $coupon->code,
				'type'             => $coupon->type,
				'amount'           => $coupon->amount,
				'status'           => $coupon->status,
				'stackable'        => $coupon->stackable,
				'show_on_checkout' => $coupon->show_on_checkout,
				'priority'         => $coupon->priority,
				'use_count'        => $coupon->use_count,
				'notes'            => $coupon->notes,
				'start_date'       => $coupon->start_date,
				'end_date'         => $coupon->end_date,
				'conditions'       => $coupon->conditions,
				'created_at'       => $coupon->created_at,
			];

			// Add formatted discount info.
			if ( 'percentage' === $coupon->type ) {
				$context['discount_display'] = $coupon->amount . '%';
			} else {
				$context['discount_display'] = '$' . number_format( $coupon->amount, 2 );
			}

			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

CreateCoupon::get_instance();
