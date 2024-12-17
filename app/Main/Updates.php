<?php
namespace AIOSEO\DuplicatePost\Main;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AIOSEO\DuplicatePost\Models;

/**
 * Handles update migrations.
 *
 * @since 1.0.0
 */
class Updates {
	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}

		add_action( 'init', [ $this, 'runUpdates' ], 1002 );
		add_action( 'init', [ $this, 'updateLatestVersion' ], 3000 );
	}

	/**
	 * Runs our migrations.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function runUpdates() {
		$lastActiveVersion = aioseoDuplicatePost()->internalOptions->internal->lastActiveVersion;
		if ( version_compare( $lastActiveVersion, '1.0.0', '<' ) ) {
		}
	}

	/**
	 * Updates the latest version after all migrations and updates have run.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function updateLatestVersion() {
		if ( aioseoDuplicatePost()->internalOptions->internal->lastActiveVersion === aioseoDuplicatePost()->version ) {
			return;
		}

		aioseoDuplicatePost()->internalOptions->internal->lastActiveVersion = aioseoDuplicatePost()->version;

		aioseoDuplicatePost()->core->db->bustCache();
		aioseoDuplicatePost()->internalOptions->database->installedTables = '';
	}

	/**
	 * Adds our custom tables.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function addInitialTables() {
		$db             = aioseoDuplicatePost()->core->db->db;
		$charsetCollate = '';

		if ( ! empty( $db->charset ) ) {
			$charsetCollate .= "DEFAULT CHARACTER SET {$db->charset}";
		}
		if ( ! empty( $db->collate ) ) {
			$charsetCollate .= " COLLATE {$db->collate}";
		}

		if ( ! aioseoDuplicatePost()->core->db->tableExists( 'aioseo_duplicate_post_notifications' ) ) {
			$tableName = $db->prefix . 'aioseo_duplicate_post_notifications';

			aioseoDuplicatePost()->core->db->execute(
				"CREATE TABLE {$tableName} (
					`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					`notification_id` bigint(20) unsigned DEFAULT NULL,
					`notification_name` varchar(255) DEFAULT NULL,
					`slug` varchar(13) NOT NULL,
					`title` text NOT NULL,
					`content` longtext NOT NULL,
					`type` varchar(64) NOT NULL,
					`level` text NOT NULL,
					`start` datetime DEFAULT NULL,
					`end` datetime DEFAULT NULL,
					`button1_label` varchar(255) DEFAULT NULL,
					`button1_action` varchar(255) DEFAULT NULL,
					`button2_label` varchar(255) DEFAULT NULL,
					`button2_action` varchar(255) DEFAULT NULL,
					`dismissed` tinyint(1) NOT NULL DEFAULT 0,
					`new` tinyint(1) NOT NULL DEFAULT 1,
					`created` datetime NOT NULL,
					`updated` datetime NOT NULL,
					PRIMARY KEY (id),
					UNIQUE KEY ndx__aioseo_duplicate_post_notifications_slug (slug),
					KEY ndx__aioseo_duplicate_post_notifications_dates (start, end),
					KEY ndx__aioseo_duplicate_post_notifications_type (type),
					KEY ndx__aioseo_duplicate_post_notifications_dismissed (dismissed)
				) {$charsetCollate};"
			);
		}
	}
}