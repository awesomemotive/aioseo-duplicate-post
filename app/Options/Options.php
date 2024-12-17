<?php
namespace AIOSEO\DuplicatePost\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Traits;

/**
 * Handles the main options.
 *
 * @since 1.0.0
 */
class Options {
	use Traits\Options;

	/**
	 * All the default options.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $defaults = [
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		'general' => [
			'enable'         => [ 'type' => 'boolean', 'default' => false ],
			'postElements'   => [
				'all'      => [ 'type' => 'boolean', 'default' => true ],
				'included' => [ 'type' => 'array', 'default' => [] ]
			],
			'dontCopyMeta'   => [ 'type' => 'string', 'default' => '' ],
			'showLinks'      => [
				'all'      => [ 'type' => 'boolean', 'default' => true ],
				'included' => [ 'type' => 'array', 'default' => [ 'duplicate', 'merge' ] ]
			],
			'showOriginal'   => [
				'metabox' => [ 'type' => 'boolean', 'default' => false ],
				'column'  => [ 'type' => 'boolean', 'default' => false ],
				'title'   => [ 'type' => 'boolean', 'default' => true ]
			],
			'titlePrefix'    => [ 'type' => 'string', 'default' => 'Copy of' ],
			'titleSuffix'    => [ 'type' => 'string', 'default' => '' ],
			'postTypes'      => [
				'all'      => [ 'type' => 'boolean', 'default' => true ],
				'included' => [ 'type' => 'array', 'default' => [ 'post', 'page' ] ]
			],
			'roles'          => [
				'all'      => [ 'type' => 'boolean', 'default' => true ],
				'included' => [ 'type' => 'array', 'default' => [ 'administrator' ] ]
			],
			'taxonomies'     => [
				'all'      => [ 'type' => 'boolean', 'default' => true ],
				'included' => [ 'type' => 'array', 'default' => [ 'category', 'post_tag' ] ]
			],
			'menuOrder'      => [ 'type' => 'string', 'default' => '' ],
			'excludePosts'   => [ 'type' => 'array', 'default' => [] ],
			'excludeDomains' => [ 'type' => 'html', 'default' => '' ],
			'uninstall'      => [ 'type' => 'boolean', 'default' => false ]
		]
		// phpcs:enable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
	];

	/**
	 * The Construct method.
	 *
	 * @since 1.0.0
	 *
	 * @param string $optionsName An array of options.
	 */
	public function __construct( $optionsName = 'aioseo_duplicate_post_options' ) {
		$this->optionsName = $optionsName;

		$this->init();

		add_action( 'shutdown', [ $this, 'save' ] );
	}

	/**
	 * Initializes the options.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function init() {
		add_action( 'init', [ $this, 'translateDefaults' ] );

		$options = $this->getDuplicatePostDbOptions();

		aioseoDuplicatePost()->core->optionsCache->setOptions( $this->optionsName, apply_filters( 'aioseo_duplicate_post_get_options', $options ) );
	}

	/**
	 * Get the DB options.
	 *
	 * @since 1.0.0
	 *
	 * @return array An array of options.
	 */
	public function getDuplicatePostDbOptions() {
		// Options from the DB.
		$dbOptions = $this->getDbOptions( $this->optionsName );

		// Refactor options.
		$this->defaultsMerged = array_replace_recursive( $this->defaults, $this->defaultsMerged );

		return array_replace_recursive(
			$this->defaultsMerged,
			$this->addValueToValuesArray( $this->defaultsMerged, $dbOptions )
		);
	}

	/**
	 * Sanitizes, then saves the options to the database.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $newOptions An array of options to sanitize, then save.
	 * @return void
	 */
	public function sanitizeAndSave( $newOptions ) {
		$this->init();

		if ( ! is_array( $newOptions ) ) {
			return;
		}

		// First, recursively replace the new options into the cached state.
		// It's important we use the helper method since we want to replace populated arrays with empty ones if needed (when a setting was cleared out).
		$cachedOptions = aioseoDuplicatePost()->core->optionsCache->getOptions( $this->optionsName );
		$dbOptions     = aioseoDuplicatePost()->helpers->arrayReplaceRecursive(
			$cachedOptions,
			$this->addValueToValuesArray( $cachedOptions, $newOptions, [], true )
		);

		// Now, we must also intersect both arrays to delete any individual keys that were unset.
		// We must do this because, while arrayReplaceRecursive will update the values for keys or empty them out,
		// it will keys that aren't present in the replacement array unaffected in the target array.
		$dbOptions = aioseoDuplicatePost()->helpers->arrayIntersectRecursive(
			$dbOptions,
			$this->addValueToValuesArray( $cachedOptions, $newOptions, [], true ),
			'value'
		);

		if ( isset( $newOptions['advanced']['excludeDomains'] ) ) {
			$dbOptions['advanced']['excludeDomains'] = preg_replace( '/\h/', "\n", $newOptions['advanced']['excludeDomains'] );
		}

		// Update the cache state.
		aioseoDuplicatePost()->core->optionsCache->setOptions( $this->optionsName, $dbOptions );

		// Finally, save the new values to the DB.
		$this->save( true );

		// Update role caps based on role setting.
		aioseoDuplicatePost()->access->addCapabilities();
	}

	/**
	 * For our defaults array, some options need to be translated, so we do that here.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function translateDefaults() {
		static $hasInitialized = false;
		if ( $hasInitialized ) {
			return;
		}

		$hasInitialized = true;

		$this->defaults['general']['titlePrefix']['default'] = esc_html__( 'Copy of', 'duplicate-post-page-aioseo' );
	}
}