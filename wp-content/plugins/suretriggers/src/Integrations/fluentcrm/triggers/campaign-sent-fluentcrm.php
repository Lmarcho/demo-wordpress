<?php
/**
 * CampaignSentFluentCRM.
 * php version 5.6
 *
 * @category CampaignSentFluentCRM
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\FluentCRM\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'CampaignSentFluentCRM' ) ) :

	/**
	 * CampaignSentFluentCRM
	 *
	 * @category CampaignSentFluentCRM
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class CampaignSentFluentCRM {


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
		public $trigger = 'campaign_sent_fluentcrm';

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
				'label'         => __( 'Campaign Sent', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'fluent_crm/campaign_archived',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param object $campaign Campaign object.
		 * @return void
		 */
		public function trigger_listener( $campaign ) {
			if ( empty( $campaign ) || ! is_object( $campaign ) ) {
				return;
			}

			$context = [];

			// Get campaign data.
			$campaign_data       = method_exists( $campaign, 'toArray' ) ? $campaign->toArray() : [];
			$context['campaign'] = $campaign_data;

			// Get campaign stats if available.
			if ( method_exists( $campaign, 'stats' ) ) {
				$campaign_stats   = $campaign->stats();
				$context['stats'] = $campaign_stats;
			}

			// Get total subscribers count for this campaign.
			if ( property_exists( $campaign, 'id' ) && class_exists( 'FluentCrm\App\Models\CampaignEmail' ) ) {
				$total_emails                 = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )->count();
				$context['total_emails_sent'] = $total_emails;

				// Get email stats.
				$sent_count    = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->where( 'status', 'sent' )
					->count();
				$opened_count  = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->where( 'is_open', '>', 0 )
					->count();
				$clicked_count = \FluentCrm\App\Models\CampaignEmail::where( 'campaign_id', $campaign->id )
					->where( 'click_counter', '>', 0 )
					->count();

				$context['sent_count']    = $sent_count;
				$context['opened_count']  = $opened_count;
				$context['clicked_count'] = $clicked_count;

				// Calculate rates.
				if ( $sent_count > 0 ) {
					$context['open_rate']  = round( ( $opened_count / $sent_count ) * 100, 2 );
					$context['click_rate'] = round( ( $clicked_count / $sent_count ) * 100, 2 );
				} else {
					$context['open_rate']  = 0;
					$context['click_rate'] = 0;
				}
			}

			$context['archived_at'] = current_time( 'mysql' );
			$context['status']      = 'archived';

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
	CampaignSentFluentCRM::get_instance();

endif;
