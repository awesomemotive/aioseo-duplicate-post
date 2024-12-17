<?php
namespace AIOSEO\DuplicatePost\Traits\Helpers;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Utils;

/**
 * Contains all WP related helper methods.
 *
 * @since 1.0.0
 */
trait Wp {
	/**
	 * Returns all registered post statuses.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean $statusesOnly Whether or not to only return statuses.
	 * @return array                 List of post statuses.
	 */
	public function getPublicPostStatuses( $statusesOnly = false ) {
		$allStatuses = get_post_stati( [ 'show_in_admin_all_list' => true ], 'objects' );

		$postStatuses = [];
		foreach ( $allStatuses as $status => $data ) {
			if (
				! $data->public &&
				! $data->protected &&
				! $data->private
			) {
				continue;
			}

			if ( $statusesOnly ) {
				$postStatuses[] = $status;
				continue;
			}

			$postStatuses[] = [
				'label'  => $data->label,
				'status' => $status
			];
		}

		return $postStatuses;
	}

	/**
	 * Returns a list of public post types with slugs/icons.
	 *
	 * @since 1.0.0
	 *
	 * @param  boolean $namesOnly       Whether only the names should be returned.
	 * @param  boolean $hasArchivesOnly Whether or not to only include post types which have archives.
	 * @param  boolean $rewriteType     Whether or not to rewrite the type slugs.
	 * @return array                    List of public post types.
	 */
	public function getPublicPostTypes( $namesOnly = false, $hasArchivesOnly = false, $rewriteType = false ) {
		$postTypes   = [];
		$postObjects = get_post_types( [ 'public' => true ], 'objects' );
		$woocommerce = class_exists( 'woocommerce' );
		foreach ( $postObjects as $postObject ) {
			if ( empty( $postObject->label ) ) {
				continue;
			}

			// We don't want to include archives for the WooCommerce shop page.
			if (
				$hasArchivesOnly &&
				(
					! $postObject->has_archive ||
					( 'product' === $postObject->name && $woocommerce )
				)
			) {
				continue;
			}

			if ( $namesOnly ) {
				$postTypes[] = $postObject->name;
				continue;
			}

			if ( 'attachment' === $postObject->name ) {
				$postObject->label = esc_html__( 'Attachments', 'duplicate-post-page-aioseo' );
			}

			if ( 'product' === $postObject->name && $woocommerce ) {
				$postObject->menu_icon = 'dashicons-products';
			}

			$name = $postObject->name;
			if ( 'type' === $postObject->name && $rewriteType ) {
				$name = '_aioseo_type';
			}

			$postTypes[] = [
				'name'         => $name,
				'label'        => ucwords( $postObject->label ),
				'singular'     => ucwords( $postObject->labels->singular_name ),
				'icon'         => $postObject->menu_icon,
				'hasExcerpt'   => post_type_supports( $postObject->name, 'excerpt' ),
				'hasArchive'   => $postObject->has_archive,
				'hierarchical' => $postObject->hierarchical,
				'taxonomies'   => get_object_taxonomies( $name ),
				'slug'         => isset( $postObject->rewrite['slug'] ) ? $postObject->rewrite['slug'] : $name
			];
		}

		return apply_filters( 'aioseo_duplicate_post_public_post_types', $postTypes, $namesOnly, $hasArchivesOnly );
	}

	/**
	 * Checks if the current user can edit posts of the given post type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $postType The name of the post type.
	 * @return bool             Whether the user can edit posts of the given post type.
	 */
	public function canEditPostType( $postType ) {
		$capabilities = $this->getPostTypeCapabilities( $postType );

		return current_user_can( $capabilities['edit_posts'] );
	}

	/**
	 * Returns a list of capabilities for the given post type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $postType The name of the post type.
	 * @return array            The capabilities.
	 */
	public function getPostTypeCapabilities( $postType ) {
		static $capabilities = [];
		if ( isset( $capabilities[ $postType ] ) ) {
			return $capabilities[ $postType ];
		}

		$postTypeObject = get_post_type_object( $postType );
		if ( ! is_a( $postTypeObject, 'WP_Post_Type' ) ) {
			$capabilities[ $postType ] = [];

			return $capabilities[ $postType ];
		}

		if ( ! is_array( $postTypeObject->capability_type ) ) {
			$postTypeObject->capability_type = [
				$postTypeObject->capability_type,
				$postTypeObject->capability_type . 's'
			];
		}

		// Singular base for meta capabilities, plural base for primitive capabilities.
		list( $singularBase, $pluralBase ) = $postTypeObject->capability_type;

		$capabilities[ $postType ] = [
			'edit_post'          => 'edit_' . $singularBase,
			'read_post'          => 'read_' . $singularBase,
			'delete_post'        => 'delete_' . $singularBase,
			'edit_posts'         => 'edit_' . $pluralBase,
			'edit_others_posts'  => 'edit_others_' . $pluralBase,
			'delete_posts'       => 'delete_' . $pluralBase,
			'publish_posts'      => 'publish_' . $pluralBase,
			'read_private_posts' => 'read_private_' . $pluralBase,
		];

		return $capabilities[ $postType ];
	}

