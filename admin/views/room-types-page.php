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
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="hbs_save_room_type">
                <?php wp_nonce_field('hbs_save_room_type', 'hbs_nonce'); ?>
                <?php if ($editing): ?>
                    <input type="hidden" name="original_slug" value="<?php echo esc_attr($edit_slug); ?>">
                <?php endif; ?>

                <!-- SECTION 1: Basic Information -->
                <div style="margin-bottom: 40px;">
                    <h2 style="border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 20px;">
                        <?php esc_html_e('Información Básica', 'hotel-booking-system'); ?>
                    </h2>

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
                        <label class="hbs-label" for="beds">
                            <?php esc_html_e('Número de Camas', 'hotel-booking-system'); ?> *
                        </label>
                        <input type="number" name="beds" id="beds"
                            value="<?php echo $editing && isset($room_data['beds']) ? esc_attr($room_data['beds']) : '2'; ?>"
                            class="hbs-input" min="1" max="10" required>
                        <p class="hbs-description">
                            <?php esc_html_e('Número de camas disponibles en la habitación.', 'hotel-booking-system'); ?>
                        </p>
                    </div>
                </div>

                <!-- SECTION 2: Pricing -->
                <div style="margin-bottom: 40px;">
                    <h2 style="border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 20px;">
                        <?php esc_html_e('Precio', 'hotel-booking-system'); ?>
                    </h2>

                    <div class="hbs-field">
                        <label class="hbs-label" for="base_price">
                            <?php esc_html_e('Precio Base por Noche (MXN)', 'hotel-booking-system'); ?> *
                        </label>
                        <input type="number" name="base_price" id="base_price"
                            value="<?php echo $editing ? esc_attr($room_data['base_price']) : '1850'; ?>" class="hbs-input"
                            min="0" step="0.01" required>
                        <p class="hbs-description">
                            <?php esc_html_e('Precio por noche para la ocupación base.', 'hotel-booking-system'); ?>
                        </p>
                    </div>
                </div>

                <!-- SECTION 3: Occupancy Rules -->
                <div style="margin-bottom: 40px;">
                    <h2 style="border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 20px;">
                        <?php esc_html_e('Reglas de Ocupación', 'hotel-booking-system'); ?>
                    </h2>
                    <p
                        style="background: #eff6ff; padding: 15px; border-left: 4px solid #3b82f6; margin-bottom: 20px; color: #1e40af;">
                        <strong><?php esc_html_e('Información:', 'hotel-booking-system'); ?></strong>
                        <?php esc_html_e('Estas reglas controlan cuántas personas pueden reservar esta habitación y cómo se calculan los precios por persona adicional.', 'hotel-booking-system'); ?>
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="hbs-field">
                            <label class="hbs-label" for="base_occupancy">
                                <?php esc_html_e('Ocupación Base', 'hotel-booking-system'); ?> *
                            </label>
                            <input type="number" name="base_occupancy" id="base_occupancy"
                                value="<?php echo $editing && isset($room_data['base_occupancy']) ? esc_attr($room_data['base_occupancy']) : '2'; ?>"
                                class="hbs-input" min="1" max="10" required>
                            <p class="hbs-description">
                                <?php esc_html_e('Número de personas incluidas en el precio base.', 'hotel-booking-system'); ?>
                            </p>
                        </div>

                        <div class="hbs-field">
                            <label class="hbs-label" for="max_total">
                                <?php esc_html_e('Capacidad Máxima Total', 'hotel-booking-system'); ?> *
                            </label>
                            <input type="number" name="max_total" id="max_total"
                                value="<?php echo $editing && isset($room_data['max_total']) ? esc_attr($room_data['max_total']) : '4'; ?>"
                                class="hbs-input" min="1" max="20" required>
                            <p class="hbs-description">
                                <?php esc_html_e('Número máximo de personas permitidas (LÍMITE DURO).', 'hotel-booking-system'); ?>
                            </p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="hbs-field">
                            <label class="hbs-label" for="max_adults">
                                <?php esc_html_e('Máximo de Adultos', 'hotel-booking-system'); ?> *
                            </label>
                            <input type="number" name="max_adults" id="max_adults"
                                value="<?php echo $editing && isset($room_data['max_adults']) ? esc_attr($room_data['max_adults']) : '3'; ?>"
                                class="hbs-input" min="1" max="20" required>
                            <p class="hbs-description">
                                <?php esc_html_e('Máximo de adultos permitidos (LÍMITE DURO).', 'hotel-booking-system'); ?>
                            </p>
                        </div>

                        <div class="hbs-field">
                            <label class="hbs-label" for="max_kids">
                                <?php esc_html_e('Máximo de Niños', 'hotel-booking-system'); ?> *
                            </label>
                            <input type="number" name="max_kids" id="max_kids"
                                value="<?php echo $editing && isset($room_data['max_kids']) ? esc_attr($room_data['max_kids']) : '3'; ?>"
                                class="hbs-input" min="0" max="10" required>
                            <p class="hbs-description">
                                <?php esc_html_e('Máximo de niños (4-11 años) permitidos (LÍMITE DURO).', 'hotel-booking-system'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="hbs-field">
                        <label class="hbs-label" for="overflow_rule">
                            <?php esc_html_e('Regla de Excedente', 'hotel-booking-system'); ?> *
                        </label>
                        <select name="overflow_rule" id="overflow_rule" class="hbs-input" required>
                            <?php
                            $current_overflow = $editing && isset($room_data['overflow_rule']) ? $room_data['overflow_rule'] : 'kids_only';
                            ?>
                            <option value="kids_only" <?php selected($current_overflow, 'kids_only'); ?>>
                                <?php esc_html_e('Solo Niños pueden exceder ocupación base', 'hotel-booking-system'); ?>
                            </option>
                            <option value="any" <?php selected($current_overflow, 'any'); ?>>
                                <?php esc_html_e('Adultos O Niños pueden exceder ocupación base', 'hotel-booking-system'); ?>
                            </option>
                        </select>
                        <p class="hbs-description">
                            <strong><?php esc_html_e('"Solo Niños":', 'hotel-booking-system'); ?></strong>
                            <?php esc_html_e('Si la ocupación base es 2, solo se permiten 2 adultos máximo. Los huéspedes adicionales deben ser niños.', 'hotel-booking-system'); ?>
                            <br>
                            <strong><?php esc_html_e('"Adultos O Niños":', 'hotel-booking-system'); ?></strong>
                            <?php esc_html_e('Permite exceder la ocupación base con adultos o niños (respetando los límites máximos).', 'hotel-booking-system'); ?>
                        </p>
                    </div>

                    <!-- Legacy fields for backwards compatibility (hidden) -->
                    <input type="hidden" name="base_guests"
                        value="<?php echo $editing && isset($room_data['base_occupancy']) ? esc_attr($room_data['base_occupancy']) : '2'; ?>">
                    <input type="hidden" name="max_capacity"
                        value="<?php echo $editing && isset($room_data['max_total']) ? esc_attr($room_data['max_total']) : '4'; ?>">
                </div>

                <!-- SECTION 4: Page Link (Optional) -->
                <div style="margin-bottom: 40px;">
                    <h2 style="border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; margin-bottom: 20px;">
                        <?php esc_html_e('Página de Detalles (Opcional)', 'hotel-booking-system'); ?>
                    </h2>

                    <div class="hbs-field">
                        <label class="hbs-label" for="detail_page_id">
                            <?php esc_html_e('Página de Detalles', 'hotel-booking-system'); ?>
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
                            'show_option_none' => __('— Seleccionar página —', 'hotel-booking-system'),
                            'option_none_value' => '0',
                            'selected' => $detail_page_id
                        ));
                        ?>
                        <p class="hbs-description">
                            <?php esc_html_e('Página donde los usuarios pueden ver detalles, amenidades e imágenes de esta habitación.', 'hotel-booking-system'); ?>
                        </p>
                    </div>
                </div>

                <!-- SECTION 5: Summary Box -->
                <div
                    style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                    <h3 style="margin-top: 0; color: #374151;">
                        <?php esc_html_e('Resumen de Configuración', 'hotel-booking-system'); ?>
                    </h3>
                    <ul style="color: #6b7280; line-height: 1.8;">
                        <li>
                            <?php esc_html_e('El precio base incluye', 'hotel-booking-system'); ?>
                            <strong id="summary_base_occupancy">2</strong>
                            <?php esc_html_e('personas', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Capacidad máxima total:', 'hotel-booking-system'); ?>
                            <strong id="summary_max_total">4</strong>
                            <?php esc_html_e('personas', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Máximo de adultos:', 'hotel-booking-system'); ?>
                            <strong id="summary_max_adults">3</strong>
                        </li>
                        <li>
                            <?php esc_html_e('Máximo de niños:', 'hotel-booking-system'); ?>
                            <strong id="summary_max_kids">3</strong>
                        </li>
                        <li id="summary_overflow_text">
                            <?php esc_html_e('Solo niños pueden exceder la ocupación base', 'hotel-booking-system'); ?>
                        </li>
                    </ul>
                </div>

                <!-- Actions -->
                <div class="hbs-actions" style="display: flex; gap: 10px;">
                    <button type="submit" class="hbs-button-primary">
                        <?php echo $editing ? esc_html__('Actualizar Tipo de Habitación', 'hotel-booking-system') : esc_html__('Crear Tipo de Habitación', 'hotel-booking-system'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=hbs_room_types')); ?>" class="button">
                        <?php esc_html_e('Cancelar', 'hotel-booking-system'); ?>
                    </a>
                </div>
            </form>

            <!-- Real-time Summary Update Script -->
            <script>
                (function () {
                    const baseOccupancy = document.getElementById('base_occupancy');
                    const maxTotal = document.getElementById('max_total');
                    const maxAdults = document.getElementById('max_adults');
                    const maxKids = document.getElementById('max_kids');
                    const overflowRule = document.getElementById('overflow_rule');

                    const summaryBaseOccupancy = document.getElementById('summary_base_occupancy');
                    const summaryMaxTotal = document.getElementById('summary_max_total');
                    const summaryMaxAdults = document.getElementById('summary_max_adults');
                    const summaryMaxKids = document.getElementById('summary_max_kids');
                    const summaryOverflowText = document.getElementById('summary_overflow_text');

                    function updateSummary() {
                        summaryBaseOccupancy.textContent = baseOccupancy.value;
                        summaryMaxTotal.textContent = maxTotal.value;
                        summaryMaxAdults.textContent = maxAdults.value;
                        summaryMaxKids.textContent = maxKids.value;

                        if (overflowRule.value === 'kids_only') {
                            summaryOverflowText.textContent = '<?php esc_html_e('Solo niños pueden exceder la ocupación base', 'hotel-booking-system'); ?>';
                        } else {
                            summaryOverflowText.textContent = '<?php esc_html_e('Adultos o niños pueden exceder la ocupación base', 'hotel-booking-system'); ?>';
                        }
                    }

                    baseOccupancy.addEventListener('input', updateSummary);
                    maxTotal.addEventListener('input', updateSummary);
                    maxAdults.addEventListener('input', updateSummary);
                    maxKids.addEventListener('input', updateSummary);
                    overflowRule.addEventListener('change', updateSummary);

                    // Auto-sync legacy fields
                    baseOccupancy.addEventListener('input', function () {
                        document.querySelector('input[name="base_guests"]').value = this.value;
                    });
                    maxTotal.addEventListener('input', function () {
                        document.querySelector('input[name="max_capacity"]').value = this.value;
                    });
                })();
            </script>
        </div>
    <?php endif; ?>
</div>