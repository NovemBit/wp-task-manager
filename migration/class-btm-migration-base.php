<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * The initial migration
 *
 * Class BTM_Migration_Base
 */
class BTM_Migration_Base implements I_BTM_Migration{
	// region Singleton

	/**
	 * @var BTM_Migration_Base
	 */
	private static $instance = null;

	/**
	 * @return BTM_Migration_Base
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct(){}
	private function __clone(){}
	private function __wakeup(){}

	// endregion

	// todo: read table prefix from options
	public function up(){
		global $wpdb;

		$wpdb->query('
			CREATE TABLE IF NOT EXISTS `btm_tasks` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `callback_action` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
			  `callback_arguments` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
			  `priority` int(11) NOT NULL DEFAULT \'10\',
			  `bulk_size` int(11) NOT NULL DEFAULT \'0\',
			  `status` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
			  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `type` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
			  `is_system` tinyint(1) NOT NULL,
			  `argument_hash` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `priority` (`priority`),
			  KEY `status` (`status`),
			  KEY `date_created` (`date_created`),
			  KEY `argument_hash` (`argument_hash`),
			  KEY `is_system` (`is_system`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		');

		$wpdb->query('
			CREATE TABLE IF NOT EXISTS `btm_task_bulk_arguments` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `task_id` bigint(20) unsigned NOT NULL,
			  `callback_arguments` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
			  `priority` int(11) NOT NULL DEFAULT \'10\',
			  `status` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
			  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
			  `argument_hash` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `priority` (`priority`),
			  KEY `status` (`status`),
			  KEY `task_id` (`task_id`),
			  KEY `argument_hash` (`argument_hash`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		');

		$wpdb->query('
			CREATE TABLE IF NOT EXISTS `btm_task_run_logs` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `task_id` bigint(20) unsigned NOT NULL,
			  `session_id` timestamp NOT NULL,
			  `logs` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
			  `date_started` timestamp NOT NULL,
			  `date_finished` timestamp NULL DEFAULT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		');

		$wpdb->query('
			CREATE TABLE IF NOT EXISTS `btm_task_manager_logs` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `session_id` timestamp NOT NULL,
			  `log` longtext COLLATE utf8mb4_unicode_ci,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		');

		$wpdb->query('
			CREATE TABLE IF NOT EXISTS `btm_notification_callbacks` (
			  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			  `callback_action` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
			  `status` varchar(127) COLLATE utf8mb4_unicode_ci NOT NULL,
			  PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		');

		$wpdb->query('
			CREATE TABLE IF NOT EXISTS `btm_notificatoin_users` (
			  `notification_callback_id` bigint(20) NOT NULL,
			  `user_id` int(11) NOT NULL,
			  PRIMARY KEY (`notification_callback_id`,`user_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
		');
	}

	public function down(){}
}