<?php
class BackupManager 
{
    public function restore_index_file($backup_file): void
    {
        if (!empty($backup_file)) {
            copy(IntegrityChecker::INDEX_PHP_PATH, $backup_file);
            (new FileLocker())->lock_index_file();
        }
    }
}