<?php
class HBS_Admin {
    private $loader;

    public function __construct($loader) {
        $this->loader = $loader;
        $this->loader->add_action('admin_menu', $this, 'add_menu');
    }

    public function add_menu() {
        add_menu_page(
            __('Hotel Booking','hotel-booking-system'),
            __('Hotel Booking','hotel-booking-system'),
            'manage_options',
            'hbs-settings',
            [$this,'render_settings_page'],
            'dashicons-calendar-alt',
            56
        );
    }

    public function render_settings_page() {
        include plugin_dir_path(__FILE__) . 'views/settings-page.php';
    }
}
