<?php

if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Task_View_Filter
 */
class BTM_Task_View_Filter extends BTM_View_Search_Status_Filter{
	/**
	 * @var string
	 */
	protected $callback = '';
	/**
	 * @return string
	 */
	public function get_callback(){
		return $this->callback;
	}
	/**
	 * @param string $callback
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $callback is not a string
	 */
	public function set_callback( $callback ){
		if( ! is_string( $callback ) ){
			throw new InvalidArgumentException('Argument $callback should be string. Input was: ' . $callback );
		}

		$this->callback = $callback;
	}
	/**
	 * @return bool
	 */
	public function has_callback(){
		if( 0 < strlen( $this->callback ) ){
			return true;
		}

		return false;
	}

	/**
	 * @var string
	 */
	protected $date_start = '';
	/**
	 * @return string
	 */
	public function get_date_start(){
		return $this->date_start;
	}
	/**
	 * @return string
	 */
	public function get_date_start_short(){
		if( ! $this->has_date_start() ){
			return '';
		}else{
			return explode( ' ', $this->date_start )[0];
		}
	}
	/**
	 * @param string $date_start should be Y-m-d or Y-m-d H:i:s
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_start is not a valid date(string)
	 */
	public function set_date_start( $date_start ){
		if( false === strpos( $date_start, ' ' ) ){
			$date_start .= ' 00:00:00';
		}
		if( '' !== $date_start && ! $this->is_valid_date( $date_start ) ){
			throw new InvalidArgumentException('Argument $date_start should be valid date. Input was: ' . $date_start );
		}

		$this->date_start = $date_start;
	}
	/**
	 * @return bool
	 */
	public function has_date_start(){
		if( 0 < strlen( $this->date_start ) ){
			return true;
		}

		return false;
	}

	/**
	 * @var string
	 */
	protected $date_end = '';
	/**
	 * @return string
	 */
	public function get_date_end(){
		return $this->date_end;
	}
	/**
	 * @return string
	 */
	public function get_date_end_short(){
		if( ! $this->has_date_end() ){
			return '';
		}else{
			return explode( ' ', $this->date_end )[0];
		}
	}
	/**
	 * @param string $date_end should be Y-m-d or Y-m-d H:i:s
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $date_end is not a valid date(string)
	 */
	public function set_date_end( $date_end ){
		if( false === strpos( $date_end, ' ' ) ){
			$date_end .= ' 00:00:00';
		}
		if( '' !== $date_end &&  ! $this->is_valid_date( $date_end ) ){
			throw new InvalidArgumentException('Argument $date_end should be valid date. Input was: ' . $date_end );
		}

		$this->date_end = $date_end;
	}
	/**
	 * @return bool
	 */
	public function has_date_end(){
		if( 0 < strlen( $this->date_end ) ){
			return true;
		}

		return false;
	}

	/**
	 * @param string $date_string
	 *
	 * @return bool
	 */
	protected function is_valid_date( $date_string ){

		$format = 'Y-m-d H:i:s';
		$date = DateTime::createFromFormat($format, $date_string);
		return $date && $date->format($format) == $date_string;
	}

	/**
	 * @var bool
	 */
	protected $show_system = false;
	/**
	 * @return bool
	 */
	public function show_system(){
		return $this->show_system;
	}
	/**
	 * @param bool $show_system
	 *
	 * @throws InvalidArgumentException
	 *      in the case the argument $show_system is not a bool
	 */
	public function set_show_system( $show_system ){
		if( ! is_bool( $show_system ) ){
			throw new InvalidArgumentException('Argument $show_system should be valid bool. Input was: ' . $show_system);
		}

		$this->show_system = $show_system;
	}
}