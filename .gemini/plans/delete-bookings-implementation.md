# Plan de Implementación: Borrar Registros de Reservaciones

## Resumen
Agregar funcionalidad para que los administradores puedan eliminar registros de reservaciones de la tabla de "Reservaciones Recientes" en el admin de WordPress.

## Archivos a Modificar

### 1. `admin/views/recent-bookings-page.php`
**Cambios necesarios:**
- Agregar columna "Acciones" en la tabla
- Incluir botón/enlace "Eliminar" por cada reservación
- El enlace debe incluir:
  - `booking_id` como parámetro
  - `nonce` de seguridad
  - Acción `delete_booking`

**Ejemplo de código:**
```php
// En el loop de reservaciones, agregar columna de acciones:
$delete_url = wp_nonce_url(
    admin_url('admin.php?page=hbs-bookings&action=delete_booking&booking_id=' . $booking['id']),
    'delete_booking_' . $booking['id']
);

echo '<td>';
echo '<a href="' . esc_url($delete_url) . '" 
         class="button button-small button-link-delete" 
         onclick="return confirm(\'' . esc_js(__('¿Estás seguro de eliminar esta reservación?', 'hotel-booking-system')) . '\');">';
echo esc_html__('Eliminar', 'hotel-booking-system');
echo '</a>';
echo '</td>';
```

### 2. `admin/class-hbs-admin-menu.php`
**Cambios necesarios:**
- Agregar método `handle_delete_booking()` en la clase
- Validar nonce de seguridad
- Verificar capacidad del usuario (`manage_options`)
- Llamar al modelo para eliminar el registro
- Redirigir con mensaje de éxito/error

**Ejemplo de código:**
```php
public function handle_delete_booking() {
    // Verificar que estamos en la página correcta
    if (!isset($_GET['page']) || $_GET['page'] !== 'hbs-bookings') {
        return;
    }

    // Verificar acción
    if (!isset($_GET['action']) || $_GET['action'] !== 'delete_booking') {
        return;
    }

    // Verificar capacidad
    if (!current_user_can('manage_options')) {
        wp_die(__('No tienes permisos para realizar esta acción.', 'hotel-booking-system'));
    }

    // Obtener booking_id
    $booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;
    if (!$booking_id) {
        wp_die(__('ID de reservación inválido.', 'hotel-booking-system'));
    }

    // Verificar nonce
    check_admin_referer('delete_booking_' . $booking_id);

    // Eliminar reservación
    $result = HBS_Booking::delete($booking_id);

    // Redirigir con mensaje
    $redirect_url = admin_url('admin.php?page=hbs-bookings');
    if ($result) {
        $redirect_url = add_query_arg('message', 'deleted', $redirect_url);
    } else {
        $redirect_url = add_query_arg('message', 'delete_error', $redirect_url);
    }

    wp_safe_redirect($redirect_url);
    exit;
}
```

**Hook necesario:**
```php
// En el constructor de la clase, agregar:
add_action('admin_init', array($this, 'handle_delete_booking'));
```

### 3. `includes/models/class-hbs-booking.php`
**Cambios necesarios:**
- Agregar método estático `delete($id)` para eliminar un registro de la base de datos
- Retornar `true` si se eliminó correctamente, `false` si hubo error

**Ejemplo de código:**
```php
/**
 * Eliminar una reservación
 * 
 * @param int $id ID de la reservación
 * @return bool True si se eliminó, false si hubo error
 */
public static function delete($id) {
    global $wpdb;
    $table = $wpdb->prefix . HBS_Config::TABLE_BOOKINGS;
    
    $result = $wpdb->delete(
        $table,
        array('id' => $id),
        array('%d')
    );
    
    return $result !== false;
}
```

### 4. `admin/views/recent-bookings-page.php` (mensajes)
**Cambios necesarios:**
- Agregar manejo de mensajes de éxito/error después de borrar

