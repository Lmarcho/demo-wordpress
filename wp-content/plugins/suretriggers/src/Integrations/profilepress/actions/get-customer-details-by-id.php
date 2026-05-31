<?php
/**
 * GetCustomerDetailsByID.
 * php version 5.6
 *
 * @category GetCustomerDetailsByID
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.1.5
 */

namespace SureTriggers\Integrations\ProfilePress\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * GetCustomerDetailsByID
 *
 * @category GetCustomerDetailsByID
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetCustomerDetailsByID extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'ProfilePress';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'ppress_get_customer_details_by_id';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get Plan Details by ID', 'suretriggers' ),
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
	 * @return object|array|void
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! class_exists( '\ProfilePress\Core\Membership\Models\Customer\CustomerFactory' ) ) {
			return [
				'status'   => __( 'error', 'suretriggers' ),
				'response' => __( 'ProfilePress Customer Factory class not found. Please ensure ProfilePress is properly installed.', 'suretriggers' ), 
				
			];
		}
		
		$customer_id = $selected_options['customer_id'];
		$customer    = \ProfilePress\Core\Membership\Models\Customer\CustomerFactory::fromId( absint( $customer_id ) );
		return $customer;
	}
}

GetCustomerDetailsByID::get_instance();
