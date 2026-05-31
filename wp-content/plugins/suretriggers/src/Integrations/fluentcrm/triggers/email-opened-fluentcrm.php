<?php
/**
 * EmailOpenedFluentCRM.
 * php version 5.6
 *
 * @category EmailOpenedFluentCRM
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'EmailOpenedFluentCRM' ) ) :

	/**
	 * EmailOpenedFluentCRM
	 *
	 * @category EmailOpenedFluentCRM
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class EmailOpenedFluentCRM {


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
		public $trigger = 'email_opened_fluentcrm';

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
				'label'         => __( 'Email Opened', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluent_crm/email_opened',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $email Campaign email object.
		 * @return void
		 */
		public function trigger_listener( $email ) {
			if ( empty( $email ) || ! is_object( $email ) ) {
				return;
			}

			// Get email data.
			$email_data = method_exists( $email, 'toArray' ) ? $email->toArray() : [];

			// Get subscriber details if subscriber_id exists.
			if ( property_exists( $email, 'subscriber_id' ) && class_exists( 'FluentCrm\App\Models\Subscriber' ) ) {
				$subscriber = \FluentCrm\App\Models\Subscriber::find( $email->subscriber_id );
				if ( $subscriber && is_object( $subscriber ) && method_exists( $subscriber, 'toArray' ) ) {
					$context['subscriber'] = $subscriber->toArray();
					// Add custom fields if subscriber has custom_fields method.
					if ( is_object( $subscriber ) && method_exists( $subscriber, 'custom_fields' ) ) {
						$context['subscriber']['custom_fields'] = $subscriber->custom_fields();
					}
				}
			}

			// Get campaign details if campaign_id exists.
			if ( property_exists( $email, 'campaign_id' ) && class_exists( 'FluentCrm\App\Models\Campaign' ) ) {
				$campaign = \FluentCrm\App\Models\Campaign::find( $email->campaign_id );
				if ( $campaign && is_object( $campaign ) && method_exists( $campaign, 'toArray' ) ) {
					$context['campaign'] = $campaign->toArray();
				}
			}

			$context['email']     = $email_data;
			$context['opened_at'] = current_time( 'mysql' );

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
	EmailOpenedFluentCRM::get_instance();

endif;
