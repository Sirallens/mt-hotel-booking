<?php
if (!defined('ABSPATH'))
    exit;

class HBS_Emails
{

    /**
     * Enviar email al staff
     */
    public static function send_staff($booking_id)
    {
        $booking = HBS_Booking::get($booking_id);
        if (!$booking) {
            return;
        }

        $opts = get_option(HBS_Config::OPTION_KEY, []);
        $to = isset($opts['staff_emails']) && !empty($opts['staff_emails'])
            ? explode(',', $opts['staff_emails'])
            : [get_option('admin_email')];

        $subject_template = !empty($opts['email_staff_subject']) ? $opts['email_staff_subject'] : '[Nueva Reservación] Solicitud #{booking_id} - {guest_name}';
        $subject = self::replace_placeholders($subject_template, $booking_id, $booking);

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $message = self::build_staff_template($booking_id, $booking);

        wp_mail($to, $subject, $message, $headers);
    }

    /**
     * Enviar email al huésped
     */
    public static function send_guest($booking_id)
    {
        $booking = HBS_Booking::get($booking_id);
        if (!$booking) {
            return;
        }

        $opts = get_option(HBS_Config::OPTION_KEY, []);
        $subject_template = !empty($opts['email_guest_subject']) ? $opts['email_guest_subject'] : 'Confirmación de Solicitud de Reservación #{booking_id} - Altavista Hotel';
        $subject = self::replace_placeholders($subject_template, $booking_id, $booking);

        $headers = ['Content-Type: text/html; charset=UTF-8'];

        $message = self::build_guest_template($booking_id, $booking);

        wp_mail($booking['guest_email'], $subject, $message, $headers);
    }

    /**
     * Reemplaza variables en la plantilla.
     */
    private static function replace_placeholders($content, $booking_id, $data)
    {
        $replacements = [
            '{booking_id}' => $booking_id,
            '{guest_name}' => $data['guest_name'],
            '{guest_email}' => $data['guest_email'],
            '{guest_phone}' => $data['guest_phone'],
            '{check_in_date}' => $data['check_in_date'],
            '{check_out_date}' => $data['check_out_date'],
            '{room_type}' => $data['room_type'] === 'single' ? 'Sencilla' : 'Doble',
            '{adults_count}' => $data['adults_count'],
            '{kids_count}' => $data['kids_count'],
            '{total_price}' => number_format($data['total_price'], 2)
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Plantilla HTML: Staff
     */
    private static function build_staff_template($booking_id, $data)
    {
        $opts = get_option(HBS_Config::OPTION_KEY, []);
        $content = !empty($opts['email_staff_content']) ? $opts['email_staff_content'] : '';

        // Si no hay contenido personalizado, usar default
        if (empty($content)) {
            ob_start(); ?>
            <h2><?php echo esc_html__('Nueva Solicitud de Reservación', 'hotel-booking-system'); ?></h2>
            <p><?php echo sprintf(__('Se ha recibido la solicitud #%d. Revise los detalles:', 'hotel-booking-system'), $booking_id); ?>
            </p>
            <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <th><?php _e('Nombre', 'hotel-booking-system'); ?></th>
                    <td>{guest_name}</td>
                </tr>
                <tr>
                    <th><?php _e('Email', 'hotel-booking-system'); ?></th>
                    <td>{guest_email}</td>
                </tr>
                <tr>
                    <th><?php _e('Teléfono', 'hotel-booking-system'); ?></th>
                    <td>{guest_phone}</td>
                </tr>
                <tr>
                    <th><?php _e('Check-in', 'hotel-booking-system'); ?></th>
                    <td>{check_in_date}</td>
                </tr>
                <tr>
                    <th><?php _e('Check-out', 'hotel-booking-system'); ?></th>
                    <td>{check_out_date}</td>
                </tr>
                <tr>
                    <th><?php _e('Habitación', 'hotel-booking-system'); ?></th>
                    <td>{room_type}</td>
                </tr>
                <tr>
                    <th><?php _e('Adultos', 'hotel-booking-system'); ?></th>
                    <td>{adults_count}</td>
                </tr>
                <tr>
                    <th><?php _e('Niños', 'hotel-booking-system'); ?></th>
                    <td>{kids_count}</td>
                </tr>
                <tr>
                    <th><?php _e('Total Estancia (MXN)', 'hotel-booking-system'); ?></th>
                    <td>{total_price}</td>
                </tr>
            </table>
            <p><strong><?php _e('Acción requerida:', 'hotel-booking-system'); ?></strong>
                <?php _e('Verifique disponibilidad y contacte al huésped.', 'hotel-booking-system'); ?></p>
            <?php
            $content = ob_get_clean();
        }

        return self::replace_placeholders($content, $booking_id, $data);
    }

    /**
     * Plantilla HTML: Huésped
     */
    private static function build_guest_template($booking_id, $data)
    {
        $opts = get_option(HBS_Config::OPTION_KEY, []);
        $content = !empty($opts['email_guest_content']) ? $opts['email_guest_content'] : '';

        // Si no hay contenido personalizado, usar default
        if (empty($content)) {
            ob_start(); ?>
            <h2><?php echo esc_html__('Confirmación de solicitud de reservación', 'hotel-booking-system'); ?></h2>
            <p><?php echo sprintf(__('Estimado/a %s, hemos recibido su solicitud de reservación (#%d).', 'hotel-booking-system'), esc_html($data['guest_name']), $booking_id); ?>
            </p>
            <table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <th><?php _e('Check-in', 'hotel-booking-system'); ?></th>
                    <td>{check_in_date}</td>
                </tr>
                <tr>
                    <th><?php _e('Check-out', 'hotel-booking-system'); ?></th>
                    <td>{check_out_date}</td>
                </tr>
                <tr>
                    <th><?php _e('Habitación', 'hotel-booking-system'); ?></th>
                    <td>{room_type}</td>
                </tr>
                <tr>
                    <th><?php _e('Adultos', 'hotel-booking-system'); ?></th>
                    <td>{adults_count}</td>
                </tr>
                <tr>
                    <th><?php _e('Niños', 'hotel-booking-system'); ?></th>
                    <td>{kids_count}</td>
                </tr>
                <tr>
                    <th><?php _e('Total Estancia (MXN)', 'hotel-booking-system'); ?></th>
                    <td>{total_price}</td>
                </tr>
            </table>
            <p><strong><?php _e('Nota:', 'hotel-booking-system'); ?></strong>
                <?php echo isset($opts['guest_email_note']) ? esc_html($opts['guest_email_note']) : ''; ?></p>
            <p><?php _e('Nos pondremos en contacto con usted pronto para confirmar la disponibilidad y los siguientes pasos.', 'hotel-booking-system'); ?>
            </p>
            <p><?php _e('Gracias por elegir Altavista Hotel.', 'hotel-booking-system'); ?></p>
            <?php
            $content = ob_get_clean();
        }

        return self::replace_placeholders($content, $booking_id, $data);
    }
}