<?php
/**
 * CreateEmailTemplate.
 * php version 5.6
 *
 * @category CreateEmailTemplate
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
 * CreateEmailTemplate
 *
 * @category CreateEmailTemplate
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class CreateEmailTemplate extends AutomateAction {


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
	public $action = 'fluentcrm_create_email_template';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {

		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Create Email Template', 'suretriggers' ),
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
		
		if ( ! class_exists( 'FluentCrm\App\Models\Template' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not installed or activated.', 'suretriggers' ),
			];
		}

		$template_data = [
			'post_title'   => sanitize_text_field( $selected_options['post_title'] ),
			'post_content' => wp_kses_post( $selected_options['post_content'] ),
			'post_excerpt' => isset( $selected_options['post_excerpt'] ) ? sanitize_textarea_field( $selected_options['post_excerpt'] ) : '',
			'post_type'    => 'fc_template',
			'post_status'  => 'publish',
		];

		$template_id = wp_insert_post( $template_data );

		if ( ! $template_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to create template.', 'suretriggers' ),
			];
		}

		$email_subject   = isset( $selected_options['email_subject'] ) ? sanitize_text_field( $selected_options['email_subject'] ) : '';
		$edit_type       = isset( $selected_options['edit_type'] ) ? sanitize_text_field( $selected_options['edit_type'] ) : 'html';
		$design_template = isset( $selected_options['design_template'] ) ? sanitize_text_field( $selected_options['design_template'] ) : 'simple';

		update_post_meta( $template_id, '_email_subject', $email_subject );
		update_post_meta( $template_id, '_edit_type', $edit_type );
		update_post_meta( $template_id, '_design_template', $design_template );

		return [
			'template_id'   => $template_id,
			'post_title'    => $template_data['post_title'],
			'post_content'  => $template_data['post_content'],
			'post_excerpt'  => $template_data['post_excerpt'],
			'email_subject' => $email_subject,
		];
	}

}

CreateEmailTemplate::get_instance();
