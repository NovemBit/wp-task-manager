<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Manages the migrations
 *
 * Class BTM_Migration_Manager
 */
class BTM_Migration_Manager{
	// region Singleton

	/**
	 * @var BTM_Migration_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Migration_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct(){
		$migration_path = BTM_Plugin_Options::get_instance()->get_path() . DIRECTORY_SEPARATOR . 'migration' . DIRECTORY_SEPARATOR;

		require_once( $migration_path . 'class-btm-migration-base.php' );
	}
	private function __clone(){}
	private function __wakeup(){}

	// endregion

	public function migrate_up(){
		BTM_Migration_Base::get_instance()->up();
	}

	public function migrate_down(){
		throw new Exception('BTM_Migration_Manager::migrate_down() is not implemented');
	}
}