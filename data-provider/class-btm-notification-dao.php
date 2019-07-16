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

	/**
	 * @return string
	 */
	public function get_users_table_name(){
		return BTM_Plugin_Options::get_instance()->get_db_table_prefix() . 'notificatoin_users';
	}

	// region CREATE

	/**
	 * @param  $notification
	 *
	 * @return bool
	 */
	public function create_callback( $notification ){
		global $wpdb;

		$data_callbacks = array(
			'callback_action'   => $notification[ "callback_action" ],
			'status'            => $notification[ "status" ],
		);
		$format_callbacks = array( '%s', '%s' );

		$inserted = $wpdb->insert(
			$this->get_callbacks_table_name(),
			$data_callbacks,
			$format_callbacks
		);

		if( false === $inserted ){
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * @param  $user
	 *
	 * @return bool
	 */
	public function create_users( $user, $last_insert_id ){
		global $wpdb;

		$data_users = array(
			'notification_callback_id' => $last_insert_id,
			'user_id'                  => $user,
		);
		$format_users = array( '%s', '%s' );

		$inserted = $wpdb->insert(
			$this->get_users_table_name(),
			$data_users,
			$format_users
		);

		if( false === $inserted ){
			return false;
		}

		return true;
	}

	// endregion

	// region READ

	public function get_callback_actions_and_statuses(){
		global $wpdb;

		$query = '
			SELECT * 
			FROM `' . $this->get_callbacks_table_name() . '`
		';

		$callbacks_and_statuses = $wpdb->get_results( $query, 'OBJECT' );

		if( empty( $callbacks_and_statuses ) ){
			return false;
		}

		return $callbacks_and_statuses;
	}

	public function get_users(){
		global $wpdb;

		$query = '
			SELECT * 
			FROM `' . $this->get_users_table_name() . '`
		';

		$users = $wpdb->get_results( $query, 'OBJECT' );

		if( empty( $users ) ){
			return false;
		}

		return $users;
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

	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_running( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_RUNNING ) );
		return $this->update( $task );
	}
	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_succeeded( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_SUCCEEDED ) );
		return $this->update( $task );
	}
	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_failed( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_FAILED ) );
		return $this->update( $task );
	}
	/**
	 * @param I_BTM_Task $task
	 *
	 * @return bool
	 */
	public function mark_as_in_progress( I_BTM_Task $task ){
		$task->set_status( new BTM_Task_Run_Status( BTM_Task_Run_Status::STATUS_IN_PROGRESS ) );
		return $this->update( $task );
	}

	// endregion

	// region DELETE

	/**
	 * @param $callback_action_id
	 * @param $user_id
	 *
	 * @return bool
	 */
	public function delete_user( $callback_action_id, $user_id ){

		global $wpdb;

		$deleted = $wpdb->delete(
			$this->get_users_table_name(),
			array( 'notification_callback_id' => $callback_action_id, 'user_id' => $user_id ),
			array( '%d', '%d' )
		);

		if( false === $deleted || 0 === $deleted ){
			return false;
		}

		return true;
	}

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

		$wpdb->delete(
			$this->get_users_table_name(),
			array( 'notification_callback_id' => $notification_id ),
			array( '%d' )
		);

		if( false === $deleted || 0 === $deleted ){
			return false;
		}

		return true;
	}

	// endregion

	/**
	 * @param stdClass $task_obj
	 *
	 * @return I_BTM_Task
	 */
	protected function create_task_from_db_obj( stdClass $task_obj ){
		$class_name = BTM_Task_Type_Service::get_instance()->get_class_from_type( $task_obj->type );

		/** @var I_BTM_Task $class_name */
		return $class_name::create_from_db_obj( $task_obj );
	}
}