<?php
/**
 * BuddyPress core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\BuddyPress;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\BuddyPress
 */
class BuddyPress extends Integrations {


	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'BuddyPress';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'BuddyPress', 'suretriggers' );
		$this->description = __( 'A WordPress plugin that lets you gamify your WordPress website.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/buddypress.png';
		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return class_exists( 'BuddyPress' );
	}

}

IntegrationsController::register( BuddyPress::class );
