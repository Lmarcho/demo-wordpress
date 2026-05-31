<?php
/**
 * SureDashSpaceDeleted trigger for handling space deletion events.
 * php version 5.6
 *
 * @category SureDashSpaceDeleted
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */

namespace SureTriggers\Integrations\SureDash\Triggers;

use SureTriggers\Controllers\AutomationController;
use SureTriggers\Integrations\WordPress\WordPress;
use SureTriggers\Traits\SingletonLoader;

/**
 * SureDashSpaceDeleted
 *
 * @category SureDashSpaceDeleted
 * @package  SureTriggers
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class SureDashSpaceDeleted {

	/**
	 * Integration type.
	 *
	 * @var string
	 */
	public $integration = 'SureDash';

	/**
	 * Trigger name.
	 *
	 * @var string
	 */
	public $trigger = 'suredash_space_deleted';

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
			'label'         => __( 'Space Deleted', 'suretriggers' ),
			'action'        => 'suredash_space_deleted',
			'function'      => [ $this, 'trigger_listener' ],
			'priority'      => 10,
			'accepted_args' => 1,
		];
		return $triggers;
	}

	/**
	 * Trigger listener for space deletion events.
	 *
	 * @param int $space_id Space ID.
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function trigger_listener( $space_id ) {
		if ( ! $space_id ) {
			return;
		}

		$context                 = [];
		$context['space_id']     = $space_id;
		$context['space_status'] = 'deleted';
		
		AutomationController::sure_trigger_handle_trigger(
			[
				'trigger' => $this->trigger,
				'context' => $context,
			]
		);
	}
}

SureDashSpaceDeleted::get_instance();
