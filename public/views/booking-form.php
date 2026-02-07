<?php
/**
 * Booking form view (shortcode [hotel_booking_form])
 * Path: public/views/booking-form.php
 */

if (!defined('ABSPATH')) {
    exit;
}

$opts = get_option(HBS_Config::OPTION_KEY, []);
$nonce = wp_create_nonce(HBS_Config::NONCE_ACTION);

// Prefill desde parámetros de URL (flotante) con límites seguros:
$today = date('Y-m-d');
$check_in_date = isset($_GET['check_in_date']) ? sanitize_text_field($_GET['check_in_date']) : '';
if (!$check_in_date || strtotime($check_in_date) < strtotime($today)) {
    $check_in_date = $today;
}

$nights = isset($_GET['nights']) ? max(1, intval($_GET['nights'])) : 1;
$adults_prefill = isset($_GET['adults']) ? max(1, intval($_GET['adults'])) : 1;
$kids_prefill = isset($_GET['kids']) ? max(0, intval($_GET['kids'])) : 0;

// Precios (defaults configurables):
$price_single = isset($opts['price_single']) ? floatval($opts['price_single']) : 1850.00;
$price_double = isset($opts['price_double']) ? floatval($opts['price_double']) : 2100.00;
$price_extra_adult = isset($opts['price_extra_adult']) ? floatval($opts['price_extra_adult']) : 450.00;
$price_extra_kid = isset($opts['price_extra_kid']) ? floatval($opts['price_extra_kid']) : 250.00;

