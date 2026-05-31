<?php
/**
 * CompanyCreatedFluentCRM.
 * php version 5.6
 *
 * @category CompanyCreatedFluentCRM
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use FluentCrm\App\Models\Company;

if ( ! class_exists( 'CompanyCreatedFluentCRM' ) ) :

	/**
	 * CompanyCreatedFluentCRM
	 *
	 * @category CompanyCreatedFluentCRM
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class CompanyCreatedFluentCRM {


		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'FluentCRM';


		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'company_created_fluentcrm';

		use SingletonLoader;


		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_filter( 'sure_trigger_register_trigger', [ $this, 'register' ] );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'Company Created', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluent_crm/company_created',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $company Company object.
		 * @param array  $data Creation data.
		 * @return void
		 */
		public function trigger_listener( $company, $data ) {
			if ( empty( $company ) || ! method_exists( $company, 'toArray' ) ) {
				return;
			}

			$company_data = $company->toArray();
			
			// Include custom field values if they exist.
			if ( is_object( $company ) && property_exists( $company, 'meta' ) && is_array( $company->meta ) && isset( $company->meta['custom_values'] ) ) {
				$company_data['custom_fields'] = $company->meta['custom_values'];
			}

			$context['company']       = $company_data;
			$context['creation_data'] = $data;

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	CompanyCreatedFluentCRM::get_instance();

endif;
