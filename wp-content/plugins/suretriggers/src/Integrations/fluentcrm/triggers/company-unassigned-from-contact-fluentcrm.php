<?php
/**
 * CompanyUnassignedFromContactFluentCRM.
 * php version 5.6
 *
 * @category CompanyUnassignedFromContactFluentCRM
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

if ( ! class_exists( 'CompanyUnassignedFromContactFluentCRM' ) ) :

	/**
	 * CompanyUnassignedFromContactFluentCRM
	 *
	 * @category CompanyUnassignedFromContactFluentCRM
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class CompanyUnassignedFromContactFluentCRM {


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
		public $trigger = 'company_unassigned_from_contact_fluentcrm';

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
				'label'         => __( 'Company Unassigned from Contact', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluentcrm_contact_removed_from_companies',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array  $company_ids Company IDs.
		 * @param object $contact Contact object.
		 * @return void
		 */
		public function trigger_listener( $company_ids, $contact ) {
			if ( empty( $company_ids ) || empty( $contact ) || ! is_object( $contact ) ) {
				return;
			}

			// Get contact details with custom fields.
			if ( class_exists( 'FluentCrm\\App\\Models\\Subscriber' ) && property_exists( $contact, 'id' ) ) {
				$subscriber      = \FluentCrm\App\Models\Subscriber::with( [ 'tags', 'lists' ] )->find( $contact->id );
				$customer_fields = [];
				
				if ( is_object( $subscriber ) && method_exists( $subscriber, 'custom_fields' ) ) {
					$customer_fields = $subscriber->custom_fields();
				}

				if ( is_object( $subscriber ) && method_exists( $subscriber, 'toArray' ) ) {
					$context['contact']['details'] = $subscriber->toArray();
				} elseif ( method_exists( $contact, 'toArray' ) ) {
					$context['contact']['details'] = $contact->toArray();
				} else {
					$context['contact']['details'] = [];
				}
				
				$context['contact']['custom'] = $customer_fields;
			} else {
				$context['contact']['details'] = method_exists( $contact, 'toArray' ) ? $contact->toArray() : [];
				$context['contact']['custom']  = [];
			}

			// Get company details.
			if ( class_exists( 'FluentCrm\\App\\Models\\Company' ) ) {
				$companies            = Company::whereIn( 'id', $company_ids )->get();
				$context['companies'] = [];
				foreach ( $companies as $company ) {
					$company_data = $company->toArray();
					// Include custom fields if they exist.
					if ( isset( $company->meta['custom_values'] ) ) {
						$company_data['custom_fields'] = $company->meta['custom_values'];
					}
					$context['companies'][] = $company_data;
				}
			}

			$context['unassigned_company_ids'] = $company_ids;
			$context['unassigned_at']          = current_time( 'mysql' );

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
	CompanyUnassignedFromContactFluentCRM::get_instance();

endif;
