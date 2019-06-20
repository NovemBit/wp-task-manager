<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Bulk_Argument_Manager
 */
class BTM_Task_Bulk_Argument_Manager {
	// region Singleton

	/**
	 * @var BTM_Task_Bulk_Argument_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Bulk_Argument_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct(){
		add_filter( 'btm_' . $this->get_callback_action(), array( $this, 'on_btm_normalize_task_bulk_arguments' ), 5, 2 );
	}
	private function __clone(){}
	private function __wakeup(){}

	// endregion

	public function get_callback_action(){
		return 'normalize_task_bulk_arguments';
	}

	/**
	 * @param int $task_id
	 * @param BTM_Task_Bulk_Argument[] $bulk_arguments_to_keep_higher_priority
	 * @param BTM_Task_Bulk_Argument[] $bulk_arguments_to_overwrite
	 *
	 * @return BTM_Task_Bulk_Argument_Normalizer
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $task_id is not a positive int
	 */
	public function create_task(
		$task_id,
		array $bulk_arguments_to_keep_higher_priority,
		array $bulk_arguments_to_overwrite
	){
		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException('Argument $task_id should be positive int. Input was: ' . $task_id );
		}

		$bulk_arguments = array(
			'task_id' => $task_id,
			'to_keep_higher_priority' => BTM_Task_Bulk_Argument::convert_to_array( $bulk_arguments_to_keep_higher_priority ),
			'to_overwrite' => BTM_Task_Bulk_Argument::convert_to_array( $bulk_arguments_to_overwrite )
		);

		return new BTM_Task_Bulk_Argument_Normalizer( $this->get_callback_action(), $bulk_arguments, -1000 );
	}

	public function on_btm_normalize_task_bulk_arguments( $logs, $args ){
		$db_transaction = BTM_DB_Transaction::get_instance();

		$task_id = $args['task_id'];
		$to_keep_higher_priority = BTM_Task_Bulk_Argument::convert_from_array( $args['to_keep_higher_priority'] );
		$to_overwrite = BTM_Task_Bulk_Argument::convert_from_array( $args['to_overwrite'] );

		$db_transaction->start();

		try{
			$inserted_to_keep_higher_priority = BTM_Task_Bulk_Argument_Dao::get_instance()->add_many_to_keep_higher_priority(
				$task_id,
				$to_keep_higher_priority
			);
			$inserted_to_overwrite = BTM_Task_Bulk_Argument_Dao::get_instance()->add_many_to_overwrite(
				$task_id,
				$to_overwrite
			);
		}catch( Exception $exception ){
			$db_transaction->rollback();
			$logs[] = $exception->getMessage();
			return $logs;
		}

		if( true !== $inserted_to_keep_higher_priority || true !== $inserted_to_overwrite ){
			$db_transaction->rollback();
			$logs[] = __( 'Could not insert bulk arguments with keeping higher priority', 'background_task_manager' );
			return $logs;
		}

		$logs[] = __( 'Bulk arguments inserted successfully', 'background_task_manager' );
		$db_transaction->commit();
		return $logs;
	}
}