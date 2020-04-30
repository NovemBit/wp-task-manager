<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * The initial migration
 *
 * Class BTM_Migration_Base_30_04_2020
 */
class BTM_Migration_30_04_2020 implements I_BTM_Migration{
	// region Singleton

	/**
	 * @var BTM_Migration_30_04_2020
	 */
	private static $instance = null;

	/**
	 * @return BTM_Migration_30_04_2020
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

		$wpdb->query('ALTER TABLE `btm_tasks` ADD COLUMN `last_run` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `date_created`;');
	}

	public function down(){}
}