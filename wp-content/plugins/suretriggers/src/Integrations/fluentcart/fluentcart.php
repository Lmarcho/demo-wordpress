<?php
/**
 * FluentCart integration class file
 *
 * @package  SureTriggers
 * @since 1.0.0
 */

namespace SureTriggers\Integrations\FluentCart;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class FluentCart
 *
 * @package SureTriggers\Integrations\FluentCart
 */
class FluentCart extends Integrations {

	use SingletonLoader;

	/**
	 * ID of the integration
	 *
	 * @var string
	 */
	protected $id = 'FluentCart';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'FluentCart', 'suretriggers' );
		$this->description = __( 'Modern WordPress eCommerce Platform.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/fluentcart.svg';
		parent::__construct();
	}

	

	

	/**
	 * Is Plugin depended on plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return function_exists( 'fluentcart' ) || class_exists( 'FluentCart\App\App' );
	}
}

IntegrationsController::register( FluentCart::class );
