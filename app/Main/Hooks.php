<?php
namespace AIOSEO\DuplicatePost\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin hooks.
 *
 * @since 1.0.0
 */
class Hooks {
	use Traits\AdminBar;
	use Traits\ColumnOriginalPost;
	use Traits\CustomMetabox;

	/**
	 * Link Builder instance.
	 *
	 * @since 1.0.0
	 *
	 * @var LinkBuilder
	 */
	public $linkBuilder;

	/**
	 * Main functions instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Functions
	 */
	public $functions;

	/**
	 * Classic Editor hooks.
	 *
	 * @since 1.0.0
	 *
	 * @var ClassicEditor
	 */
	public $classicEditor;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->linkBuilder   = new LinkBuilder();
		$this->functions     = new Functions();
		$this->classicEditor = new ClassicEditor();

		if ( is_admin() ) {
			add_action( 'current_screen', [ $this, 'initSpecificScreenHooks' ] );

			if ( in_array( 'duplicate', aioseoDuplicatePost()->options->general->showLinks->included, true ) ) {
				add_filter( 'post_row_actions', [ $this, 'addCloneLink' ], 10, 2 );
				add_filter( 'page_row_actions', [ $this, 'addCloneLink' ], 10, 2 );

				if ( aioseoDuplicatePost()->options->general->postTypes->all ) {
					$postTypes = aioseoDuplicatePost()->helpers->getPublicPostTypes( false, false, true );
					foreach ( $postTypes as $postType ) {
						if ( 'attachment' === $postType['slug'] ) {
							continue;
						}
						add_filter( 'bulk_actions-edit-' . $postType['slug'], [ $this, 'registerCloneBulkAction' ] );
						add_filter( 'handle_bulk_actions-edit-' . $postType['slug'], [ $this, 'handleCloneBulkAction' ], 10, 3 );
					}
				} else {
					foreach ( aioseoDuplicatePost()->options->general->postTypes->included as $postType ) {
						add_filter( 'bulk_actions-edit-' . $postType, [ $this, 'registerCloneBulkAction' ] );
						add_filter( 'handle_bulk_actions-edit-' . $postType, [ $this, 'handleCloneBulkAction' ], 10, 3 );
					}
				}
			}

			if ( in_array( 'merge', aioseoDuplicatePost()->options->general->showLinks->included, true ) ) {
				add_filter( 'post_row_actions', [ $this, 'addRevisionLink' ], 10, 2 );
				add_filter( 'page_row_actions', [ $this, 'addRevisionLink' ], 10, 2 );

				if ( aioseoDuplicatePost()->options->general->postTypes->all ) {
					$postTypes = aioseoDuplicatePost()->helpers->getPublicPostTypes( false, false, true );
					foreach ( $postTypes as $postType ) {
						if ( 'attachment' === $postType['slug'] ) {
							continue;
						}
						add_filter( 'bulk_actions-edit-' . $postType['slug'], [ $this, 'registerRevisionBulkAction' ] );
						add_filter( 'handle_bulk_actions-edit-' . $postType['slug'], [ $this, 'handleRevisionBulkAction' ], 10, 3 );
					}
				} else {
					foreach ( aioseoDuplicatePost()->options->general->postTypes->included as $postType ) {
						add_filter( 'bulk_actions-edit-' . $postType, [ $this, 'registerRevisionBulkAction' ] );
						add_filter( 'handle_bulk_actions-edit-' . $postType, [ $this, 'handleRevisionBulkAction' ], 10, 3 );
					}
				}
			}

			add_filter( 'display_post_states', [ $this, 'customPostStatuses' ], 10, 2 );

			$this->initColumnOriginalPostHooks();
		}

		// Handle custom actions
		add_action( 'admin_init', [ $this, 'handleCloneAction' ] );
		add_action( 'admin_init', [ $this, 'handleAddRevisionAction' ] );

		add_action( 'admin_menu', [ $this, 'createComparePage' ] );
		add_action( 'admin_menu', [ $this, 'hideComparePage' ] );

