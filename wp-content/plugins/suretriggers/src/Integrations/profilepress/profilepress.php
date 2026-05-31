<?php
/**
 * ProfilePress integration class file
 *
 * @package  SureTriggers
 * @since 1.0.0
 */

namespace SureTriggers\Integrations\ProfilePress;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class ProfilePress
 *
 * @package SureTriggers\Integrations\ProfilePress
 */
class ProfilePress extends Integrations {

	use SingletonLoader;

	/**
	 * ID of the integration
	 *
	 * @var string
	 */
	protected $id = 'ProfilePress';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'ProfilePress', 'suretriggers' );
		$this->description = __( 'Modern WordPress Membership Plugin.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/profilepress.png';

		parent::__construct();
	}

	/**
	 * Check plugin is installed.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'PPRESS_VERSION_NUMBER' );
	}
}

IntegrationsController::register( ProfilePress::class );
