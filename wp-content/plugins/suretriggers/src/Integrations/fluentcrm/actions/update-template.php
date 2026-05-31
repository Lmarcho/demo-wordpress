<?php
/**
 * UpdateTemplate.
 * php version 5.6
 *
 * @category UpdateTemplate
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
 * UpdateTemplate
 *
 * @category UpdateTemplate
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UpdateTemplate extends AutomateAction {

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
	public $action = 'fluentcrm_update_template';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Update Email Template', 'suretriggers' ),
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
		// Check if FluentCRM is active.
		if ( ! class_exists( '\FluentCrm\App\Models\Template' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCRM is not installed or activated.', 'suretriggers' ), 
				
			];
		}

		$template_id = isset( $selected_options['template_id'] ) ? absint( $selected_options['template_id'] ) : 0;
		
		if ( ! $template_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Template ID is required.', 'suretriggers' ), 
				
			];
		}

		// Get the template.
		$template = \FluentCrm\App\Models\Template::find( $template_id );
		
		if ( ! $template ) {
			return [
				'status'  => 'error',
				'message' => __( 'Template not found.', 'suretriggers' ), 
				
			];
		}

		// Prepare template data.
		$template_data = [
			'post_title'    => isset( $selected_options['post_title'] ) ? sanitize_text_field( $selected_options['post_title'] ) : $template->post_title,
			'post_content'  => isset( $selected_options['post_content'] ) ? wp_kses_post( $selected_options['post_content'] ) : $template->post_content,
			'post_excerpt'  => isset( $selected_options['post_excerpt'] ) ? sanitize_textarea_field( $selected_options['post_excerpt'] ) : $template->post_excerpt,
			'email_subject' => isset( $selected_options['email_subject'] ) ? sanitize_text_field( $selected_options['email_subject'] ) : get_post_meta( $template->ID, '_email_subject', true ),
		];

		$edit_type = get_post_meta( $template->ID, '_edit_type', true );
		if ( ! $edit_type ) {
			$edit_type = 'html';
		}

		if ( isset( $selected_options['edit_type'] ) && ! empty( $selected_options['edit_type'] ) ) {
			$edit_type = sanitize_text_field( $selected_options['edit_type'] );
		}

		$design_template = get_post_meta( $template->ID, '_design_template', true );
		if ( ! $design_template ) {
			$design_template = 'simple';
		}

		if ( isset( $selected_options['design_template'] ) && ! empty( $selected_options['design_template'] ) ) {
			$design_template = sanitize_text_field( $selected_options['design_template'] );
		}

		// Update the template.
		$post_data = [
			'post_title'        => $template_data['post_title'],
			'post_content'      => $template_data['post_content'],
			'post_excerpt'      => $template_data['post_excerpt'],
			'post_modified'     => current_time( 'mysql' ),
			'post_modified_gmt' => gmdate( 'Y-m-d H:i:s' ),
		];

		\FluentCrm\App\Models\Template::where( 'ID', $template_id )->update( $post_data );

		// Update meta fields.
		update_post_meta( $template_id, '_email_subject', $template_data['email_subject'] );
		update_post_meta( $template_id, '_design_template', $design_template );
		update_post_meta( $template_id, '_edit_type', $edit_type );

		// Get the updated template.
		$updated_template = \FluentCrm\App\Models\Template::find( $template_id );

		if ( ! $updated_template ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to update template.', 'suretriggers' ), 
				
			];
		}

		return [
			'template_id'   => $template_id,
			'post_title'    => $updated_template->post_title,
			'post_content'  => $updated_template->post_content,
			'post_excerpt'  => $updated_template->post_excerpt,
			'email_subject' => get_post_meta( $template_id, '_email_subject', true ),
		];
	}
}

UpdateTemplate::get_instance();