		// Scheduled post
		add_action( 'future_to_publish', [ $this, 'mergeScheduledRevision' ] );

		// Add admin bar links
		$this->initAdminBarHook();
	}

	/**
	 * Initialize hooks that should be added on a specific screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function initSpecificScreenHooks() {
		if ( aioseoDuplicatePost()->helpers->isScreenBase( 'post' ) ) {
			add_action( 'save_post', [ $this, 'mergeRevision' ], 999, 2 );
			$this->initCustomMetaboxHooks();
		}
	}

	/**
	 * Add custom post statuses for the cloned posts and those ready for merging.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $postStatuses An array of post statuses.
	 * @param \WP_Post $name         The post object.
	 *
	 *
	 * @return array   $postStatuses The filtered array with the custom post statuses added.
	 */
	public function customPostStatuses( $postStatuses, $post ) {
		if ( get_post_meta( $post->ID, '_aioseo_clone', true ) ) {
			// Try to find the original post
			$originalPostId = $this->functions->hasOriginal( $post );
			if ( $originalPostId && aioseoDuplicatePost()->options->general->showOriginal->title ) {
				$originalPost = get_post( $originalPostId );
				$title = aioseoDuplicatePost()->helpers->truncateString( $originalPost->post_title );
				if ( get_post_status( $originalPost->ID ) === 'trash' ) {
					$postStatuses['aioseo_clone'] = $title;
				} else {
					$clonedText = '';
					// Check if user can get the edit post link
					$editPostLink = get_edit_post_link( $originalPost->ID );

					if ( $editPostLink ) {
						$clonedText = '<a href="' . esc_url( $editPostLink ) . '">' . $title . '</a>';
					} else {
						$clonedText = $title;
					}

					$postStatuses['aioseo_clone'] = sprintf(
					/* Translators: %s: Post title / and link. */
					esc_html__( 'Original: %s', 'duplicate-post-page-aioseo' ), $clonedText );
				}
			}
		}

		if ( get_post_meta( $post->ID, '_aioseo_revision', true ) ) {
			// Try to find the original post
			$originalPostId = $this->functions->hasOriginal( $post );
			if ( $originalPostId && aioseoDuplicatePost()->options->general->showOriginal->title ) {
				$originalPost = get_post( $originalPostId );
				$title = aioseoDuplicatePost()->helpers->truncateString( $originalPost->post_title );

				// Check if it's a scheduled merge
				$label = 'future' === get_post_status( $post->ID ) ? esc_html__( 'Scheduled', 'duplicate-post-page-aioseo' ) : esc_html__( 'Pending', 'duplicate-post-page-aioseo' );
				if ( get_post_status( $originalPost->ID ) === 'trash' ) {
					$postStatuses['aioseo_clone'] = $title;
				} else {

					$clonedText = '';
					// Check if user can get the edit post link
					$editPostLink = get_edit_post_link( $originalPost->ID );

					if ( $editPostLink ) {
						$clonedText = '<a href="' . esc_url( $editPostLink ) . '">' . $title . '</a>';
					} else {
						$clonedText = $title;
					}

					$postStatuses['aioseo_clone'] = sprintf(
					/* Translators: %s: Post title. */
					esc_html__( '%1$1s merge with: %2$2s', 'duplicate-post-page-aioseo' ), $label, $clonedText );
				}
			}
		}

		return $postStatuses;
	}

	/**
	 * Add bulk actions for cloning.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $bulks The bulk actions.
	 * @return mixed        The modified bulk action links.
	 */
	public function registerCloneBulkAction( $bulks ) {
		// Create a new array to hold the reordered bulk actions
		$newBulkActions = [];

		foreach ( $bulks as $key => $title ) {
			// Add the Clone action before the Trash action
			if ( 'trash' === $key ) {
				$newBulkActions['clone'] = esc_html__( 'Clone', 'duplicate-post-page-aioseo' );
			}
			// Add the existing action to the new array
			$newBulkActions[ $key ] = $title;
		}

		return $newBulkActions;
	}

	/**
	 * Add bulk actions for revisions.
	 *
	 * @since 1.0.0
	 *
	 * @param  mixed $bulks The bulk actions.
	 * @return mixed        The modified bulk action links.
	 */
	public function registerRevisionBulkAction( $bulks ) {
		// Create a new array to hold the reordered bulk actions
		$newBulkActions = [];

		foreach ( $bulks as $key => $title ) {
			// Add the Revisions action before the Trash action
			if ( 'trash' === $key ) {
				$newBulkActions['revision'] = esc_html__( 'Add Revision', 'duplicate-post-page-aioseo' );
			}
			// Add the existing action to the new array
			$newBulkActions[ $key ] = $title;
		}

		return $newBulkActions;
	}

	/**
	 * Add the clone link to the post/page row actions.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $actions  An array of row actions.
	 * @param \WP_Post $post     The post object.
	 *
	 * @return array   $array    The filtered array with the custom link added.
	 */
	public function addCloneLink( $actions, $post ) {
		if ( ! $post instanceof \WP_Post
			|| ! aioseoDuplicatePost()->helpers->shouldLinkBeDisplayed( $post, 'aioseo_duplicate_post_can_clone' )
			|| ! is_array( $actions )
			|| 'draft' === get_post_status( $post )
			) {
			return $actions;
		}

		$title = _draft_or_post_title( $post );
		$link  = $this->linkBuilder->generateLink( $post, 'aioseo_clone' );

		if ( $link ) {
			$actions['clone'] = '<a href="' . $link
			. '" aria-label="' . \esc_attr(
				/* Translators: %s: Post title. */
				sprintf( __( 'Clone &#8220;%s&#8221;', 'duplicate-post-page-aioseo' ), $title )
			) . '">'
			. esc_html_x( 'Clone', 'verb', 'duplicate-post-page-aioseo' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Add the revision link to the post/page row actions.
	 *
	 * @since 1.0.0
	 *
	 * @param array    $actions  An array of row actions.
	 * @param \WP_Post $post     The post object.
	 *
	 * @return array   $array    The filtered array with the custom link added.
	 */
	public function addRevisionLink( $actions, $post ) {

		if ( ! $post instanceof \WP_Post
			|| ! aioseoDuplicatePost()->helpers->shouldLinkBeDisplayed( $post, 'aioseo_duplicate_post_can_merge' )
			|| ! is_array( $actions )
			|| 'draft' === get_post_status( $post )
			|| ! current_user_can( 'edit_post', $post->ID )
			) {
			return $actions;
		}

		$title = _draft_or_post_title( $post );
		$link  = $this->linkBuilder->generateLink( $post, 'aioseo_revision' );

		if ( $link ) {
			$actions['revision'] = '<a href="' . $link
			. '" aria-label="' . \esc_attr(
				/* Translators: %s: Post title. */
				sprintf( __( 'Add Revision of &#8220;%s&#8221;', 'duplicate-post-page-aioseo' ), $title )
			) . '">'
			. esc_html_x( 'Add Revision', 'verb', 'duplicate-post-page-aioseo' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Handle the cloning action and clone the post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handleCloneAction() {
		// Check if the request parameters are set and verify nonce.
		if ( isset( $_REQUEST['action'] )
			&& isset( $_REQUEST['post'] )
			&& 'aioseo_clone' === $_REQUEST['action']
			&& isset( $_REQUEST['_wpnonce'] ) ) {

			if ( ! aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_clone' ) ) {
				wp_die( esc_html__( 'Permission to clone posts denied.', 'duplicate-post-page-aioseo' ) );
			}

			$postId = intval( $_REQUEST['post'] );

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) . '_' . $postId ) ) {
				wp_die( esc_html__( 'Security check failed.', 'duplicate-post-page-aioseo' ) );
			}

			$post = get_post( $postId );

			if ( ! $post || is_wp_error( $post ) ) {
				wp_die( esc_html__( 'Clone operation failed, cannot find the original post.', 'duplicate-post-page-aioseo' ) );
			}

			// Clone the post
			$newPostId = $this->functions->clonePost( $post, false );

			// Check if request needs to redirect to the post editor
			if ( isset( $_REQUEST['redirect'] ) && 'post_editor' === $_REQUEST['redirect'] ) {
				wp_safe_redirect( admin_url( 'post.php?post=' . $newPostId . '&action=edit' ) );
				exit;
			}

			wp_safe_redirect( wp_get_referer() );
			exit;
		}
	}

	/**
	 * Handle the adding of revision action and clone then redirect to the new post.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handleAddRevisionAction() {

		//Check if the request parameters are set and verify nonce
		if ( isset( $_REQUEST['action'] )
			&& isset( $_REQUEST['post'] )
			&& 'aioseo_revision' === $_REQUEST['action']
			&& isset( $_REQUEST['_wpnonce'] ) ) {

			if ( ! aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_merge' ) ) {
				wp_die( esc_html__( 'Permission to add revision of posts denied.', 'duplicate-post-page-aioseo' ) );
			}

			$postId = intval( $_REQUEST['post'] );

			// Check if the current user can edit the post
			if ( ! current_user_can( 'edit_post', $postId ) ) {
				wp_die( esc_html__( 'Permission to add revision of posts that are not create by this user is denied.', 'duplicate-post-page-aioseo' ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) . '_' . $postId ) ) {
				wp_die( esc_html__( 'Security check failed.', 'duplicate-post-page-aioseo' ) );
			}

			$post = get_post( $postId );

			if ( ! $post || is_wp_error( $post ) ) {
				wp_die( esc_html__( 'Adding of revision operation failed, cannot find the original post.', 'duplicate-post-page-aioseo' ) );
			}

			// Clone the post
			$newPostId = $this->functions->clonePost( $post, true );

			// Redirect to the new post
			wp_safe_redirect(
				add_query_arg(
					[
						'revision' => 1,
						'original' => $postId
					],
					admin_url( 'post.php?post=' . $newPostId . '&action=edit' )
				)
			);
			exit;
		}
	}

	/**
	 * Handle the bulk action for cloning a post.
	 *
	 * @since 1.0.0
	 *
	* @param  string $redirectTo  The redirect URL.
	* @param  string $doAction    The action being taken.
	* @param  array  $postIds     The IDs of the posts to take the action on.
	* @return string              The redirect URL.
	 */
	public function handleCloneBulkAction( $redirectTo, $doAction, $postIds ) {
		if ( 'clone' === $doAction ) {
			foreach ( $postIds as $postId ) {
				$post = get_post( $postId );

				if ( ! $post || is_wp_error( $post ) ) {
					wp_die( esc_html__( 'Clone operation failed, cannot find the original post.', 'duplicate-post-page-aioseo' ) );
				}

				$this->functions->clonePost( $post, false );
			}
			$redirectTo = add_query_arg( 'bulk_cloned_posts', count( $postIds ), $redirectTo );
		}

		return $redirectTo;
	}

	/**
	 * Handle the bulk action for adding a revision.
	 *
	 * @since 1.0.0
	 *
	* @param  string $redirectTo  The redirect URL.
	* @param  string $doAction    The action being taken.
	* @param  array  $postIds     The IDs of the posts to take the action on.
	* @return string              The redirect URL.
	 */
	public function handleRevisionBulkAction( $redirectTo, $doAction, $postIds ) {
		if ( 'revision' === $doAction ) {
			foreach ( $postIds as $postId ) {
				$post = get_post( $postId );

				if ( ! $post || is_wp_error( $post ) ) {
					wp_die( esc_html__( 'Adding of revision operation failed, cannot find the original post.', 'duplicate-post-page-aioseo' ) );
				}

				if ( current_user_can( 'edit_post', $postId ) ) {
					$this->functions->clonePost( $post, true );
				}
			}
			$redirectTo = add_query_arg( 'bulk_revision_posts', count( $postIds ), $redirectTo );
		}

		return $redirectTo;
	}

	/**
	 * Create compare page menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function createComparePage() {
		add_menu_page(
			'Compare Page',
			'Compare Page',
			'aioseo_duplicate_post_can_merge',
			'aioseo_compare_page',
			[ $this, 'comparePage' ]
		);
	}

	/**
	 * Hide the compare page from the menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hideComparePage() {
		remove_menu_page( 'aioseo_compare_page' );
	}

	/**
	 * Handle the compare action and output the page that shows differences between the original and the revision.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function comparePage() {
		//Check if the request parameters are set and verify nonce
		if ( isset( $_REQUEST['action'] )
			&& isset( $_REQUEST['post'] )
			&& isset( $_REQUEST['original_post_id'] )
			&& 'aioseo_compare' === $_REQUEST['action']
			&& isset( $_REQUEST['_wpnonce'] ) ) {

			if ( ! aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_merge' ) ) {
				wp_die( esc_html__( 'Permission to compare changes denied.', 'duplicate-post-page-aioseo' ) );
			}

			$postId         = intval( $_REQUEST['post'] );
			$originalPostId = intval( $_REQUEST['original_post_id'] );

			$currentPost = get_post( $postId );

			if ( ! $currentPost || is_wp_error( $currentPost ) ) {
				wp_die( esc_html__( 'Comparison operation failed, cannot find post.', 'duplicate-post-page-aioseo' ) );
			}

			// Check if the original post exists
			$originalPost = get_post( $originalPostId );
			if ( ! $originalPost || is_wp_error( $originalPost ) ) {
				wp_die( esc_html__( 'Comparison operation failed, cannot find the original post.', 'duplicate-post-page-aioseo' ) );
			}

			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) . '_' . $postId . '_' . $originalPostId ) ) {
				wp_die( esc_html__( 'Security check failed.', 'duplicate-post-page-aioseo' ) );
			}

			global $post;
			set_current_screen( 'aioseo_compare' );
			$post = $currentPost;

			global $wp_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
			?>
			<div class="wrap">
				<h1 class="long-header">
				<?php
					echo sprintf(
						/* translators: %s: original item link (to view or edit) or title. */
						esc_html__( 'Compare changes of revision post with the original (&#8220;%s&#8221;)', 'duplicate-post-page-aioseo' ),
						esc_url( get_edit_post_link( $originalPostId ) )
					);
				?>
					</h1>
				<a href="<?php echo esc_url( get_edit_post_link( $postId ) ); ?>"><?php esc_html_e( '&larr; Return to editor', 'duplicate-post-page-aioseo' ); ?></a>
				<div class="revisions">
					<div class="revisions-control-frame">
						<div class="revisions-controls"></div>
					</div>
					<div class="revisions-diff-frame">
						<div class="revisions-diff">
							<div class="diff">
							<?php
							$fields = [
								'post_title'   => __( 'Title', 'duplicate-post-page-aioseo' ),
								'post_content' => __( 'Content', 'duplicate-post-page-aioseo' ),
								'post_excerpt' => __( 'Excerpt', 'duplicate-post-page-aioseo' ),
							];

							$args = [
								'show_split_view' => true,
								'title_left'      => __( 'Removed', 'duplicate-post-page-aioseo' ),
								'title_right'     => __( 'Added', 'duplicate-post-page-aioseo' ),
							];

							if ( version_compare( $wp_version, '5.7' ) < 0 ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
								unset( $args['title_left'] );
								unset( $args['title_right'] );
							}

							$postData = get_post( $post, ARRAY_A );

							/** This filter is documented in wp-admin/includes/revision.php */
							$fields = apply_filters( '_wp_post_revision_fields', $fields, $postData );

							foreach ( $fields as $field => $name ) {
								/** This filter is documented in wp-admin/includes/revision.php */
								$contentFrom = apply_filters( "_wp_post_revision_field_{$field}", $originalPost->$field, $field, $originalPost, 'from' );

								/** This filter is documented in wp-admin/includes/revision.php */
								$contentTo = apply_filters( "_wp_post_revision_field_{$field}", $post->$field, $field, $post, 'to' );

								$diff = wp_text_diff( $contentFrom, $contentTo, $args );

								if ( ! $diff && 'post_title' === $field ) {
									// It's a better user experience to still show the Title, even if it didn't change.
									$diff  = '<table class="diff"><colgroup><col class="content diffsplit left"><col class="content diffsplit middle">
												<col class="content diffsplit right"></colgroup><tbody><tr>';
									$diff .= '<td>' . esc_html( $originalPost->post_title ) . '</td><td></td><td>' . esc_html( $post->post_title ) . '</td>';
									$diff .= '</tr></tbody>';
									$diff .= '</table>';
								}

								if ( $diff ) {
									?>
									<h3><?php echo esc_html( $name ); ?></h3>
									<?php
										$allowedHtml = [
											'div'      => [],
											'h1'       => [],
											'a'        => [
												'href'  => [],
												'title' => []
											],
											'h3'       => [],
											'table'    => [],
											'colgroup' => [],
											'col'      => [ 'class' => [] ],
											'tbody'    => [],
											'tr'       => [],
											'td'       => [],
										];
										echo wp_kses( $diff, $allowedHtml );
								}
							}
							?>

							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Handle the merging of the revision with the original post.
	 *
	 * @param int      $post_id The ID of the post being saved.
	 * @param \WP_Post $post    The post object.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function mergeRevision( $postId, $post ) {
		$this->maybeMerge( $postId, $post );
	}

	/**
	 * Handle the merging of a scheduled revision with the original post.
	 *
	 * @param \WP_Post $post    The post object that was scheduled.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function mergeScheduledRevision( $post ) {
		$this->maybeMerge( $post->ID, $post, true );
	}

	/**
	 * Perform checks before merging the posts, check if it's a scheduled merge or not.
	 *
	 * @param int      $post_id The ID of the post being saved.
	 * @param \WP_Post $post    The post object.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function maybeMerge( $postId, $post, $scheduled = false ) {

		// phpcs:disable
		if ( isset( $_POST['save'] ) && 'Save Draft' === $_POST['save'] ) {
			return;
		}

		if ( isset( $_GET['action'] ) && ( 'trash' === $_GET['action'] || 'untrash' === $_GET['action'] ) ) {
			return;
		}
		// phpcs:enable

		$isClassicActive = aioseoDuplicatePost()->helpers->isClassicEditorActive();
		$mergeReady      = get_post_meta( $postId, '_aioseo_merge_ready', true );

		// Don't merge if we are scheduling the post through the Classic Editor
		if ( $isClassicActive && 'future' === $post->post_status ) {
			return;
		}

		if ( ( ( $mergeReady || $isClassicActive ) && aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_merge' ) ) || $scheduled ) {
			// Check if the post is a revision
			if ( get_post_meta( $postId, '_aioseo_revision', true ) ) {

				$originalPostId = get_post_meta( $postId, '_aioseo_original', true );

				// Check if the original post exists
				if ( $originalPostId ) {
					$originalPost = get_post( $originalPostId );

					if ( $originalPost && ! is_wp_error( $originalPost ) ) {

						$originalPostId = $this->functions->mergePosts( $post, $originalPost );
						wp_delete_post( $postId, true );

						if ( ! $scheduled ) {
							// Safe redirect to the original post
							wp_safe_redirect(
								add_query_arg(
									[
										'merged' => 1
									],
									admin_url( 'post.php?post=' . $originalPostId . '&action=edit' )
								)
							);
							exit;
						}
					}
				}
			}
		}
	}
}