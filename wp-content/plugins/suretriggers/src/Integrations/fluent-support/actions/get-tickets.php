<?php
/**
 * GetAllTickets.
 * php version 5.6
 *
 * @category GetAllTickets
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentSupport\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;
use FluentSupport\App\Models\Ticket;
use FluentSupport\App\Api\Classes\Tickets;

/**
 * GetAllTickets
 *
 * @category GetAllTickets
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class GetAllTickets extends AutomateAction {

	use SingletonLoader;

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentSupport';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'get_all_tickets_fluent_support';

	/**
	 * Register the action.
	 *
	 * @param array $actions Existing actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Get All Tickets', 'suretriggers' ),
			'action'   => $this->action,
			'function' => [ $this, 'action_listener' ],
		];

		return $actions;
	}

	/**
	 * Action listener.
	 *
	 * @param int   $user_id User ID.
	 * @param int   $automation_id Automation ID.
	 * @param array $fields Field values.
	 * @param array $selected_options Selected options.
	 *
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( 'FluentSupport\App\Models\Ticket' ) || ! class_exists( 'FluentSupport\App\Api\Classes\Tickets' ) ) {
			return [
				'status'  => 'error',
				'message' => 'Error: FluentSupport plugin is not installed correctly. Required classes are missing.',
			];
		}

		try {
			$tickets_instance = new Tickets( new Ticket() );
			$response         = $tickets_instance->getTickets();
	
			if ( $response ) {
				return [
					'success' => true,
					'message' => 'Tickets retrieved successfully.',
					'data'    => $response,
				];
			} else {
				return [
					'success' => false,
					'message' => 'No tickets found or failed to retrieve tickets.',
				];
			}
		} catch ( Exception $e ) {
			return [
				'success' => false,
				'message' => 'Error while retrieving tickets: ' . $e->getMessage(),
				'data'    => [],
			];
		}
		
	}
}

GetAllTickets::get_instance();