**Ejemplo de código:**
```php
// Al inicio de la página, después del título:
<?php
if (isset($_GET['message'])) {
    if ($_GET['message'] === 'deleted') {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>' . esc_html__('Reservación eliminada correctamente.', 'hotel-booking-system') . '</p>';
        echo '</div>';
    } elseif ($_GET['message'] === 'delete_error') {
        echo '<div class="notice notice-error is-dismissible">';
        echo '<p>' . esc_html__('Error al eliminar la reservación.', 'hotel-booking-system') . '</p>';
        echo '</div>';
    }
}
?>
```

## Consideraciones de Seguridad

1. ✅ **Nonce verification:** Usar `wp_nonce_url()` y `check_admin_referer()`
2. ✅ **Capability check:** Verificar que el usuario tiene `manage_options`
3. ✅ **Sanitización:** Usar `intval()` para el booking_id
4. ✅ **Confirmación:** JavaScript `confirm()` antes de eliminar
5. ✅ **Escape:** Usar `esc_url()`, `esc_html__()`, `esc_js()` en outputs

## Pasos de Implementación

### Paso 1: Crear método delete en el modelo
1. Abrir `includes/models/class-hbs-booking.php`
2. Agregar método `delete($id)`
3. Probar que funciona eliminando un registro de prueba

### Paso 2: Agregar manejador en admin
1. Abrir `admin/class-hbs-admin-menu.php`
2. Agregar método `handle_delete_booking()`
3. Agregar hook `admin_init`

### Paso 3: Actualizar UI de la tabla
1. Abrir `admin/views/recent-bookings-page.php`
2. Agregar columna "Acciones" en el `<thead>`
3. Agregar botón "Eliminar" en cada fila del `<tbody>`
4. Agregar manejo de mensajes de éxito/error

### Paso 4: Testing
1. Crear reservación de prueba
2. Intentar eliminarla sin permisos (debe fallar)
3. Eliminarla con usuario admin (debe funcionar)
4. Verificar que muestra mensaje de éxito
5. Verificar que realmente se eliminó de la BD

## Mejoras Opcionales

### 1. Soft Delete (Borrado suave)
En lugar de eliminar permanentemente, agregar columna `deleted_at`:
```sql
ALTER TABLE wp_hbs_bookings ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL;
```

Modificar el método delete:
```php
public static function delete($id) {
    global $wpdb;
    $table = $wpdb->prefix . HBS_Config::TABLE_BOOKINGS;
    
    return $wpdb->update(
        $table,
        array('deleted_at' => current_time('mysql')),
        array('id' => $id),
        array('%s'),
        array('%d')
    );
}
```

Y actualizar las queries para excluir eliminados:
```php
WHERE deleted_at IS NULL
```

### 2. Bulk Delete (Eliminar múltiples)
Agregar checkboxes y acción bulk en la tabla:
```php
<select name="action">
    <option value="-1">Acciones en lote</option>
    <option value="delete">Eliminar</option>
</select>
<input type="submit" class="button" value="Aplicar">
```

### 3. Papelera/Restaurar
Similar a WordPress posts, tener estados:
- `publish` (activo)
- `trash` (papelera)
- Opción de "Restaurar" o "Eliminar permanentemente"

## Riesgos y Mitigaciones

| Riesgo | Mitigación |
|--------|-----------|
| Eliminar por error | Agregar confirmación JavaScript |
| Pérdida de datos | Implementar soft delete |
| Acceso no autorizado | Verificar capacidades y nonce |
| Eliminar reservación confirmada | Agregar check de status antes de eliminar |

## Estimación de Tiempo
- **Implementación básica:** 30-45 minutos
- **Con soft delete:** +15 minutos
- **Con bulk actions:** +30 minutos
- **Testing completo:** 15 minutos

## Notas Adicionales
- Considerar registrar en log de actividad cuando se elimina una reservación
- Enviar email de notificación al administrador cuando se elimina
- Exportar backup antes de eliminar (opcional)
