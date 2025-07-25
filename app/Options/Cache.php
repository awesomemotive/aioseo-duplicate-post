<?php
namespace AIOSEO\DuplicatePost\Options;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that holds all the cache for the options.
 *
 * @since 1.0.0
 */
class Cache {
	/**
	 * The DB options cache.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private static $db = [];

	/**
	 * The options cache.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private static $options = [];

	/**
	 * Sets the cache for the DB option.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name  The cache name.
	 * @param  array  $value The value.
	 * @return void
	 */
	public function setDb( $name, $value ) {
		self::$db[ $name ] = $value;
	}

	/**
	 * Gets the cache for the DB option.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name The cache name.
	 * @return array        The data from the cache.
	 */
	public function getDb( $name ) {
		return ! empty( self::$db[ $name ] ) ? self::$db[ $name ] : [];
	}

	/**
	 * Sets the cache for the options.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name  The cache name.
	 * @param  array  $value The value.
	 * @return void
	 */
	public function setOptions( $name, $value ) {
		self::$options[ $name ] = $value;
	}

	/**
	 * Gets the cache for the options.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name The cache name.
	 * @return array        The data from the cache.
	 */
	public function getOptions( $name ) {
		return ! empty( self::$options[ $name ] ) ? self::$options[ $name ] : [];
	}

	/**
	 * Resets the DB cache.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function resetDb() {
		self::$db = [];
	}
}