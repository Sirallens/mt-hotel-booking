<?php
/**
 * Public (frontend) functionality for Altavista Hotel Booking System.
 *
 * Responsibilities:
 * - Enqueue frontend assets and localize script (prices, ajax, nonce).
 * - Register shortcode [hotel_booking_form].
 * - Render floating mini–form in footer (optional via settings).
 * - Handle AJAX booking submission.
 *
 * @package Altavista_Hotel_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class HBS_Public
{

    /**
     * Loader instance for registering hooks.
     *
     * @var HBS_Loader
     */
    private $loader;

    /**
     * Constructor.
     *
     * @param HBS_Loader $loader Loader instance.
     */
    public function __construct($loader)
    {
        $this->loader = $loader;

        // Frontend assets.
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_assets');

        // Shortcode registration.
        $this->loader->add_action('init', $this, 'register_shortcode');

        // Floating form output.
        $this->loader->add_action('wp_footer', $this, 'render_floating_form');

        // AJAX handlers (logged & guest).
        $this->loader->add_action('wp_ajax_hbs_submit_booking', $this, 'handle_booking');
        $this->loader->add_action('wp_ajax_nopriv_hbs_submit_booking', $this, 'handle_booking');
    }

    /**
     * Enqueue public CSS & JS and localize script with dynamic data.
     */
    public function enqueue_assets()
    {
        // Use plugins_url for robust path resolution (handles ../ correctly)
        // Assets are in: mt-hotel-booking/assets/
        // This file is in: mt-hotel-booking/public/

        // Enqueue Flatpickr library for datepicker
        wp_enqueue_style(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
            array(),
            '4.6.13'
        );

        wp_enqueue_script(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
            array(),
            '4.6.13',
            true
        );

        // Flatpickr Spanish locale (optional)
        wp_enqueue_script(
            'flatpickr-es',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js',
            array('flatpickr'),
            '4.6.13',
            true
        );

        wp_enqueue_style(
            'hbs-public',
            plugins_url('../assets/css/hotel-booking.css', __FILE__),
            array('flatpickr'), // Depend on Flatpickr CSS
            '0.1.4' // Info note styling
        );

        wp_enqueue_script(
            'hbs-public',
            plugins_url('../assets/js/hotel-booking.js', __FILE__),
            array('jquery', 'flatpickr', 'flatpickr-es'), // Depend on Flatpickr and Spanish locale
            '0.1.5', // Flatpickr init now runs on ALL pages
            true
        );

        // --- Dynamic Custom Styles ---
        $options = get_option(HBS_Config::OPTION_KEY, array());

        if (!empty($options['enable_custom_styles'])) {
            // Defaults
            $main_primary = !empty($options['main_color_primary']) ? $options['main_color_primary'] : '#0f172a';
            $main_accent = !empty($options['main_color_accent']) ? $options['main_color_accent'] : '#3b82f6';
            $main_bg = !empty($options['main_color_bg']) ? $options['main_color_bg'] : '#ffffff';
            $main_text = !empty($options['main_color_text']) ? $options['main_color_text'] : '#334155';
            $main_primary_hover = $this->adjust_brightness($main_primary, -15);

            $float_bg = !empty($options['float_color_bg']) ? $options['float_color_bg'] : '#ffffff';
            $float_text = !empty($options['float_color_text']) ? $options['float_color_text'] : '#0f172a';
            $float_btn = !empty($options['float_color_btn']) ? $options['float_color_btn'] : '#0f172a';
            $float_btn_hover = $this->adjust_brightness($float_btn, -15);

            $custom_css = "
                :root {
                    --hbs-primary: {$main_primary};
                    --hbs-primary-hover: {$main_primary_hover};
                    --hbs-accent: {$main_accent};
                    --hbs-card-bg: {$main_bg};
                    --hbs-text: {$main_text};
                }
                #hbs-floating-form {
                    background: {$float_bg};
                }
                #hbs-floating-form span {
                    color: {$float_text};
                }
                #hbs-floating-form button {
                    background: {$float_btn};
                }
                #hbs-floating-form button:hover {
                    background: {$float_btn_hover};
                }
            ";

            wp_add_inline_style('hbs-public', $custom_css);
        }

        // --- Custom CSS from Settings ---
        $custom_css_booking = !empty($options['custom_css_booking_form']) ? $options['custom_css_booking_form'] : '';
        $custom_css_floating = !empty($options['custom_css_floating_form']) ? $options['custom_css_floating_form'] : '';

        if (!empty($custom_css_booking) || !empty($custom_css_floating)) {
            $combined_css = $custom_css_booking . "\n" . $custom_css_floating;
            wp_add_inline_style('hbs-public', $combined_css);
        }

        // Extract prices (cast to float for safety).
        $prices = array(
            'price_single' => isset($options['price_single']) ? (float) $options['price_single'] : 1850.00,
            'price_double' => isset($options['price_double']) ? (float) $options['price_double'] : 2100.00,
            'price_extra_adult' => isset($options['price_extra_adult']) ? (float) $options['price_extra_adult'] : 450.00,
            'price_extra_kid' => isset($options['price_extra_kid']) ? (float) $options['price_extra_kid'] : 250.00,
        );

        // Get room types for JavaScript
        $room_types = HBS_Room_Types::get_all();

        wp_localize_script(
            'hbs-public',
            'HBS_VARS',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(HBS_Config::NONCE_ACTION),
                'prices' => $prices,
                'thankyou_url' => !empty($options['thankyou_page_url']) ? esc_url($options['thankyou_page_url']) : '',
                'room_types' => $room_types,
                'show_price_breakdown' => !empty($options['show_price_breakdown']),
            )
        );
    }

    /**
     * Register the booking form shortcodes.
     */
    public function register_shortcode()
    {
        add_shortcode('hotel_booking_form', array($this, 'shortcode_booking_form'));
        add_shortcode('hotel_booking_confirmation', array($this, 'shortcode_booking_confirmation'));
    }

    /**
     * Shortcode callback: returns the booking form HTML.
     *
     * @return string
     */
    public function shortcode_booking_form()
    {
        ob_start();
        $view_file = plugin_dir_path(__FILE__) . 'views/booking-form.php';

        if (file_exists($view_file)) {
            include $view_file;
        } else {
            echo '<p>' . esc_html__('Plantilla de formulario no encontrada.', 'hotel-booking-system') . '</p>';
        }

        return ob_get_clean();
    }

    /**
     * Shortcode callback: displays booking confirmation.
     *
     * @return string
     */
    public function shortcode_booking_confirmation()
    {
        // Get booking ID from URL
        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

        if (empty($booking_id)) {
            return '<div class="hbs-confirmation hbs-error"><p>' . esc_html__('No se encontró el ID de reservación.', 'hotel-booking-system') . '</p></div>';
        }

        // Fetch booking details
        $booking = HBS_Booking::get($booking_id);

        if (!$booking) {
            return '<div class="hbs-confirmation hbs-error"><p>' . esc_html__('Reservación no encontrada.', 'hotel-booking-system') . '</p></div>';
        }

        // Format room type - get from database
        $room = HBS_Room_Types::get($booking['room_type']);
        $room_type_label = $room ? $room['name'] : $booking['room_type'];

        // Build output
        ob_start();
        ?>
        <div class="hbs-confirmation hbs-success">
            <div class="hbs-confirmation-header">
                <h2><?php esc_html_e('¡Solicitud Recibida!', 'hotel-booking-system'); ?></h2>
                <p class="hbs-confirmation-subtitle">
                    <?php printf(esc_html__('Gracias %s, hemos recibido su solicitud.', 'hotel-booking-system'), '<strong>' . esc_html($booking['guest_name']) . '</strong>'); ?>
                </p>
            </div>

            <div class="hbs-confirmation-details">
                <table class="hbs-confirmation-table">
                    <tr>
                        <th><?php esc_html_e('Check-in', 'hotel-booking-system'); ?></th>
                        <td><?php echo esc_html($booking['check_in_date']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Check-out', 'hotel-booking-system'); ?></th>
                        <td><?php echo esc_html($booking['check_out_date']); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Tipo de Habitación', 'hotel-booking-system'); ?></th>
                        <td><?php echo esc_html($room_type_label); ?></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Huéspedes', 'hotel-booking-system'); ?></th>
                        <td><?php printf(esc_html__('%d Adultos, %d Niños', 'hotel-booking-system'), $booking['adults_count'], $booking['kids_count']); ?>
                        </td>
                    </tr>
                    <?php
                    $opts_confirmation = get_option(HBS_Config::OPTION_KEY, []);
                    if (!empty($opts_confirmation['show_price_breakdown'])):
                        ?>
                        <tr class="hbs-total-row">
                            <th><?php esc_html_e('Total Est', 'hotel-booking-system'); ?></th>
                            <td><strong>$<?php echo esc_html(number_format($booking['total_price'], 2)); ?> MXN</strong></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <div class="hbs-confirmation-footer">
                <p><?php esc_html_e('Nos pondremos en contacto con usted pronto para confirmar la disponibilidad y los siguientes pasos.', 'hotel-booking-system'); ?>
                </p>
                <p><?php printf(esc_html__('Si tiene preguntas, contáctenos a %s', 'hotel-booking-system'), '<a href="mailto:contacto@altavistahotel.com.mx">contacto@altavistahotel.com.mx</a>'); ?>
                </p>
            </div>
        </div>

        <style>
            .hbs-confirmation {
                max-width: 600px;
                margin: 40px auto;
                padding: 40px;
                background: #ffffff;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                border: 1px solid #e2e8f0;
            }

            .hbs-confirmation.hbs-error {
                border-color: #fecaca;
                background: #fee2e2;
                color: #991b1b;
            }

            .hbs-confirmation-header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #3b82f6;
            }

            .hbs-confirmation-header h2 {
                color: #0f172a;
                font-size: 2rem;
                margin: 0 0 10px;
            }

            .hbs-confirmation-subtitle {
                color: #64748b;
                font-size: 1.1rem;
                margin: 0;
            }

            .hbs-confirmation-table {
                width: 100%;
                border-collapse: collapse;
                margin: 20px 0;
            }

            .hbs-confirmation-table th,
            .hbs-confirmation-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #e2e8f0;
            }

            .hbs-confirmation-table th {
                color: #64748b;
                font-weight: 600;
                width: 40%;
            }

            .hbs-confirmation-table td {
                color: #0f172a;
                font-weight: 500;
            }

            .hbs-confirmation-table tr.hbs-total-row th,
            .hbs-confirmation-table tr.hbs-total-row td {
                font-size: 1.2rem;
                padding-top: 20px;
                border-bottom: none;
                color: #0f172a;
            }

            .hbs-confirmation-footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #e2e8f0;
                text-align: center;
                color: #64748b;
            }

            .hbs-confirmation-footer p {
                margin: 10px 0;
            }

            .hbs-confirmation-footer a {
                color: #3b82f6;
                text-decoration: none;
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if a page builder is currently active/editing.
     * 
     * @return bool True if in page builder edit mode.
     */
    private function is_page_builder_active()
    {
        // Elementor
        if (isset($_GET['elementor-preview']) || did_action('elementor/preview/init')) {
            return true;
        }

        // Divi Builder
        if (function_exists('et_core_is_fb_enabled') && et_core_is_fb_enabled()) {
            return true;
        }
        if (isset($_GET['et_fb'])) {
            return true;
        }

        // Beaver Builder
        if (isset($_GET['fl_builder'])) {
            return true;
        }

        // Bricks Builder
        if (isset($_GET['bricks']) && $_GET['bricks'] === 'run') {
            return true;
        }

        // Oxygen Builder
        if (isset($_GET['ct_builder']) || defined('SHOW_CT_BUILDER')) {
            return true;
        }

        // Brizy Builder
        if (isset($_GET['brizy-edit']) || isset($_GET['brizy-edit-iframe'])) {
            return true;
        }

        return false;
    }

    /**
     * Render floating mini–form in footer (desktop only).
     * Outputs only if 'floating_enabled' setting is truthy AND not in admin.
     */
    public function render_floating_form()
    {
        if (is_admin()) {
            return;
        }

        // Don't show in page builders
        if ($this->is_page_builder_active()) {
            return;
        }

        $options = get_option(HBS_Config::OPTION_KEY, array());

        if (empty($options['floating_enabled'])) {
            return;
        }

        // EXCLUSIONS LOGIC
        if (!empty($options['floating_exceptions'])) {
            // Can be comma-separated string OR array (new behavior)
            if (is_array($options['floating_exceptions'])) {
                $excluded_ids = array_map('intval', $options['floating_exceptions']);
            } else {
                $excluded_ids = array_map('intval', explode(',', $options['floating_exceptions']));
            }

            // Check if current page ID is in the exclusion list
            if (is_page($excluded_ids) || is_single($excluded_ids)) {
                return;
            }
        }

        // Destination booking page (URL where main shortcode lives).
        $booking_url = '';
        if (!empty($options['booking_page_url'])) {
            $booking_url = esc_url($options['booking_page_url']);
        } elseif (!empty($options['booking_page_id'])) {
            $booking_url = esc_url(get_permalink((int) $options['booking_page_id']));
        } else {
            $booking_url = esc_url(home_url('/'));
        }

        $today = date_i18n('Y-m-d');
        ?>
        <div id="hbs-floating-form"
            aria-label="<?php echo esc_attr__('Formulario rápido de reservación', 'hotel-booking-system'); ?>">
            <form method="get" action="<?php echo $booking_url; ?>">
                <!-- Check-in -->
                <label>
                    <span><?php echo esc_html__('Check-In', 'hotel-booking-system'); ?></span>
                    <div class="hbs-input-wrapper">
                        <svg class="hbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                        <input type="text" name="check_in_date" class="js-flatpickr" value="<?php echo esc_attr($today); ?>"
                            min="<?php echo esc_attr($today); ?>"
                            placeholder="<?php echo esc_attr__('Seleccionar fecha', 'hotel-booking-system'); ?>" readonly>
                    </div>
                </label>

                <!-- Nights (Moon Icon) -->
                <label>
                    <span><?php echo esc_html__('Noches', 'hotel-booking-system'); ?></span>
                    <div class="hbs-input-wrapper">
                        <svg class="hbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                        <input type="number" name="nights" value="1" min="1" style="width:70px;">
                    </div>
                </label>

                <!-- Adults -->
                <label>
                    <span><?php echo esc_html__('Adultos', 'hotel-booking-system'); ?></span>
                    <div class="hbs-input-wrapper">
                        <svg class="hbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <input type="number" name="adults" value="1" min="1" style="width:70px;">
                    </div>
                </label>

                <!-- Kids -->
                <label>
                    <span><?php echo esc_html__('Niños', 'hotel-booking-system'); ?></span>
                    <div class="hbs-input-wrapper">
                        <svg class="hbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15.5 20.5a3.5 3.5 0 1 0-7 0V15a2 2 0 0 1 2-2h3a2 2 0 0 1 2 2v5.5Z" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        <input type="number" name="kids" value="0" min="0" style="width:70px;">
                    </div>
                </label>

                <button type="submit">
                    <?php echo esc_html(!empty($options['submit_btn_text']) ? $options['submit_btn_text'] : __('Reservar', 'hotel-booking-system')); ?>
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Handle AJAX booking submission.
     * - Validates nonce & honeypot.
     * - Sanitizes all inputs.
     * - Delegates insertion + price calc to HBS_Booking::insert().
     * - Sends staff & guest emails.
     * - Returns JSON response.
     */
    public function handle_booking()
    {
        // Clean any buffered output (warnings/notices) to ensure clean JSON response
        // This works in tandem with ob_start() in the main plugin file
        if (ob_get_length()) {
            ob_clean();
        }

        // Nonce.
        check_ajax_referer(HBS_Config::NONCE_ACTION, HBS_Config::NONCE_KEY);

        // Honeypot (múltiples campos anti-spam).
        // Si cualquier campo honeypot tiene valor, es spam
        if (!empty($_POST['hbs_hp_field']) || !empty($_POST['website_url']) || !empty($_POST['company_name'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            wp_send_json_error(
                array(
                    'msg' => esc_html__('Spam detectado.', 'hotel-booking-system'),
                )
            );
        }

        // Sanitize & collect input.
        $guest_name = isset($_POST['guest_name']) ? sanitize_text_field(wp_unslash($_POST['guest_name'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $guest_email = isset($_POST['guest_email']) ? sanitize_email(wp_unslash($_POST['guest_email'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $guest_phone = isset($_POST['guest_phone']) ? sanitize_text_field(wp_unslash($_POST['guest_phone'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $check_in_date = isset($_POST['check_in_date']) ? sanitize_text_field(wp_unslash($_POST['check_in_date'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $nights = isset($_POST['nights']) ? (int) $_POST['nights'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $room_type = isset($_POST['room_type']) ? sanitize_text_field(wp_unslash($_POST['room_type'])) : 'single'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $adults_count = isset($_POST['adults_count']) ? (int) $_POST['adults_count'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $kids_count = isset($_POST['kids_count']) ? (int) $_POST['kids_count'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing

        // Basic validation (could be expanded).
        if (empty($guest_name) || empty($guest_email) || empty($guest_phone)) {
            wp_send_json_error(
                array(
                    'msg' => esc_html__('Datos incompletos.', 'hotel-booking-system'),
                )
            );
        }

        $data = array(
            'guest_name' => $guest_name,
            'guest_email' => $guest_email,
            'guest_phone' => $guest_phone,
            'check_in_date' => $check_in_date,
            'nights' => max(1, $nights),
            'room_type' => $room_type,
            'adults_count' => max(1, $adults_count),
            'kids_count' => max(0, $kids_count),
        );

        // Insert booking (calculates total internally; never trust client total).
        $result = HBS_Booking::insert($data);

        if (is_wp_error($result)) {
            wp_send_json_error(
                array(
                    'msg' => $result->get_error_message(),
                )
            );
        }

        $booking_id = (int) $result;

        // Emails (methods expected to pull any needed data themselves).
        HBS_Emails::send_staff($booking_id);
        HBS_Emails::send_guest($booking_id);

        wp_send_json_success(
            array(
                'msg' => esc_html__('Solicitud enviada correctamente.', 'hotel-booking-system'),
                'booking_id' => $booking_id,
            )
        );
    }
    /**
     * Helper: Ajustar brillo de color Hex.
     * 
     * @param string $hex Color hexadecimal.
     * @param int $steps Pasos de ajuste (-255 a 255).
     * @return string Nuevo color hex.
     */
    private function adjust_brightness($hex, $steps)
    {
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = max(0, min(255, $r + $steps));
        $g = max(0, min(255, $g + $steps));
        $b = max(0, min(255, $b + $steps));

        return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
    }
}