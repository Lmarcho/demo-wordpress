<?php
/**
 * ListSequences.
 * php version 5.6
 *
 * @category ListSequences
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
 * ListSequences
 *
 * @category ListSequences
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListSequences extends AutomateAction {

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
	public $action = 'fluentcrm_list_sequences';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Sequences', 'suretriggers' ),
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
	 * @return array|void|mixed
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM Pro is not installed or activated.', 'suretriggers' ), 
				
			];
		}

		$sequences_data = [];
		$sequences      = \FluentCampaign\App\Models\Sequence::orderBy( 'id', 'DESC' )->get();
		
		foreach ( $sequences as $sequence ) {
			$sequences_data[] = [
				'id'    => $sequence->id,
				'title' => $sequence->title,
			];
		}

		return [
			'sequences' => $sequences_data,
			'count'     => count( $sequences_data ),
		];
	}
}

ListSequences::get_instance();
