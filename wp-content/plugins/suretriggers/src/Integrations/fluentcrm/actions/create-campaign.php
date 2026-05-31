<?php
/**
 * CreateCampaign.
 * php version 5.6
 *
 * @category CreateCampaign
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
use FluentCrm\App\Models\Campaign;


/**
 * CreateCampaign
 *
 * @category CreateCampaign
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateCampaign extends AutomateAction {


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
	public $action = 'fluentcrm_create_campaign';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Campaign', 'suretriggers' ),
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

		if ( ! class_exists( 'FluentCrm\App\Models\Campaign' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM Campaign model not available.', 'suretriggers' ),
			];
		}

		$campaign_name = isset( $selected_options['campaign_name'] ) ? sanitize_text_field( $selected_options['campaign_name'] ) : '';

		if ( empty( $campaign_name ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'Campaign name is required.', 'suretriggers' ),
			];
		}

		// Create campaign with minimal data.
		$campaign_data = [
			'title' => $campaign_name,
		];

		try {
			$campaign = Campaign::create( $campaign_data );

			if ( ! $campaign ) {
				return [
					'status'  => 'error',
					'message' => __( 'Failed to create campaign.', 'suretriggers' ),
				];
			}

			return [
				'status'          => 'success',
				'message'         => sprintf( __( 'Campaign "%s" created successfully.', 'suretriggers' ), $campaign_name ),
				'campaign_id'     => $campaign->id,
				'campaign_title'  => $campaign->title,
				'campaign_slug'   => $campaign->slug,
				'campaign_status' => $campaign->status,
				'campaign_type'   => isset( $campaign->type ) ? $campaign->type : 'campaign',
				'created_at'      => $campaign->created_at,
			];

		} catch ( Exception $e ) {
			return [
				'status'  => 'error',
				'message' => sprintf( __( 'Error creating campaign: %s', 'suretriggers' ), $e->getMessage() ),
			];
		}
	}

}

CreateCampaign::get_instance();
