<?php
namespace AIOSEO\DuplicatePost\Main\Traits;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait ColumnOriginalPost {

	/**
	 * Initializes hooks specific to the columns in the Post/Page List page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function initColumnOriginalPostHooks() {
		add_filter( 'manage_posts_columns', [ $this, 'addOriginalPostColumn' ], 10, 2 );
		add_filter( 'manage_pages_columns', [ $this, 'addOriginalPageColumn' ], 10, 1 );
		add_action( 'manage_posts_custom_column', [ $this, 'addOriginalPostToColumn' ], 10, 2 );
		add_action( 'manage_pages_custom_column', [ $this, 'addOriginalPostToColumn' ], 10, 2 );
		add_action( 'quick_edit_custom_box', [ $this, 'addRemoveOriginalCheckbox' ], 10, 2 );

		add_action( 'save_post', [ $this, 'removeOriginal' ], 10, 1 ); // We need this outside for Quick Edit to work
	}

	/**
	 * Add custom column if selected in the seetings to show original post to custom post types.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $columns  The existing columns.
	 * @param  array $postType The post type to add columns to.
	 * @return array           The modified columns.
	 */
	public function addOriginalPostColumn( $columns, $postType ) {
		if ( aioseoDuplicatePost()->options->general->showOriginal->column ) {
			if ( aioseoDuplicatePost()->options->general->postTypes->all || in_array( $postType, aioseoDuplicatePost()->options->general->postTypes->included, true ) ) {
				$columns['aioseo_original'] = __( 'Original Post', 'duplicate-post-page-aioseo' );
			}
		}

		return $columns;
	}

	/**
	 * Add custom column if selected in the settings to show original post to pages.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $columns  The existing columns.
	 * @param  array $postType The post type to add columns to.
	 * @return array           The modified columns.
	 */
	public function addOriginalPageColumn( $columns ) {
		if ( aioseoDuplicatePost()->options->general->showOriginal->column ) {
			if ( aioseoDuplicatePost()->options->general->postTypes->all || in_array( 'page', aioseoDuplicatePost()->options->general->postTypes->included, true ) ) {
				$columns['aioseo_original'] = __( 'Original Post', 'duplicate-post-page-aioseo' );
			}
		}

		return $columns;
	}

	/**
	 * Add the original post to the post/page columns.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $column The existing columns.
	 * @param  mixed $postId The current post ID.
	 * @return void          The modified columns.
	 */
	public function addOriginalPostToColumn( $column, $postId ) {
		if ( 'aioseo_original' === $column ) {
			$output = '-';
			$originalPostId = aioseoDuplicatePost()->main->hooks->functions->hasOriginal( $postId );
			if ( $originalPostId ) {
				$originalPost = get_post( $originalPostId );
				$title = aioseoDuplicatePost()->helpers->truncateString( $originalPost->post_title );
				if ( get_post_status( $originalPost->ID ) === 'trash' ) {
					$output = $title;
				} else {
					$title = aioseoDuplicatePost()->helpers->truncateString( $originalPost->post_title );
					$output = '<a href="' . esc_url( get_edit_post_link( $originalPost->ID ) )
							. '">' . $title . '</a>';
				}
			}

			$allowedHtml = [
				'a' => [
					'href' => [],
				],
			];

			echo wp_kses( $output, $allowedHtml );
		}
	}

	/**
	 * Add a custom checkbox for deleteing original post reference to the Quick Edit Form.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $columnName The column name.
	 * @return void
	 */
	public function addRemoveOriginalCheckbox( $columnName ) {
		if ( 'aioseo_original' === $columnName ) {
			?>
			<fieldset class="inline-edit-col-right aioseo_remove_original_fieldset" style="opacity: 0">
				<div class="inline-edit-col">
					<label class="alignleft">
						<input type="checkbox" name="aioseo_remove_original" value="1">
						<span class="checkbox-title"><?php esc_html_e( 'Remove Original Reference', 'duplicate-post-page-aioseo' ); ?></span>
					</label>
				</div>
			</fieldset>
			<?php
		}
	}

	/**
	 * Remove the reference to the original post if the checkbox is selected.
	 *
	 * @since 1.0.0
	 *
	 * @param  int  $postId The current post ID.
	 * @return void
	 */
	public function removeOriginal( $postId ) {
		// Check if this is an autosave or a revision.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		#phpcs:disable
		if ( isset( $_POST['aioseo_remove_original'] ) ) {
			delete_post_meta( $postId, '_aioseo_original' );
		}
		#phpcs:enable
	}
}