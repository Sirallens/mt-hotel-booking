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

        wp_enqueue_style(
            'hbs-public',
            plugins_url('../assets/css/hotel-booking.css', __FILE__),
            array(),
            '0.1.1' // Bump version
        );

        wp_enqueue_script(
            'hbs-public',
            plugins_url('../assets/js/hotel-booking.js', __FILE__),
            array('jquery'),
            '0.1.1',
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

        // Extract prices (cast to float for safety).
        $prices = array(
            'price_single' => isset($options['price_single']) ? (float) $options['price_single'] : 1850.00,
            'price_double' => isset($options['price_double']) ? (float) $options['price_double'] : 2100.00,
            'price_extra_adult' => isset($options['price_extra_adult']) ? (float) $options['price_extra_adult'] : 450.00,
            'price_extra_kid' => isset($options['price_extra_kid']) ? (float) $options['price_extra_kid'] : 250.00,
        );

        wp_localize_script(
            'hbs-public',
            'HBS_VARS',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(HBS_Config::NONCE_ACTION),
                'prices' => $prices,
            )
        );
    }

    /**
     * Register the booking form shortcode.
     */
    public function register_shortcode()
    {
        add_shortcode('hotel_booking_form', array($this, 'shortcode_booking_form'));
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
     * Render floating mini–form in footer (desktop only).
     * Outputs only if 'floating_enabled' setting is truthy AND not in admin.
     */
    public function render_floating_form()
    {
        if (is_admin()) {
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
                    <span><?php echo esc_html__('Llegada', 'hotel-booking-system'); ?></span>
                    <div class="hbs-input-wrapper">
                        <input type="date" name="check_in_date" value="<?php echo esc_attr($today); ?>"
                            min="<?php echo esc_attr($today); ?>">
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
        // Nonce.
        check_ajax_referer(HBS_Config::NONCE_ACTION, HBS_Config::NONCE_KEY);

        // Honeypot (simple anti-spam).
        if (!empty($_POST['hbs_hp_field'])) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
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