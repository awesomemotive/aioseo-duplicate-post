<?php
namespace AIOSEO\DuplicatePost\Traits\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Models;

/**
 * Generates the data we need for Vue.
 *
 * @since 1.0.0
 */
trait Vue {
	/**
	 * The data to pass to Vue.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $vueData = [];

	/**
	 * Returns the data for Vue.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $currentPage The current page.
	 * @return array               The data.
	 */
	public function getVueData( $currentPage = null ) {
		global $wp_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps

		static $showNotificationsDrawer = null;
		if ( null === $showNotificationsDrawer ) {
			$showNotificationsDrawer = aioseoDuplicatePost()->core->cache->get( 'show_notifications_drawer' ) ? true : false;

			// IF this is set to true, let's disable it now so it doesn't pop up again.
			if ( $showNotificationsDrawer ) {
				aioseoDuplicatePost()->core->cache->delete( 'show_notifications_drawer' );
			}
		}

		$this->vueData = [
			// The following data is needed on all screens.
			'wpVersion'           => $wp_version, // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
			'page'                => $currentPage,
			'postId'              => get_the_ID(),
			'screen'              => aioseoDuplicatePost()->helpers->getCurrentScreen(),
			'internalOptions'     => aioseoDuplicatePost()->internalOptions->all(),
			'options'             => aioseoDuplicatePost()->options->all(),
			'settings'            => aioseoDuplicatePost()->vueSettings->all(),
			'notifications'       => array_merge( Models\Notification::getNotifications( false ), [ 'force' => $showNotificationsDrawer ] ),
			'helpPanel'           => [],
			'urls'                => [
				'domain'        => $this->getSiteDomain(),
				'mainSiteUrl'   => $this->getSiteUrl(),
				'home'          => home_url(),
				'restUrl'       => rest_url(),
				'adminUrl'      => admin_url( 'admin-ajax.php' ),
				'editScreen'    => admin_url( 'edit.php' ),
				'publicPath'    => aioseoDuplicatePost()->core->assets->normalizeAssetsHost( plugin_dir_url( AIOSEO_DUPLICATE_POST_FILE ) ),
				'assetsPath'    => aioseoDuplicatePost()->core->assets->getAssetsPath(),
				'marketingSite' => $this->getMarketingSiteUrl(),
				'connect'       => admin_url( 'index.php?page=duplicate-post-connect' )
			],
			'user'                => [
				'capabilities'   => aioseoDuplicatePost()->access->getAllCapabilities(),
				'data'           => wp_get_current_user(),
				'locale'         => function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
				'unfilteredHtml' => current_user_can( 'unfiltered_html' )
			],
			'isDev'               => $this->isDev(),
			'isSsl'               => is_ssl(),
			'isMultisite'         => is_multisite(),
			'isNetworkAdmin'      => is_network_admin(),
			'mainSite'            => is_main_site(),
			'hasUrlTrailingSlash' => '/' === user_trailingslashit( '' ),
			'nonce'               => wp_create_nonce( 'wp_rest' ),
			'dismissNonce'        => wp_create_nonce( 'aioseo-duplicate-post-dismiss-review' ),
			'translations'        => $this->getJedLocaleData( 'duplicate-post-page-aioseo' ),
			'compareLink'         => $this->getCompareLink(),
		];

		switch ( $currentPage ) {
			case 'about':
				$this->addAboutData();
				break;
			case 'duplicator':
				$this->addDuplicatePostData();
				break;
			default:
				break;
		}

		return $this->vueData;
	}

	/**
	 * Adds additional data for Duplicate Post Plugin
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function addDuplicatePostData() {
		$this->vueData += [
			'plugins'      => [
				'isAioseoActive' => function_exists( 'aioseo' ),
			],
			'postElements' => $this->getPostElements(),
			'showLinks'    => $this->getLinks(),
			'postTypes'    => $this->getPublicPostTypes( false, false, true ),
			'postStatuses' => $this->getPublicPostStatuses(),
			'roles'        => $this->getAllRegisteredRoles( true ),
			'taxonomies'   => $this->getAllTaxonomies()
		];
	}

	/**
	 * Get the compare link for a post that is pending merge.
	 *
	 * @return mixed The compare link.
	 */
	private function getCompareLink() {

		$postId = get_the_ID();

		if ( aioseoDuplicatePost()->helpers->isScreenBase( 'post' ) &&
			aioseoDuplicatePost()->helpers->checkCapability( 'aioseo_duplicate_post_can_merge' ) ) {
			// Check if the post is a revision
			if ( get_post_meta( $postId, '_aioseo_revision', true ) ) {

				$originalPostId = get_post_meta( $postId, '_aioseo_original', true );

				// Check if the original post exists
				if ( $originalPostId ) {
					$originalPost = get_post( $originalPostId );

					if ( $originalPost && ! is_wp_error( $originalPost ) ) {
						return aioseoDuplicatePost()->main->hooks->linkBuilder->generateComparePostsLink( $postId, $originalPostId );
					}
				}
			}
		}

		return null;
	}

	/**
	 * Adds the data for the About Us screen.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	private function addAboutData() {
		$this->vueData['plugins'] = $this->getPluginData();
	}

	/**
	 * Returns Jed-formatted localization data.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $domain The text domain.
	 * @return array          The information of the locale.
	 */
	private function getJedLocaleData( $domain ) {
		$translations = get_translations_for_domain( $domain );

		$locale = [
			'' => [
				'domain' => $domain,
				'lang'   => is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale(),
			],
		];

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			if ( empty( $entry->translations ) || ! is_array( $entry->translations ) ) {
				continue;
			}

			$locale[ $msgid ] = $entry->translations;
		}

		return $locale;
	}

	/**
	 * Returns the marketing site URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string The marketing site URL.
	 */
	private function getMarketingSiteUrl() {
		if ( defined( 'AIOSEO_MARKETING_SITE_URL' ) && AIOSEO_MARKETING_SITE_URL ) {
			return AIOSEO_MARKETING_SITE_URL;
		}

		return 'https://aioseo.com/';
	}
}