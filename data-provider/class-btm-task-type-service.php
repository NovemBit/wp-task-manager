<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Type_Service
 */
class BTM_Task_Type_Service {
	// region Singleton

	/**
	 * @var BTM_Task_Type_Service
	 */
	private static $instance = null;

	/**
	 * @return BTM_Task_Type_Service
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return string
	 */
	public function get_type_from_task( I_BTM_Task $task ){
		return str_replace( '_', ' ', get_class( $task ) );
	}
	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_class_from_type( $type ){
		return str_replace( ' ', '_', $type );
	}
}