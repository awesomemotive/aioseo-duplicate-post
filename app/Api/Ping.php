<?php
namespace AIOSEO\DuplicatePost\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Route class for the API.
 *
 * @since 1.0.0
 */
class Ping {
	/**
	 * Returns a success if the API is alive.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response The response.
	 */
	public static function ping() {
		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}
}