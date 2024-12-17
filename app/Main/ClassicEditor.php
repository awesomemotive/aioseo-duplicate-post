<?php
namespace AIOSEO\DuplicatePost\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the post/page edit screen notices and other stuff regarding Classic Editor.
 *
 * @since 1.0.0
 */
class ClassicEditor {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'current_screen', [ $this, 'checkScreenAndAddHooks' ] );
		}
	}

	/**
	 * Check if we're on the Post/Page edit screen and display notices.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function checkScreenAndAddHooks() {
		if ( aioseoDuplicatePost()->helpers->isScreenBase( 'post' ) ) {
			add_action( 'admin_notices', [ $this, 'displayNotices' ] );
			add_action( 'post_submitbox_misc_actions', [ $this, 'addCompareLink' ], 90 );
			add_action( 'load-post.php', [ $this, 'addTranslations' ] );
		}
	}

	/**
	 * Hooks the functions to change the translations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addTranslations() {
		add_filter( 'gettext', [ $this, 'translatePublishStrings' ], 10, 3 );
		add_filter( 'gettext_with_context', [ $this, 'translateScheduleStrings' ], 10, 4 );
	}

	/**
	 * Display a notice if specific metadata exists for the current post.
	 *
	 * @since 1.0.0
	 *
	 * @param int|null $postId The post ID to check for metadata if using this function via API.
	 *
	 * @return string|null The HTML output for the notice.
	 */
	public function displayNotices( $postId = null ) {
		global $post;
		if ( $postId ) {
			$post = get_post( $postId );
			if ( $post ) {
				setup_postdata( $post );
			}
		}

		if ( ! $post ) {
			return;
		}

		// Strings for the notices.
		$strings = [
			'is_revision'              => __( 'Feel free to start revising this post. This is a copy of the original so no changes will happen to your original post until you click "Merge".', 'duplicate-post-page-aioseo' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
			'merge_success_notice'     => __( '😎 Looking good! Your revision has been updated to the original post and you are now all set to publish.', 'duplicate-post-page-aioseo' ),
			'scheduled_success_notice' => __( '😎 Looking good! Your revision has been scheduled to merge with the original post.', 'duplicate-post-page-aioseo' )
		];

		$html = '';
		$notices = maybe_unserialize( get_option( 'aioseo_duplicate_post_notices' ) );

		if ( $postId && aioseoDuplicatePost()->main->hooks->functions->isRevision( $post ) ) {
			return $strings['is_revision'];
		}

		// Generate notice for Classic Editor.
		#phpcs:disable
		if ( aioseoDuplicatePost()->main->hooks->functions->isRevision( $post ) ) {
			if ( isset( $notices[ $postId ]['revision'] ) && 'dismissed' !== $notices[ $postId ]['revision'] ) {
				$html .= '<div class="notice notice-warning is-dismissible">';
				$html .= '<p>' . esc_html( $strings['is_revision'] ) . '</p>';
				$html .= '</div>';
			}			
		} elseif ( isset( $_GET['merged'] ) ) {
			if ( isset( $notices[ $postId ]['merged'] ) && 'dismissed' !== $notices[ $postId ]['merged'] ) {
				$html .= '<div class="notice notice-success is-dismissible">';
				$html .= '<p>' . esc_html( $strings['merge_success_notice'] ) . '</p>';
				$html .= '</div>';
			}
		} elseif ( isset( $_GET['scheduled-merge'] ) ) {
			if ( isset( $notices[ $postId ]['scheduled'] ) && 'dismissed' !== $notices[ $postId ]['scheduled'] ) {
				$html .= '<div class="notice notice-success is-dismissible">';
				$html .= '<p>' . esc_html( $strings['scheduled_success_notice'] ) . '</p>';
				$html .= '</div>';
			}
		}
		#phpcs:enable

		echo wp_kses_post( $html );
	}

	/**
	 * Adds the Compare button to the Classic Editor.
	 *
	 * @param \WP_Post|null $post The post object that's being edited.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addCompareLink( $post = null ) {
		if ( ! $post ) {
			return;
		}

		$originalPostId = get_post_meta( $post->ID, '_aioseo_original', true );

		// Check if it's a revision and if the user has the capability to merge.
		if ( aioseoDuplicatePost()->main->hooks->functions->isRevision( $post ) && aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_merge' ) ) {
			?>
				<div id="check-changes-action" class="misc-pub-section">
					<?php esc_html_e( 'Do you want to compare your changes with the original version before merging? Please save any changes first.', 'duplicate-post-page-aioseo' ); ?>
					<br><br>
					<a class='button' href=<?php echo esc_url( aioseoDuplicatePost()->main->hooks->linkBuilder->generateComparePostsLink( $post->ID, $originalPostId ) ); ?> >
						<?php esc_html_e( 'Compare', 'duplicate-post-page-aioseo' ); ?>
					</a>
				</div>
				<?php
		}
	}

	/**
	 * Changes the 'Publish' copies in the submitbox to 'Merge' if a post is intended for merging.
	 *
	 * @param string $translation The translated text.
	 * @param string $text        The text to translate.
	 * @param string $domain      The translation domain.
	 *
	 * @since 1.0.0
	 *
	 * @return string The to-be-used copy of the text.
	 */
	public function translatePublishStrings( $translation, $text, $domain ) {
		if ( 'default' !== $domain ) {
			return $translation;
		}

		if ( aioseoDuplicatePost()->main->hooks->functions->isRevision( get_post() ) ) {
			if ( 'Publish' === $text ) {
				return esc_html__( 'Merge', 'duplicate-post-page-aioseo' );
			} elseif ( 'Publish on: %s' === $text ) {
				#phpcs:disable
				/* Translators: %s: Post title. */
				return esc_html__( 'Merge on: %s', 'duplicate-post-page-aioseo' );
				#phpcs:enable
			}
		}

		return $translation;
	}

	/**
	 * Changes the 'Schedule' copy in the submitbox to 'Schedule Merge' if a post is intended for merging.
	 *
	 * @param string $translation The translated text.
	 * @param string $text        The text to translate.
	 * @param string $context     The translation context.
	 * @param string $domain      The translation domain.
	 *
	 * @return string The to-be-used copy of the text.
	 */
	public function translateScheduleStrings( $translation, $text, $context, $domain ) {
		if ( 'default' !== $domain || 'post action/button label' !== $context ) {
			return $translation;
		}

		if ( 'Schedule' === $text && aioseoDuplicatePost()->main->hooks->functions->isRevision( get_post() ) ) {
			return esc_html__( 'Schedule republish', 'duplicate-post-page-aioseo' );
		}

		return $translation;
	}
}