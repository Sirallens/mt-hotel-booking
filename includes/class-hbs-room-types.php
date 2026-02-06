<?php
/**
 * Room Types Helper Class
 *
 * Handles CRUD operations for room types stored in WordPress options.
 * Room types are stored as an array keyed by slug.
 */

if (!defined('ABSPATH')) {
    exit;
}

class HBS_Room_Types
{
    const OPTION_KEY = 'hbs_room_types';

    /**
     * Get all room types.
     *
     * @return array Array of room types keyed by slug
     */
    public static function get_all()
    {
        $room_types = get_option(self::OPTION_KEY, []);

        // If empty, initialize with defaults
        if (empty($room_types)) {
            $room_types = self::get_default_types();
            update_option(self::OPTION_KEY, $room_types);
        }

        return $room_types;
    }

    /**
     * Get a single room type by slug.
     *
     * @param string $slug Room type slug
     * @return array|null Room type data or null if not found
     */
    public static function get($slug)
    {
        $room_types = self::get_all();
        return isset($room_types[$slug]) ? $room_types[$slug] : null;
    }

    /**
     * Save (add or update) a room type.
     *
     * @param array $room_type Room type data
     * @return bool True on success, false on failure
     */
    public static function save($room_type)
    {
        // Validate required fields
        if (empty($room_type['slug']) || empty($room_type['name'])) {
            return false;
        }

        // Sanitize and set defaults
        $slug = sanitize_key($room_type['slug']);
        $data = [
            'slug' => $slug,
            'name' => sanitize_text_field($room_type['name']),

            // Legacy fields (kept for backwards compatibility)
            'base_guests' => isset($room_type['base_guests']) ? max(1, intval($room_type['base_guests'])) : 2,
            'max_capacity' => isset($room_type['max_capacity']) ? max(1, intval($room_type['max_capacity'])) : 4,

            // Pricing
            'base_price' => isset($room_type['base_price']) ? max(0, floatval($room_type['base_price'])) : 0,

            // NEW: Occupancy parameters
            'beds' => isset($room_type['beds']) ? max(1, intval($room_type['beds'])) : 2,
            'base_occupancy' => isset($room_type['base_occupancy']) ? max(1, intval($room_type['base_occupancy'])) : 2,
            'max_total' => isset($room_type['max_total']) ? max(1, intval($room_type['max_total'])) : 4,
            'max_adults' => isset($room_type['max_adults']) ? max(1, intval($room_type['max_adults'])) : 3,
            'max_kids' => isset($room_type['max_kids']) ? max(0, intval($room_type['max_kids'])) : 3,
            'overflow_rule' => isset($room_type['overflow_rule']) && in_array($room_type['overflow_rule'], ['kids_only', 'any'])
                ? $room_type['overflow_rule']
                : 'kids_only',

            // Page link
            'detail_page_url' => isset($room_type['detail_page_url']) ? esc_url_raw($room_type['detail_page_url']) : '',
        ];

        $room_types = self::get_all();
        $room_types[$slug] = $data;

        return update_option(self::OPTION_KEY, $room_types);
    }

    /**
     * Delete a room type by slug.
     *
     * @param string $slug Room type slug
     * @return bool True on success, false on failure
     */
    public static function delete($slug)
    {
        $room_types = self::get_all();

        // Prevent deletion if it's the last room type
        if (count($room_types) <= 1) {
            return false;
        }

        if (isset($room_types[$slug])) {
            unset($room_types[$slug]);
            return update_option(self::OPTION_KEY, $room_types);
        }

        return false;
    }

    /**
     * Get default room types (single and double).
     *
     * @return array Default room types
     */
    public static function get_default_types()
    {
        // Get prices from main settings for backward compatibility
        $settings = get_option(HBS_Config::OPTION_KEY, []);

        return [
            'single' => [
                'slug' => 'single',
                'name' => 'Sencilla',
                'base_guests' => 2, // Legacy field, kept for compatibility
                'max_capacity' => 4, // Legacy field, kept for compatibility
                'base_price' => isset($settings['price_single']) ? floatval($settings['price_single']) : 1850.00,
                'detail_page_url' => '',
                // NEW: Occupancy parameters
                'beds' => 2,
                'base_occupancy' => 2, // 2 people included in base price
                'max_total' => 4,      // Maximum 4 people total
                'max_adults' => 3,     // Maximum 3 adults (hard limit)
                'max_kids' => 3,       // Maximum 3 kids (hard limit)
                'overflow_rule' => 'kids_only', // Only kids can exceed base occupancy
            ],
            'double' => [
                'slug' => 'double',
                'name' => 'Doble',
                'base_guests' => 2, // Legacy field, kept for compatibility
                'max_capacity' => 4, // Legacy field, kept for compatibility
                'base_price' => isset($settings['price_double']) ? floatval($settings['price_double']) : 2100.00,
                'detail_page_url' => '',
                // NEW: Occupancy parameters
                'beds' => 2,
                'base_occupancy' => 2, // 2 people included in base price
                'max_total' => 4,      // Maximum 4 people total
                'max_adults' => 4,     // Maximum 4 adults (hard limit)
                'max_kids' => 3,       // Maximum 3 kids (hard limit)
                'overflow_rule' => 'any', // Both adults and kids can exceed base
            ],
        ];
    }

    /**
     * Check if a slug is available (not already in use).
     *
     * @param string $slug Slug to check
     * @param string $current_slug Current slug (when editing)
     * @return bool True if available, false if taken
     */
    public static function is_slug_available($slug, $current_slug = '')
    {
        $room_types = self::get_all();

        // If editing and slug hasn't changed, it's available
        if ($current_slug && $slug === $current_slug) {
            return true;
        }

        return !isset($room_types[$slug]);
    }
}
