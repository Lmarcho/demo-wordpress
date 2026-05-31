<?php
/**
 * UserCertificateVerified.
 * php version 5.6
 *
 * @category UserCertificateVerified
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ThriveApprentice\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\ThriveApprentice\ThriveApprentice;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'UserCertificateVerified' ) ) :

	/**
	 * UserCertificateVerified
	 *
	 * @category UserCertificateVerified
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 */
	class UserCertificateVerified {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'ThriveApprentice';

		/**
		 * Action name.
		 *
		 * @var string
		 */
		public $trigger = 'user_certificate_verified';

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
		 * Register a trigger.
		 *
		 * @param array $triggers triggers.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'User Certificate for Course is Verified', 'suretriggers' ),
				'action'        => 'user_certificate_verified',
				'common_action' => 'tva_certificate_verified',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 3,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array $certificate_data Certificate data.
		 * @param array $user_data User data.
		 * @param array $course_data Course data.
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function trigger_listener( $certificate_data, $user_data, $course_data ) {
			if ( empty( $certificate_data ) || empty( $user_data ) || empty( $course_data ) ) {
				return;
			}
			$context              = [];
			$context['course_id'] = $course_data['course_id'];
			$context              = [
				'certificate_data' => $certificate_data,
				'course_data'      => $course_data,
				'user_data'        => $user_data,
			];

			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $context,
				]
			);
		}
	}

	UserCertificateVerified::get_instance();

endif;