	/**
	 * Returns the current post object.
	 *
	 * @since 1.0.0
	 *
	 * @param  int|null      $postId The post ID.
	 * @return \WP_Post|null         The post object.
	 */
	public function getPost( $postId = null ) {
		$postId = is_a( $postId, 'WP_Post' ) ? $postId->ID : $postId;

		if ( $this->isWooCommerceShopPage( $postId ) ) {
			return get_post( wc_get_page_id( 'shop' ) );
		}

		if ( is_front_page() || is_home() ) {
			$showOnFront = 'page' === get_option( 'show_on_front' );
			if ( $showOnFront ) {
				if ( is_front_page() ) {
					$pageOnFront = (int) get_option( 'page_on_front' );

					return get_post( $pageOnFront );
				} elseif ( is_home() ) {
					$pageForPosts = (int) get_option( 'page_for_posts' );

					return get_post( $pageForPosts );
				}
			}
		}

		// We need to check these conditions and cannot always return get_post() because we'll return the first post on archive pages (dynamic homepage, term pages, etc.).

		if (
			$this->isScreenBase( 'post' ) ||
			$postId ||
			is_singular()
		) {
			return get_post( $postId );
		}

		return null;
	}

	/**
	 * Gets the links that a user can choose from in the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array The array of links.
	 */
	public function getLinks() {
		return [
			[
				'label' => esc_html__( 'Clone', 'duplicate-post-page-aioseo' ),
				'name'  => 'duplicate',
				'slug'  => 'duplicate',
			],
			[
				'label' => esc_html__( 'Add Revision', 'duplicate-post-page-aioseo' ),
				'name'  => 'merge',
				'slug'  => 'merge',
			]
		];
	}

	/**
	 * Gets the links that a user can choose from in the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array The array of links.
	 */
	public function getOriginalItemSettings() {
		return [
			[
				'label'       => esc_html__( 'In a metabox in the Edit screen', 'duplicate-post-page-aioseo' ),
				'description' => esc_html__( 'You\'ll also be able to delete the reference to the original item with a checkbox.', 'duplicate-post-page-aioseo' ),
				'name'        => 'metabox',
				'slug'        => 'metabox',
			],
			[
				'label'       => esc_html__( 'In a column in the Post list', 'duplicate-post-page-aioseo' ),
				'description' => esc_html__( 'You\'ll also be able to delete the reference to the original item with a checkbox in Quick Edit.', 'duplicate-post-page-aioseo' ),
				'name'        => 'column',
				'slug'        => 'column',
			],
			[
				'label'       => esc_html__( 'After the title in the Post list.', 'duplicate-post-page-aioseo' ),
				'description' => '',
				'name'        => 'title',
				'slug'        => 'title',
			]
		];
	}

	/**
	 * Gets all registered roles and their names.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $canEditPosts Whether or not the user can edit posts.
	 *
	 * @return array             The array of roles.
	 */
	public function getAllRegisteredRoles( $canEditPosts = false ) {
		// phpcs:disable Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		global $wp_roles;

		$roles = [];
		if ( ! empty( $wp_roles ) ) {
			foreach ( $wp_roles->roles as $role => $data ) {
				if ( ! $canEditPosts || ( isset( $data['capabilities']['edit_posts'] ) && $data['capabilities']['edit_posts'] ) ) {
					$roles[] = [
						'slug'         => $role,
						'name'         => $data['name'],
						'label'        => $data['name'],
						'capabilities' => $data['capabilities']
					];
				}
			}
		}
		// phpcs:enable

		return $roles;
	}

