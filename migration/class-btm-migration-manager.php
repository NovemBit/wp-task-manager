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
		require_once( $migration_path . 'class-btm-migration-30-04-2020.php' );
	}
	private function __clone(){}
	private function __wakeup(){}

	// endregion

	private $migrations = [ BTM_Migration_Base::class, BTM_Migration_30_04_2020::class ];

	public function migrate_up(){
		$history = get_option( 'background_task_manager_migrations', [] );
		$count = 0;
		foreach ( $this->migrations as $class ){
			if( ! in_array( $class, $history ) ){
				/** @var I_BTM_Migration $class */
				$class::get_instance()->up();
				$history[] = $class;
				$count++;
			}
		}
		if( $count > 0 ){
			update_option( 'background_task_manager_migrations', $history );
		}
	}

	public function migrate_down(){
		throw new Exception('BTM_Migration_Manager::migrate_down() is not implemented');
	}
}