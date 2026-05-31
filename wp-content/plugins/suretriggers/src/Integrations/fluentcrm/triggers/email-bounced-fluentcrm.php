<?php
/**
 * EmailBouncedFluentCRM.
 * php version 5.6
 *
 * @category EmailBouncedFluentCRM
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'EmailBouncedFluentCRM' ) ) :

	/**
	 * EmailBouncedFluentCRM
	 *
	 * @category EmailBouncedFluentCRM
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class EmailBouncedFluentCRM {


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
		public $trigger = 'email_bounced_fluentcrm';

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
				'label'         => __( 'Email Bounced', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluentcrm_subscriber_status_to_bounced',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $subscriber Subscriber object.
		 * @param string $old_status Previous status.
		 * @return void
		 */
		public function trigger_listener( $subscriber, $old_status ) {
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

			// Get recent email data for subscriber.
			if ( property_exists( $subscriber, 'id' ) && class_exists( 'FluentCrm\App\Models\CampaignEmail' ) ) {
				$recent_email = \FluentCrm\App\Models\CampaignEmail::where( 'subscriber_id', $subscriber->id )
					->orderBy( 'id', 'desc' )
					->first();
				if ( $recent_email && is_object( $recent_email ) && method_exists( $recent_email, 'toArray' ) ) {
					$context['email'] = $recent_email->toArray();
				}
			}

			// Get bounce reason from subscriber meta if available.
			if ( property_exists( $subscriber, 'id' ) && function_exists( 'fluentcrm_get_subscriber_meta' ) ) {
				$bounce_reason            = fluentcrm_get_subscriber_meta( $subscriber->id, 'reason', '' );
				$context['bounce_reason'] = $bounce_reason;
				
				// Get soft bounce count if available.
				$soft_bounce_count            = fluentcrm_get_subscriber_meta( $subscriber->id, '_soft_bounce_count', 0 );
				$context['soft_bounce_count'] = (int) $soft_bounce_count;
			}

			$context['old_status'] = $old_status;
			$context['new_status'] = 'bounced';
			$context['bounced_at'] = current_time( 'mysql' );

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
	EmailBouncedFluentCRM::get_instance();

endif;
