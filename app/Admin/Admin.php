<?php
namespace AIOSEO\DuplicatePost\Admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all general admin code.
 *
 * @since 1.0.0
 */
class Admin {
	/**
	 * The main page slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $pageSlug = 'duplicate-post';

	/**
	 * The current page.
	 * This gets set as soon as we've identified that we're on a Duplicate Post page.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $currentPage = '';

	/**
	 * An list of asset slugs to use.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $assetSlugs = [
		'pages' => 'src/vue/pages/{page}/main.js'
	];

	/**
	 * The plugin basename.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $plugin = '';

	/**
	 * The list of pages.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $pages = [];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'registerMenu' ] );
		add_action( 'admin_menu', [ $this, 'hideScheduledActionsMenu' ], 999 );
		add_filter( 'language_attributes', [ $this, 'addDirAttribute' ], 3000 );

		// add_filter( 'plugin_row_meta', [ $this, 'registerRowMeta' ], 10, 2 );
		add_filter( 'plugin_action_links_' . AIOSEO_DUPLICATE_POST_PLUGIN_BASENAME, [ $this, 'registerActionLinks' ], 10, 2 );

		add_action( 'admin_footer', [ $this, 'addAioseoModalPortal' ] );
	}

	/**
	 * Checks whether the current page is a Duplicate Post page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the current page is a Duplicate Post page.
	 */
	public function isDuplicatePostPage() {
		return ! empty( $this->currentPage );
	}

	/**
	 * Add the dir attribute to the HTML tag.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $output The HTML language attribute.
	 * @return string         The modified HTML language attribute.
	 */
	public function addDirAttribute( $output ) {
		if ( is_rtl() || preg_match( '/dir=[\'"](ltr|rtl|auto)[\'"]/i', $output ) ) {
			return $output;
		}

		return 'dir="ltr" ' . $output;
	}

