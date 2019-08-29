<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Class BTM_Notification_Runner
 */
class BTM_Notification_Runner {

	/**
	 * @var $webhook string
	 */
	private $webhook = null;

	/**
	 * @var $callback_action string
	 */
	private $callback_action = null;

	/**
	 * @var $status string
	 */
	private $status = null;

	/**
	 * @var string $task_url
	 */
	private $task_url = null;

	/**
	 * @var int $task_id
	 */
	private $task_id = null;

	/**
	 * BTM_Notification_Runner constructor.
	 */
	public function __construct() {
	}

	/**
	 * @param I_BTM_Task $task
	 */
	public function notify_failed_task( $task ) {
		$notifications        = BTM_Notification_Dao::get_instance()->get_notification_rules();
		$task_callback_action = $task->get_callback_action();
		$status               = $task->get_status()->get_value();
		$this->task_id        = $task->get_id();

		foreach ( $notifications as $item ) {
			$callback_actions = maybe_unserialize( $item->callback_action );
			$report_type      = maybe_unserialize( $item->report_type );
			$this->webhook    = $item->webhook;
			foreach ( $callback_actions as $callback_action ) {
				if( $callback_action === 'all' && ! $task->is_system() ){
					$this->notification_form( $task_callback_action, $status );
				}elseif( $task_callback_action === $callback_action && $status === 'failed' && in_array( 'failed', $report_type ) ) {
					$this->notification_form( $task_callback_action, $status );
				}
			}

		}
	}

	/**
	 * @param $task_callback_action
	 * @param $status
	 */
	private function notification_form( $task_callback_action, $status ){
		$this->callback_action = $task_callback_action;
		$this->status          = $status;
		$this->task_url        = admin_url( 'admin.php?page=btm-task-view&action=edit&task_id=' . $this->task_id );
		$body                  = json_encode(
			array(
				'content' => 'Task #' . $this->task_id . ' - ' . $this->status,
				'embeds'  => array(
					array(
						'fields' => array(
							array(
								"name"   => "Callback action",
								"value"  => $this->callback_action,
								"inline" => true
							),
							array(
								"name"   => "Status",
								"value"  => $this->status,
								"inline" => true
							),
							array(
								"name"   => "Task url",
								"value"  => $this->task_url,
								"inline" => false
							)
						)
					)
				)
			)
		);
		wp_remote_post(
			$this->webhook,
			array(
				'method'  => 'POST',
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => $body
			)
		);
	}

	/**
	 * @param string $report_range
	 *
	 * @return bool
	 */
	public function report( $report_range ) {
		$notifications = BTM_Notification_Dao::get_instance()->get_notification_rules();

		foreach ( $notifications as $item ) {
			$this->webhook = $item->webhook;
			$report_type   = maybe_unserialize( $item->report_type );
			if ( in_array( $report_range, $report_type ) ) {
				$hour = 0;
				if ( $report_range === 'daily' ) {
					$hour = 24;
				} elseif ( $report_range === 'hourly' ) {
					$hour = 1;
				}

				$tasks            = BTM_Task_Dao::get_instance()->get_last_tasks_by_hours( $hour );
				$callback_actions = BTM_Task_Dao::get_instance()->get_callback_actions();

				$tmp = [];
				foreach ( $callback_actions as $callback_action ) {
					$callback_action = $callback_action->callback_action;

					foreach ( $tasks as $task ) {
						$task_callback_action = $task->get_callback_action();
						$task_status          = $task->get_status()->get_value();

						if ( $callback_action === $task_callback_action ) {
							if ( isset( $tmp[ $callback_action ] ) ) {
								if ( isset( $tmp[ $callback_action ][ $task_status ] ) ) {
									$tmp[ $callback_action ][ $task_status ] ++;
								} else {
									$tmp[ $callback_action ][ $task_status ] = 1;
								}
							} else {
								$tmp[ $callback_action ][ $task_status ] = 1;
							}
						}
					}
				}

				$clb                    = array_keys( $tmp );
				$callback_action_report = '';
				foreach ( $clb as $value ) {
					$callback_action_report .= $value . "\n";
				}

				$fields = [];
				foreach ( $clb as $callback_action ) {
					$statuses = array_keys( $tmp[ $callback_action ] );
					$value    = '';
					foreach ( $statuses as $status ) {
						$value .= $status . ' - ' . $tmp[ $callback_action ][ $status ] . "\n";
					}

					$fields[] = array(
						"name"  => $callback_action,
						"value" => $value
					);
				}

				$content = '';
				if( $report_range === 'daily' ){
					$content = 'Daily Report '. PHP_EOL .' Tasks Count - ' . count( $tasks );
				}elseif ( $report_range === 'hourly' ){
					$content = 'Hourly Report '. PHP_EOL .' Tasks Count - ' . count( $tasks );
				}
				$body = json_encode(
					array(
						'content' => $content,
						'embeds'  => array(
							array(
								'fields' => $fields
							)
						)
					)
				);
				wp_remote_post(
					$this->webhook,
					array(
						'method'  => 'POST',
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'body'    => $body
					)
				);

				return true;
			}
		}

		return false;
	}

}