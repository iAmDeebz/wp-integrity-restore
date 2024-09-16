<?php
/*
Plugin Name: WP Integrity Restore
Plugin URI: https://wpir.dylanonline.nl
Description: Restores WP file integrity and prevents malicious code injection
Version: 1.0
Author: Dylan Online
Author URI: https://dylanonline.nl/
*/

include_once "classes/IntegrityChecker.php";
include_once "classes/BackupManager.php";
include_once "classes/Logger.php";
include_once "classes/Scheduler.php";
include_once "classes/FileLocker.php";

class WPIntegrityRestore 
{
    protected $integrity_checker;
    protected $backup_manager;
    protected $logger;
    protected $scheduler;

    public function __construct() {
        $this->integrity_checker = new IntegrityChecker();
        $this->backup_manager = new BackupManager();
        $this->logger = new Logger();
        $this->scheduler = new Scheduler();

        add_action('init', [$this->integrity_checker, 'check_index_file_integrity']);
        add_action('wp', [$this->scheduler, 'schedule_file_check']);
        add_action('check_index_file_integrity_event', [$this->integrity_checker, 'check_index_file_integrity']);

        register_activation_hook(__FILE__, [$this, 'on_plugin_activation']);
        register_deactivation_hook(__FILE__, [$this->scheduler, 'clear_scheduled_file_check']);
    }

    public function on_plugin_activation() {
        $this->integrity_checker->check_if_altered_before_activation();
        $this->backup_manager->create_index_backup();
        $this->integrity_checker->store_original_index_file_hash();
    }
}

new WPIntegrityRestore();
?>