	/**
	 * Registers the menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerMenu() {
		$hook = add_menu_page(
			__( 'Duplicate Post', 'duplicate-post-page-aioseo' ),
			__( 'Duplicate Post', 'duplicate-post-page-aioseo' ),
			'aioseo_duplicate_post_settings',
			$this->pageSlug,
			[ $this, 'renderMenuPage' ],
			'data:image/svg+xml;base64,' . base64_encode( aioseoDuplicatePost()->helpers->icon() )
		);

		add_action( "load-{$hook}", [ $this, 'checkCurrentPage' ] );

		$this->registerMenuPages();
	}

	/**
	 * Renders the element that we mount our Vue UI on.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function renderMenuPage() {
		echo '<div id="aioseo-duplicate-post-app"></div>';
	}

	/**
	 * Registers our menu pages.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function registerMenuPages() {
		$hooks = [];

		$hooks[] = add_submenu_page(
			$this->pageSlug,
			__( 'Settings', 'duplicate-post-page-aioseo' ),
			__( 'Settings', 'duplicate-post-page-aioseo' ),
			'aioseo_duplicate_post_settings',
			$this->pageSlug,
			[ $this, 'renderMenuPage' ]
		);

		$this->pages[] = $this->pageSlug;

		$hooks[] = add_submenu_page(
			$this->pageSlug,
			__( 'About Us', 'duplicate-post-page-aioseo' ),
			__( 'About Us', 'duplicate-post-page-aioseo' ),
			'aioseo_duplicate_post_settings',
			$this->pageSlug . '-about',
			[ $this, 'renderMenuPage' ]
		);

		$this->pages[] = $this->pageSlug . '-about';

		foreach ( $hooks as $hook ) {
			add_action( "load-{$hook}", [ $this, 'checkCurrentPage' ] );
		}
	}

	/**
	 * Checks if the current page is a Duplicate Post page and if so, starts enqueing the relevant assets.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function checkCurrentPage() {
		global $admin_page_hooks; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		$currentScreen = function_exists( 'get_current_screen' ) ? get_current_screen() : false;

		if ( empty( $currentScreen->id ) || empty( $admin_page_hooks ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
			return;
		}

		$pages = [
			'about',
			'duplicator',
		];

		foreach ( $pages as $page ) {
			$addScripts = false;

			if ( 'toplevel_page_duplicate-post' === $currentScreen->id ) {
				$page       = 'duplicator';
				$addScripts = true;
			}

			if ( strpos( $currentScreen->id, 'duplicate-post-' . $page ) !== false ) {
				$addScripts = true;
			}

			if ( ! $addScripts ) {
				continue;
			}

			// We don't want other plugins adding notices to our screens. Let's clear them out here.
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );

			$this->currentPage = $page;
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueueMenuAssets' ], 11 );

			// TODO: Add this in once the final slug is known.
			// add_filter( 'admin_footer_text', [ $this, 'addFooterText' ] );

			break;
		}
	}

	/**
	 * Enqueues our menu assets, based on the current page.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueueMenuAssets() {
		if ( ! $this->currentPage ) {
			return;
		}

		$scriptHandle = str_replace( '{page}', $this->currentPage, $this->assetSlugs['pages'] );
		aioseoDuplicatePost()->core->assets->load( $scriptHandle, [], aioseoDuplicatePost()->helpers->getVueData( $this->currentPage ) );
	}

	/**
	 * Hides the Scheduled Actions menu.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function hideScheduledActionsMenu() {
		// Don't hide it for developers when the main plugin isn't active.
		if ( defined( 'AIOSEO_DUPLICATE_POST_DEV' ) && ! function_exists( 'aioseo' ) ) {
			return;
		}

		global $submenu;
		if ( ! isset( $submenu['tools.php'] ) ) {
			return;
		}

		foreach ( $submenu['tools.php'] as $index => $props ) {
			if ( ! empty( $props[2] ) && 'action-scheduler' === $props[2] ) {
				unset( $submenu['tools.php'][ $index ] );

				return;
			}
		}
	}

	/**
	 * Registers our row meta for the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions    List of existing actions.
	 * @param  string $pluginFile The plugin file.
	 * @return array              List of action links.
	 */
	public function registerRowMeta( $actions, $pluginFile ) {
		// TODO: Add this once the plugin is launched.
		$actionLinks = [
			'settings' => [
				// Translators: This is an action link users can click to open a feature request.
				'label' => esc_html__( 'Suggest a Feature', 'duplicate-post-page-aioseo' ),
				'url'   => aioseoDuplicatePost()->helpers->utmUrl( AIOSEO_DUPLICATE_POST_MARKETING_URL . 'dp-suggest-a-feature/', 'plugin-row-meta', 'Feature' ),
			]
		];

		return $this->parseActionLinks( $actions, $pluginFile, $actionLinks );
	}

