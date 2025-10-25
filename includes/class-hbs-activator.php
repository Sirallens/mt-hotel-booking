<?php
class HBS_Activator {
    public static function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'hbs_bookings';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE $table (
            booking_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            guest_name VARCHAR(255) NOT NULL,
            guest_email VARCHAR(255) NOT NULL,
            guest_phone VARCHAR(20) NOT NULL,
            check_in_date DATE NOT NULL,
            check_out_date DATE NOT NULL,
            room_type ENUM('single','double') NOT NULL,
            adults_count INT NOT NULL,
            kids_count INT NOT NULL,
            total_price DECIMAL(10,2) NOT NULL,
            booking_status ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            notes TEXT,
            PRIMARY KEY (booking_id)
        ) $charset_collate;";
        dbDelta($sql);
    }
}