// URL políticas:
$policies_url = !empty($opts['policies_url']) ? esc_url($opts['policies_url']) : '#';
?>
<form id="hbs-booking-form" class="hbs-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>"
    data-price-single="<?php echo esc_attr($price_single); ?>"
    data-price-double="<?php echo esc_attr($price_double); ?>"
    data-price-extra-adult="<?php echo esc_attr($price_extra_adult); ?>"
    data-price-extra-kid="<?php echo esc_attr($price_extra_kid); ?>" novalidate>
    <!-- Requerido por admin-ajax -->
    <input type="hidden" name="action" value="hbs_submit_booking">
    <!-- Nonce (seguridad) -->
    <input type="hidden" name="<?php echo esc_attr(HBS_Config::NONCE_KEY); ?>" value="<?php echo esc_attr($nonce); ?>">

    <!-- Honeypot anti-spam (múltiples trampas) -->
    <div style="position: absolute; left: -9999px; top: -9999px;" aria-hidden="true">
        <input type="text" name="hbs_hp_field" value="" tabindex="-1" autocomplete="off">
        <input type="email" name="website_url" value="" tabindex="-1" autocomplete="off" placeholder="Tu sitio web">
        <input type="text" name="company_name" value="" tabindex="-1" autocomplete="off"
            placeholder="Nombre de empresa">
    </div>

    <div class="hbs-header">
        <h3><?php echo esc_html__('Solicitud de Cotización', 'hotel-booking-system'); ?></h3>
        <p class="hbs-subtitle">
            <?php echo esc_html__('Complete sus datos para recibir una cotización oficial.', 'hotel-booking-system'); ?>
        </p>
        <div class="hbs-info-note">
            <svg class="hbs-info-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="16" x2="12" y2="12"></line>
                <line x1="12" y1="8" x2="12.01" y2="8"></line>
            </svg>
            <p>
                <?php echo esc_html__('Este formulario es para solicitar cotización de reservación. Un miembro de nuestro equipo le atenderá a través de la información de contacto que proporcione. Se recomienda enviar su solicitud con al menos 2 días de anticipación.', 'hotel-booking-system'); ?>
            </p>
        </div>
    </div>

    <!-- Avisos -->
    <div class="hbs-notices" aria-live="polite">
        <span class="hbs-notice-badge">Check-in 15:00</span>
        <span class="hbs-notice-badge">Check-out 13:00</span>
    </div>

    <div class="hbs-section-title"><?php echo esc_html__('Detalles de la estancia', 'hotel-booking-system'); ?></div>

    <div class="hbs-grid hbs-grid-2">
        <div class="hbs-field">
            <label for="hbs-checkin"><?php echo esc_html__('Fecha de llegada', 'hotel-booking-system'); ?></label>
            <div class="hbs-input-wrapper">
                <input id="hbs-checkin" type="date" class="js-flatpickr" name="check_in_date"
                    value="<?php echo esc_attr($check_in_date); ?>" min="<?php echo esc_attr($today); ?>" required>
            </div>
        </div>

        <div class="hbs-field">
            <label for="hbs-nights"><?php echo esc_html__('Noches', 'hotel-booking-system'); ?></label>
            <div class="hbs-input-wrapper">
                <input id="hbs-nights" type="number" name="nights" min="1" value="<?php echo esc_attr($nights); ?>"
                    required>
                <svg class="hbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                </svg>
            </div>
        </div>
    </div>

    <div class="hbs-grid hbs-grid-2">
        <div class="hbs-field">
            <label for="hbs-adults"><?php echo esc_html__('Adultos', 'hotel-booking-system'); ?></label>
            <div class="hbs-input-wrapper">
                <input id="hbs-adults" type="number" name="adults_count" min="1"
                    value="<?php echo esc_attr($adults_prefill); ?>" required>
                <svg class="hbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </div>
        </div>

        <div class="hbs-field">
            <label for="hbs-kids">
                <?php echo esc_html__('Niños', 'hotel-booking-system'); ?>
                <span class="hbs-sub-label">(4 - 11 años)</span>
            </label>
            <div class="hbs-input-wrapper">
                <input id="hbs-kids" type="number" name="kids_count" min="0"
                    value="<?php echo esc_attr($kids_prefill); ?>" required>
                <svg class="hbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15.5 20.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v5.5Z" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
            </div>
        </div>
    </div>

    <div class="hbs-section-title"><?php echo esc_html__('Habitación', 'hotel-booking-system'); ?></div>

    <!-- Room Type Cards -->
    <div class="hbs-room-selector">
        <?php
        $room_types = HBS_Room_Types::get_all();
        foreach ($room_types as $room):
            ?>
            <label class="hbs-room-card">
                <input type="radio" name="room_type" value="<?php echo esc_attr($room['slug']); ?>" class="hbs-room-radio">
                <div class="hbs-room-content">
                    <span class="hbs-room-title"><?php echo esc_html($room['name']); ?></span>
                    <span class="hbs-room-desc">
                        <?php
                        // Minimum guests = number of beds, Maximum = max_total configured
                        $min_guests = isset($room['beds']) ? (int) $room['beds'] : 1;
                        $max_guests = isset($room['max_total']) ? (int) $room['max_total'] : $room['max_capacity'];
                        printf(
                            esc_html(_n('%1$d - %2$d Huésped', '%1$d - %2$d Huéspedes', $max_guests, 'hotel-booking-system')),
                            $min_guests,
                            $max_guests
                        );
                        ?>
                    </span>
                    <?php if (!empty($room['detail_page_url'])): ?>
                        <a href="<?php echo esc_url($room['detail_page_url']); ?>" target="_blank" class="hbs-room-link">
                            <?php esc_html_e('Ver detalles', 'hotel-booking-system'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="hbs-check-mark"></div>
            </label>
        <?php endforeach; ?>
    </div>
    <div id="hbs-room-hint" class="hbs-hint"></div>

    <div class="hbs-section-title"><?php echo esc_html__('Datos de Contacto', 'hotel-booking-system'); ?></div>

    <div class="hbs-field">
        <label for="hbs-name"><?php echo esc_html__('Nombre completo', 'hotel-booking-system'); ?></label>
        <input id="hbs-name" type="text" name="guest_name" required
            placeholder="<?php echo esc_attr__('Ej. Juan Pérez', 'hotel-booking-system'); ?>">
    </div>

    <div class="hbs-grid hbs-grid-2">
        <div class="hbs-field">
            <label for="hbs-email"><?php echo esc_html__('Email', 'hotel-booking-system'); ?></label>
            <input id="hbs-email" type="email" name="guest_email" required placeholder="nombre@correo.com">
        </div>
        <div class="hbs-field">
            <label for="hbs-phone"><?php echo esc_html__('Teléfono', 'hotel-booking-system'); ?></label>
            <div class="hbs-phone-wrapper">
                <select id="hbs-country-code" name="guest_country_code" class="hbs-country-select">
                    <option value="+52" selected>MX (+52)</option>
                    <option value="+1">US (+1)</option>
                    <option value="+1">CA (+1)</option>
                    <option value="+34">ES (+34)</option>
                    <option value="+54">AR (+54)</option>
                    <option value="+57">CO (+57)</option>
                    <option value="+56">CL (+56)</option>
                    <option value="+51">PE (+51)</option>
                    <option value="+506">CR (+506)</option>
                    <option value="+44">UK (+44)</option>
                    <option value="+33">FR (+33)</option>
                    <option value="+49">DE (+49)</option>
                    <option value="+39">IT (+39)</option>
                    <option value="+55">BR (+55)</option>
                </select>
                <input id="hbs-phone" type="tel" name="guest_phone" required placeholder="000 000 0000" maxlength="10"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);">
            </div>
        </div>
    </div>

    <div class="hbs-footer-actions">
        <label class="hbs-checkbox-wrapper">
            <input type="checkbox" name="accept_policies" required>
            <span class="hbs-checkbox-text">
                <?php
                printf(
                    esc_html__('He leído y acepto las %s del hotel.', 'hotel-booking-system'),
                    '<a href="' . $policies_url . '" target="_blank" class="hbs-link">' . esc_html__('políticas', 'hotel-booking-system') . '</a>'
                );
                ?>
            </span>
        </label>

        <label class="hbs-checkbox-wrapper">
            <input type="checkbox" name="accept_quote_terms" required>
            <span class="hbs-checkbox-text">
                <?php echo esc_html__('Entiendo que esta es una solicitud de cotización sujeto a disponibilidad y que se me recomienda reservar con al menos 2 días de anticipación.', 'hotel-booking-system'); ?>
            </span>
        </label>

        <?php if (!empty($opts['show_price_breakdown'])): ?>
            <!-- Desglose de precio -->
            <div id="hbs-price-breakdown" class="hbs-price-box"></div>

            <input type="hidden" name="total_price" value="0">
        <?php endif; ?>

        <button type="submit" class="hbs-btn-primary">
            <?php echo esc_html(!empty($opts['submit_btn_text']) ? $opts['submit_btn_text'] : __('Confirmar Reserva', 'hotel-booking-system')); ?>
        </button>
    </div>

    <noscript>
        <div class="hbs-error-box">
            <?php echo esc_html__('Para continuar, habilite JavaScript en su navegador.', 'hotel-booking-system'); ?>
        </div>
    </noscript>
</form>

<!-- Loading Overlay -->
<div id="hbs-loading-overlay" class="hbs-loading-overlay" style="display: none;" role="status" aria-live="assertive">
    <div class="hbs-loading-content">
        <div class="hbs-dots-spinner">
            <span class="hbs-dot"></span>
            <span class="hbs-dot"></span>
            <span class="hbs-dot"></span>
        </div>
        <p class="hbs-loading-text"><?php echo esc_html__('Enviando tu solicitud...', 'hotel-booking-system'); ?></p>
    </div>
</div>

<!-- Contenedor para mensajes de éxito/error del envío AJAX -->
<div id="hbs-form-message" role="status" aria-live="polite"></div>