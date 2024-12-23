<?php
class FileLocker 
{
    public function lock_index_file(): void
    {
        chmod(IntegrityChecker::INDEX_PHP_PATH, 0444);
    }
}