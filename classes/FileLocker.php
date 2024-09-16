<?php
class FileLocker 
{
    public function lock_index_file() 
    {
        chmod(IntegrityChecker::INDEX_PHP_PATH, 0444);
    }
}