<?php
/**
 * SureDash core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\SureDash;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;


/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\SureDash
 */
class SureDash extends Integrations {


	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'SureDash';

	/**
	 * SureDash constructor.
	 */
	public function __construct() {
		$this->name        = __( 'SureDash', 'suretriggers' );
		$this->description = __( 'A comprehensive learning management system for creating and managing online courses.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/suredash.svg';

		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return class_exists( 'SureDashboard\Portals_Loader' ) || defined( 'SUREDASHBOARD_VER' );
	}

}

IntegrationsController::register( SureDash::class );
