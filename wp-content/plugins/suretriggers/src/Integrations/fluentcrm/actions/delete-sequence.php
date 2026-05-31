<?php
/**
 * DeleteSequence.
 * php version 5.6
 *
 * @category DeleteSequence
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
 * DeleteSequence
 *
 * @category DeleteSequence
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteSequence extends AutomateAction {


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
	public $action = 'fluentcrm_delete_sequence';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Sequence', 'suretriggers' ),
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
		
		if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM Pro is not installed or activated.', 'suretriggers' ),
			];
		}

		$sequence_id = intval( $selected_options['sequence_id'] );
		
		if ( ! $sequence_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Invalid sequence ID.', 'suretriggers' ),
			];
		}

		$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );
		
		if ( ! $sequence ) {
			return [
				'status'  => 'error',
				'message' => __( 'Sequence not found.', 'suretriggers' ),
			];
		}

		$context = [
			'sequence_id'    => $sequence->id,
			'sequence_title' => $sequence->title,
			'sequence_slug'  => $sequence->slug,
		];

		$sequence->delete();

		return [
			'success'          => true,
			'message'          => __( 'Sequence deleted successfully.', 'suretriggers' ),
			'deleted_sequence' => $context,
		];
	}

}

DeleteSequence::get_instance();
