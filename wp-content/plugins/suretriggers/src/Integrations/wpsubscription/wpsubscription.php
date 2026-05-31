<?php
/**
 * WPSubscription core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\WPSubscription;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\WPSubscription
 */
class WPSubscription extends Integrations {

	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'WPSubscription';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'WPSubscription', 'suretriggers' );
		$this->description = __( 'WPSubscription for WooCommerce integration.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/wpsubscription.svg';

		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'WP_SUBSCRIPTION_VERSION' );
	}
}

IntegrationsController::register( WPSubscription::class );
