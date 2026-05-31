<?php
/**
 * CreateSequence.
 * php version 5.6
 *
 * @category CreateSequence
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
 * CreateSequence
 *
 * @category CreateSequence
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateSequence extends AutomateAction {


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
	public $action = 'fluentcrm_create_sequence';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Sequence', 'suretriggers' ),
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

		$sequence_data = [
			'title'      => sanitize_text_field( $selected_options['sequence_title'] ),
			'slug'       => sanitize_title( $selected_options['sequence_title'] ),
			'status'     => 'draft',
			'created_at' => current_time( 'mysql' ),
			'updated_at' => current_time( 'mysql' ),
		];

		$sequence = \FluentCampaign\App\Models\Sequence::create( $sequence_data );

		if ( ! $sequence ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create sequence.', 'suretriggers' ),
			];
		}

		return [
			'sequence_id' => $sequence->id,
			'title'       => $sequence->title,
			'slug'        => $sequence->slug,
			'status'      => $sequence->status,
		];
	}

}

CreateSequence::get_instance();
