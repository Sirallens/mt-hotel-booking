<?php
/**
 * Booking Model Class
 *
 * Handles database interactions and pricing logic.
 */

if (!defined('ABSPATH')) {
    exit;
}

class HBS_Booking
{

    /**
     * Get table name with prefix.
     */
    public static function table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'hbs_bookings';
    }

    /**
     * Get a single booking by ID.
     */
    public static function get($booking_id)
    {
        global $wpdb;
        $table = self::table_name();
        $sql = $wpdb->prepare("SELECT * FROM $table WHERE booking_id = %d", $booking_id);
        return $wpdb->get_row($sql, ARRAY_A);
    }

    /**
     * Get recent bookings.
     */
    public static function get_recent($limit = 10)
    {
        global $wpdb;
        $table = self::table_name();
        $sql = $wpdb->prepare("SELECT * FROM $table ORDER BY booking_id DESC LIMIT %d", $limit);
        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Delete a booking by ID.
     * 
     * @param int $booking_id
     * @return int|false Number of rows affected or false on error.
     */
    public static function delete($booking_id)
    {
        global $wpdb;
        $table = self::table_name();
        return $wpdb->delete($table, array('booking_id' => $booking_id), array('%d'));
    }

    /**
     * Insert a new booking.
     *
     * @param array $data Booking data.
     * @return int|WP_Error Booking ID or error.
     */
    public static function insert($data)
    {
        global $wpdb;

        // 1. Sanitize & Validate
        $defaults = [
            'guest_name' => '',
            'guest_email' => '',
            'guest_phone' => '',
            'check_in_date' => '',
            'nights' => 1,
            'room_type' => 'single',
            'adults_count' => 1,
            'kids_count' => 0,
        ];

        $data = wp_parse_args($data, $defaults);

        $guest_name = sanitize_text_field($data['guest_name']);
        $guest_email = sanitize_email($data['guest_email']);
        $guest_phone = sanitize_text_field($data['guest_phone']);
        $check_in_date = sanitize_text_field($data['check_in_date']); // Validate format below
        $nights = max(1, intval($data['nights']));

        // Validate room type exists
        $room_types = HBS_Room_Types::get_all();
        $room_type = isset($room_types[$data['room_type']]) ? $data['room_type'] : 'single';
        $room = HBS_Room_Types::get($room_type);

        $adults = max(1, intval($data['adults_count']));
        $kids = max(0, intval($data['kids_count']));
        $total_guests = $adults + $kids;

        // Check capacity based on room type
        if ($room && $total_guests > $room['max_capacity']) {
            return new WP_Error('hbs_limit', sprintf(__('La capacidad máxima para este tipo de habitación es de %d personas.', 'hotel-booking-system'), $room['max_capacity']));
        }

        // Date format validation (Y-m-d)
        $d = DateTime::createFromFormat('Y-m-d', $check_in_date);
        if (!$d || $d->format('Y-m-d') !== $check_in_date) {
            return new WP_Error('hbs_date', __('Fecha de llegada inválida.', 'hotel-booking-system'));
        }

        // Calculate Check-out
        $check_out_obj = clone $d;
        $check_out_obj->modify("+$nights days");
        $check_out_date = $check_out_obj->format('Y-m-d');

        // 2. Pricing Engine (Server Side)
        $opts = get_option(HBS_Config::OPTION_KEY, []);
        $prices = [
            'single' => isset($opts['price_single']) ? floatval($opts['price_single']) : 1850.00,
            'double' => isset($opts['price_double']) ? floatval($opts['price_double']) : 2100.00,
            'extra_adult' => isset($opts['price_extra_adult']) ? floatval($opts['price_extra_adult']) : 450.00,
            'extra_kid' => isset($opts['price_extra_kid']) ? floatval($opts['price_extra_kid']) : 250.00,
        ];

        $total_price = self::calculate_total($room_type, $adults, $kids, $nights, $prices);

        // 3. Insert
        $table = self::table_name();
        $inserted = $wpdb->insert(
            $table,
            [
                'guest_name' => $guest_name,
                'guest_email' => $guest_email,
                'guest_phone' => $guest_phone,
                'check_in_date' => $check_in_date,
                'check_out_date' => $check_out_date,
                'room_type' => $room_type,
                'adults_count' => $adults,
                'kids_count' => $kids,
                'total_price' => $total_price,
                'booking_status' => 'pending',
                'created_date' => current_time('mysql'),
                'notes' => '', // reserved for future use
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%s', '%s']
        );

        if (false === $inserted) {
            return new WP_Error('hbs_db_error', __('Error al guardar la reservación en la base de datos.', 'hotel-booking-system'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Pricing Engine.
     */
    public static function calculate_total($room_type, $adults, $kids, $nights, $prices)
    {
        $base = 0.0;
        $extras_cost = 0.0;
        $total_guests = $adults + $kids;

        if ('single' === $room_type) {
            $base = $prices['single'];
            // Single: All guests above 2 are charged as extra adults
            if ($total_guests > 2) {
                $extras = $total_guests - 2;
                $extras_cost = $extras * $prices['extra_adult'];
            }
        } else {
            // Double
            $base = $prices['double'];
            if ($total_guests > 2) {
                $extra_adults = max($adults - 2, 0);
                // Remaining slots above 2 (after accounting for extra adults) are kids
                // Total extra people = total - 2
                // extra_kids = (total - 2) - extra_adults
                $extra_kids = max(($total_guests - 2) - $extra_adults, 0);

                $extras_cost += ($extra_adults * $prices['extra_adult']);
                $extras_cost += ($extra_kids * $prices['extra_kid']);
            }
        }

        $nightly_total = $base + $extras_cost;
        return round($nightly_total * $nights, 2);
    }
}
