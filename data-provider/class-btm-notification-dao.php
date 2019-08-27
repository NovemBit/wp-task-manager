<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Notification_Dao
 */
class BTM_Notification_Dao{
	// region Singleton

	/**
	 * @var BTM_Notification_Dao
	 */
	private static $instance = null;
	/**
	 * @return BTM_Notification_Dao
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
	 * @return string
	 */
	public function get_callbacks_table_name(){
		return BTM_Plugin_Options::get_instance()->get_db_table_prefix() . 'notification_callbacks';
	}

	// region CREATE

	/**
	 * @param $callback
	 * @param $webhook
	 * @param $report_type
	 *
	 * @return bool|int
	 */
	public function create( $callback, $webhook, $report_type ){
		global $wpdb;

		$sql = 'INSERT INTO `' . $this->get_callbacks_table_name() . '` (callback_action, webhook, report_type)
				SELECT * FROM (SELECT %s, %s, %s) AS tmp
				WHERE NOT EXISTS (
				    SELECT webhook FROM `' . $this->get_callbacks_table_name() . '` WHERE webhook = %s
				) LIMIT 1';

		$sql = $wpdb->prepare($sql, $callback, $webhook, $report_type, $webhook);

		$inserted = $wpdb->query($sql);


		if( false === $inserted ){
			return false;
		}

		return $wpdb->insert_id;
	}

	// endregion

	// region READ

	/**
	 * @return bool|object
	 */
	public function get_notification_rules(){
		global $wpdb;

		$query = '
			SELECT * 
			FROM `' . $this->get_callbacks_table_name() . '`
		';

		$callbacks = $wpdb->get_results( $query, OBJECT );

		if( empty( $callbacks ) ){
			return false;
		}

		return $callbacks;
	}

	// endregion

	// region UPDATE

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function update( I_BTM_Task $task ){
		global $wpdb;

		$callback_arguments = serialize( $task->get_callback_arguments() );
		$updated = $wpdb->update(
			$this->get_table_name(),
			array(
				'callback_action' => $task->get_callback_action(),
				'callback_arguments' => $callback_arguments,
				'priority' => $task->get_priority(),
				'bulk_size' => $task->get_bulk_size(),
				'status' => $task->get_status()->get_value(),
				'date_created' => date( 'Y-m-d H:i:s' , $task->get_date_created_timestamp() ),
				'type' => BTM_Task_Type_Service::get_instance()->get_type_from_task( $task ),
				'argument_hash' => md5( $callback_arguments )
			),
			array(
				'id' => $task->get_id()
			),
			array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if( false === $updated ){
			return false;
		}

		return true;
	}

	// endregion

	// region DELETE

	/**
	 * @param $notification_id
	 *
	 * @return bool
	 */
	public function delete_notification_rule( $notification_id ){

		global $wpdb;

		$deleted = $wpdb->delete(
			$this->get_callbacks_table_name(),
			array( 'id' => $notification_id ),
			array( '%d' )
		);

		if( false === $deleted || 0 === $deleted ){
			return false;
		}

		return true;
	}

	// endregion
}