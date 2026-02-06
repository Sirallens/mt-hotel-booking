<?php
/**
 * Data Migration Handler
 *
 * Handles automatic data migrations for the Hotel Booking System plugin.
 * Migrations run once per installation when the plugin version changes.
 *
 * @package Hotel_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class HBS_Migrator
{
    const VERSION_OPTION_KEY = 'hbs_data_version';
    const CURRENT_VERSION = '0.2.0'; // Increment this to trigger new migrations

    /**
     * Run all pending migrations.
     *
     * @return void
     */
    public static function run()
    {
        $current_version = get_option(self::VERSION_OPTION_KEY, '0.0.0');

        // Skip if already at current version
        if (version_compare($current_version, self::CURRENT_VERSION, '>=')) {
            return;
        }

        // Run migrations in order
        self::migrate_to_0_2_0();

        // Update version marker
        update_option(self::VERSION_OPTION_KEY, self::CURRENT_VERSION);
    }

    /**
     * Migration to version 0.2.0: Add occupancy parameters to room types.
     *
     * @return void
     */
    private static function migrate_to_0_2_0()
    {
        $current_version = get_option(self::VERSION_OPTION_KEY, '0.0.0');

        // Skip if already migrated
        if (version_compare($current_version, '0.2.0', '>=')) {
            return;
        }

        $room_types = get_option(HBS_Room_Types::OPTION_KEY, []);

        // If no room types exist, get defaults (which already include new params)
        if (empty($room_types)) {
            $room_types = HBS_Room_Types::get_default_types();
            update_option(HBS_Room_Types::OPTION_KEY, $room_types);
            return;
        }

        $migrated = false;

        foreach ($room_types as $slug => &$room_type) {
            // Check if already has new parameters
            if (isset($room_type['beds']) && isset($room_type['base_occupancy'])) {
                continue; // Already migrated
            }

            // Apply migration based on room type
            $defaults = self::get_occupancy_defaults($slug);

            $room_type['beds'] = $defaults['beds'];
            $room_type['base_occupancy'] = $defaults['base_occupancy'];
            $room_type['max_total'] = $defaults['max_total'];
            $room_type['max_adults'] = $defaults['max_adults'];
            $room_type['max_kids'] = $defaults['max_kids'];
            $room_type['overflow_rule'] = $defaults['overflow_rule'];

            $migrated = true;
        }

        // Save migrated data
        if ($migrated) {
            update_option(HBS_Room_Types::OPTION_KEY, $room_types);

            // Log migration (optional)
            error_log('HBS: Successfully migrated room types to v0.2.0 (occupancy parameters)');
        }
    }

    /**
     * Get default occupancy parameters for a room type.
     *
     * @param string $slug Room type slug
     * @return array Default occupancy parameters
     */
    private static function get_occupancy_defaults($slug)
    {
        $defaults = [
            'single' => [
                'beds' => 2,
                'base_occupancy' => 2,
                'max_total' => 4,
                'max_adults' => 3,
                'max_kids' => 3,
                'overflow_rule' => 'kids_only', // Only kids can exceed base occupancy
            ],
            'double' => [
                'beds' => 2,
                'base_occupancy' => 2,
                'max_total' => 4,
                'max_adults' => 4,
                'max_kids' => 3,
                'overflow_rule' => 'any', // Both adults and kids can exceed base
            ],
        ];

        // Return type-specific defaults or generic defaults
        return isset($defaults[$slug]) ? $defaults[$slug] : [
            'beds' => 2,
            'base_occupancy' => 2,
            'max_total' => 4,
            'max_adults' => 3,
            'max_kids' => 3,
            'overflow_rule' => 'kids_only',
        ];
    }

    /**
     * Clear all bookings (for testing/migration purposes).
     *
     * WARNING: This is destructive! Only use for testing or when explicitly requested.
     *
     * @return int Number of bookings deleted
     */
    public static function clear_all_bookings()
    {
        global $wpdb;
        $table = HBS_Booking::table_name();

        // Get count before deletion
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        // Delete all bookings
        $wpdb->query("TRUNCATE TABLE $table");

        error_log("HBS: Cleared $count booking(s) from database");

        return (int) $count;
    }
}
