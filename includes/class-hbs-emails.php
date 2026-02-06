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

        // Sanitize all recipient emails
        $to = array_map('sanitize_email', array_map('trim', $to));
        $to = array_filter($to, 'is_email');

        if (empty($to)) {
            error_log('HBS: No valid staff email addresses configured');
            return;
        }

        $subject_template = !empty($opts['email_staff_subject']) ? $opts['email_staff_subject'] : '[Nueva Solicitud de Cotización] - {guest_name}';
        $subject = self::replace_placeholders($subject_template, $booking_id, $booking);

        // Proper headers for better deliverability
        $from_email = sanitize_email(get_option('admin_email'));
        $from_name = sanitize_text_field(get_bloginfo('name'));

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

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

        // Validate and sanitize guest email
        $guest_email = sanitize_email($booking['guest_email']);
        if (!is_email($guest_email)) {
            error_log('HBS: Invalid guest email address: ' . $booking['guest_email']);
            return;
        }

        $opts = get_option(HBS_Config::OPTION_KEY, []);
        $subject_template = !empty($opts['email_guest_subject']) ? $opts['email_guest_subject'] : 'Confirmación de Solicitud de Cotización';
        $subject = self::replace_placeholders($subject_template, $booking_id, $booking);

        // Proper headers for better deliverability
        $from_email = sanitize_email(get_option('admin_email'));
        $from_name = sanitize_text_field(get_bloginfo('name'));

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>',
        ];

        $message = self::build_guest_template($booking_id, $booking);

        wp_mail($guest_email, $subject, $message, $headers);
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
            '{room_type}' => self::get_room_type_name($data['room_type']),
            '{adults_count}' => $data['adults_count'],
            '{kids_count}' => $data['kids_count'],
            '{total_price}' => number_format($data['total_price'], 2)
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Get room type display name
     */
    private static function get_room_type_name($slug)
    {
        $room = HBS_Room_Types::get($slug);
        return $room ? $room['name'] : $slug;
    }

    /**
     * Plantilla HTML: Staff
     */
    private static function build_staff_template($booking_id, $data)
    {
        $opts = get_option(HBS_Config::OPTION_KEY, []);

        // Check if custom template is enabled AND has content
        $use_custom = !empty($opts['use_custom_staff_template']);
        $custom_content = !empty($opts['email_staff_content']) ? $opts['email_staff_content'] : '';

        // Use custom template only if enabled and has content
        if ($use_custom && !empty($custom_content)) {
            return self::replace_placeholders($custom_content, $booking_id, $data);
        }

        // Otherwise use modern default template
        // Get logo
        $logo_id = isset($opts['hotel_logo_id']) ? (int) $opts['hotel_logo_id'] : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';

        ob_start(); ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>

        <body
            style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f5f5;">
                <tr>
                    <td align="center" style="padding: 40px 20px;">
                        <table role="presentation"
                            style="max-width: 600px; width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td
                                    style="padding: 40px 40px 30px; text-align: center; background-color: #e88e4b; border-radius: 12px 12px 0 0;">
                                    <?php if ($logo_url): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo"
                                            style="max-width: 180px; height: auto; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;">
                                    <?php endif; ?>
                                    <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">
                                        <?php esc_html_e('Nueva Solicitud de Reservación', 'hotel-booking-system'); ?>
                                    </h1>
                                    <p style="margin: 10px 0 0; color: #ffffff; font-size: 16px; opacity: 0.95;">
                                        <?php echo sprintf(__('Solicitud #%d', 'hotel-booking-system'), $booking_id); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px 40px;">
                                    <p style="margin: 0 0 25px; color: #333333; font-size: 16px; line-height: 1.6;">
                                        <?php _e('Se ha recibido una nueva solicitud de reservación. A continuación los detalles:', 'hotel-booking-system'); ?>
                                    </p>

                                    <!-- Guest Info Card -->
                                    <table role="presentation"
                                        style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #fafafa; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <h2
                                                    style="margin: 0 0 15px; color: #000000; font-size: 18px; font-weight: 600;">
                                                    <?php _e('Información del Huésped', 'hotel-booking-system'); ?>
                                                </h2>
                                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                                    <tr>
                                                        <td
                                                            style="padding: 8px 0; color: #666666; font-size: 14px; width: 35%;">
                                                            <strong><?php _e('Nombre:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {guest_name}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Email:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            <a href="mailto:{guest_email}"
                                                                style="color: #e88e4b; text-decoration: none;">{guest_email}</a>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Teléfono:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            <a href="tel:{guest_phone}"
                                                                style="color: #e88e4b; text-decoration: none;">{guest_phone}</a>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Booking Details Card -->
                                    <table role="presentation"
                                        style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #fafafa; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <h2
                                                    style="margin: 0 0 15px; color: #000000; font-size: 18px; font-weight: 600;">
                                                    <?php _e('Detalles de la Reservación', 'hotel-booking-system'); ?>
                                                </h2>
                                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                                    <tr>
                                                        <td
                                                            style="padding: 8px 0; color: #666666; font-size: 14px; width: 35%;">
                                                            <strong><?php _e('Check-in:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {check_in_date}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Check-out:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {check_out_date}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Habitación:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {room_type}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Huéspedes:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {adults_count} <?php _e('Adultos', 'hotel-booking-system'); ?>,
                                                            {kids_count} <?php _e('Niños', 'hotel-booking-system'); ?>
                                                        </td>
                                                    </tr>
                                                    <tr style="border-top: 2px solid #e0e0e0;">
                                                        <td style="padding: 15px 0 8px; color: #000000; font-size: 16px;">
                                                            <strong><?php _e('Total Estancia:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td
                                                            style="padding: 15px 0 8px; color: #e88e4b; font-size: 20px; font-weight: 700;">
                                                            ${total_price} MXN
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <?php
                                    // Calculate price breakdown for staff email
                                    $opts_breakdown = get_option(HBS_Config::OPTION_KEY, []);
                                    $prices_config = [
                                        'single' => isset($opts_breakdown['price_single']) ? floatval($opts_breakdown['price_single']) : 1850.00,
                                        'double' => isset($opts_breakdown['price_double']) ? floatval($opts_breakdown['price_double']) : 2100.00,
                                        'extra_adult' => isset($opts_breakdown['price_extra_adult']) ? floatval($opts_breakdown['price_extra_adult']) : 450.00,
                                        'extra_kid' => isset($opts_breakdown['price_extra_kid']) ? floatval($opts_breakdown['price_extra_kid']) : 250.00,
                                    ];

                                    // Calculate breakdown (mimics calculate_total logic)
                                    $adults_int = intval($data['adults_count']);
                                    $kids_int = intval($data['kids_count']);
                                    $total_guests_int = $adults_int + $kids_int;
                                    $room_type_slug = $data['room_type'];
                                    $nights_int = intval((new DateTime($data['check_out_date']))->diff(new DateTime($data['check_in_date']))->days);

                                    $base_price = 0.0;
                                    $extra_adults_count = 0;
                                    $extra_kids_count = 0;
                                    $extra_adults_cost = 0.0;
                                    $extra_kids_cost = 0.0;

                                    if ($room_type_slug === 'single') {
                                        $base_price = $prices_config['single'];
                                        if ($total_guests_int > 2) {
                                            $extra_adults_count = $total_guests_int - 2;
                                            $extra_adults_cost = $extra_adults_count * $prices_config['extra_adult'];
                                        }
                                    } else {
                                        $base_price = $prices_config['double'];
                                        if ($total_guests_int > 2) {
                                            $extra_adults_count = max($adults_int - 2, 0);
                                            $extra_kids_count = max(($total_guests_int - 2) - $extra_adults_count, 0);
                                            $extra_adults_cost = $extra_adults_count * $prices_config['extra_adult'];
                                            $extra_kids_cost = $extra_kids_count * $prices_config['extra_kid'];
                                        }
                                    }

                                    $subtotal_per_night = $base_price + $extra_adults_cost + $extra_kids_cost;
                                    $calculated_total = $subtotal_per_night * $nights_int;
                                    ?>

                                    <!-- Price Breakdown Card -->
                                    <table role="presentation"
                                        style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #f0fdf4; border-radius: 8px; border: 1px solid #86efac;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <h2
                                                    style="margin: 0 0 15px; color: #15803d; font-size: 18px; font-weight: 600;">
                                                    💰
                                                    <?php _e('Desglose de Precios', 'hotel-booking-system'); ?>
                                                </h2>
                                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                                    <tr>
                                                        <td
                                                            style="padding: 8px 0; color: #666666; font-size: 14px; width: 60%;">
                                                            <strong>
                                                                <?php _e('Tipo de habitación:', 'hotel-booking-system'); ?>
                                                            </strong>
                                                        </td>
                                                        <td
                                                            style="padding: 8px 0; color: #000000; font-size: 14px; text-align: right;">
                                                            {room_type}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <?php _e('Base por noche (incluye 2 personas)', 'hotel-booking-system'); ?>
                                                        </td>
                                                        <td
                                                            style="padding: 8px 0; color: #000000; font-size: 14px; text-align: right;">
                                                            $
                                                            <?php echo number_format($base_price, 2); ?>
                                                        </td>
                                                    </tr>
                                                    <?php if ($extra_adults_count > 0): ?>
                                                        <tr>
                                                            <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                                <?php printf(__('Adultos extra (%d × $%s)', 'hotel-booking-system'), $extra_adults_count, number_format($prices_config['extra_adult'], 2)); ?>
                                                            </td>
                                                            <td
                                                                style="padding: 8px 0; color: #000000; font-size: 14px; text-align: right;">
                                                                $
                                                                <?php echo number_format($extra_adults_cost, 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php if ($extra_kids_count > 0): ?>
                                                        <tr>
                                                            <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                                <?php printf(__('Niños extra (%d × $%s)', 'hotel-booking-system'), $extra_kids_count, number_format($prices_config['extra_kid'], 2)); ?>
                                                            </td>
                                                            <td
                                                                style="padding: 8px 0; color: #000000; font-size: 14px; text-align: right;">
                                                                $
                                                                <?php echo number_format($extra_kids_cost, 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <tr style="border-top: 1px dashed #86efac;">
                                                        <td style="padding: 12px 0 8px; color: #000000; font-size: 14px;">
                                                            <strong>
                                                                <?php _e('Subtotal por noche:', 'hotel-booking-system'); ?>
                                                            </strong>
                                                        </td>
                                                        <td
                                                            style="padding: 12px 0 8px; color: #000000; font-size: 14px; text-align: right;">
                                                            <strong>$
                                                                <?php echo number_format($subtotal_per_night, 2); ?>
                                                            </strong>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <?php _e('Noches:', 'hotel-booking-system'); ?>
                                                        </td>
                                                        <td
                                                            style="padding: 8px 0; color: #000000; font-size: 14px; text-align: right;">
                                                            ×
                                                            <?php echo $nights_int; ?>
                                                        </td>
                                                    </tr>
                                                    <tr style="border-top: 2px solid #15803d;">
                                                        <td style="padding: 15px 0 8px; color: #15803d; font-size: 16px;">
                                                            <strong>
                                                                <?php _e('TOTAL ESTANCIA:', 'hotel-booking-system'); ?>
                                                            </strong>
                                                        </td>
                                                        <td
                                                            style="padding: 15px 0 8px; color: #15803d; font-size: 20px; font-weight: 700; text-align: right;">
                                                            $
                                                            <?php echo number_format($calculated_total, 2); ?> MXN
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Configured Prices Reference Card -->
                                    <table role="presentation"
                                        style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #f5f5f5; border-radius: 8px; border: 1px solid #d1d5db;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <h2
                                                    style="margin: 0 0 15px; color: #374151; font-size: 16px; font-weight: 600;">
                                                    📊
                                                    <?php _e('Precios Configurados (Referencia)', 'hotel-booking-system'); ?>
                                                </h2>
                                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                                    <tr>
                                                        <td style="padding: 6px 0; color: #6b7280; font-size: 13px;">
                                                            <?php _e('Habitación Sencilla:', 'hotel-booking-system'); ?>
                                                        </td>
                                                        <td
                                                            style="padding: 6px 0; color: #374151; font-size: 13px; text-align: right;">
                                                            $
                                                            <?php echo number_format($prices_config['single'], 2); ?> MXN
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 6px 0; color: #6b7280; font-size: 13px;">
                                                            <?php _e('Habitación Doble:', 'hotel-booking-system'); ?>
                                                        </td>
                                                        <td
                                                            style="padding: 6px 0; color: #374151; font-size: 13px; text-align: right;">
                                                            $
                                                            <?php echo number_format($prices_config['double'], 2); ?> MXN
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 6px 0; color: #6b7280; font-size: 13px;">
                                                            <?php _e('Adulto Extra:', 'hotel-booking-system'); ?>
                                                        </td>
                                                        <td
                                                            style="padding: 6px 0; color: #374151; font-size: 13px; text-align: right;">
                                                            $
                                                            <?php echo number_format($prices_config['extra_adult'], 2); ?> MXN
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 6px 0; color: #6b7280; font-size: 13px;">
                                                            <?php _e('Niño Extra (≥4 años):', 'hotel-booking-system'); ?>
                                                        </td>
                                                        <td
                                                            style="padding: 6px 0; color: #374151; font-size: 13px; text-align: right;">
                                                            $
                                                            <?php echo number_format($prices_config['extra_kid'], 2); ?> MXN
                                                        </td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Action Required -->
                                    <table role="presentation"
                                        style="width: 100%; border-collapse: collapse; background-color: #fff8f3; border-left: 4px solid #e88e4b; border-radius: 4px;">
                                        <tr>
                                            <td style="padding: 15px 20px;">
                                                <p style="margin: 0; color: #000000; font-size: 14px;">
                                                    <strong>⚡
                                                        <?php _e('Acción requerida:', 'hotel-booking-system'); ?></strong><br>
                                                    <?php _e('Verifique la disponibilidad y contacte al huésped lo antes posible.', 'hotel-booking-system'); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td
                                    style="padding: 30px 40px; text-align: center; background-color: #fafafa; border-radius: 0 0 12px 12px;">
                                    <p style="margin: 0; color: #666666; font-size: 12px;">
                                        <?php _e('Este es un correo automático del sistema de reservaciones', 'hotel-booking-system'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>

        </html>
        <?php
        $content = ob_get_clean();

        return self::replace_placeholders($content, $booking_id, $data);
    }

    /**
     * Plantilla HTML: Huésped
     */
    private static function build_guest_template($booking_id, $data)
    {
        $opts = get_option(HBS_Config::OPTION_KEY, []);

        // Check if custom template is enabled AND has content
        $use_custom = !empty($opts['use_custom_guest_template']);
        $custom_content = !empty($opts['email_guest_content']) ? $opts['email_guest_content'] : '';

        // Use custom template only if enabled and has content
        if ($use_custom && !empty($custom_content)) {
            return self::replace_placeholders($custom_content, $booking_id, $data);
        }

        // Otherwise use modern default template
        // Get logo
        $logo_id = isset($opts['hotel_logo_id']) ? (int) $opts['hotel_logo_id'] : 0;
        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';

        ob_start(); ?>
        <!DOCTYPE html>
        <html lang="es">

        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>

        <body
            style="margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5;">
            <table role="presentation" style="width: 100%; border-collapse: collapse; background-color: #f5f5f5;">
                <tr>
                    <td align="center" style="padding: 40px 20px;">
                        <table role="presentation"
                            style="max-width: 600px; width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td
                                    style="padding: 40px 40px 30px; text-align: center; background-color: #e88e4b; border-radius: 12px 12px 0 0;">
                                    <?php if ($logo_url): ?>
                                        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo"
                                            style="max-width: 180px; height: auto; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;">
                                    <?php endif; ?>
                                    <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: 700;">
                                        <?php esc_html_e('Confirmación de Solicitud', 'hotel-booking-system'); ?>
                                    </h1>
                                    <p style="margin: 10px 0 0; color: #ffffff; font-size: 16px; opacity: 0.95;">
                                        <?php _e('¡Gracias por su confianza!', 'hotel-booking-system'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px 40px;">
                                    <p style="margin: 0 0 10px; color: #000000; font-size: 18px; font-weight: 600;">
                                        Estimado/a {guest_name},
                                    </p>

                                    <p style="margin: 0 0 25px; color: #333333; font-size: 16px; line-height: 1.6;">
                                        Hemos recibido su solicitud. Nuestro equipo la está
                                        revisando. Nos pondremos en contacto con usted pronto para confirmarla y proceder con
                                        los siguientes pasos.
                                    </p>

                                    <!-- Booking Summary -->
                                    <table role="presentation"
                                        style="width: 100%; border-collapse: collapse; margin-bottom: 25px; background-color: #fafafa; border-radius: 8px; border: 1px solid #e0e0e0;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <h2
                                                    style="margin: 0 0 15px; color: #000000; font-size: 18px; font-weight: 600;">
                                                    <?php _e('Resumen de su Reservación', 'hotel-booking-system'); ?>
                                                </h2>
                                                <table role="presentation" style="width: 100%; border-collapse: collapse;">
                                                    <tr>
                                                        <td
                                                            style="padding: 8px 0; color: #666666; font-size: 14px; width: 35%;">
                                                            <strong><?php _e('Check-in:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {check_in_date}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Check-out:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {check_out_date}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Habitación:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {room_type}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td style="padding: 8px 0; color: #666666; font-size: 14px;">
                                                            <strong><?php _e('Huéspedes:', 'hotel-booking-system'); ?></strong>
                                                        </td>
                                                        <td style="padding: 8px 0; color: #000000; font-size: 14px;">
                                                            {adults_count} <?php _e('Adultos', 'hotel-booking-system'); ?>,
                                                            {kids_count} <?php _e('Niños', 'hotel-booking-system'); ?>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                    $opts = get_option(HBS_Config::OPTION_KEY, []);
                                                    if (!empty($opts['show_price_breakdown'])):
                                                        ?>
                                                        <tr style="border-top: 2px solid #e0e0e0;">
                                                            <td style="padding: 15px 0 8px; color: #000000; font-size: 16px;">
                                                                <strong><?php _e('Total Estimado:', 'hotel-booking-system'); ?></strong>
                                                            </td>
                                                            <td
                                                                style="padding: 15px 0 8px; color: #e88e4b; font-size: 20px; font-weight: 700;">
                                                                ${total_price} MXN
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Important Note -->
                                    <?php
                                    $guest_note = isset($opts['guest_email_note']) && !empty($opts['guest_email_note']) ? $opts['guest_email_note'] : '';
                                    if ($guest_note):
                                        ?>
                                        <table role="presentation"
                                            style="width: 100%; border-collapse: collapse; background-color: #fff8f3; border-left: 4px solid #e88e4b; border-radius: 4px; margin-bottom: 20px;">
                                            <tr>
                                                <td style="padding: 15px 20px;">
                                                    <p style="margin: 0; color: #000000; font-size: 14px; line-height: 1.5;">
                                                        <strong><?php _e('Nota importante:', 'hotel-booking-system'); ?></strong><br>
                                                        <?php echo esc_html($guest_note); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    <?php endif; ?>

                                    <p style="margin: 0 0 15px; color: #333333; font-size: 15px; line-height: 1.6;">
                                        <?php _e('Si tiene alguna pregunta, no dude en contactarnos.', 'hotel-booking-system'); ?>
                                    </p>

                                    <p style="margin: 0; color: #333333; font-size: 15px; line-height: 1.6;">
                                        <?php _e('¡Esperamos darle la bienvenida pronto!', 'hotel-booking-system'); ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td
                                    style="padding: 30px 40px; text-align: center; background-color: #fafafa; border-radius: 0 0 12px 12px;">
                                    <p style="margin: 0 0 5px; color: #000000; font-size: 14px; font-weight: 600;">
                                        <?php _e('Este es un correo automático', 'hotel-booking-system'); ?>
                                    </p>
                                    <p style="margin: 0; color: #666666; font-size: 12px;">
                                        <?php _e('Por favor, no responda a este mensaje', 'hotel-booking-system'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>

        </html>
        <?php
        $content = ob_get_clean();

        return self::replace_placeholders($content, $booking_id, $data);
    }
}