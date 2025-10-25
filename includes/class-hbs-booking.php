<?php
/**
 * Booking form view (shortcode [hotel_booking_form])
 * Path: public/views/booking-form.php
 */

if (!defined('ABSPATH')) { exit; }

$opts   = get_option(HBS_Config::OPTION_KEY, []);
$nonce  = wp_create_nonce(HBS_Config::NONCE_ACTION);

// Prefill desde parámetros de URL (flotante) con límites seguros:
$today            = date('Y-m-d');
$check_in_date    = isset($_GET['check_in_date']) ? sanitize_text_field($_GET['check_in_date']) : '';
if (!$check_in_date || strtotime($check_in_date) < strtotime($today)) {
    $check_in_date = $today;
}

$nights           = isset($_GET['nights']) ? max(1, intval($_GET['nights'])) : 1;
$adults_prefill   = isset($_GET['adults']) ? max(1, intval($_GET['adults'])) : 1;
$kids_prefill     = isset($_GET['kids']) ? max(0, intval($_GET['kids'])) : 0;

// Precios (defaults configurables):
$price_single      = isset($opts['price_single']) ? floatval($opts['price_single']) : 1850.00;
$price_double      = isset($opts['price_double']) ? floatval($opts['price_double']) : 2100.00;
$price_extra_adult = isset($opts['price_extra_adult']) ? floatval($opts['price_extra_adult']) : 450.00;
$price_extra_kid   = isset($opts['price_extra_kid']) ? floatval($opts['price_extra_kid']) : 250.00;

// URL políticas:
$policies_url = !empty($opts['policies_url']) ? esc_url($opts['policies_url']) : '#';
?>
<form
    id="hbs-booking-form"
    class="hbs-form"
    method="post"
    action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    data-price-single="<?php echo esc_attr($price_single); ?>"
    data-price-double="<?php echo esc_attr($price_double); ?>"
    data-price-extra-adult="<?php echo esc_attr($price_extra_adult); ?>"
    data-price-extra-kid="<?php echo esc_attr($price_extra_kid); ?>"
    novalidate
>
    <!-- Requerido por admin-ajax -->
    <input type="hidden" name="action" value="hbs_submit_booking">
    <!-- Nonce -->
    <input type="hidden" name="<?php echo esc_attr(HBS_Config::NONCE_KEY); ?>" value="<?php echo esc_attr($nonce); ?>">
    <!-- Honeypot anti-spam -->
    <input type="text" name="hbs_hp_field" value="" style="display:none;" tabindex="-1" autocomplete="off">

    <h3><?php echo esc_html__('Solicitud de reservación', 'hotel-booking-system'); ?></h3>

    <!-- Avisos de políticas clave -->
    <div class="hbs-notices" aria-live="polite" style="margin-bottom:12px;">
        <small>
            <?php echo esc_html__('Check-in: 15:00 • Check-out: 13:00 (estricto) • Sujeto a disponibilidad • Niños &lt; 4 años gratis compartiendo cama.', 'hotel-booking-system'); ?>
        </small>
    </div>

    <!-- Fechas -->
    <label>
        <?php echo esc_html__('Fecha de llegada', 'hotel-booking-system'); ?>
        <input
            type="date"
            class="js-flatpickr"
            name="check_in_date"
            value="<?php echo esc_attr($check_in_date); ?>"
            min="<?php echo esc_attr($today); ?>"
            required
        >
    </label>

    <label>
        <?php echo esc_html__('Noches', 'hotel-booking-system'); ?>
        <input
            type="number"
            name="nights"
            min="1"
            value="<?php echo esc_attr($nights); ?>"
            required
        >
    </label>

    <!-- Huéspedes -->
    <label>
        <?php echo esc_html__('Adultos', 'hotel-booking-system'); ?>
        <input
            type="number"
            name="adults_count"
            min="1"
            value="<?php echo esc_attr($adults_prefill); ?>"
            required
        >
    </label>

    <label>
        <?php echo esc_html__('Niños (≥4 años)', 'hotel-booking-system'); ?>
        <input
            type="number"
            name="kids_count"
            min="0"
            value="<?php echo esc_attr($kids_prefill); ?>"
            required
        >
    </label>

    <!-- Tipo de habitación (la lógica de auto/elección se controla con JS en Fase 5) -->
    <div class="hbs-room-type" style="margin:10px 0;">
        <strong><?php echo esc_html__('Tipo de habitación', 'hotel-booking-system'); ?></strong><br>
        <label style="margin-right:12px;">
            <input type="radio" name="room_type" value="single">
            <?php echo esc_html__('Habitación Sencilla', 'hotel-booking-system'); ?>
        </label>
        <label>
            <input type="radio" name="room_type" value="double">
            <?php echo esc_html__('Habitación Doble', 'hotel-booking-system'); ?>
        </label>
        <div id="hbs-room-hint" class="hbs-hint" style="margin-top:6px;font-size:12px;color:#555;"></div>
    </div>

    <!-- Datos de contacto -->
    <label>
        <?php echo esc_html__('Nombre completo', 'hotel-booking-system'); ?>
        <input type="text" name="guest_name" required>
    </label>

    <label>
        <?php echo esc_html__('Email', 'hotel-booking-system'); ?>
        <input type="email" name="guest_email" required>
    </label>

    <label>
        <?php echo esc_html__('Teléfono', 'hotel-booking-system'); ?>
        <input type="tel" name="guest_phone" required>
    </label>

    <!-- Aceptación de políticas -->
    <label style="display:flex;align-items:center;gap:8px;margin-top:6px;">
        <input type="checkbox" name="accept_policies" required>
        <span>
            <?php
                printf(
                    /* translators: %s URL de políticas del hotel */
                    esc_html__('Acepto las %s del hotel', 'hotel-booking-system'),
                    '<a href="' . $policies_url . '" target="_blank" rel="noopener noreferrer">' . esc_html__('políticas', 'hotel-booking-system') . '</a>'
                );
            ?>
        </span>
    </label>

    <!-- Desglose de precio (Fase 5 JS lo actualiza en tiempo real) -->
    <div id="hbs-price-breakdown" style="margin-top:12px;"></div>

    <!-- Total (enviado al servidor; el servidor recalcula de todas formas por seguridad) -->
    <input type="hidden" name="total_price" value="0">

    <button type="submit" style="margin-top:12px;"><?php echo esc_html__('Reservar', 'hotel-booking-system'); ?></button>

    <noscript>
        <p style="margin-top:10px;color:#a00;">
            <?php echo esc_html__('Para enviar su solicitud y ver el desglose de precio, habilite JavaScript en su navegador.', 'hotel-booking-system'); ?>
        </p>
    </noscript>
</form>

<!-- Contenedor para mensajes de éxito/error del envío AJAX -->
<div id="hbs-form-message" role="status" aria-live="polite"></div>
