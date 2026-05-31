<?php
/**
 * CampaignCreated.
 * php version 5.6
 *
 * @category CampaignCreated
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\MailerPress\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;
use SureTriggers\Integrations\WordPress\WordPress;

if ( ! class_exists( 'CampaignCreated' ) ) :

	/**
	 * CampaignCreated
	 *
	 * @category CampaignCreated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class CampaignCreated {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'MailerPress';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'mailerpress_campaign_created';

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
				'label'         => __( 'Campaign Created', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'mailerpress_campaign_created',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int $campaign_id Campaign ID.
		 * @return void
		 */
		public function trigger_listener( $campaign_id ) {
			if ( empty( $campaign_id ) ) {
				return;
			}

			global $wpdb;

			// Get campaign details.
			$campaign = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM `' . esc_sql( $wpdb->prefix . 'mailerpress_campaigns' ) . '` WHERE campaign_id = %d',
					$campaign_id
				)
			);

			if ( ! $campaign ) {
				return;
			}

			$context             = WordPress::get_user_context( get_current_user_id() );
			$context['campaign'] = [
				'campaign_id' => (int) $campaign->campaign_id,
				'name'        => $campaign->name,
				'subject'     => $campaign->subject,
				'status'      => $campaign->status,
				'type'        => $campaign->type,
				'created_at'  => $campaign->created_at,
				'updated_at'  => $campaign->updated_at,
			];
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
	CampaignCreated::get_instance();

endif;
