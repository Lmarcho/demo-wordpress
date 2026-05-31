<?php
/**
 * EmailClickedFluentCRM.
 * php version 5.6
 *
 * @category EmailClickedFluentCRM
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'EmailClickedFluentCRM' ) ) :

	/**
	 * EmailClickedFluentCRM
	 *
	 * @category EmailClickedFluentCRM
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class EmailClickedFluentCRM {


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
		public $trigger = 'email_clicked_fluentcrm';

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
				'label'         => __( 'Email Clicked', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluent_crm/email_url_clicked',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $campaign_email Campaign email object.
		 * @param object $url_data URL data object.
		 * @return void
		 */
		public function trigger_listener( $campaign_email, $url_data ) {
			if ( empty( $campaign_email ) || ! is_object( $campaign_email ) || empty( $url_data ) || ! is_object( $url_data ) ) {
				return;
			}

			// Get email data.
			$email_data = method_exists( $campaign_email, 'toArray' ) ? $campaign_email->toArray() : [];

			// Get URL data.
			$url_info = method_exists( $url_data, 'toArray' ) ? $url_data->toArray() : [];

			// Get subscriber details if subscriber_id exists.
			if ( property_exists( $campaign_email, 'subscriber_id' ) && class_exists( 'FluentCrm\App\Models\Subscriber' ) ) {
				$subscriber = \FluentCrm\App\Models\Subscriber::find( $campaign_email->subscriber_id );
				if ( $subscriber && is_object( $subscriber ) && method_exists( $subscriber, 'toArray' ) ) {
					$context['subscriber'] = $subscriber->toArray();
					// Add custom fields if subscriber has custom_fields method.
					if ( is_object( $subscriber ) && method_exists( $subscriber, 'custom_fields' ) ) {
						$context['subscriber']['custom_fields'] = $subscriber->custom_fields();
					}
				}
			}

			// Get campaign details if campaign_id exists.
			if ( property_exists( $campaign_email, 'campaign_id' ) && class_exists( 'FluentCrm\App\Models\Campaign' ) ) {
				$campaign = \FluentCrm\App\Models\Campaign::find( $campaign_email->campaign_id );
				if ( $campaign && is_object( $campaign ) && method_exists( $campaign, 'toArray' ) ) {
					$context['campaign'] = $campaign->toArray();
				}
			}

			$context['email']      = $email_data;
			$context['url']        = $url_info;
			$context['clicked_at'] = current_time( 'mysql' );

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
	EmailClickedFluentCRM::get_instance();

endif;
