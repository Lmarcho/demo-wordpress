<?php
/**
 * Pretty Links core integrations file
 *
 * @since 1.1.9
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\PrettyLinks;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class PrettyLinks
 *
 * @package SureTriggers\Integrations\PrettyLinks
 */
class PrettyLinks extends Integrations {

	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'PrettyLinks';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'Pretty Links', 'suretriggers' );
		$this->description = __( 'Pretty Links is a WordPress plugin that allows you to create, manage, and track shortened links with detailed analytics and click tracking.', 'suretriggers' );
		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'PRLI_VERSION' );
	}
}

IntegrationsController::register( PrettyLinks::class );
