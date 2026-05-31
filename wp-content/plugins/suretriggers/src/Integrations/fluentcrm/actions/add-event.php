<?php
/**
 * AddEvent.
 * php version 5.6
 *
 * @category AddEvent
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Actions;

use Exception;
use SureTriggers\Integrations\AutomateAction;
use SureTriggers\Traits\SingletonLoader;

/**
 * AddEvent
 *
 * @category AddEvent
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddEvent extends AutomateAction {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'FluentCRM';

	/**
	 * Action name.
	 *
	 * @var string
	 */
	public $action = 'fluentcrm_add_event';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Event', 'suretriggers' ),
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
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM API is not active.', 'suretriggers' ), 
				
			];
		}

		// Required fields validation.
		if ( empty( $selected_options['email'] ) || empty( $selected_options['event_key'] ) || empty( $selected_options['title'] ) ) {
			return [
				'success' => false,
				'message' => __( 'Email, Event Key, and Title are required.', 'suretriggers' ), 
				
			];
		}
		$value    = $selected_options['value'] ? sanitize_text_field( $selected_options['value'] ) : '';
		$provider = $selected_options['provider'] ? sanitize_text_field( $selected_options['provider'] ) : 'custom';
		$result   = FluentCrmApi( 'event_tracker' )->track(
			[
				'event_key' => $selected_options['event_key'],
				'title'     => $selected_options['title'],
				'value'     => $value,
				'email'     => sanitize_email( $selected_options['email'] ),
				'provider'  => $provider,
			],
			true
		);

		if ( ! $result ) {
			return [
				'success' => false,
				'message' => __( 'Failed to add event. Please try again.', 'suretriggers' ), 
				
			];
		}

		return [
			'success' => true,
			'message' => __( 'Event has been successfully added.', 'suretriggers' ),
			'event'   => $result,
		];
	}
}

AddEvent::get_instance();
