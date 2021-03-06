<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_Bulk_Argument
 */
class BTM_Task_Bulk_Argument {
	/**
	 * @param stdClass $task_bulk_argument_obj
	 *
	 * @return BTM_Task_Bulk_Argument
	 */
	public static function create_from_db_obj( stdClass $task_bulk_argument_obj ){
		$task_bulk_argument = new self(
			unserialize( $task_bulk_argument_obj->callback_arguments ),
			(int) $task_bulk_argument_obj->priority,
			new BTM_Task_Run_Status( $task_bulk_argument_obj->status ),
			strtotime( $task_bulk_argument_obj->date_created )
		);

		$task_bulk_argument->set_task_id( (int) $task_bulk_argument_obj->task_id );
		$task_bulk_argument->set_id( (int) $task_bulk_argument_obj->id );

		return $task_bulk_argument;
	}
	/**
	 * Converts BTM_Task_Bulk_Argument instances into arrays
	 *
	 * @param BTM_Task_Bulk_Argument[] $task_bulk_arguments
	 *
	 * @return array
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument array $task_bulk_arguments contains non BTM_Task_Bulk_Argument instance
	 */
	public static function convert_to_array( array $task_bulk_arguments ){
		$data = array();
		/** @var BTM_Task_Bulk_Argument $argument */
		foreach ( $task_bulk_arguments as $argument ){
			if( ! is_a( $argument, 'BTM_Task_Bulk_Argument' ) ){
				throw new InvalidArgumentException(
					'The argument array $task_bulk_arguments should only contain BTM_Task_Bulk_Argument instances'
				);
			}

			$arg_array = array();
			if( $argument->get_id() ){
				$arg_array['id'] = $argument->get_id();
			}
			if( $argument->get_task_id() ){
				$arg_array['task_id'] = $argument->get_task_id();
			}
			if( $argument->get_callback_arguments() ){
				$arg_array['callback_arguments'] = $argument->get_callback_arguments();
			}
			if( $argument->get_priority() ){
				$arg_array['priority'] = $argument->get_priority();
			}
			if( $argument->get_status() ){
				$arg_array['status'] = $argument->get_status()->get_value();
			}
			if( $argument->get_date_created_timestamp() ){
				$arg_array['date_created_timestamp'] = $argument->get_date_created_timestamp();
			}
			$data[] = $arg_array;
		}

		return $data;
	}
	/**
	 * @param array $arguments
	 *
	 * @return BTM_Task_Bulk_Argument[]
	 */
	public static function convert_from_array( array $arguments ){
		$btm_arguments = array();
		foreach( $arguments as $argument ){
			$btm_argument = new BTM_Task_Bulk_Argument();

			if( isset( $argument['id'] ) ){
				$btm_argument->set_id( (int) $argument['id'] );
			}
			if( isset( $argument['task_id'] ) ){
				$btm_argument->set_task_id( (int) $argument['task_id'] );
			}
			if( isset( $argument['callback_arguments'] ) ){
				$btm_argument->set_callback_arguments( $argument['callback_arguments'] );
			}
			if( isset( $argument['priority'] ) ){
				$btm_argument->set_priority( (int) $argument['priority'] );
			}
			if( isset( $argument['status'] ) ){
				$btm_argument->set_status( new BTM_Task_Run_Status( $argument['status'] ) );
			}
			if( isset( $argument['date_created_timestamp'] ) ){
				$btm_argument->set_date_created_timestamp( (int) $argument['date_created_timestamp'] );
			}

			$btm_arguments[] = $btm_argument;
		}

		return $btm_arguments;
	}

	/**
	 * @var int
	 */
	protected $id;
	/**
	 * @return int
	 */
	public function get_id(){
		return $this->id;
	}
	/**
	 * @param int $id
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $id is not a positive int
	 * @throws LogicException
	 *      in the case the id is already evaluated
	 */
	public function set_id( $id ){
		if( ! is_int( $id ) || 0 >= $id ){
			throw new InvalidArgumentException( 'Argument $id should be positive int. Input was: ' . $id );
		}
		if( ! empty( $this->id ) ){
			throw new LogicException( 'Id is already evaluated.' );
		}

		$this->id = $id;
	}

