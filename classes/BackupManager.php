<?php
class BackupManager 
{
    const BACKUP_FILE = 'index_backup.php';

    public function create_index_backup() 
    {
        $backup_file = plugin_dir_path(__FILE__) . self::BACKUP_FILE;
        if (!file_exists($backup_file)) {
            copy(IntegrityChecker::INDEX_PHP_PATH, $backup_file);
        }
    }

    public function restore_index_file() 
    {
        $backup_file = plugin_dir_path(__FILE__) . self::BACKUP_FILE;
        if (file_exists($backup_file)) {
            copy(IntegrityChecker::INDEX_PHP_PATH, $backup_file);
            (new FileLocker())->lock_index_file();
        }
    }
}