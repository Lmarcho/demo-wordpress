<?php
/**
 * RemoveSubscriberFromSequence.
 * php version 5.6
 *
 * @category RemoveSubscriberFromSequence
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
use FluentCrm\App\Models\FunnelSubscriber;

/**
 * RemoveSubscriberFromSequence
 *
 * @category RemoveSubscriberFromSequence
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class RemoveSubscriberFromSequence extends AutomateAction {


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
	public $action = 'fluentcrm_remove_subscriber_from_sequence';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Remove Subscriber from Sequence', 'suretriggers' ),
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
	 * @return array
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		
		if ( ! function_exists( 'FluentCrmApi' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCrmApi function not found.', 'suretriggers' ),
			];
		}

		$contact_api = FluentCrmApi( 'contacts' );
		$contact     = $contact_api->getContact( trim( $selected_options['contact_email'] ) );

		if ( is_null( $contact ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid contact.', 'suretriggers' ),
			];
		}

		$sequence_id = intval( $selected_options['sequence_id'] );
		
		if ( ! class_exists( 'FluentCrm\App\Models\FunnelSubscriber' ) ) {
			return [
				'status'  => 'error',
				'message' => 'FunnelSubscriber class not found.',
			];
		}

		$funnel_subscriber = FunnelSubscriber::where( 'subscriber_id', $contact->id )
			->where( 'funnel_id', $sequence_id )
			->first();

		if ( ! $funnel_subscriber ) {
			return [
				'status'  => 'error',
				'message' => __( 'Subscriber not found in sequence.', 'suretriggers' ),
			];
		}

		$funnel_subscriber->delete();

		return [
			'success'     => true,
			'message'     => __( 'Subscriber removed from sequence successfully.', 'suretriggers' ),
			'contact_id'  => $contact->id,
			'email'       => $contact->email,
			'sequence_id' => $sequence_id,
		];
	}

}

RemoveSubscriberFromSequence::get_instance();
