<?php
/**
 * ClickWhale core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\ClickWhale;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class ClickWhale
 *
 * @package SureTriggers\Integrations\ClickWhale
 */
class ClickWhale extends Integrations {

	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'ClickWhale';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'ClickWhale', 'suretriggers' );
		$this->description = __( 'ClickWhale is a powerful link management and tracking plugin for WordPress that helps you create, manage, and track short links with detailed analytics.', 'suretriggers' );
		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return class_exists( 'ClickWhale\ClickWhale' ) || defined( 'CLICKWHALE_VERSION' );
	}
}

IntegrationsController::register( ClickWhale::class );
