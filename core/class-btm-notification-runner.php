<?php


if ( ! defined( 'BTM_PLUGIN_ACTIVE' ) ) {
	exit;
}

/**
 * Should only be used from the BTM_Notification_Runner
 *
 * Class BTM_Notification_Runner
 */
final class BTM_Notification_Runner {
	// region Singleton

	/**
	 * @var bool
	 */
	private static $instance = null;

	/**
	 * @return BTM_Notification_Runner
	 *
	 * @throws Exception
	 *      in the case this method called more than once
	 */
	public static function get_instance() {
		if( null === self::$instance ){
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->init_webhook();
	}

	private function __clone() {
	}

	private function __wakeup() {
	}

	// endregion

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
	 * Send Notification by callback action and status if exist in Discord Notification rules from settings
	 *
	 * @param I_BTM_Task $task
	 */
	public function send( $task ){
		$callbacks_and_statuses = BTM_Notification_Dao::get_instance()->get_callback_actions_and_statuses();
		$callback_action = $task->get_callback_action();
		$status = $task->get_status()->get_value();
		$this->task_id = $task->get_id();
		foreach ( $callbacks_and_statuses as $item ){
			if( $callback_action === $item->callback_action && $status === $item->status ){
				$this->callback_action = $item->callback_action;
				$this->status = $item->status;
				$this->task_url = admin_url( 'admin.php?page=btm-task-view&action=edit&task_id=' . $this->task_id );
				wp_remote_post(
					$this->webhook,
					array(
						'method'  => 'POST',
						'headers' => array(
							'Content-Type'  => 'application/json',
						),
						'body' => array(
									'content' => 'Task #' . $this->task_id . ' - ' . $this->status,
									'embeds'  => array(
													'fields' => array(
																	array(
																		"name"=> "Callback action",
																		"value"=> $this->callback_action,
																		"inline"=> true
																	),
																	array(
																		"name"=> "Status",
																		"value"=> $this->status,
																		"inline"=> true
																	),
																	array(
																		"name"=> "Task url",
																		"value"=> $this->task_url,
																		"inline"=> false
																	)
																)
												)
								),
					)
				);
			}
		}
	}

	/**
	 * Initialize Discord webhook
	 */
	private function init_webhook(){
		if( BTM_Plugin_Options::get_instance()->get_discord_webhook() !== '' ){
			$this->webhook = BTM_Plugin_Options::get_instance()->get_discord_webhook();
		}
    }

}