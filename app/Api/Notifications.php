<?php
namespace AIOSEO\DuplicatePost\Api;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Models;

/**
 * Handles notification related routes.
 *
 * @since 1.0.0
 */
class Notifications {
	/**
	 * This allows us to not repeat code over and over.
	 *
	 * @since 1.0.0
	 *
	 * @param  string            $slug The slug of the reminder.
	 * @return \WP_REST_Response       The response.
	 */
	public static function reminder( $slug ) {
		aioseoDuplicatePost()->notifications->remindMeLater( $slug );

		return new \WP_REST_Response( [
			'success'       => true,
			'notifications' => Models\Notification::getNotifications()
		], 200 );
	}

	/**
	 * Dismiss notifications.
	 *
	 * @since 1.0.0
	 *
	 * @param  \WP_REST_Request  $request The REST Request
	 * @return \WP_REST_Response          The response.
	 */
	public static function dismissNotifications( $request ) {
		$slugs = $request->get_json_params();

		$notifications = aioseoDuplicatePost()->core->db
			->start( 'aioseo_duplicate_post_notifications' )
			->whereIn( 'slug', $slugs )
			->run()
			->models( 'AIOSEO\\DuplicatePost\\Models\\Notification' );

		foreach ( $notifications as $notification ) {
			$notification->dismissed = 1;
			$notification->save();
		}

		// Dismiss static notifications.
		if ( in_array( 'notification-review', $slugs, true ) ) {
			update_user_meta( get_current_user_id(), '_aioseo_duplicate_post_notification_plugin_review_dismissed', '3' );
		}

		if ( in_array( 'notification-review-delay', $slugs, true ) ) {
			update_user_meta( get_current_user_id(), '_aioseo_duplicate_post_notification_plugin_review_dismissed', strtotime( '+1 week' ) );
		}

		return new \WP_REST_Response( [
			'success'       => true,
			'notifications' => Models\Notification::getNotifications()
		], 200 );
	}
}