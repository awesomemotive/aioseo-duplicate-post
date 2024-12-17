<?php
namespace AIOSEO\DuplicatePost\Admin\Notices;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Review plugin notice.
 *
 * @since 1.0.0
 */
class Review {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_aioseo-duplicate-post-dismiss-review-plugin-cta', [ $this, 'dismissNotice' ] );
	}

	/**
	 * Go through all the checks to see if we should show the notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function maybeShowNotice() {
		// Don't show to users that cannot interact with the plugin.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		if ( aioseoDuplicatePost()->admin->isDuplicatePostScreen() ) {
			return;
		}

		$dismissed = get_user_meta( get_current_user_id(), '_aioseo_duplicate_post_plugin_review_dismissed', true );
		if ( '3' === $dismissed || '4' === $dismissed ) {
			return;
		}

		if ( ! empty( $dismissed ) && $dismissed > time() ) {
			return;
		}

		// Show once plugin has been active for 2 weeks.
		if ( ! aioseoDuplicatePost()->internalOptions->internal->firstActivated ) {
			aioseoDuplicatePost()->internalOptions->internal->firstActivated = time();
		}

		$activated = aioseoDuplicatePost()->internalOptions->internal->firstActivated( time() );
		if ( $activated > strtotime( '-2 weeks' ) ) {
			return;
		}

		$this->showNotice();

		// Print the script to the footer.
		add_action( 'admin_footer', [ $this, 'printScript' ] );
	}

	/**
	 * Actually show the review plugin 2.0.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function showNotice() {
		$string1 = sprintf(
			// Translators: 1 - The plugin name ("Duplicate Post").
			__( 'Hey, we noticed you have been using %1$s for some time - that’s awesome! Could you please do us a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'duplicate-post-page-aioseo' ), // phpcs:ignore Generic.Files.LineLength.MaxExceeded
			'<strong>' . esc_html( AIOSEO_DUPLICATE_POST_PLUGIN_NAME ) . '</strong>'
		);

		$allowedHtml = [
			'strong' => [],
		];

		?>
		<div class="notice notice-info aioseo-duplicate-post-review-plugin-cta is-dismissible">
			<div class="step-3">
				<p><?php echo wp_kses( $string1, $allowedHtml ); ?></p>
				<p>
					<?php // phpcs:ignore Generic.Files.LineLength.MaxExceeded ?>
					<a href="https://wordpress.org/support/plugin/duplicate-post-page-aioseo/reviews/?filter=5#new-post" class="aioseo-duplicate-post-dismiss-review-notice" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Ok, you deserve it', 'duplicate-post-page-aioseo' ); ?>
					</a>&nbsp;&bull;&nbsp;
					<a href="#" class="aioseo-duplicate-post-dismiss-review-notice-delay" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'Nope, maybe later', 'duplicate-post-page-aioseo' ); ?>
					</a>&nbsp;&bull;&nbsp;
					<a href="#" class="aioseo-duplicate-post-dismiss-review-notice" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e( 'I already did', 'duplicate-post-page-aioseo' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Print the script for dismissing the notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function printScript() {
		aioseoDuplicatePost()->core->assets->load( 'src/vue/standalone/review-notice/index.js', [], aioseoDuplicatePost()->helpers->getVueData() );
	}

	/**
	 * Dismiss the review plugin CTA.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function dismissNotice() {
		// Early exit if we're not on a aioseo-duplicate-post-dismiss-review-plugin-cta action.
		if ( ! isset( $_POST['action'] ) || 'aioseo-duplicate-post-dismiss-review-plugin-cta' !== $_POST['action'] ) {
			return;
		}

		check_ajax_referer( 'aioseo-duplicate-post-dismiss-review', 'nonce' );
		$delay = isset( $_POST['delay'] ) ? 'true' === sanitize_text_field( wp_unslash( $_POST['delay'] ) ) : false; // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized
		$relay = isset( $_POST['relay'] ) ? 'true' === sanitize_text_field( wp_unslash( $_POST['relay'] ) ) : false; // phpcs:ignore HM.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! $delay ) {
			update_user_meta( get_current_user_id(), '_aioseo_duplicate_post_plugin_review_dismissed', $relay ? '4' : '3' );

			wp_send_json_success();

			return;
		}

		update_user_meta( get_current_user_id(), '_aioseo_duplicate_post_plugin_review_dismissed', strtotime( '+1 week' ) );

		wp_send_json_success();
	}
}