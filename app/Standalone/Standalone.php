<?php
namespace AIOSEO\DuplicatePost\Standalone;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the standalone components.
 *
 * @since 1.0.0
 */
class Standalone {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! is_admin() || wp_doing_cron() ) {
			return;
		}

		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminScripts' ] );
	}

	/**
	 * Enqueues the main JavaScript file.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue() {
		if (
			! aioseoDuplicatePost()->helpers->isScreenBase( 'post' ) ||
			! aioseoDuplicatePost()->access->hasCapability( 'aioseo_duplicate_post_can_clone' ) ||
			! aioseoDuplicatePost()->access->hasCapability( 'aioseo_duplicate_post_can_merge' )
		) {
			return;
		}

		aioseoDuplicatePost()->core->assets->load( 'src/vue/standalone/duplicate-post/main.js', [], aioseoDuplicatePost()->helpers->getVueData() );
	}

	/**
	 * Enqueues additional scripts needed in the admin for Duplicate Post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueueAdminScripts() {
		if (
			! aioseoDuplicatePost()->helpers->isScreenBase( 'edit' ) ||
			! aioseoDuplicatePost()->access->hasCapability( 'aioseo_duplicate_post_can_clone' ) ||
			! aioseoDuplicatePost()->access->hasCapability( 'aioseo_duplicate_post_can_merge' )
		) {
			return;
		}

		aioseoDuplicatePost()->core->assets->load( 'src/vue/standalone/duplicate-post/handleCustomCheckbox.js', [], aioseoDuplicatePost()->helpers->getVueData() );
	}
}