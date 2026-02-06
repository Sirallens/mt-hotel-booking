<?php
/**
 * Occupancy Validator Class
 *
 * Validates booking occupancy against configurable room type rules.
 * Implements hard limits and overflow rules for adults and kids.
 *
 * @package Hotel_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class HBS_Occupancy_Validator
{
    /**
     * Validate occupancy for a given room type.
     *
     * @param string $room_type_slug Room type slug (e.g., 'single', 'double')
     * @param int    $adults         Number of adults
     * @param int    $kids           Number of kids
     * @return array {
     *     Validation result
     *
     *     @type bool   $valid   Whether the occupancy is valid
     *     @type string $error   Error message if invalid (empty if valid)
     *     @type array  $details Room type configuration details
     * }
     */
    public static function validate($room_type_slug, $adults, $kids)
    {
        $room_type = HBS_Room_Types::get($room_type_slug);

        if (!$room_type) {
            return [
                'valid' => false,
                'error' => __('Tipo de habitación no válido.', 'hotel-booking-system'),
                'details' => null,
            ];
        }

        // Extract occupancy rules
        $max_adults = isset($room_type['max_adults']) ? (int) $room_type['max_adults'] : 4;
        $max_kids = isset($room_type['max_kids']) ? (int) $room_type['max_kids'] : 3;
        $max_total = isset($room_type['max_total']) ? (int) $room_type['max_total'] : 4;
        $base_occupancy = isset($room_type['base_occupancy']) ? (int) $room_type['base_occupancy'] : 2;
        $overflow_rule = isset($room_type['overflow_rule']) ? $room_type['overflow_rule'] : 'kids_only';

        $adults = (int) $adults;
        $kids = (int) $kids;
        $total = $adults + $kids;

        // Rule 1: Minimum 1 adult required
        if ($adults < 1) {
            return [
                'valid' => false,
                'error' => __('Se requiere al menos 1 adulto por reserva.', 'hotel-booking-system'),
                'details' => $room_type,
            ];
        }

        // Rule 2: At least 1 person total
        if ($total < 1) {
            return [
                'valid' => false,
                'error' => __('Debe haber al menos 1 huésped.', 'hotel-booking-system'),
                'details' => $room_type,
            ];
        }

        // Rule 3: Hard limit - max adults
        if ($adults > $max_adults) {
            return [
                'valid' => false,
                'error' => sprintf(
                    __('Máximo %d adultos permitidos para esta habitación.', 'hotel-booking-system'),
                    $max_adults
                ),
                'details' => $room_type,
            ];
        }

        // Rule 4: Hard limit - max kids
        if ($kids > $max_kids) {
            return [
                'valid' => false,
                'error' => sprintf(
                    __('Máximo %d niños permitidos para esta habitación.', 'hotel-booking-system'),
                    $max_kids
                ),
                'details' => $room_type,
            ];
        }

        // Rule 5: Hard limit - max total capacity
        if ($total > $max_total) {
            return [
                'valid' => false,
                'error' => sprintf(
                    __('Capacidad máxima de %d personas excedida.', 'hotel-booking-system'),
                    $max_total
                ),
                'details' => $room_type,
            ];
        }

        // Rule 6: Overflow rule (only if exceeding base occupancy)
        if ($total > $base_occupancy) {
            $overflow = $total - $base_occupancy;

            if ($overflow_rule === 'kids_only') {
                // Only kids can exceed base occupancy
                if ($adults > $base_occupancy) {
                    return [
                        'valid' => false,
                        'error' => sprintf(
                            __('Solo se permiten hasta %d adultos. Los huéspedes adicionales deben ser niños.', 'hotel-booking-system'),
                            $base_occupancy
                        ),
                        'details' => $room_type,
                    ];
                }
            }
            // 'any' rule allows both adults and kids to exceed base (already validated by hard limits)
        }

        // All validation passed
        return [
            'valid' => true,
            'error' => '',
            'details' => $room_type,
        ];
    }

    /**
     * Get occupancy configuration for a room type.
     *
     * @param string $room_type_slug Room type slug
     * @return array|null Occupancy configuration or null if room type not found
     */
    public static function get_occupancy_config($room_type_slug)
    {
        $room_type = HBS_Room_Types::get($room_type_slug);

        if (!$room_type) {
            return null;
        }

        return [
            'beds' => isset($room_type['beds']) ? (int) $room_type['beds'] : 2,
            'base_occupancy' => isset($room_type['base_occupancy']) ? (int) $room_type['base_occupancy'] : 2,
            'max_total' => isset($room_type['max_total']) ? (int) $room_type['max_total'] : 4,
            'max_adults' => isset($room_type['max_adults']) ? (int) $room_type['max_adults'] : 4,
            'max_kids' => isset($room_type['max_kids']) ? (int) $room_type['max_kids'] : 3,
            'overflow_rule' => isset($room_type['overflow_rule']) ? $room_type['overflow_rule'] : 'kids_only',
        ];
    }
}
