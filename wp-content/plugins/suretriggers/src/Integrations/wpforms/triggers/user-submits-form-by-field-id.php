<?php
/**
 * UserSubmitsFormFieldIdBased.
 * php version 5.6
 *
 * @category UserSubmitsFormFieldIdBased
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\Wpforms\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'UserSubmitsFormFieldIdBased' ) ) :

	/**
	 * UserSubmitsFormFieldIdBased
	 *
	 * @category UserSubmitsFormFieldIdBased
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class UserSubmitsFormFieldIdBased {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'WPForms';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'user_submits_wpform_field_id_based';

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
				'label'         => __( 'Form Submitted (Field ID Based)', 'suretriggers' ),
				'action'        => 'user_submits_wpform_by_field_id',
				'common_action' => 'wpforms_process_complete',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 4,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $fields Sanitized entry field values/properties.
		 * @param array $entry Original $_POST global.
		 * @param array $form_data Processed form settings/data, prepared to be used later.
		 * @param int   $entry_id Entry ID.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $fields, $entry, $form_data, $entry_id ) {
			if ( empty( $form_data ) ) {
				return;
			}

			$user_id                    = ap_get_current_user_id();
			$context                    = [];
			$context['form_id']         = (int) $form_data['id'];
			$context['form_title']      = isset( $form_data['settings'] ) ? $form_data['settings']['form_title'] : '';
			$context['submission_date'] = gmdate( 'd M Y', strtotime( $form_data['created'] ) );

			foreach ( $fields as $field ) {
				$field_id = 'field_' . $field['id'];
				
				if ( 'name' === $field['type'] ) {
					if ( ! empty( $field['first'] ) || ! empty( $field['middle'] ) || ! empty( $field['last'] ) ) {
						if ( ! empty( $field['first'] ) ) {
							$context[ 'field_' . $field['id'] . '_first' ] = $field['first'];
						}
						if ( ! empty( $field['middle'] ) ) {
							$context[ 'field_' . $field['id'] . '_middle' ] = $field['middle'];
						}
						if ( ! empty( $field['last'] ) ) {
							$context[ 'field_' . $field['id'] . '_last' ] = $field['last'];
						}
					} else {
						$context[ $field_id ] = $field['value'];
					}
				} else {
					$context[ $field_id ] = $field['value'];
				}
			}

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger'    => $this->trigger,
					'wp_user_id' => $user_id,
					'context'    => $context,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	UserSubmitsFormFieldIdBased::get_instance();

endif;
