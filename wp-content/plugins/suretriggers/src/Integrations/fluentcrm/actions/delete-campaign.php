<?php
/**
 * DeleteCampaign.
 * php version 5.6
 *
 * @category DeleteCampaign
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
 * DeleteCampaign
 *
 * @category DeleteCampaign
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class DeleteCampaign extends AutomateAction {


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
	public $action = 'fluentcrm_delete_campaign';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Delete Campaign', 'suretriggers' ),
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
		
		if ( ! class_exists( 'FluentCrm\App\Models\Campaign' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not active.', 'suretriggers' ),
			];
		}

		$campaign_id = intval( $selected_options['campaign_id'] );
		$campaign    = Campaign::find( $campaign_id );

		if ( ! $campaign ) {
			return [
				'status'  => 'error',
				'message' => __( 'Campaign not found.', 'suretriggers' ),
			];
		}

		$context = [
			'campaign_id' => $campaign->id,
			'title'       => $campaign->title,
			'status'      => $campaign->status,
		];

		$campaign->deleteCampaignData();
		$campaign->delete();

		return [
			'success'          => true,
			'message'          => __( 'Campaign deleted successfully.', 'suretriggers' ),
			'deleted_campaign' => $context,
		];
	}

}

DeleteCampaign::get_instance();
