<?php
/**
 * Room Types Management Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get all room types
$room_types = HBS_Room_Types::get_all();

// Check for add/edit action
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
$edit_slug = isset($_GET['slug']) ? sanitize_key($_GET['slug']) : '';
$editing = false;
$room_data = null;

if ($action === 'edit' && $edit_slug) {
    $room_data = HBS_Room_Types::get($edit_slug);
    $editing = true;
}
?>

<div class="hbs-wrap">
    <?php if ($action === 'list'): ?>
        <!-- List View -->
        <div class="hbs-header"
            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="margin: 0;">Tipos de Habitación</h1>
            <a href="<?php echo esc_url(add_query_arg('action', 'add')); ?>" class="hbs-button-primary"
                style="text-decoration: none; padding: 10px 20px; background: #3b82f6; color: white; border-radius: 6px;">+
                Agregar Nuevo</a>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="notice notice-success is-dismissible" style="padding: 12px; margin-bottom: 20px;">
                <p>
                    <?php esc_html_e('Tipo de habitación guardado exitosamente.', 'hotel-booking-system'); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="notice notice-success is-dismissible" style="padding: 12px; margin-bottom: 20px;">
                <p>
                    <?php esc_html_e('Tipo de habitación eliminado.', 'hotel-booking-system'); ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="notice notice-error is-dismissible" style="padding: 12px; margin-bottom: 20px;">
                <p>
                    <?php esc_html_e('No se puede eliminar el último tipo de habitación.', 'hotel-booking-system'); ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="hbs-card">
            <table class="wp-list-table widefat fixed striped" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="padding: 10px;">
                            <?php esc_html_e('Nombre', 'hotel-booking-system'); ?>
                        </th>
                        <th style="padding: 10px;">
                            <?php esc_html_e('Slug', 'hotel-booking-system'); ?>
                        </th>
                        <th style="padding: 10px;">
                            <?php esc_html_e('Huéspedes Base', 'hotel-booking-system'); ?>
                        </th>
                        <th style="padding: 10px;">
                            <?php esc_html_e('Capacidad Máx', 'hotel-booking-system'); ?>
                        </th>
                        <th style="padding: 10px;">
                            <?php esc_html_e('Precio Base', 'hotel-booking-system'); ?>
                        </th>
                        <th style="padding: 10px;">
                            <?php esc_html_e('Página de Detalles', 'hotel-booking-system'); ?>
                        </th>
                        <th style="padding: 10px;">
                            <?php esc_html_e('Acciones', 'hotel-booking-system'); ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($room_types)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <?php esc_html_e('No hay tipos de habitación. Agrega uno nuevo.', 'hotel-booking-system'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($room_types as $room): ?>
                            <tr>
                                <td style="padding: 10px;"><strong>
                                        <?php echo esc_html($room['name']); ?>
                                    </strong></td>
                                <td style="padding: 10px;"><code><?php echo esc_html($room['slug']); ?></code></td>
                                <td style="padding: 10px;">
                                    <?php echo esc_html($room['base_guests']); ?>
                                </td>
                                <td style="padding: 10px;">
                                    <?php echo esc_html($room['max_capacity']); ?>
                                </td>
                                <td style="padding: 10px;">$
                                    <?php echo esc_html(number_format($room['base_price'], 2)); ?> MXN
                                </td>
                                <td style="padding: 10px;">
                                    <?php if (!empty($room['detail_page_url'])): ?>
                                        <a href="<?php echo esc_url($room['detail_page_url']); ?>" target="_blank">Ver página</a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px;">
                                    <a href="<?php echo esc_url(add_query_arg(['action' => 'edit', 'slug' => $room['slug']])); ?>"
                                        class="button button-small">Editar</a>
                                    <?php if (count($room_types) > 1): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=hbs_delete_room_type&slug=' . $room['slug']), 'hbs_delete_room_type')); ?>"
                                            class="button button-small"
                                            onclick="return confirm('¿Está seguro de eliminar este tipo de habitación?');">Eliminar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: ?>
        <!-- Add/Edit Form -->
        <div class="hbs-header" style="margin-bottom: 30px;">
            <h1>
                <?php echo $editing ? esc_html__('Editar Tipo de Habitación', 'hotel-booking-system') : esc_html__('Agregar Tipo de Habitación', 'hotel-booking-system'); ?>
            </h1>
        </div>

        <div class="hbs-card">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width: 600px;">
                <input type="hidden" name="action" value="hbs_save_room_type">
                <?php wp_nonce_field('hbs_save_room_type', 'hbs_nonce'); ?>
                <?php if ($editing): ?>
                    <input type="hidden" name="original_slug" value="<?php echo esc_attr($edit_slug); ?>">
                <?php endif; ?>

                <div class="hbs-field">
                    <label class="hbs-label" for="room_name">
                        <?php esc_html_e('Nombre', 'hotel-booking-system'); ?> *
                    </label>
                    <input type="text" name="name" id="room_name"
                        value="<?php echo $editing ? esc_attr($room_data['name']) : ''; ?>" class="hbs-input" required>
                    <p class="hbs-description">
                        <?php esc_html_e('Nombre para mostrar (ej: "Habitación Deluxe", "Suite Junior")', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label class="hbs-label" for="room_slug">
                        <?php esc_html_e('Slug', 'hotel-booking-system'); ?> *
                    </label>
                    <input type="text" name="slug" id="room_slug"
                        value="<?php echo $editing ? esc_attr($room_data['slug']) : ''; ?>" class="hbs-input"
                        pattern="[a-z0-9_-]+" required <?php echo $editing ? 'readonly' : ''; ?>>
                    <p class="hbs-description">
                        <?php esc_html_e('Identificador único (solo minúsculas, números, guiones). No se puede editar después de crear.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label class="hbs-label" for="base_guests">
                        <?php esc_html_e('Huéspedes Base', 'hotel-booking-system'); ?> *
                    </label>
                    <input type="number" name="base_guests" id="base_guests"
                        value="<?php echo $editing ? esc_attr($room_data['base_guests']) : '2'; ?>" class="hbs-input"
                        min="1" max="10" required>
                    <p class="hbs-description">
                        <?php esc_html_e('Número de huéspedes incluidos en el precio base.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label class="hbs-label" for="max_capacity">
                        <?php esc_html_e('Capacidad Máxima', 'hotel-booking-system'); ?> *
                    </label>
                    <input type="number" name="max_capacity" id="max_capacity"
                        value="<?php echo $editing ? esc_attr($room_data['max_capacity']) : '4'; ?>" class="hbs-input"
                        min="1" max="20" required>
                    <p class="hbs-description">
                        <?php esc_html_e('Número máximo de huéspedes permitidos.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label class="hbs-label" for="base_price">
                        <?php esc_html_e('Precio Base por Noche (MXN)', 'hotel-booking-system'); ?> *
                    </label>
                    <input type="number" name="base_price" id="base_price"
                        value="<?php echo $editing ? esc_attr($room_data['base_price']) : '1850'; ?>" class="hbs-input"
                        min="0" step="0.01" required>
                    <p class="hbs-description">
                        <?php esc_html_e('Precio por noche para los huéspedes base.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label class="hbs-label" for="detail_page_id">
                        <?php esc_html_e('Página de Detalles (Opcional)', 'hotel-booking-system'); ?>
                    </label>
                    <?php
                    // Get page ID from existing URL if editing
                    $detail_page_id = 0;
                    if ($editing && !empty($room_data['detail_page_url'])) {
                        $detail_page_id = url_to_postid($room_data['detail_page_url']);
                    }

                    wp_dropdown_pages(array(
                        'name' => 'detail_page_id',
                        'id' => 'detail_page_id',
                        'class' => 'hbs-input',
                        'show_option_none' => __('Seleccionar página', 'hotel-booking-system'),
                        'option_none_value' => '0',
                        'selected' => $detail_page_id
                    ));
                    ?>
                    <p class="hbs-description">
                        <?php esc_html_e('Página donde los usuarios pueden ver detalles, amenidades e imágenes de esta habitación.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-actions" style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="hbs-button-primary">
                        <?php echo $editing ? esc_html__('Actualizar Tipo de Habitación', 'hotel-booking-system') : esc_html__('Crear Tipo de Habitación', 'hotel-booking-system'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hbs_room_types')); ?>" class="button">
                        <?php esc_html_e('Cancelar', 'hotel-booking-system'); ?>
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>