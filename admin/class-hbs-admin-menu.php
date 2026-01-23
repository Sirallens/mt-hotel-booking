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

        // Room type actions
        $this->loader->add_action('admin_post_hbs_save_room_type', $this, 'save_room_type');
        $this->loader->add_action('admin_post_hbs_delete_room_type', $this, 'delete_room_type');
    }

    public function enqueue_admin_assets($hook)
    {
        // Only load on our settings page
        if ('toplevel_page_hbs_settings' !== $hook && 'hotel-booking_page_hbs_settings' !== $hook) {
            return;
        }
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Custom Admin CSS
        wp_enqueue_style('hbs-admin-settings', plugins_url('../assets/css/admin-settings.css', __FILE__), [], '1.0.0');

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

        add_submenu_page(
            'hbs_settings',
            __('Tipos de Habitación', 'hotel-booking-system'),
            __('Tipos de Habitación', 'hotel-booking-system'),
            'manage_options',
            'hbs_room_types',
            [$this, 'render_room_types_page']
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

    public function render_room_types_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        include plugin_dir_path(__FILE__) . 'views/room-types-page.php';
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
        // Booking Page URL - support relative paths
        if (isset($input['booking_page_url']) && !empty($input['booking_page_url'])) {
            $url = trim($input['booking_page_url']);
            // If starts with /, prepend site URL
            if (strpos($url, '/') === 0) {
                $settings['booking_page_url'] = esc_url_raw(home_url($url));
            } else {
                $settings['booking_page_url'] = esc_url_raw($url);
            }
        } else {
            $settings['booking_page_url'] = '';
        }

        // Policies Page - convert page ID to URL OR handle direct URL input
        if (isset($input['policies_page_id']) && intval($input['policies_page_id']) > 0) {
            $settings['policies_url'] = get_permalink(intval($input['policies_page_id']));
        } elseif (isset($input['policies_url']) && !empty($input['policies_url'])) {
            // Support direct URL input with relative path
            $url = trim($input['policies_url']);
            if (strpos($url, '/') === 0) {
                $settings['policies_url'] = esc_url_raw(home_url($url));
            } else {
                $settings['policies_url'] = esc_url_raw($url);
            }
        } else {
            $settings['policies_url'] = '';
        }

        $settings['submit_btn_text'] = isset($input['submit_btn_text']) ? sanitize_text_field($input['submit_btn_text']) : '';

        // Custom CSS
        $settings['custom_css_booking_form'] = isset($input['custom_css_booking_form']) ? wp_strip_all_tags($input['custom_css_booking_form']) : '';
        $settings['custom_css_floating_form'] = isset($input['custom_css_floating_form']) ? wp_strip_all_tags($input['custom_css_floating_form']) : '';

        // Thank You Page - convert page ID to URL
        if (isset($input['thankyou_page_id']) && intval($input['thankyou_page_id']) > 0) {
            $settings['thankyou_page_url'] = get_permalink(intval($input['thankyou_page_id']));
        } else {
            $settings['thankyou_page_url'] = '';
        }

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

    public function save_room_type()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes.', 'hotel-booking-system'));
        }

        check_admin_referer('hbs_save_room_type', 'hbs_nonce');

        $room_type = [
            'slug' => sanitize_key($_POST['slug']),
            'name' => sanitize_text_field($_POST['name']),
            'base_guests' => isset($_POST['base_guests']) ? max(1, intval($_POST['base_guests'])) : 2,
            'max_capacity' => isset($_POST['max_capacity']) ? max(1, intval($_POST['max_capacity'])) : 4,
            'base_price' => isset($_POST['base_price']) ? max(0, floatval($_POST['base_price'])) : 0,
            'detail_page_url' => '',
        ];

        // Handle detail_page_url: support page ID dropdown OR direct URL with relative paths
        if (isset($_POST['detail_page_id']) && intval($_POST['detail_page_id']) > 0) {
            $room_type['detail_page_url'] = get_permalink(intval($_POST['detail_page_id']));
        } elseif (isset($_POST['detail_page_url']) && !empty($_POST['detail_page_url'])) {
            $url = trim($_POST['detail_page_url']);
            if (strpos($url, '/') === 0) {
                $room_type['detail_page_url'] = esc_url_raw(home_url($url));
            } else {
                $room_type['detail_page_url'] = esc_url_raw($url);
            }
        }

        HBS_Room_Types::save($room_type);

        wp_redirect(admin_url('admin.php?page=hbs_room_types&saved=true'));
        exit;
    }

    public function delete_room_type()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('Permisos insuficientes.', 'hotel-booking-system'));
        }

        check_admin_referer('hbs_delete_room_type');

        $slug = isset($_GET['slug']) ? sanitize_key($_GET['slug']) : '';

        if ($slug && !HBS_Room_Types::delete($slug)) {
            wp_redirect(admin_url('admin.php?page=hbs_room_types&error=cannot_delete_last'));
            exit;
        }

        wp_redirect(admin_url('admin.php?page=hbs_room_types&deleted=true'));
        exit;
    }
}
