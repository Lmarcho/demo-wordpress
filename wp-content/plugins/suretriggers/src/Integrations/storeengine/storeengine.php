<?php
/**
 * StoreEngine core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\StoreEngine;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\StoreEngine
 */
class StoreEngine extends Integrations {


	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'StoreEngine';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'StoreEngine', 'suretriggers' );
		$this->description = __( 'StoreEngine makes online sales easy with limitless possibilities. From marketing tools to a wide range of payment options.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/servicesforsurecart.svg';

		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'STOREENGINE_VERSION' );
	}
}

IntegrationsController::register( StoreEngine::class );
