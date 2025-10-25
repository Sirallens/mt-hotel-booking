<?php
/**
 * Fired during plugin deactivation
 *
 * @link       https://altavistahotel.com
 * @since      0.1.0
 *
 * @package    Hotel_Booking_System
 * @subpackage Hotel_Booking_System/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Bloquear acceso directo
}

class HBS_Deactivator {

    /**
     * Método ejecutado al desactivar el plugin.
     *
     * @since 0.1.0
     */
    public static function deactivate() {
        // No eliminamos tablas ni opciones.
        // Si quieres limpiar datos al desinstalar,
        // crea un archivo uninstall.php con la lógica.
        
        // Ejemplo: limpiar transients o cache si existiera
        // delete_transient('hbs_some_cache_key');
    }
}
