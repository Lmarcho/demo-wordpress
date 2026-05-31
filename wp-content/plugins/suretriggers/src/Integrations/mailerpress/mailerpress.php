<?php
/**
 * MailerPress core integrations file
 *
 * @since   1.0.0
 * @package SureTrigger
 */

namespace SureTriggers\Integrations\MailerPress;

use SureTriggers\Controllers\IntegrationsController;
use SureTriggers\Integrations\Integrations;
use SureTriggers\Traits\SingletonLoader;

/**
 * Class MailerPress
 *
 * @package SureTriggers\Integrations\MailerPress
 */
class MailerPress extends Integrations {

	use SingletonLoader;

	/**
	 * ID
	 *
	 * @var string
	 */
	protected $id = 'MailerPress';

	/**
	 * SureTrigger constructor.
	 */
	public function __construct() {
		$this->name        = __( 'MailerPress', 'suretriggers' );
		$this->description = __( 'MailerPress is a powerful email marketing and newsletter plugin for WordPress.', 'suretriggers' );
		$this->icon_url    = SURE_TRIGGERS_URL . 'assets/icons/mailerpress.png';
		parent::__construct();
	}

	/**
	 * Is Plugin depended plugin is installed or not.
	 *
	 * @return bool
	 */
	public function is_plugin_installed() {
		return defined( 'MAILERPRESS_VERSION' );
	}

}

IntegrationsController::register( MailerPress::class );
