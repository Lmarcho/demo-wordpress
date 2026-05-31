<?php
/**
 * EmailUnsubscribedFluentCRM.
 * php version 5.6
 *
 * @category EmailUnsubscribedFluentCRM
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'EmailUnsubscribedFluentCRM' ) ) :

	/**
	 * EmailUnsubscribedFluentCRM
	 *
	 * @category EmailUnsubscribedFluentCRM
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class EmailUnsubscribedFluentCRM {


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
		public $trigger = 'email_unsubscribed_fluentcrm';

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
				'label'         => __( 'Email Unsubscribed', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluent_crm/before_contact_unsubscribe_from_email',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 3,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $subscriber Subscriber object.
		 * @param object $campaign_email Campaign email object.
		 * @param string $source Unsubscribe source.
		 * @return void
		 */
		public function trigger_listener( $subscriber, $campaign_email, $source ) {
			if ( empty( $subscriber ) || ! is_object( $subscriber ) ) {
				return;
			}

			$context = [];

			// Get subscriber data.
			$subscriber_data       = method_exists( $subscriber, 'toArray' ) ? $subscriber->toArray() : [];
			$context['subscriber'] = $subscriber_data;

			// Add custom fields if subscriber has custom_fields method.
			if ( method_exists( $subscriber, 'custom_fields' ) ) {
				$context['subscriber']['custom_fields'] = $subscriber->custom_fields();
			}

			// Get campaign email data if available.
			if ( ! empty( $campaign_email ) && is_object( $campaign_email ) ) {
				$email_data       = method_exists( $campaign_email, 'toArray' ) ? $campaign_email->toArray() : [];
				$context['email'] = $email_data;

			} else {
				// If no campaign_email provided, try to get recent email data for subscriber.
				if ( property_exists( $subscriber, 'id' ) && class_exists( 'FluentCrm\App\Models\CampaignEmail' ) ) {
					$recent_email = \FluentCrm\App\Models\CampaignEmail::where( 'subscriber_id', $subscriber->id )
						->orderBy( 'id', 'desc' )
						->first();
					if ( $recent_email && is_object( $recent_email ) && method_exists( $recent_email, 'toArray' ) ) {
						$context['email'] = $recent_email->toArray();
					}
				}
			}

			$context['source']          = $source;
			$context['unsubscribed_at'] = current_time( 'mysql' );

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
	EmailUnsubscribedFluentCRM::get_instance();

endif;
