<?php
/**
 * Uninstall cleanup for Altavista Hotel Booking System
 *
 * This file is executed when the plugin is deleted from WordPress.
 * It removes the custom DB table and plugin options.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit; // Seguridad: solo corre en proceso de desinstalación
}

// CUIDADO: Elimina datos permanentemente.
// Si quieres conservar datos, comenta las líneas correspondientes.

global $wpdb;

// Nombre de la tabla (coincide con el activator)
$table_name = $wpdb->prefix . 'hbs_bookings';

// 1) Borrar tabla de reservaciones
// Nota: No usamos prepare() para nombres de tabla, solo concatenamos el prefijo seguro de WP.
$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );

// 2) Borrar opciones del plugin (almacenamos todo en un solo array)
delete_option( 'hbs_settings' );

// 3) Borrar opciones en red si el plugin se usó en multisite (opcional)
if ( is_multisite() ) {
    delete_site_option( 'hbs_settings' );
}

// 4) (Opcional) Limpiar transients/cachés relacionadas si las hubiere
// delete_transient( 'hbs_some_cache_key' );

// 5) (Opcional) Otras limpiezas (logs/archivos) si las gestionaste manualmente
// Ejemplo: eliminar archivos en wp-content/uploads/hbs/ (si existieran)
// $upload_dir = wp_upload_dir();
// $hbs_dir = trailingslashit( $upload_dir['basedir'] ) . 'hbs/';
// // Implementar borrado recursivo con extremo cuidado.
