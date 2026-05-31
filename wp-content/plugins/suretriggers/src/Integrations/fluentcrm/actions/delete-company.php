<?php
/**
 * DeleteCompany.
 * php version 5.6
 *
 * @category DeleteCompany
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

/**
 * DeleteCompany
 *
 * @category DeleteCompany
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteCompany extends AutomateAction {


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
	public $action = 'fluentcrm_delete_company';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Company', 'suretriggers' ),
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
		
		if ( ! function_exists( 'FluentCrmApi' ) || ! class_exists( 'FluentCrm\App\Services\Helper' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Required functions not found.', 'suretriggers' ),
			];
		}

		$is_company_enabled = Helper::isCompanyEnabled();
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

		$context = [
			'company_id'    => $company->id,
			'company_name'  => $company->name,
			'company_email' => $company->email,
		];

		$company->delete();

		return $context;
	}

}

DeleteCompany::get_instance();
