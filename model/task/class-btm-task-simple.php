<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Simple
 */
class BTM_Task_Simple extends BTM_Task {
	/**
	 * @param stdClass $task_obj
	 *
	 * @return BTM_Task_Simple
	 */
	public static function create_from_db_obj( stdClass $task_obj ){
		$task = new self(
			$task_obj->callback_action,
			unserialize( $task_obj->callback_arguments ),
			(int) $task_obj->priority,
			new BTM_Task_Run_Status( $task_obj->status ),
			strtotime( $task_obj->date_created )
		);

		$task->set_id( (int) $task_obj->id );

		return $task;
	}

	/**
	 * BTM_Task_Bulk constructor.
	 *
	 * @param string $callback_action
	 * @param mixed[] $callback_arguments
	 * @param int $priority
	 * @param BTM_Task_Run_Status $status
	 * @param int|null $date_created_timestamp
	 */
	public function __construct(
		$callback_action,
		array $callback_arguments = array(),
		$priority = 10,
		BTM_Task_Run_Status $status = null,
		$date_created_timestamp = null
	){
		parent::__construct(
			$callback_action,
			$callback_arguments,
			$priority,
			0,
			$status,
			$date_created_timestamp
		);
	}
}