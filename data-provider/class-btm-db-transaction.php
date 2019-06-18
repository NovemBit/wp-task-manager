<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_DB_Transaction
 */
final class BTM_DB_Transaction{
	// region Singleton

	/**
	 * @var BTM_DB_Transaction
	 */
	private static $instance = null;
	/**
	 * @return BTM_DB_Transaction
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @var bool
	 */
	private $started = false;

	public function start(){
		global $wpdb;

		if( $this->started ){
			throw new LogicException('Transaction is already started');
		}

		$wpdb->query('START TRANSACTION');
	}

	public function commit(){
		global $wpdb;

		if( ! $this->started ){
			throw new LogicException('Transaction is not started');
		}

		$wpdb->query('COMMIT');
	}

	public function rollback(){
		global $wpdb;

		if( ! $this->started ){
			throw new LogicException('Transaction is not started');
		}

		$wpdb->query('ROLLBACK');
	}
}