	/**
	 * @var int
	 */
	protected $task_id;
	/**
	 * @return int
	 */
	public function get_task_id(){
		return $this->task_id;
	}
	/**
	 * @param int $task_id
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $task_id is not a positive int
	 * @throws LogicException
	 *      in the case the task_id is already evaluated
	 */
	public function set_task_id( $task_id ){
		if( ! is_int( $task_id ) || 0 >= $task_id ){
			throw new InvalidArgumentException( 'Argument $task_id should be positive int. Input was: ' . $task_id );
		}
		if( ! empty( $this->task_id ) ){
			throw new LogicException( 'Task id is already evaluated.' );
		}

		$this->task_id = $task_id;
	}

	/**
	 * @var mixed[]
	 */
	protected $callback_arguments;
	/**
	 * @return mixed[]
	 */
	public function get_callback_arguments(){
		return $this->callback_arguments;
	}
	/**
	 * @param mixed[] $callback_arguments
	 */
	public function set_callback_arguments( array $callback_arguments ){
		// @todo: check arguments to be serializable,
		// log error otherwise?
		ksort( $callback_arguments );
		$this->callback_arguments = $callback_arguments;
	}

	/**
	 * @var int
	 */
	protected $priority;
	/**
	 * @return int
	 */
	public function get_priority(){
		return $this->priority;
	}
	/**
	 * @param int $priority
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $priority is not an int
	 *      or is not between BTM_Plugin_Options::get_max_priority() and BTM_Plugin_Options::get_min_priority()
	 */
	public function set_priority( $priority ){
		$plugin_options = BTM_Plugin_Options::get_instance();
		$max_priority = $plugin_options->get_max_priority();
		$min_priority = $plugin_options->get_min_priority();
		if( ! is_int( $priority ) || $max_priority > $priority || $min_priority < $priority ){
			throw new InvalidArgumentException(
				'Argument $priority should be int between ' . $max_priority . ' and ' . $min_priority . '. Input was: ' . $priority
			);
		}

		$this->priority = $priority;
	}

	/**
	 * @var BTM_Task_Run_Status
	 */
	protected $status;
	/**
	 * @return BTM_Task_Run_Status
	 */
	public function get_status(){
		return $this->status;
	}
	/**
	 * @param BTM_Task_Run_Status $status
	 */
	public function set_status( BTM_Task_Run_Status $status ){
		$this->status = $status;
	}

	/**
	 * @var int
	 */
	protected $date_created_timestamp;
	/**
	 * @return int
	 */
	public function get_date_created_timestamp(){
		return $this->date_created_timestamp;
	}
	/**
	 * @param int $date_created_timestamp
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_created_timestamp is not an int
	 */
	public function set_date_created_timestamp( $date_created_timestamp ){
		if( ! is_int( $date_created_timestamp ) ){
			throw new InvalidArgumentException( 'Argument $date_created should be int. Input was: ' . $date_created_timestamp );
		}

		$this->date_created_timestamp = $date_created_timestamp;
	}

	/**
	 * BTM_Task constructor.
	 *
	 * @param mixed[] $callback_arguments
	 * @param int $priority
	 * @param BTM_Task_Run_Status $status
	 * @param int|null $date_created_timestamp
	 */
	public function __construct(
		array $callback_arguments = array(),
		$priority = 10,
		BTM_Task_Run_Status $status = null,
		$date_created_timestamp = null
	){
		$this->set_callback_arguments( $callback_arguments );
		$this->set_priority( $priority );

		if( null !== $status ){
			$this->set_status( $status );
		}

		if( null !== $date_created_timestamp ){
			$this->set_date_created_timestamp( $date_created_timestamp );
		}
	}
}