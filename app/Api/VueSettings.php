<?php
namespace AIOSEO\DuplicatePost\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Models;

/**
 * Handles the user Vue settings (toggled cards, etc.).
 *
 * @since 1.0.0
 */
class VueSettings {
	/**
	 * Returns the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response The response.
	 */
	public static function getOptions() {
		return new \WP_REST_Response( [
			'success'         => true,
			'options'         => aioseoDuplicatePost()->options->all(),
			'internalOptions' => aioseoDuplicatePost()->internalOptions->all(),
			'settings'        => aioseoDuplicatePost()->vueSettings->all()
		], 200 );
	}

	/**
	 * Toggles a card in the settings.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function toggleCard( $request ) {
		$body = $request->get_json_params();
		$card = ! empty( $body['card'] ) ? sanitize_text_field( $body['card'] ) : null;

		$cards = aioseoDuplicatePost()->vueSettings->toggledCards;
		if ( ! array_key_exists( $card, $cards ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$cards[ $card ] = ! $cards[ $card ];
		aioseoDuplicatePost()->vueSettings->toggledCards = $cards;

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Toggles a radio in the settings.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function toggleRadio( $request ) {
		$body   = $request->get_json_params();
		$radio  = ! empty( $body['radio'] ) ? sanitize_text_field( $body['radio'] ) : null;
		$value  = ! empty( $body['value'] ) ? sanitize_text_field( $body['value'] ) : null;

		$radios = aioseoDuplicatePost()->vueSettings->toggledRadio;
		if ( ! array_key_exists( $radio, $radios ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$radios[ $radio ] = $value;
		aioseoDuplicatePost()->vueSettings->toggledRadio = $radios;

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Toggles a table's items per page setting.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function changeItemsPerPage( $request ) {
		$body   = $request->get_json_params();
		$table  = ! empty( $body['table'] ) ? sanitize_text_field( $body['table'] ) : null;
		$value  = ! empty( $body['value'] ) ? intval( $body['value'] ) : null;

		$tables = aioseoDuplicatePost()->vueSettings->tablePagination;
		if ( ! array_key_exists( $table, $tables ) ) {
			return new \WP_REST_Response( [
				'success' => false
			], 400 );
		}

		$tables[ $table ] = $value;
		aioseoDuplicatePost()->vueSettings->tablePagination = $tables;

		return new \WP_REST_Response( [
			'success' => true
		], 200 );
	}

	/**
	 * Save options from the frontend.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function saveChanges( $request ) {
		$body    = $request->get_json_params();
		$options = ! empty( $body['options'] ) ? $body['options'] : []; // The options class will sanitize them.

		aioseoDuplicatePost()->options->sanitizeAndSave( $options );

		// Re-initialize the notices.
		aioseoDuplicatePost()->notifications->init();

		return new \WP_REST_Response( [
			'success'       => true,
			'notifications' => Models\Notification::getNotifications()
		], 200 );
	}
}