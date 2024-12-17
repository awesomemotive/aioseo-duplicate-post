<?php
namespace AIOSEO\DuplicatePost\Traits\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Utils;

/**
 * Contains helper method that checks for specific permissions for the plugin.
 *
 * @since 1.0.0
 */
trait Permissions {

	/**
	 * Checks for permissions if the link should be displayed.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed     $post The post object.
	 * @param  string    $permission The permission name to check.
	 * @return bool
	 */
	public function shouldLinkBeDisplayed( $post, $permission ) {
		// Check if post type is allowed.
		if ( ! aioseoDuplicatePost()->options->general->postTypes->all ) {
			if ( ! in_array( $post->post_type, aioseoDuplicatePost()->options->general->postTypes->included, true ) ) {
				return false;
			}
		}

		// Check if the user has the capability to clone.
		if ( ! $this->checkCapability( $permission ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Checks if a user has the specific capability.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $cap The capability to check against.
	 * @return bool
	 */
	public function checkCapability( $cap ) {
		return current_user_can( $cap );
	}
}