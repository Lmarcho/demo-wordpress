<?php
/**
 * AddNoteToCompany.
 * php version 5.6
 *
 * @category AddNoteToCompany
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
use FluentCrm\App\Services\Helper;
use FluentCrm\App\Models\CompanyNote;

/**
 * AddNoteToCompany
 *
 * @category AddNoteToCompany
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddNoteToCompany extends AutomateAction {


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
	public $action = 'fluentcrm_add_note_to_company';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Note to Company', 'suretriggers' ),
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
				'message' => __( 'FluentCRM is not active.', 'suretriggers' ),
			];
		}

		if ( ! class_exists( 'FluentCrm\App\Models\CompanyNote' ) ) {
			return [
				'status'  => 'error',
				'message' => 'CompanyNote class not found.',
			];
		}

		$is_company_enabled = class_exists( 'FluentCrm\App\Services\Helper' ) && Helper::isCompanyEnabled();
		if ( ! $is_company_enabled ) {
			return [
				'status'  => 'error',
				'message' => __( 'Company module disabled.', 'suretriggers' ),
			];
		}

		$company_id  = $selected_options['company_id'];
		$company_api = FluentCrmApi( 'companies' );
		$company     = $company_api->getCompany( $company_id );

		if ( is_null( $company ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Company not found.', 'suretriggers' ),
			];
		}

		$note_data = [
			'title'         => $selected_options['title'],
			'description'   => $selected_options['description'],
			'type'          => isset( $selected_options['type'] ) ? $selected_options['type'] : 'general',
			'created_at'    => current_time( 'mysql' ),
			'subscriber_id' => intval( $company_id ),
		];

		$subscriber_note = CompanyNote::create( $note_data );

		if ( ! $subscriber_note ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to add note.', 'suretriggers' ),
			];
		}

		return [
			'success' => true,
			'message' => __( 'Note has been successfully added to company.', 'suretriggers' ),
			'note'    => $subscriber_note,
		];
	}

}

AddNoteToCompany::get_instance();
