<?php
namespace AIOSEO\DuplicatePost {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * The main DuplicatePost class.
	 *
	 * @since 1.0.0
	 */
	final class DuplicatePost {
		/**
		 * Holds the instance of the plugin currently in use.
		 *
		 * @since 1.0.0
		 *
		 * @var DuplicatePost
		 */
		private static $instance;

		/**
		 * Plugin version for enqueueing, etc.
		 * The value is retrieved from the AIOSEO_DUPLICATE_POST_BROKEN_LINK_CHECKER_VERSION constant.
		 *
		 * @since 1.0.0
		 *
		 * @var string
		 */
		public $version = '';

		/**
		 * Whether we're in a dev environment.
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		public $isDev = false;

		/**
		 * Core class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Core\Core
		 */
		public $core;

		/**
		 * InternalOptions class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Options\InternalOptions
		 */
		public $internalOptions;

		/**
		 * Pre updates class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Main\PreUpdates
		 */
		public $preUpdates;

		/**
		 * Helpers class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\Helpers
		 */
		public $helpers;

		/**
		 * Options class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Options\Options
		 */
		public $options;

		/**
		 * Updates class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Main\Updates
		 */
		public $updates;

		/**
		 * Action scheduler class.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\ActionScheduler
		 */
		public $actionScheduler;

		/**
		 * Access class.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\Access
		 */
		public $access;

		/**
		 * Main class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Main\Main
		 */
		public $main;

		/**
		 * API class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Api\Api
		 */
		public $api;

		/**
		 * Standalone class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Standalone\Standalone
		 */
		public $standalone;

		/**
		 * Notifications class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Admin\Notifications
		 */
		public $notifications;

		/**
		 * VueSettings class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Utils\VueSettings
		 */
		public $vueSettings;

		/**
		 * Admin class instance.
		 *
		 * @since 1.0.0
		 *
		 * @var Admin\Admin
		 */
		public $admin;

		/**
		 * The main DuplicatePost Instance.
		 *
		 * Insures that only one instance of DuplicatePost exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0.0
		 *
		 * @return DuplicatePost The Duplicate Post instance.
		 */
		public static function instance() {
			if ( null === self::$instance || ! self::$instance instanceof self ) {
				self::$instance = new self();

				self::$instance->init();
			}

			return self::$instance;
		}

		/**
		 * Initialize Duplicate Post!
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function init() {
			$this->constants();
			$this->includes();
			$this->preLoad();
			$this->load();
		}

		/**
		 * Setup plugin constants.
		 * All the path/URL related constants are defined in main plugin file.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function constants() {
			$defaultHeaders = [
				'name'    => 'Plugin Name',
				'version' => 'Version',
			];

			$pluginData = get_file_data( AIOSEO_DUPLICATE_POST_FILE, $defaultHeaders );

			$constants = [
				'AIOSEO_DUPLICATE_POST_PLUGIN_BASENAME'  => plugin_basename( AIOSEO_DUPLICATE_POST_FILE ),
				'AIOSEO_DUPLICATE_POST_PLUGIN_NAME'      => 'Duplicate Post',
				'AIOSEO_DUPLICATE_POST_PLUGIN_URL'       => plugin_dir_url( AIOSEO_DUPLICATE_POST_FILE ),
				'AIOSEO_DUPLICATE_POST_VERSION'          => $pluginData['version'],
				'AIOSEO_DUPLICATE_POST_MARKETING_URL'    => 'https://aioseo.com/',
				'AIOSEO_DUPLICATE_POST_MARKETING_DOMAIN' => 'aioseo.com'
			];

			foreach ( $constants as $constant => $value ) {
				if ( ! defined( $constant ) ) {
					define( $constant, $value );
				}
			}

			$this->version = AIOSEO_DUPLICATE_POST_VERSION;
		}

		/**
		 * Including the new files with PHP 5.3 style.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function includes() {
			$dependencies = [
				'/vendor/autoload.php',
				'/vendor/woocommerce/action-scheduler/action-scheduler.php'
			];

			foreach ( $dependencies as $path ) {
				if ( ! file_exists( AIOSEO_DUPLICATE_POST_DIR . $path ) ) {
					// Something is not right.
					status_header( 500 );
					wp_die( esc_html__( 'Plugin is missing required dependencies. Please contact support for more information.', 'duplicate-post-page-aioseo' ) );
				}
				require_once AIOSEO_DUPLICATE_POST_DIR . $path;
			}

			$this->loadVersion();
		}

		/**
		 * Load the version of the plugin we are currently using.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function loadVersion() {
			if (
				! class_exists( '\Dotenv\Dotenv' ) ||
				! file_exists( AIOSEO_DUPLICATE_POST_DIR . '/build/.env' )
			) {
				return;
			}

			$dotenv = \Dotenv\Dotenv::createUnsafeImmutable( AIOSEO_DUPLICATE_POST_DIR, '/build/.env' );
			$dotenv->load();

			$devPort = strtolower( getenv( 'VITE_AIOSEO_DUPLICATE_POST_DEV_PORT' ) );
			if ( ! empty( $devPort ) ) {
				$this->isDev = true;

				// Fix SSL certificate invalid in our local environments.
				add_filter( 'https_ssl_verify', '__return_false' );
			}
		}

		/**
		 * Runs before we load the plugin.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		private function preLoad() {
			$this->core            = new Core\Core();
			$this->internalOptions = new Options\InternalOptions();
			$this->preUpdates      = new Main\PreUpdates();
		}

		/**
		 * Load our classes.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function load() {
			$this->helpers         = new Utils\Helpers();
			$this->options         = new Options\Options();
			$this->updates         = new Main\Updates();
			$this->actionScheduler = new Utils\ActionScheduler();
			$this->access          = new Utils\Access();
			$this->main            = new Main\Main();
			$this->api             = new Api\Api();
			$this->standalone      = new Standalone\Standalone();
			$this->notifications   = new Admin\Notifications();
			$this->admin           = new Admin\Admin();

			add_action( 'init', [ $this, 'loadInit' ], 999 );
		}

		/**
		 * Things that need to load after init.
		 *
		 * @since 1.0.0
		 *
		 * @return void
		 */
		public function loadInit() {
			$this->vueSettings = new Utils\VueSettings( '_aioseo_duplicate_post_settings' );
		}
	}
}

namespace {
	// Exit if accessed directly.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * The function which returns the one AIOSEO instance.
	 *
	 * @since 1.0.0
	 *
	 * @return AIOSEO\DuplicatePost\DuplicatePost The instance.
	 */
	function aioseoDuplicatePost() {
		return AIOSEO\DuplicatePost\DuplicatePost::instance();
	}
}