	/**
	 * Registers our action links for the plugins page.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions    List of existing actions.
	 * @param  string $pluginFile The plugin file.
	 * @return array              List of action links.
	 */
	public function registerActionLinks( $actions, $pluginFile ) {
		$actionLinks = [
			'support' => [
				// Translators: This is an action link users can click to open our support.
				'label' => esc_html__( 'Support', 'duplicate-post-page-aioseo' ),
				'url'   => aioseoDuplicatePost()->helpers->utmUrl( AIOSEO_DUPLICATE_POST_MARKETING_URL . 'plugin/duplicate-post-support', 'plugin-action-links', 'Documentation' ),
			],
			'docs'    => [
				// Translators: This is an action link users can click to open our documentation page.
				'label' => esc_html__( 'Documentation', 'duplicate-post-page-aioseo' ),
				'url'   => aioseoDuplicatePost()->helpers->utmUrl( AIOSEO_DUPLICATE_POST_MARKETING_URL . 'doc-categories/duplicate-post/', 'plugin-action-links', 'Documentation' ),
			]
		];

		if ( isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		return $this->parseActionLinks( $actions, $pluginFile, $actionLinks, 'before' );
	}

	/**
	 * Parses the action links.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $actions     The actions.
	 * @param  string $pluginFile  The plugin file.
	 * @param  array  $actionLinks The action links.
	 * @param  string $position    The position.
	 * @return array               The parsed actions.
	 */
	private function parseActionLinks( $actions, $pluginFile, $actionLinks = [], $position = 'after' ) {
		if ( empty( $this->plugin ) ) {
			$this->plugin = AIOSEO_DUPLICATE_POST_PLUGIN_BASENAME;
		}

		if ( $this->plugin === $pluginFile && ! empty( $actionLinks ) ) {
			foreach ( $actionLinks as $key => $value ) {
				$link = [
					$key => '<a href="' . $value['url'] . '" target="_blank">' . $value['label'] . '</a>'
				];

				$actions = 'after' === $position ? array_merge( $actions, $link ) : array_merge( $link, $actions );
			}
		}

		return $actions;
	}

	/**
	 * Add the div for the modal portal.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addAioseoModalPortal() {
		if ( ! function_exists( 'aioseo' ) ) {
			echo '<div id="aioseo-modal-portal"></div>';
		}
	}

	/**
	 * Checks whether the current page is a Duplicate Post menu page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the current page is a Duplicate Post menu page.
	 */
	public function isDuplicatePostScreen() {
		$currentScreen = aioseoDuplicatePost()->helpers->getCurrentScreen();
		if ( empty( $currentScreen->id ) ) {
			return false;
		}

		$adminPages = array_keys( $this->pages );
		$adminPages = array_map( function( $slug ) {
			if ( 'aioseo' === $slug ) {
				return 'toplevel_page_duplicate-post';
			}

			return 'duplicate-post_page_' . $slug;
		}, $adminPages );

		return in_array( $currentScreen->id, $adminPages, true );
	}

	/**
	 * Add footer text to the WordPress admin screens.
	 *
	 * @since 1.0.0
	 *
	 * @return string The footer text.
	 */
	public function addFooterText() {
		$linkText = esc_html__( 'Give us a 5-star rating!', 'duplicate-post-page-aioseo' );
		$href     = 'https://wordpress.org/support/plugin/duplicate-post/reviews/?filter=5#new-post';

		$link1 = sprintf(
			'<a href="%1$s" target="_blank" title="%2$s">&#9733;&#9733;&#9733;&#9733;&#9733;</a>',
			$href,
			$linkText
		);

		$link2 = sprintf(
			'<a href="%1$s" target="_blank" title="%2$s">WordPress.org</a>',
			$href,
			$linkText
		);

		printf(
			// Translators: 1 - The plugin name ("Duplicate Post"), - 2 - This placeholder will be replaced with star icons, - 3 - "WordPress.org" - 4 - The plugin name ("Duplicate Post").
			esc_html__( 'Please rate %1$s %2$s on %3$s to help us spread the word. Thank you!', 'duplicate-post-page-aioseo' ),
			sprintf( '<strong>%1$s</strong>', esc_html( AIOSEO_DUPLICATE_POST_PLUGIN_NAME ) ),
			wp_kses_post( $link1 ),
			wp_kses_post( $link2 )
		);

		// Stop WP Core from outputting its version number and instead add both theirs & ours.
		global $wp_version; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		printf(
			wp_kses_post( '<p class="alignright">%1$s</p>' ),
			sprintf(
				// Translators: 1 - WP Core version number, 2 - Version number.
				esc_html__( 'WordPress %1$s | Duplicate Post %2$s', 'duplicate-post-page-aioseo' ),
				esc_html( $wp_version ), // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
				esc_html( AIOSEO_DUPLICATE_POST_VERSION )
			)
		);

		remove_filter( 'update_footer', 'core_update_footer' );

		return '';
	}
}