	/**
	 * Gets all taxonomies for each of the currently registered post types.
	 *
	 * @since 1.0.0
	 *
	 * @return array $taxonomies The array of taxonomies.
	 */
	public function getAllTaxonomies() {
		// Get all registered post types
		$postTypes = $this->getPublicPostTypes();

		$taxonomies = [];
		// Loop through each post type
		foreach ( $postTypes as $postType ) {
			// Find the taxonomies for each post type and add them to the array
			if ( ! empty( $postType['taxonomies'] ) ) {
				foreach ( $postType['taxonomies'] as $taxonomy ) {
					$taxonomy = get_taxonomy( $taxonomy );
					if ( $taxonomy ) {
						$taxonomies[] = [
							'name'               => $taxonomy->name,
							'slug'               => $taxonomy->name,
							'label'              => ucwords( $taxonomy->label ),
							'object_type'        => $taxonomy->object_type,
							'hierarchical'       => $taxonomy->hierarchical,
							'public'             => $taxonomy->public,
							'show_ui'            => $taxonomy->show_ui,
							'show_in_menu'       => $taxonomy->show_in_menu,
							'show_in_nav_menus'  => $taxonomy->show_in_nav_menus,
							'show_tagcloud'      => $taxonomy->show_tagcloud,
							'show_in_quick_edit' => $taxonomy->show_in_quick_edit,
							'show_admin_column'  => $taxonomy->show_admin_column,
							'description'        => $taxonomy->description,
							'capabilities'       => $taxonomy->cap,
						];
					}
				}
			}
		}

		return $taxonomies;
	}

