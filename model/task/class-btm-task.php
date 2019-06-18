<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task
 */
class BTM_Task implements I_BTM_Task{
	/**
	 * @param stdClass $task_obj
	 *
	 * @return BTM_Task
	 */
	public static function create_from_db_obj( stdClass $task_obj ){
		$task = new self(
			$task_obj->callback_action,
			unserialize( $task_obj->callback_arguments ),
			(int) $task_obj->priority,
			(int) $task_obj->bulk_size,
			new BTM_Task_Run_Status( $task_obj->status ),
			strtotime( $task_obj->date_created )
		);

		$task->set_id( (int) $task_obj->id );

		return $task;
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
	 * @var string
	 */
	protected $callback_action;
	/**
	 * @return string
	 */
	public function get_callback_action(){
		return $this->callback_action;
	}
	/**
	 * @param string $callback_action
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $callback_action is not a string or is empty
	 */
	public function set_callback_action( $callback_action ){
		if( ! is_string( $callback_action ) || empty( $callback_action ) ){
			throw new InvalidArgumentException( 'Argument $callback_action should be non empty string. Input was: ' . $callback_action );
		}

		$this->callback_action = $callback_action;
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
	 */
	public function set_priority( $priority ){
		if( ! is_int( $priority ) ){
			throw new InvalidArgumentException( 'Argument $priority should be int. Input was: ' . $priority );
		}

		$this->priority = $priority;
	}

	/**
	 * @var int
	 */
	protected $bulk_size;
	/**
	 * @return int
	 */
	public function get_bulk_size(){
		return $this->bulk_size;
	}
	/**
	 * @param $bulk_size
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $bulk_size is not an int or is less than 0
	 */
	public function set_bulk_size( $bulk_size ){
		if( ! is_int( $bulk_size ) || 0 > $bulk_size ){
			throw new InvalidArgumentException( 'Argument $bulk_size should be non negative int. Input was: ' . $bulk_size );
		}

		$this->bulk_size = $bulk_size;
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
	 * @param string $callback_action
	 * @param mixed[] $callback_arguments
	 * @param int $priority
	 * @param int $bulk_size
	 * @param BTM_Task_Run_Status $status
	 * @param int|null $date_created_timestamp
	 */
	public function __construct(
		$callback_action,
		array $callback_arguments = array(),
		$priority = 10,
		$bulk_size = 1,
		BTM_Task_Run_Status $status = null,
		$date_created_timestamp = null
	){
		$this->set_callback_action( $callback_action );
		$this->set_callback_arguments( $callback_arguments );
		$this->set_priority( $priority );
		$this->set_bulk_size( $bulk_size );

		if( null !== $status ){
			$this->set_status( $status );
		}

		if( null !== $date_created_timestamp ){
			$this->set_date_created_timestamp( $date_created_timestamp );
		}
	}
}