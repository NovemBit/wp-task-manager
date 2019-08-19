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
		add_action( 'admin_enqueue_scripts', array( $this, 'on_hook_admin_enqueue_scripts' ) );
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
	 * Should only be called from hook admin_enqueue_scripts
	 * @see https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
	 */
	function on_hook_admin_enqueue_scripts() {
		$asset_version = BTM_Plugin_Options::get_instance()->get_asset_version();

		wp_enqueue_script( 'btm-admin-notification-scripts', plugin_dir_url( __DIR__ ) . 'assets/js/notification.js' , array( 'jquery' ), $asset_version , true );
		if( $this->webhook !== null &&
		    $this->callback_action !== null &&
		    $this->status !== null
		){
			wp_localize_script(
				'btm-admin-notification-scripts',
				'ajax_object',
				array(
					'webhook'           => $this->webhook,
					'task_id'           => $this->task_id,
					'task_url'          => $this->task_url,
					'callback_action'   => $this->callback_action,
					'status'            => $this->status,
				)
			);
		}
	}

	/**
	 * Send Notification by callback action and status if exist in Discord Notification rules from settings
	 *
	 * @param BTM_Task $task
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