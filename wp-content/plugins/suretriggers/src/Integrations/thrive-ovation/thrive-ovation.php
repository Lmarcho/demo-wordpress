<?php
/**
 * ThriveOvation core integrations file
 *
 * @since 1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\ThriveOvation;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class SureTrigger
 *
 * @package SureTriggers\Integrations\ThriveOvation
 */
class ThriveOvation extends Integrations {

	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'ThriveOvation';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'Thrive Ovation', 'suretriggers' );
		$this->description = __( 'The all-in-one WordPress testimonial plugin to collect, manage, and showcase customer testimonials.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/images/thrive-ovation.svg';

		parent::__construct();
	}

	/**
	 * Is Plugin depended on plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'TVO_VERSION' );
	}
}

IntegrationsController::register( ThriveOvation::class );
