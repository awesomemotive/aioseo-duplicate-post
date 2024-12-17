<?php
namespace AIOSEO\DuplicatePost\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Highlighter;
use AIOSEO\DuplicatePost\Links;
use AIOSEO\DuplicatePost\LinkStatus;
use AIOSEO\DuplicatePost\Models;

/**
 * Main class where core features are handled/registered.
 *
 * @since 1.0.0
 */
class Main {
	/**
	 * Hooks instance.
	 *
	 * @var Hooks
	 */
	public $hooks;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		new Activate();

		$this->hooks = new Hooks();
	}
}