<?php
/**
 * WP Travel Engine core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\WPTravelEngine;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\WPTravelEngine
 */
class WPTravelEngine extends Integrations {


	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'WPTravelEngine';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'WP Travel Engine', 'suretriggers' );
		$this->description = __( 'WP Travel Engine is a complete travel booking WordPress plugin to create travel and tour packages.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/WPTravelEngine.svg';

		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'WP_TRAVEL_ENGINE_VERSION' );
	}

}

IntegrationsController::register( WPTravelEngine::class );
