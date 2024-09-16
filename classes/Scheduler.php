<?php 
class Scheduler {
    public function schedule_file_check() {
        if (!wp_next_scheduled('check_index_file_integrity_event')) {
            wp_schedule_event(time(), 'daily', 'check_index_file_integrity_event');
        }
    }

    public function clear_scheduled_file_check() {
        $timestamp = wp_next_scheduled('check_index_file_integrity_event');
        wp_unschedule_event($timestamp, 'check_index_file_integrity_event');
    }
}