<?php 
class IntegrityChecker
{
    const INDEX_PHP_PATH = ABSPATH . 'index.php';
    const KNOWN_GOOD_INDEX_PHP_CONTENT = "<?php\n/**\n * Front to the WordPress application.\n * This file doesn't do anything, but loads\n * wp-blog-header.php which does and tells WordPress to load the theme.\n *\n * @package WordPress\n */\n\n/**\n * Tells WordPress to load the WordPress environment and template.\n */\nrequire __DIR__ . '/wp-blog-header.php';\n";

    public function get_index_file_hash()
    {
        return hash_file('sha256', self::INDEX_PHP_PATH);
    }

    public function store_original_index_file_hash()
    {
        if (!get_option('index_php_original_hash')) {
            update_option('index_php_original_hash', $this->get_index_file_hash());
        }

        if (!get_option('index_php_original_content')) {
            update_option('index_php_original_content', file_get_contents(self::INDEX_PHP_PATH));
        }
    }

    public function check_if_altered_before_activation()
    {
        $current_hash = $this->get_index_file_hash();
        $original_hash = get_option('index_php_original_hash');

        // Restore the index.php file if it has been altered
        if ($original_hash && $current_hash !== $original_hash) {
            // Restore the original content
            file_put_contents(self::INDEX_PHP_PATH, self::KNOWN_GOOD_INDEX_PHP_CONTENT);
            // Update the hash in the database
            update_option('index_php_original_hash', $this->get_index_file_hash());
            wp_mail(get_option('admin_email'), 'Alert: index.php was restored before plugin activation', 'Your index.php file was altered before activating the plugin and has been restored to its original state.');
        }
    }

    public function check_index_file_integrity()
    {
        $original_hash = get_option('index_php_original_hash');
        if (!$original_hash) {
            $this->store_original_index_file_hash();
            return true;
        }

        $current_hash = $this->get_index_file_hash();
        if ($original_hash !== $current_hash) {
            $this->restore_original_index_file();
            $logger = new Logger();
            $logger->log_unauthorized_access();
            wp_mail(get_option('admin_email'), 'Alert: index.php file restored', 'Your index.php file was altered and has been restored.');
            return false;
        }

        return true;
    }

    public function unlock_and_restore_index_file()
    {
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            wp_die('You do not have permission to unlock this file.');
        }

        $this->set_file_permissions(self::INDEX_PHP_PATH, 0644);
        file_put_contents(self::INDEX_PHP_PATH, self::KNOWN_GOOD_INDEX_PHP_CONTENT);
        $new_hash = $this->get_index_file_hash();
        update_option('index_php_original_hash', $new_hash);
        $logger = new Logger();
        $logger->log_unauthorized_access('index.php was unlocked and restored.');
        $this->lock_index_file();
    }

    public function lock_index_file()
    {
        $this->set_file_permissions(self::INDEX_PHP_PATH, 0444);
        $logger = new Logger();
        $logger->log_unauthorized_access('index.php file locked.');
    }

    public function unlock_index_file()
    {
        $this->set_file_permissions(self::INDEX_PHP_PATH, 0644);
        $logger = new Logger();
        $logger->log_unauthorized_access('index.php file unlocked.');
    }

    private function set_file_permissions($file_path, $permissions)
    {
        if (file_exists($file_path)) {
            chmod($file_path, $permissions);
        } else {
            $logger = new Logger();
            $logger->log_unauthorized_access('Failed to change permissions: index.php does not exist.');
        }
    }

    private function restore_original_index_file()
    {
        file_put_contents(self::INDEX_PHP_PATH, self::KNOWN_GOOD_INDEX_PHP_CONTENT);
    }
}