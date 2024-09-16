<?php
class IntegrityChecker 
{
    const INDEX_PHP_PATH = ABSPATH . 'index.php';
    const KNOWN_GOOD_INDEX_PHP_HASH = 'YOUR_ORIGINAL_INDEX_PHP_FILE_HASH'; // Replace with actual hash

    public function get_index_file_hash() 
    {
        return hash_file('sha256', self::INDEX_PHP_PATH);
    }

    public function store_original_index_file_hash() 
    {
        if (!get_option('index_php_original_hash')) {
            update_option('index_php_original_hash', $this->get_index_file_hash());
        }
    }

    public function check_if_altered_before_activation() 
    {
        $current_hash = $this->get_index_file_hash();

        if ($current_hash !== self::KNOWN_GOOD_INDEX_PHP_HASH) {
            wp_mail(get_option('admin_email'), 'Alert: index.php was altered before plugin installation', 'Your index.php file was altered before installing the plugin.');
        }
    }

    public function check_index_file_integrity() 
    {
        $original_hash = get_option('index_php_original_hash');

        if (!$original_hash) {
            $this->store_original_index_file_hash();
            return;
        }

        $current_hash = $this->get_index_file_hash();
        if ($original_hash !== $current_hash) {
            $backup_manager = new BackupManager();
            $backup_manager->restore_index_file();

            $logger = new Logger();
            $logger->log_unauthorized_access();

            wp_mail(get_option('admin_email'), 'Alert: index.php file restored', 'Your index.php file was altered and has been restored.');
        }
    }
}

?>