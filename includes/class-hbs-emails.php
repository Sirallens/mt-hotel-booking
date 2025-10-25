<?php
if ( ! defined('ABSPATH')) exit;

class HBS_Emails {

    /**
     * Enviar email al staff
     */
    public static function send_staff($booking_id, $data) {
        $opts = get_option(HBS_Config::OPTION_KEY, []);
        $to   = isset($opts['staff_emails']) && !empty($opts['staff_emails']) 
                  ? explode(',', $opts['staff_emails']) 
                  : [get_option('admin_email')];

        $subject = sprintf(
            __('[Nueva Reservación] Solicitud #%d - %s', 'hotel-booking-system'),
            $booking_id,
            $data['guest_name']
        );

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $message = self::build_staff_template($booking_id, $data);

        wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Enviar email al huésped
     */
    public static function send_guest($booking_id, $data) {
        $subject = sprintf(
            __('Confirmación de Solicitud de Reservación #%d - Altavista Hotel', 'hotel-booking-system'),
            $booking_id
        );

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $message = self::build_guest_template($booking_id, $data);

        wp_mail($data['guest_email'], $subject, $message, $headers);
    }

    /**
     * Plantilla HTML: Staff
     */
    private static function build_staff_template($booking_id, $data) {
        ob_start(); ?>
        <h2><?php echo esc_html__('Nueva Solicitud de Reservación', 'hotel-booking-system'); ?></h2>
        <p><?php echo sprintf(__('Se ha recibido la solicitud #%d. Revise los detalles:', 'hotel-booking-system'), $booking_id); ?></p>
        <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
            <tr><th><?php _e('Nombre', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['guest_name']); ?></td></tr>
            <tr><th><?php _e('Email', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['guest_email']); ?></td></tr>
            <tr><th><?php _e('Teléfono', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['guest_phone']); ?></td></tr>
            <tr><th><?php _e('Check-in', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['check_in']); ?></td></tr>
            <tr><th><?php _e('Check-out', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['check_out']); ?></td></tr>
            <tr><th><?php _e('Habitación', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['room_type']); ?></td></tr>
            <tr><th><?php _e('Adultos', 'hotel-booking-system'); ?></th><td><?php echo intval($data['adults']); ?></td></tr>
            <tr><th><?php _e('Niños', 'hotel-booking-system'); ?></th><td><?php echo intval($data['kids']); ?></td></tr>
            <tr><th><?php _e('Total Estancia (MXN)', 'hotel-booking-system'); ?></th><td><?php echo number_format($data['total_price'], 2); ?></td></tr>
        </table>
        <p><strong><?php _e('Acción requerida:', 'hotel-booking-system'); ?></strong> <?php _e('Verifique disponibilidad y contacte al huésped.', 'hotel-booking-system'); ?></p>
        <?php
        return ob_get_clean();
    }

    /**
     * Plantilla HTML: Huésped
     */
    private static function build_guest_template($booking_id, $data) {
        $opts = get_option(HBS_Config::OPTION_KEY, []);
        ob_start(); ?>
        <h2><?php echo esc_html__('Confirmación de solicitud de reservación', 'hotel-booking-system'); ?></h2>
        <p><?php echo sprintf(__('Estimado/a %s, hemos recibido su solicitud de reservación (#%d).', 'hotel-booking-system'), esc_html($data['guest_name']), $booking_id); ?></p>
        <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
            <tr><th><?php _e('Check-in', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['check_in']); ?></td></tr>
            <tr><th><?php _e('Check-out', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['check_out']); ?></td></tr>
            <tr><th><?php _e('Habitación', 'hotel-booking-system'); ?></th><td><?php echo esc_html($data['room_type']); ?></td></tr>
            <tr><th><?php _e('Adultos', 'hotel-booking-system'); ?></th><td><?php echo intval($data['adults']); ?></td></tr>
            <tr><th><?php _e('Niños', 'hotel-booking-system'); ?></th><td><?php echo intval($data['kids']); ?></td></tr>
            <tr><th><?php _e('Total Estancia (MXN)', 'hotel-booking-system'); ?></th><td><?php echo number_format($data['total_price'], 2); ?></td></tr>
        </table>
        <p><strong><?php _e('Nota:', 'hotel-booking-system'); ?>
    }
