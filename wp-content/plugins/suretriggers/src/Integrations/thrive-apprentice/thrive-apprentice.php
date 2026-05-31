<?php
/**
 * ThriveApprentice core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\ThriveApprentice;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\ThriveApprentice
 */
class ThriveApprentice extends Integrations {

	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'ThriveApprentice';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'Thrive Apprentice', 'suretriggers' );
		$this->description = __( 'Create online courses you can sell with the most customizable LMS plugin for WordPress.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/ThriveApprentice.svg';

		parent::__construct();
	}

	/**
	 * Is Plugin depended on plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'TVA_IS_APPRENTICE' ) || class_exists( 'TVA_Const' );
	}
}

IntegrationsController::register( ThriveApprentice::class );
