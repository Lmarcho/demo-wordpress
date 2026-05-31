<?php
/**
 * EDDDeleteDiscount.
 * php version 5.6
 *
 * @category EDDDeleteDiscount
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\EDD\Actions;

use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use Exception;

/**
 * EDDDeleteDiscount
 *
 * @category EDDDeleteDiscount
 * @package  SureTriggers
 * @author   BSF
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class EDDDeleteDiscount extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'EDD';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'edd_delete_discount';

	use SingletonLoader;

	/**
	 * Register the action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Discount', 'suretriggers' ),
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
	 * @throws Exception Exception.
	 *
	 * @return void|array|bool
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'edd_delete_discount' ) || ! function_exists( 'edd_get_discount' ) ) {
			return [
				'status'  => 'error',
				'message' => 'EDD plugin is not active.',
			];
		}       

		if ( empty( $selected_options['discount_id'] ) ) {
			return [
				'success' => false,
				'message' => 'Discount ID is required.',
			];
		}
		$discount_id = $selected_options['discount_id'];
		$discount    = edd_get_discount( $discount_id );
		
		if ( ! $discount ) {
			return [
				'success' => false,
				'message' => 'Discount not found.',
			];
		}

		$deleted = edd_delete_discount( $discount_id, true );

		if ( ! $deleted ) {
			return [
				'success' => false,
				'message' => 'Failed to delete the discount.',
			];
		}

		return [
			'discount_id' => $discount_id,
			'status'      => 'deleted',
		];
	}
}

EDDDeleteDiscount::get_instance();
