<?php
/*
Plugin Name: WP Integrity Restore
Plugin URI: https://dylanonline.nl
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

    public function __construct()
    {
        $this->integrity_checker = new IntegrityChecker();
        $this->backup_manager = new BackupManager();
        $this->logger = new Logger();
        $this->scheduler = new Scheduler();

        add_action('init', [$this->integrity_checker, 'check_index_file_integrity']);
        add_action('wp', [$this->scheduler, 'schedule_file_check']);
        add_action('check_index_file_integrity_event', [$this->integrity_checker, 'check_index_file_integrity']);

        register_activation_hook(__FILE__, [$this, 'on_plugin_activation']);
        register_deactivation_hook(__FILE__, [$this->scheduler, 'clear_scheduled_file_check']);

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
    }

    public function on_plugin_activation()
    {
        $this->integrity_checker->check_if_altered_before_activation();
        $this->backup_manager->restore_index_file(IntegrityChecker::KNOWN_GOOD_INDEX_PHP_CONTENT);
        $this->integrity_checker->store_original_index_file_hash();
    }

    // Add admin menu and page
    public function add_admin_menu()
    {
        add_management_page(
            'WP Integrity Check',
            'Integrity Check',
            'manage_options',
            'wp-integrity-check',
            [$this, 'render_admin_page']
        );
    }

    public function enqueue_admin_styles()
    {
        wp_enqueue_style('wp-integrity-restore-admin-style', plugin_dir_url(__FILE__) . 'css/admin-style.css');
    }

    // Render the custom admin page with additional buttons for lock/unlock/restore
    public function render_admin_page()
    {
        // Check if the user clicked the "Check Now" button
        if (isset($_POST['check_now'])) {
            $result = $this->integrity_checker->check_index_file_integrity();

            if ($result) {
                $message = "The index.php file is intact and unaltered.";
                $class = "updated";
            } else {
                $message = "Warning: The index.php file has been altered!";
                $class = "error";
            }

            echo "<div class=\"$class notice\"><p>$message</p></div>";
        }

        // Handle Unlock & Restore
        if (isset($_POST['unlock_restore'])) {
            $this->integrity_checker->unlock_and_restore_index_file();

            echo '<div class="updated notice"><p>index.php has been unlocked, restored, and a new hash has been saved.</p></div>';
        }

        // Handle Lock
        if (isset($_POST['lock_file'])) {
            $this->integrity_checker->lock_index_file();

            echo '<div class="updated notice"><p>index.php has been locked and set to read-only.</p></div>';
        }

        // Handle Unlock
        if (isset($_POST['unlock_file'])) {
            $this->integrity_checker->unlock_index_file();

            echo '<div class="updated notice"><p>index.php has been unlocked (writable).</p></div>';
        }

        // Render the admin page content
?>
        <div class="wrap">
            <h1>WP Integrity Restore - File Integrity Actions</h1>
            <p>Use the buttons below to manually check, lock, unlock, or restore the integrity of your <code>index.php</code> file.</p>
            <form method="post" action="">
                <div class="buttons">
                    <h2>Check if your index.php file is okay:</h2>
                    <?php submit_button('Check Now', 'primary', 'check_now'); ?>
                </div>
                <div class="buttons">
                    <h2>If not okay, unlock the file and restore the original content:</h2>
                    <?php submit_button('Unlock & Restore', 'secondary', 'unlock_restore'); ?>
                </div>
                <div class="buttons">
                    <h2>Lock File:</h2>
                    <?php submit_button('Lock File', 'primary', 'lock_file'); ?>
                </div>
                <div class="buttons">
                    <h2>Unlock File:</h2>
                    <?php submit_button('Unlock File', 'secondary', 'unlock_file'); ?>
                </div>
            </form>
        </div>
<?php
    }
}

new WPIntegrityRestore();
