<?php
/**
 * AffiliateWP core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\AffiliateWP;

use Affiliate_WP;
use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\AffiliateWP
 */
class AffiliateWP extends Integrations {

	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'AffiliateWP';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'AffiliateWP', 'suretriggers' );
		$this->description = __( 'Affiliate Plugin for WordPress.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/affiliatewp.svg';

		parent::__construct();
	}

	/**
	 * Is Plugin dependent plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return class_exists( Affiliate_WP::class );
	}

}

IntegrationsController::register( AffiliateWP::class );
