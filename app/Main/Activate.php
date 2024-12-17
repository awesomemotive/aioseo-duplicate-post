<?php
namespace AIOSEO\DuplicatePost\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin (de)activation.
 *
 * @since 1.0.0
 */
class Activate {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		register_activation_hook( AIOSEO_DUPLICATE_POST_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( AIOSEO_DUPLICATE_POST_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Runs on activation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function activate() {
		aioseoDuplicatePost()->access->addCapabilities();

		// Set the activation timestamps.
		$time = time();
		aioseoDuplicatePost()->internalOptions->internal->activated = $time;

		if ( ! aioseoDuplicatePost()->internalOptions->internal->firstActivated ) {
			aioseoDuplicatePost()->internalOptions->internal->firstActivated = $time;
		}
	}

	/**
	 * Runs on deactivation.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function deactivate() {
		aioseoDuplicatePost()->access->removeCapabilitiesForUnknownRoles();
	}
}