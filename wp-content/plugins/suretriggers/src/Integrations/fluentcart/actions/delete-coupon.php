<?php
/**
 * DeleteCoupon.
 * php version 5.6
 *
 * @category DeleteCoupon
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
use FluentCart\App\Models\AppliedCoupon;

/**
 * DeleteCoupon
 *
 * @category DeleteCoupon
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteCoupon extends AutomateAction {

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
	public $action = 'fluentcart_delete_coupon';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Coupon', 'suretriggers' ),
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

		$coupon_id   = isset( $selected_options['coupon_id'] ) ? $selected_options['coupon_id'] : '';
		$coupon_code = isset( $selected_options['coupon_code'] ) ? $selected_options['coupon_code'] : '';
		
		if ( empty( $coupon_id ) && empty( $coupon_code ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Coupon ID or coupon code is required.', 'suretriggers' ),
			];
		}

		try {
			$coupon = null;

			// Find coupon by ID first, then by code.
			if ( ! empty( $coupon_id ) ) {
				$coupon = Coupon::find( $coupon_id );
			} elseif ( ! empty( $coupon_code ) ) {
				$coupon = Coupon::where( 'code', $coupon_code )->first();
			}

			if ( ! $coupon ) {
				return [
					'status'  => 'error',
					'message' => __( 'Coupon not found.', 'suretriggers' ),
				];
			}

			// Store coupon data before deletion for context.
			$deleted_coupon_data = [
				'coupon_id'        => $coupon->id,
				'title'            => $coupon->title,
				'code'             => $coupon->code,
				'type'             => $coupon->type,
				'amount'           => $coupon->amount,
				'status'           => $coupon->status,
				'use_count'        => $coupon->use_count,
				'stackable'        => $coupon->stackable,
				'show_on_checkout' => $coupon->show_on_checkout,
				'priority'         => $coupon->priority,
				'start_date'       => $coupon->start_date,
				'end_date'         => $coupon->end_date,
				'conditions'       => $coupon->conditions,
				'notes'            => $coupon->notes,
				'created_at'       => $coupon->created_at,
			];


			// Fire before delete hook.
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- FluentCart uses forward slashes in hook names
			do_action( 'fluent_cart/before_coupon_delete', $coupon );

			// Delete the coupon.
			$deleted = $coupon->delete();

			if ( ! $deleted ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to delete coupon.', 'suretriggers' ),
				];
			}

			// Fire after delete hook.
			// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- FluentCart uses forward slashes in hook names
			do_action( 'fluent_cart/coupon_deleted', $deleted_coupon_data );

			$context = array_merge(
				$deleted_coupon_data,
				[
					'deleted_successfully' => true,
					'deletion_timestamp'   => current_time( 'mysql' ),
				]
			);

			return $context;

		} catch ( \Exception $e ) {
			return [
				'status'  => 'error',
				'message' => $e->getMessage(),
			];
		}
	}
}

DeleteCoupon::get_instance();
