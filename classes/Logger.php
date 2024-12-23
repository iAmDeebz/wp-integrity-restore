<?php
class Logger
{
    const LOG_FILE = 'access_log.txt';

    public function log_unauthorized_access(): void
    {
        $log_file = plugin_dir_path(__FILE__) . self::LOG_FILE;
        $current_time = date("Y-m-d H:i:s");
        $ip_address = $_SERVER['REMOTE_ADDR'];

        $log_entry = "Unauthorized modification attempt on index.php at $current_time from IP: $ip_address\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}
