<?php
/**
 * TagCreated.
 * php version 5.6
 *
 * @category TagCreated
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

if ( ! class_exists( 'TagCreated' ) ) :

	/**
	 * TagCreated
	 *
	 * @category TagCreated
	 * @package  SureTriggers
	 * @author   BSF <username@example.com>
	 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
	 * @link     https://www.brainstormforce.com/
	 * @since    1.0.0
	 *
	 * @psalm-suppress UndefinedTrait
	 */
	class TagCreated {

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
		public $trigger = 'mailerpress_tag_created';

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
				'label'         => __( 'Tag Created', 'suretriggers' ),
				'action'        => $this->trigger,
				'common_action' => 'mailerpress_tag_created',
				'function'      => [ $this, 'trigger_listener' ],
				'priority'      => 10,
				'accepted_args' => 1,
			];

			return $triggers;
		}

		/**
		 * Trigger listener
		 *
		 * @param int $tag_id Tag ID.
		 * @return void
		 */
		public function trigger_listener( $tag_id ) {
			if ( empty( $tag_id ) ) {
				return;
			}
			AutomationController::sure_trigger_handle_trigger(
				[
					'trigger' => $this->trigger,
					'context' => $tag_id,
				]
			);
		}
	}

	/**
	 * Ignore false positive
	 *
	 * @psalm-suppress UndefinedMethod
	 */
	TagCreated::get_instance();

endif;
