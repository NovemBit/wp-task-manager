<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/*
 * Registers background tasks
 * Checks the run restrictions and runs the tasks in a loop
 * Logs information about run restrictions and about the tasks it request to run
 */
final class BTM_Task_Manager{
	// region Singleton

	/**
	 * @var BTM_Task_Manager
	 */
	private static $instance = null;
	/**
	 * @return BTM_Task_Manager
	 */
	public static function get_instance(){
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->task_runner = BTM_Task_Runner::get_the_instance_once();
	}
	private function __clone() {}
	private function __wakeup() {}

	// endregion

	/**
	 * @var BTM_Task_Runner
	 */
	private $task_runner = null;

	/**
	 * Checks run restrictions, runs the tasks, logs steps
	 */
	public function run_the_tasks(){
		$task_manager_log_dao = BTM_Task_Manager_Log_Dao::get_instance();
		$task_manager_log_dao->log( __( 'Started running the tasks', 'background_task_manager' ) );

		while( true ){
			$restriction_message = BTM_Run_Restrictor::get_instance()->check_all_restrictions();
			if( true === $restriction_message ){

				$task_to_run = BTM_Task_Dao::get_instance()->get_next_task_to_run();
				if( false === $task_to_run ){
					$task_manager_log_dao->log( __( 'There are no tasks to run', 'background_task_manager' ) );
					break;
				}else{
					if( 0 < $task_to_run->get_bulk_size() ){
						$log_message = sprintf(
							__( 'Started running bulk task %s with id: %s : %s', 'background_task_manager' ),
							BTM_Task_Type_Service::get_instance()->get_type_from_task( $task_to_run ),
							$task_to_run->get_id(),
							$task_to_run->get_callback_action()
						);
					}else{
						$log_message = sprintf(
							__( 'Started running simple task %s with id: %s : %s', 'background_task_manager' ),
							BTM_Task_Type_Service::get_instance()->get_type_from_task( $task_to_run ),
							$task_to_run->get_id(),
							$task_to_run->get_callback_action()
						);
					}

					$task_manager_log_dao->log( $log_message );
					$this->task_runner->run_task( $task_to_run );
				}
			}else{
				$task_manager_log_dao->log( $restriction_message );
				break;
			}
		}

		$task_manager_log_dao->log( __( 'Finished running the tasks', 'background_task_manager' ) );
	}

	/**
	 * Registers a task to run later in background
	 *
	 * @param I_BTM_Task $task
	 * @param BTM_Task_Bulk_Argument[] $bulk_arguments_to_keep_higher_priority
	 * @param BTM_Task_Bulk_Argument[] $bulk_arguments_to_overwrite
	 *
	 * @return bool
	 */
	public function register_task(
		I_BTM_Task $task,
		array $bulk_arguments_to_keep_higher_priority,
		array $bulk_arguments_to_overwrite
	){
		$task_dao = BTM_Task_Dao::get_instance();
		$db_transaction = BTM_DB_Transaction::get_instance();

		if( 0 < $task->get_bulk_size() ){
			$existing_task = $task_dao->get_existing_task( $task );

			$db_transaction->start();

			if( false === $existing_task ){
				$created = $task_dao->create( $task );
				if( true !== $created ){
					$db_transaction->rollback();
					return false;
				}
				$task_id = $task->get_id();
			}else{
				$existing_task->set_bulk_size( $task->get_bulk_size() );
				if( $task->get_status() ){
					$existing_task->set_status( $task->get_status() );
				}

				$updated = $task_dao->update( $existing_task );
				if( true !== $updated ){
					$db_transaction->rollback();
					return false;
				}
				$task_id = $existing_task->get_id();
			}

			$argument_normalization_task = BTM_Task_Bulk_Argument_Manager::get_instance()->create_task(
				$task_id,
				$bulk_arguments_to_keep_higher_priority,
				$bulk_arguments_to_overwrite
			);
			$created = $task_dao->create( $argument_normalization_task );
			if( true !== $created ){
				$db_transaction->rollback();
				return false;
			}

			$db_transaction->commit();
			return true;
		}else{
			return $task_dao->create( $task );
		}
	}
}