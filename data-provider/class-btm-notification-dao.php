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
	 * @return bool|array
	 */
	public function get_notification_rules(){
		global $wpdb;

		$query = '
			SELECT * 
			FROM `' . $this->get_callbacks_table_name() . '`
		';

		$rules = $wpdb->get_results( $query, OBJECT );

		if( empty( $rules ) ){
			return false;
		}

		return $rules;
	}

	/**
	 * @return bool|object
	 */
	public function get_notification_rule_by_id( $id ){
		global $wpdb;

		$query = '
			SELECT * 
			FROM `' . $this->get_callbacks_table_name() . '`
			WHERE id = '. $id .'
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
	 * @param $id
	 * @param $callback
	 * @param $webhook
	 * @param $report_type
	 *
	 * @return bool
	 */
	public function update( $id, $callback, $webhook, $report_type ){
		global $wpdb;

		$updated = $wpdb->update(
			$this->get_callbacks_table_name(),
			array(
				'id' => $id,
				'callback_action' => $callback,
				'webhook' => $webhook,
				'report_type' => $report_type
			),
			array(
				'id' => $id
			),
			array( '%d', '%s', '%s', '%s' ),
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