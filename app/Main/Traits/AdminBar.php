<?php
namespace AIOSEO\DuplicatePost\Main\Traits;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait AdminBar {

	/**
	 * Initializes hooks specific to the admin bar.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function initAdminBarHook() {
		add_action( 'init', [ $this, 'init' ] );
	}

	public function init() {
		add_action( 'admin_bar_menu', [ $this, 'addAdminBarLinks' ], 1000 );
	}

	/**
	 * Add links to the admin bar.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Admin_Bar $wpAdminBar The WP_Admin_Bar instance.
	 */
	public function addAdminBarLinks( $wpAdminBar ) {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		if ( is_preview() || is_singular() ) {
			global $post;
			$logo = aioseoDuplicatePost()->helpers->icon( 16, 16, '#FFFFFF' );

			$canClone = aioseoDuplicatePost()->helpers->shouldLinkBeDisplayed( $post, 'aioseo_duplicate_post_can_clone' );
			$canMerge = aioseoDuplicatePost()->helpers->shouldLinkBeDisplayed( $post, 'aioseo_duplicate_post_can_merge' );

			if ( ( $canClone || $canMerge ) && 'draft' !== get_post_status( $post ) ) {
				$wpAdminBar->add_node( [
					'id'    => 'duplicate_post_menu',
					'title' => '<span class="ab-icon">' . $logo . '</span><span class="ab-label">Duplicate Post</span>',
					'href'  => '#',
				] );

				if ( $canClone ) {
					$wpAdminBar->add_node( [
						'id'     => 'clone_menu_link',
						'parent' => 'duplicate_post_menu',
						'title'  => esc_html__( 'Clone', 'duplicate-post-page-aioseo' ),
						'href'   => $this->linkBuilder->generateLink( $post, 'aioseo_clone', [ 'redirect' => 'post_editor' ] ),
					] );
				}

				if ( $canMerge ) {
					// Add second child link
					$wpAdminBar->add_node( [
						'id'     => 'add_revision_menu_link',
						'parent' => 'duplicate_post_menu',
						'title'  => esc_html__( 'Add Revision', 'duplicate-post-page-aioseo' ),
						'href'   => $this->linkBuilder->generateLink( $post, 'aioseo_revision' ),
					] );
				}
			}
		}
	}
}