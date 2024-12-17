<?php
namespace AIOSEO\DuplicatePost\Main\Traits;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait CustomMetabox {

	/**
	 * Initialize specific hooks for the CustomMetaBox trait.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function initCustomMetaboxHooks() {
		add_action( 'add_meta_boxes', [ $this, 'addOriginalPostMetabox' ] );
	}

	/**
	 * Create a metabox for the original post where we will also have the option to remove it.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addOriginalPostMetabox() {
		global $post;
		$originalPostId = aioseoDuplicatePost()->main->hooks->functions->hasOriginal( $post );
		if (
			aioseoDuplicatePost()->options->general->showOriginal->metabox &&
			(
				aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_merge' ) ||
				aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_clone' )
			)
		) {
			if ( $originalPostId ) {
				add_meta_box(
					'original_post',
					__( 'Original Post:', 'duplicate-post-page-aioseo' ),
					[ $this, 'customMetaboxCallback' ],
					null,
					'side',
					'default'
				);
			}
		}
	}

	/**
	 * Output HTML for the custom metabox.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_POST $post The current post object.
	 * @return void
	 */
	public function customMetaboxCallback( $post ) {
		$originalPostId   = aioseoDuplicatePost()->main->hooks->functions->hasOriginal( $post );
		$originalPostLink = null;

		if ( $originalPostId ) {
			$originalPost = get_post( $originalPostId );
			$title = aioseoDuplicatePost()->helpers->truncateString( $originalPost->post_title );
			if ( get_post_status( $originalPost->ID ) === 'trash' ) {
				$originalPostLink = $title;
			} else {
				$originalPostLink = '<a href="' . esc_url( get_edit_post_link( $originalPost->ID ) ) . '">' . $title . '</a>';
			}

			$allowedHtml = [
				'a' => [
					'href' => [],
				],
			];

			?>
				<label for="aioseo_remove_original" style="display: flex; align-items: center; margin-bottom: 8px">
					<div><input type="checkbox" id="aioseo_remove_original" name="aioseo_remove_original" value="1" /></div>
					<div><?php esc_html_e( 'Delete reference to original post', 'duplicate-post-page-aioseo' ); ?></div>
				</label>
				<?php if ( $originalPostLink ) { ?>
					<span>
						<?php
							echo esc_html__( 'This was copied from:', 'duplicate-post-page-aioseo' );
						?>
					</span>
					<span>
						<?php
							echo wp_kses( $originalPostLink, $allowedHtml );
						?>
					</span>
				<?php } ?>
			<?php
		}
	}
}