<?php
/**
 * TestimonialSubmitted.
 * php version 5.6
 *
 * @category TestimonialSubmitted
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\ThriveOvation\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\ThriveOvation\ThriveOvation;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

if ( ! class_exists( 'TestimonialSubmitted' ) ) :

	/**
	 * TestimonialSubmitted
	 *
	 * @category TestimonialSubmitted
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class TestimonialSubmitted {

		/**
		 * Integration type.
		 *
		 * @var string
		 */
		public $integration = 'ThriveOvation';

		/**
		 * Trigger name.
		 *
		 * @var string
		 */
		public $trigger = 'thrive_testimonial_submitted';

		use SingletonLoader;

		/**
		 * Constructor
		 *
		 * @since  1.0.0
		 */
		public function __construct() {
			add_action( 'thrive_ovation_testimonial_submit', [ $this, 'trigger_listener' ], 10, 2 );
		}

		/**
		 * Register action.
		 *
		 * @param array $triggers trigger data.
		 * @return array
		 */
		public function register( $triggers ) {

			$triggers[ $this->integration ][ $this->trigger ] = [
				'label'         => __( 'A testimonial is submitted', 'suretriggers' ),
				'action'        => $this->trigger,
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 2,
				'description'   => __( 'Triggers when a user submits a testimonial through Thrive Ovation.', 'suretriggers' ),
			];
			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param array         $testimonial_data Testimonial data.
		 * @param \WP_User|null $user User object.
		 * @return void
		 */
		public function trigger_listener( $testimonial_data, $user ) {
			if ( empty( $testimonial_data ) ) {
				return;
			}
			$context = [
				'testimonial_data' => $testimonial_data,
				'user'             => $user,
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
	TestimonialSubmitted::get_instance();

endif;
