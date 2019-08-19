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
	 *
	 * @param I_BTM_Task $task
	 */
	public function __construct( $task ) {
		$this->init_webhook();
		$callbacks_and_statuses = BTM_Notification_Dao::get_instance()->get_callback_actions_and_statuses();
		$callback_action        = $task->get_callback_action();
		$status                 = $task->get_status()->get_value();
		$this->task_id          = $task->get_id();
		foreach ( $callbacks_and_statuses as $item ) {
			if ( $callback_action === $item->callback_action && $status === $item->status ) {
				$this->callback_action = $item->callback_action;
				$this->status          = $item->status;
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

				$asd = wp_remote_post(
					$this->webhook,
					array(
						'method'  => 'POST',
						'headers' => array(
							'Content-Type' => 'application/json',
						),
						'body'    => $body
					)
				);
				var_dump( $asd );
				die;
			}
		}
	}

	/**
	 * Initialize Discord webhook
	 */
	private function init_webhook() {
		if ( BTM_Plugin_Options::get_instance()->get_discord_webhook() !== '' ) {
			$this->webhook = BTM_Plugin_Options::get_instance()->get_discord_webhook();
		}
	}

}