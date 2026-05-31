<?php
/**
 * ListEmailTemplates.
 * php version 5.6
 *
 * @category ListEmailTemplates
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

/**
 * ListEmailTemplates
 *
 * @category ListEmailTemplates
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class ListEmailTemplates extends AutomateAction {

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
	public $action = 'fluentcrm_list_email_templates';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'List Email Templates', 'suretriggers' ),
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
	 *
	 * @return array|void|mixed
	 *
	 * @throws Exception Exception.
	 */
	public function _action_listener( $user_id, $automation_id, $fields, $selected_options ) {
		if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not installed or activated.', 'suretriggers' ), 
				
			];
		}

		$template_data = [];
		$templates     = \FluentCrm\App\Models\Template::emailTemplates( [ 'publish', 'draft' ] )->orderBy( 'ID', 'desc' )->get();
		
		foreach ( $templates as $template ) {
			$template_data[] = [
				'id'              => $template->ID,
				'title'           => $template->post_title,
				'email_subject'   => get_post_meta( $template->ID, '_email_subject', true ),
				'design_template' => get_post_meta( $template->ID, '_design_template', true ),
			];
		}

		return [
			'templates' => $template_data,
			'count'     => count( $template_data ),
		];
	}
}

ListEmailTemplates::get_instance();
