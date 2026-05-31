<?php
/**
 * AddEmailToSequence.
 * php version 5.6
 *
 * @category AddEmailToSequence
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
 * AddEmailToSequence
 *
 * @category AddEmailToSequence
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class AddEmailToSequence extends AutomateAction {

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
	public $action = 'fluentcrm_add_email_to_sequence';

	use SingletonLoader;

	/**
	 * Register a action.
	 *
	 * @param array $actions actions.
	 * @return array
	 */
	public function register( $actions ) {
		$actions[ $this->integration ][ $this->action ] = [
			'label'    => __( 'Add Email to Sequence', 'suretriggers' ),
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
		// Check if FluentCampaign Pro is active.
		if ( ! class_exists( '\FluentCampaign\App\Models\Sequence' ) || ! class_exists( '\FluentCampaign\App\Models\SequenceMail' ) ) {
			return [
				'status'  => 'error',
				'message' => __( 'FluentCampaign Pro is not installed or activated.', 'suretriggers' ), 
				
			];
		}

		$sequence_id = isset( $selected_options['sequence_id'] ) ? absint( $selected_options['sequence_id'] ) : 0;
		
		if ( ! $sequence_id ) {
			return [
				'status'  => 'error',
				'message' => __( 'Sequence ID is required.', 'suretriggers' ), 
				
			];
		}

		$sequence = \FluentCampaign\App\Models\Sequence::find( $sequence_id );
		
		if ( ! $sequence ) {
			return [
				'status'  => 'error',
				'message' => __( 'Sequence not found.', 'suretriggers' ), 
				
			];
		}

		$default_template = 'simple';
		if ( function_exists( 'fluentcrm_get_option' ) ) {
			$default_template = fluentcrm_get_option( 'default_email_template', 'simple' );
		}

		$design_template = isset( $selected_options['design_template'] ) ? sanitize_text_field( $selected_options['design_template'] ) : $default_template;

		$sending_time = [ '', '' ];
		if ( isset( $selected_options['sending_start_time'] ) && isset( $selected_options['sending_end_time'] ) ) {
			$sending_time = [
				sanitize_text_field( $selected_options['sending_start_time'] ),
				sanitize_text_field( $selected_options['sending_end_time'] ),
			];
		}
		
		$allowed_days = [];
		if ( isset( $selected_options['allowed_days'] ) ) {
			if ( is_array( $selected_options['allowed_days'] ) ) {
				foreach ( $selected_options['allowed_days'] as $day ) {
					if ( isset( $day['value'] ) ) {
						$allowed_days[] = $day['value'];
					} else {
						$allowed_days[] = $day;
					}
				}
			} else {
				$allowed_days = [ $selected_options['allowed_days'] ];
			}
		}

		$template_config = [];
		
		if ( class_exists( '\FluentCrm\App\Services\Helper' ) ) {
			$helper_class = '\FluentCrm\App\Services\Helper';
			if ( is_callable( [ $helper_class, 'getTemplateConfig' ] ) ) {
				$template_config = $helper_class::getTemplateConfig( $design_template );
			}
		}

		$template_id   = isset( $selected_options['template_id'] ) ? absint( $selected_options['template_id'] ) : 0;
		$email_body    = '';
		$email_subject = '';

		if ( $template_id && class_exists( '\FluentCrm\App\Models\Template' ) ) {
			$template = \FluentCrm\App\Models\Template::find( $template_id );
			if ( $template ) {
				$email_body      = $template->post_content;
				$email_subject   = get_post_meta( $template->ID, '_email_subject', true );
				$design_template = get_post_meta( $template->ID, '_design_template', true ) ? get_post_meta( $template->ID, '_design_template', true ) : $design_template;
			}
		}

		if ( isset( $selected_options['email_body'] ) && ! empty( $selected_options['email_body'] ) ) {
			$email_body = wp_kses_post( $selected_options['email_body'] );
		}

		if ( isset( $selected_options['email_subject'] ) && ! empty( $selected_options['email_subject'] ) ) {
			$email_subject = sanitize_text_field( $selected_options['email_subject'] );
		}

		$email_data = [
			'title'            => $email_subject,
			'email_subject'    => $email_subject,
			'email_pre_header' => isset( $selected_options['email_pre_header'] ) ? sanitize_text_field( $selected_options['email_pre_header'] ) : '',
			'email_body'       => $email_body,
			'design_template'  => $design_template,
			'parent_id'        => $sequence_id,
			'template_id'      => $template_id,
			'settings'         => [
				'timings'         => [
					'delay_unit'         => isset( $selected_options['delay_unit'] ) ? sanitize_text_field( $selected_options['delay_unit'] ) : 'days',
					'delay'              => isset( $selected_options['delay'] ) ? absint( $selected_options['delay'] ) : 0,
					'is_anytime'         => isset( $selected_options['is_anytime'] ) ? sanitize_text_field( $selected_options['is_anytime'] ) : 'yes',
					'sending_time'       => $sending_time,
					'selected_days_only' => isset( $selected_options['selected_days_only'] ) ? sanitize_text_field( $selected_options['selected_days_only'] ) : 'no',
					'allowed_days'       => $allowed_days,
				],
				'template_config' => $template_config,
			],
		];
		
		$sequence_email = \FluentCampaign\App\Models\SequenceMail::create( $email_data );

		if ( ! $sequence_email ) {
			return [
				'status'  => 'error',
				'message' => __( 'Failed to add email to sequence.', 'suretriggers' ), 
				
			];
		}

		// Update mailer settings from the parent sequence.
		$mailer_settings = $sequence->settings['mailer_settings'];
		$sequence_email->updateMailerSettings( $mailer_settings );

		return [
			'sequence_id'       => $sequence_id,
			'sequence_email_id' => $sequence_email->id,
			'email_subject'     => $sequence_email->email_subject,
			'delay'             => $sequence_email->delay,
			'delay_unit'        => $email_data['settings']['timings']['delay_unit'],
			'template_id'       => $template_id,
			'design_template'   => $design_template,
			'message'           => 'Email successfully added to sequence',
		];
	}
}

AddEmailToSequence::get_instance();
