<?php
/**
 * Plugin Name: MT Hotel Booking System
 * Description: Plugin de cotización y solicitud de reservaciones (sin pagos, sin disponibilidad en tiempo real). Envía solicitud por email al staff y al huésped.
 * Version: 0.1.1b
 * Author: Mtech Software
 * Text Domain: hotel-booking-system
 * Domain Path: /languages
 */

if (!defined('WPINC')) {
    die;
}

// Output buffering to prevent "headers already sent" errors from WordPress warnings/notices
// This captures any unexpected output before headers are sent during AJAX responses
ob_start();

// Output buffering to prevent "headers already sent" errors from WordPress warnings/notices
// This captures any unexpected output before headers are sent during AJAX responses
ob_start();

// Requerimos las clases necesarias
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-config.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-loader.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-activator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-deactivator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-booking.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-room-types.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-occupancy-validator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-migrator.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-hbs-emails.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-hbs-admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'public/class-hbs-public.php';

// Hooks de activación / desactivación
register_activation_hook(__FILE__, ['HBS_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['HBS_Deactivator', 'deactivate']);

// Inicializamos loader y registramos admin + public
function run_hbs_plugin()
{
    // Run migrations automatically (transparent data upgrades)
    HBS_Migrator::run();

    $loader = new HBS_Loader();
    $admin = new HBS_Admin_Menu($loader);
    $public = new HBS_Public($loader);
    $loader->run();
}
run_hbs_plugin();