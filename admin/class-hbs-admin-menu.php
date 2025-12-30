<?php
/**
 * Admin Menu and Logic
 */

if (!defined('ABSPATH')) {
    exit;
}

class HBS_Admin_Menu
{
    private $loader;

    public function __construct($loader)
    {
        $this->loader = $loader;

        // Add menu
        $this->loader->add_action('admin_menu', $this, 'register_menu');

        // Enqueue admin assets (color picker)
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');

        // Save settings action
        $this->loader->add_action('admin_post_hbs_save_settings', $this, 'save_settings');

        // Delete booking action
        $this->loader->add_action('admin_post_hbs_delete_booking', $this, 'delete_booking');
    }

    public function enqueue_admin_assets($hook)
    {
        // Only load on our settings page
        if ('toplevel_page_hbs_settings' !== $hook && 'hotel-booking_page_hbs_settings' !== $hook) {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Select2 for multiselect
        wp_enqueue_style('select2-css', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
        wp_enqueue_script('select2-js', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true);
    }

    public function register_menu()
    {
        add_menu_page(
            __('Hotel Booking', 'hotel-booking-system'),
            __('Hotel Booking', 'hotel-booking-system'),
            'manage_options',
            'hbs_settings',
            [$this, 'render_settings_page'],
            'dashicons-calendar-alt',
            56
        );

        add_submenu_page(
            'hbs_settings',
            __('Ajustes', 'hotel-booking-system'),
            __('Ajustes', 'hotel-booking-system'),
            'manage_options',
            'hbs_settings',
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            'hbs_settings',
            __('Reservaciones recientes', 'hotel-booking-system'),
            __('Reservaciones recientes', 'hotel-booking-system'),
            'manage_options',
            'hbs_recent_bookings',
            [$this, 'render_recent_bookings_page']
        );
    }

    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        include plugin_dir_path(__FILE__) . 'views/settings-page.php';
    }

    public function render_recent_bookings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        include plugin_dir_path(__FILE__) . 'views/recent-bookings-page.php';
    }

    public function save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes.', 'hotel-booking-system'));
        }

        // Verify nonce using HBS_Config constants
        check_admin_referer(HBS_Config::NONCE_ACTION, HBS_Config::NONCE_KEY);

        $input = $_POST;
        $settings = get_option(HBS_Config::OPTION_KEY, []);

        // Sanitize fields
        $settings['staff_emails'] = sanitize_text_field($input['staff_emails']);
        $settings['policies_url'] = esc_url_raw($input['policies_url']);
        $settings['price_single'] = floatval($input['price_single']);
        $settings['price_double'] = floatval($input['price_double']);
        $settings['price_extra_adult'] = floatval($input['price_extra_adult']);
        $settings['price_extra_kid'] = floatval($input['price_extra_kid']);
        $settings['enable_custom_styles'] = isset($input['enable_custom_styles']) ? 1 : 0;
        $settings['floating_enabled'] = isset($input['floating_enabled']) ? 1 : 0;
        $settings['guest_email_note'] = sanitize_textarea_field($input['guest_email_note']);

        // Email Configuration
        $settings['email_staff_subject'] = sanitize_text_field($input['email_staff_subject']);
        $settings['email_staff_content'] = wp_kses_post($input['email_staff_content']);
        $settings['email_guest_subject'] = sanitize_text_field($input['email_guest_subject']);
        $settings['email_guest_content'] = wp_kses_post($input['email_guest_content']);

        // Excepciones (IDs de página)
        // Store as comma-separated string even if input is array (from dropdown)
        if (isset($input['floating_exceptions']) && is_array($input['floating_exceptions'])) {
            $settings['floating_exceptions'] = implode(',', array_map('sanitize_text_field', $input['floating_exceptions']));
        } else {
            $settings['floating_exceptions'] = sanitize_text_field($input['floating_exceptions']);
        }

        // Appearance: Main Form
        $settings['main_color_primary'] = sanitize_hex_color($input['main_color_primary']);
        $settings['main_color_accent'] = sanitize_hex_color($input['main_color_accent']);
        $settings['main_color_bg'] = sanitize_hex_color($input['main_color_bg']);
        $settings['main_color_text'] = sanitize_hex_color($input['main_color_text']);

        // Appearance: Floating Form
        $settings['float_color_bg'] = sanitize_hex_color($input['float_color_bg']);
        $settings['float_color_text'] = sanitize_hex_color($input['float_color_text']);
        $settings['float_color_btn'] = sanitize_hex_color($input['float_color_btn']);

        // Optional booking URL for floating form redirect
        if (isset($input['booking_page_url'])) {
            $settings['booking_page_url'] = esc_url_raw($input['booking_page_url']);
        }

        $settings['submit_btn_text'] = isset($input['submit_btn_text']) ? sanitize_text_field($input['submit_btn_text']) : '';

        update_option(HBS_Config::OPTION_KEY, $settings);

        wp_redirect(admin_url('admin.php?page=hbs_settings&hbs_saved=true'));
        exit;
    }
    public function delete_booking()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes.', 'hotel-booking-system'));
        }

        check_admin_referer('hbs_delete_booking', 'hbs_delete_nonce');

        $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

        if ($booking_id > 0) {
            HBS_Booking::delete($booking_id);
        }

        wp_redirect(admin_url('admin.php?page=hbs_recent_bookings&deleted=true'));
        exit;
    }
}