	/**
	 * Get a list of elements for post types to be selected in the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array The array of roles.
	 */
	public function getPostElements() {
		$elements = [
			[
				'slug'  => 'title',
				'label' => esc_html__( 'Title', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Title', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'content',
				'label' => esc_html__( 'Content', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Content', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'excerpt',
				'label' => esc_html__( 'Excerpt', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Excerpt', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'date',
				'label' => esc_html__( 'Date', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Date', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'featured_image',
				'label' => esc_html__( 'Featured Image', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Featured Image', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'attachments',
				'label' => esc_html__( 'Attachments', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Attachments', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'author',
				'label' => esc_html__( 'Author', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Author', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'status',
				'label' => esc_html__( 'Status', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Status', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'slug',
				'label' => esc_html__( 'Slug', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Slug', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'comments',
				'label' => esc_html__( 'Comments', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Comments', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'template',
				'label' => esc_html__( 'Template', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Template', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'post_format',
				'label' => esc_html__( 'Post Format', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Post Format', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'password',
				'label' => esc_html__( 'Password', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Password', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'parents',
				'label' => esc_html__( 'Parents', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Parents', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'children',
				'label' => esc_html__( 'Children', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Children', 'duplicate-post-page-aioseo' )
			],
			[
				'slug'  => 'menu_order',
				'label' => esc_html__( 'Menu Order', 'duplicate-post-page-aioseo' ),
				'name'  => esc_html__( 'Menu Order', 'duplicate-post-page-aioseo' )
			]
		];

		return $elements;
	}

	/**
	 * Returns true if the request is a non-legacy REST API request.
	 * This function was copied from WooCommerce and improved.
	 *
	 * @since 1.0.0
	 *
	 * @return bool True if this is a REST API request.
	 */
	public function isRestApiRequest() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		global $wp_rewrite; // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
		if ( empty( $wp_rewrite ) ) { // phpcs:ignore Squiz.NamingConventions.ValidVariableName.NotCamelCaps
			return false;
		}

		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$restUrl = wp_parse_url( get_rest_url() );
		$restUrl = $restUrl['path'] . ( ! empty( $restUrl['query'] ) ? '?' . $restUrl['query'] : '' );

		$isRestApiRequest = ( 0 === strpos( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), $restUrl ) );

		return apply_filters( 'aioseo_is_rest_api_request', $isRestApiRequest );
	}

	/**
	 * Checks whether the current request is an AJAX, CRON or REST request.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Wether the request is an AJAX, CRON or REST request.
	 */
	public function isAjaxCronRestRequest() {
		return wp_doing_ajax() || wp_doing_cron() || $this->isRestApiRequest();
	}

	/**
	 * Check if the post passed in is a valid post, not a revision or autosave.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_Post $post                The Post object to check.
	 * @param  array    $allowedPostStatuses Allowed post statuses.
	 * @return bool                          True if valid, false if not.
	 */
	public function isValidPost( $post, $allowedPostStatuses = [ 'publish' ] ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		// In order to prevent recursion, we are skipping scheduled-action posts.
		if (
			! is_object( $post ) ||
			'scheduled-action' === $post->post_type ||
			'revision' === $post->post_type ||
			! in_array( $post->post_status, $allowedPostStatuses, true )
		) {
			return false;
		}

		return true;
	}

	/**
	 * Returns a list of plugins with the active status.
	 *
	 * @since 1.0.0
	 *
	 * @return array List of plugins with active status.
	 */
	public function getPluginData() {
		static $pluginData = [];
		if ( ! empty( $pluginData ) ) {
			return $pluginData;
		}

		$pluginUpgrader   = new Utils\PluginUpgraderSilentAjax();
		$installedPlugins = array_keys( get_plugins() );

		foreach ( $pluginUpgrader->pluginSlugs as $key => $slug ) {
			$pluginData[ $key ] = [
				'basename'    => $slug,
				'installed'   => in_array( $slug, $installedPlugins, true ),
				'activated'   => is_plugin_active( $slug ),
				'adminUrl'    => admin_url( $pluginUpgrader->pluginAdminUrls[ $key ] ),
				'canInstall'  => $this->canInstall(),
				'canActivate' => $this->canActivate(),
				'canUpdate'   => $this->canUpdate(),
				'wpLink'      => ! empty( $pluginUpgrader->wpPluginLinks[ $key ] ) ? $pluginUpgrader->wpPluginLinks[ $key ] : null
			];
		}

		return $pluginData;
	}

	/**
	 * Installs and activates a given addon or plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name    The addon name/SKU.
	 * @param  bool   $network Whether or not we are in a network environment.
	 * @return bool            Whether or not the installation was succesful.
	 */
	public function installAddon( $name, $network = false ) {
		if ( ! $this->canInstall() ) {
			return false;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/template.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		require_once ABSPATH . 'wp-admin/includes/screen.php';

		// Set the current screen to avoid undefined notices.
		set_current_screen( 'toplevel_page_duplicate-post' );

		// Prepare variables.
		$url = esc_url_raw(
			add_query_arg(
				[
					'page' => 'duplicate-post-links'
				],
				admin_url( 'admin.php' )
			)
		);

		// Do not allow WordPress to search/download translations, as this will break JS output.
		remove_action( 'upgrader_process_complete', [ 'Language_Pack_Upgrader', 'async_upgrade' ], 20 );

		// Create the plugin upgrader with our custom skin.
		$installer = new Utils\PluginUpgraderSilentAjax( new Utils\PluginUpgraderSkin() );

		// Activate the plugin silently.
		$pluginUrl = ! empty( $installer->pluginSlugs[ $name ] ) ? $installer->pluginSlugs[ $name ] : $name;
		$activated = activate_plugin( $pluginUrl, '', $network );

		if ( ! is_wp_error( $activated ) ) {
			return $name;
		}

		// Using output buffering to prevent the FTP form from being displayed in the screen.
		ob_start();
		$creds = request_filesystem_credentials( $url, '', false, false, null );
		ob_end_clean();

		// Check for file system permissions.
		$fs = aioseoDuplicatePost()->core->fs->noConflict();
		$fs->init( $creds );
		if ( false === $creds || ! $fs->isWpfsValid() ) {
			return false;
		}

		// Error check.
		if ( ! method_exists( $installer, 'install' ) ) {
			return false;
		}

		$installLink = ! empty( $installer->pluginLinks[ $name ] ) ? $installer->pluginLinks[ $name ] : null;
		// Check if this is an addon and if we have a download link.
		if ( empty( $installLink ) ) {
			return false;
		}

		$installer->install( $installLink );

		// Flush the cache and return the newly installed plugin basename.
		wp_cache_flush();

		$pluginBasename = $installer->plugin_info();
		if ( ! $pluginBasename ) {
			return false;
		}

		// Activate the plugin silently.
		$activated = activate_plugin( $pluginBasename, '', $network );

		if ( is_wp_error( $activated ) ) {
			return false;
		}

		return $pluginBasename;
	}

	/**
	 * Determine if plugins can be installed.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the plugin can be installed.
	 */
	public function canInstall() {
		if ( ! current_user_can( 'install_plugins' ) ) {
			return false;
		}

		// Determine whether file modifications are allowed.
		if ( ! wp_is_file_mod_allowed( 'aioseo_duplicate_post_can_install' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine if plugins can be updated.
	 *
	 * @since 1.0.0
	 *
	 * @return bool  Whether the plugin can be updated.
	 */
	public function canUpdate() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return false;
		}

		// Determine whether file modifications are allowed.
		if ( ! wp_is_file_mod_allowed( 'aioseo_duplicate_post_can_update' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determine if plugins can be activated.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the plugin can be activated.
	 */
	public function canActivate() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Returns the charset for the site.
	 *
	 * @since 1.0.0
	 *
	 * @return string The name of the charset.
	 */
	public function getCharset() {
		static $charset = null;
		if ( null !== $charset ) {
			return $charset;
		}

		$charset = get_option( 'blog_charset' );
		$charset = $charset ? $charset : 'UTF-8';

		return $charset;
	}

	/**
	 * Checks if the Classic eEditor is active and if the Block Editor is disabled in its settings.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the Classic Editor is active.
	 */
	public function isClassicEditorActive() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		if ( ! is_plugin_active( 'classic-editor/classic-editor.php' ) ) {
			return false;
		}

		return 'classic' === get_option( 'classic-editor-replace' );
	}
}