<?php
if (!defined('ABSPATH')) {
    exit;
}

$opts = get_option(HBS_Config::OPTION_KEY, []);

// Defaults for display if not set
$staff_emails = isset($opts['staff_emails']) ? $opts['staff_emails'] : get_option('admin_email');
$policies_url = isset($opts['policies_url']) ? $opts['policies_url'] : '';
$p_single = isset($opts['price_single']) ? $opts['price_single'] : '1850.00';
$p_double = isset($opts['price_double']) ? $opts['price_double'] : '2100.00';
$p_ex_adult = isset($opts['price_extra_adult']) ? $opts['price_extra_adult'] : '450.00';
$p_ex_kid = isset($opts['price_extra_kid']) ? $opts['price_extra_kid'] : '250.00';
$floating = !empty($opts['floating_enabled']);
$note = isset($opts['guest_email_note']) ? $opts['guest_email_note'] : '';
$book_url = isset($opts['booking_page_url']) ? $opts['booking_page_url'] : '';
?>

<div class="wrap">
    <h1><?php echo esc_html__('Hotel Booking — Ajustes', 'hotel-booking-system'); ?></h1>

    <?php if (isset($_GET['hbs_saved'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Ajustes guardados correctamente.', 'hotel-booking-system'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="hbs_save_settings">
        <?php wp_nonce_field(HBS_Config::NONCE_ACTION, HBS_Config::NONCE_KEY); ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label
                        for="staff_emails"><?php echo esc_html__('Emails Staff', 'hotel-booking-system'); ?></label>
                </th>
                <td>
                    <input name="staff_emails" type="text" id="staff_emails"
                        value="<?php echo esc_attr($staff_emails); ?>" class="regular-text">
                    <p class="description"><?php echo esc_html__('Separados por comas.', 'hotel-booking-system'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="policies_url"><?php echo esc_html__('URL Políticas', 'hotel-booking-system'); ?></label>
                </th>
                <td>
                    <input name="policies_url" type="url" id="policies_url"
                        value="<?php echo esc_attr($policies_url); ?>" class="regular-text">
                </td>
            </tr>

            <!-- APARIENCIA: Formulario Principal -->
            <tr>
                <th colspan="2">
                    <h3><?php echo esc_html__('Apariencia: Formulario Principal', 'hotel-booking-system'); ?></h3>
                </th>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Habilitar Estilos Personalizados', 'hotel-booking-system'); ?></th>
                <td>
                    <label>
                        <input name="enable_custom_styles" type="checkbox" value="1" <?php checked(isset($opts['enable_custom_styles']) ? $opts['enable_custom_styles'] : 0, 1); ?>>
                        <?php esc_html_e('Activar para sobrescribir los estilos predeterminados con los colores de abajo.', 'hotel-booking-system'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Color Primario', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="main_color_primary"
                        value="<?php echo esc_attr(!empty($opts['main_color_primary']) ? $opts['main_color_primary'] : '#0f172a'); ?>"
                        class="hbs-color-field" data-default-color="#0f172a"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Color Acento', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="main_color_accent"
                        value="<?php echo esc_attr(!empty($opts['main_color_accent']) ? $opts['main_color_accent'] : '#3b82f6'); ?>"
                        class="hbs-color-field" data-default-color="#3b82f6"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fondo Tarjeta', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="main_color_bg"
                        value="<?php echo esc_attr(!empty($opts['main_color_bg']) ? $opts['main_color_bg'] : '#ffffff'); ?>"
                        class="hbs-color-field" data-default-color="#ffffff"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Color Texto', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="main_color_text"
                        value="<?php echo esc_attr(!empty($opts['main_color_text']) ? $opts['main_color_text'] : '#334155'); ?>"
                        class="hbs-color-field" data-default-color="#334155"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Texto Botón Enviar', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="submit_btn_text"
                        value="<?php echo esc_attr(!empty($opts['submit_btn_text']) ? $opts['submit_btn_text'] : __('Reservar', 'hotel-booking-system')); ?>"
                        class="regular-text">
                    <p class="description">
                        <?php esc_html_e('Texto del botón de envío (ej: Reservar, Cotizar).', 'hotel-booking-system'); ?>
                    </p>
                </td>
            </tr>

            <!-- APARIENCIA: Formulario Flotante -->
            <tr>
                <th colspan="2">
                    <h3><?php echo esc_html__('Apariencia: Formulario Flotante', 'hotel-booking-system'); ?></h3>
                </th>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fondo Barra', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="float_color_bg"
                        value="<?php echo esc_attr(!empty($opts['float_color_bg']) ? $opts['float_color_bg'] : '#ffffff'); ?>"
                        class="hbs-color-field" data-default-color="#ffffff"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Color Texto', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="float_color_text"
                        value="<?php echo esc_attr(!empty($opts['float_color_text']) ? $opts['float_color_text'] : '#0f172a'); ?>"
                        class="hbs-color-field" data-default-color="#0f172a"></td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Fondo Botón', 'hotel-booking-system'); ?></th>
                <td><input type="text" name="float_color_btn"
                        value="<?php echo esc_attr(!empty($opts['float_color_btn']) ? $opts['float_color_btn'] : '#0f172a'); ?>"
                        class="hbs-color-field" data-default-color="#0f172a"></td>
            </tr>

            <tr>
                <th colspan="2">
                    <h3><?php echo esc_html__('Precios Base (MXN)', 'hotel-booking-system'); ?></h3>
                </th>
            </tr>

            <tr>
                <th scope="row"><label
                        for="price_single"><?php echo esc_html__('Sencilla (Base 2)', 'hotel-booking-system'); ?></label>
                </th>
                <td><input name="price_single" type="number" step="0.01" id="price_single"
                        value="<?php echo esc_attr($p_single); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="price_double"><?php echo esc_html__('Doble (Base 2)', 'hotel-booking-system'); ?></label>
                </th>
                <td><input name="price_double" type="number" step="0.01" id="price_double"
                        value="<?php echo esc_attr($p_double); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="price_extra_adult"><?php echo esc_html__('Adulto Extra', 'hotel-booking-system'); ?></label>
                </th>
                <td><input name="price_extra_adult" type="number" step="0.01" id="price_extra_adult"
                        value="<?php echo esc_attr($p_ex_adult); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="price_extra_kid"><?php echo esc_html__('Niño Extra (>4)', 'hotel-booking-system'); ?></label>
                </th>
                <td><input name="price_extra_kid" type="number" step="0.01" id="price_extra_kid"
                        value="<?php echo esc_attr($p_ex_kid); ?>" class="regular-text"></td>
            </tr>

            <tr>
                <th colspan="2">
                    <h3><?php echo esc_html__('Configuración de Correos', 'hotel-booking-system'); ?></h3>
                </th>
            </tr>
            <tr>
                <td colspan="2">
                    <p class="description">
                        <?php esc_html_e('Variables disponibles:', 'hotel-booking-system'); ?>
                        <code>{booking_id}</code>, <code>{guest_name}</code>, <code>{guest_email}</code>,
                        <code>{guest_phone}</code>, <code>{check_in_date}</code>, <code>{check_out_date}</code>,
                        <code>{room_type}</code>, <code>{adults_count}</code>, <code>{kids_count}</code>,
                        <code>{total_price}</code>
                    </p>
                </td>
            </tr>

            <!-- Staff Email -->
            <tr>
                <th scope="row"><?php esc_html_e('Email Staff: Asunto', 'hotel-booking-system'); ?></th>
                <td>
                    <input name="email_staff_subject" type="text"
                        value="<?php echo esc_attr(!empty($opts['email_staff_subject']) ? $opts['email_staff_subject'] : '[Nueva Reservación] Solicitud #{booking_id}'); ?>"
                        class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Email Staff: Contenido', 'hotel-booking-system'); ?></th>
                <td>
                    <?php
                    $content_staff = !empty($opts['email_staff_content']) ? $opts['email_staff_content'] : '';
                    wp_editor($content_staff, 'email_staff_content', ['textarea_rows' => 10, 'media_buttons' => false]);
                    ?>
                    <p class="description">
                        <?php esc_html_e('Deje vacío para usar la plantilla predeterminada.', 'hotel-booking-system'); ?>
                    </p>
                </td>
            </tr>

            <!-- Guest Email -->
            <tr>
                <th scope="row"><?php esc_html_e('Email Huésped: Asunto', 'hotel-booking-system'); ?></th>
                <td>
                    <input name="email_guest_subject" type="text"
                        value="<?php echo esc_attr(!empty($opts['email_guest_subject']) ? $opts['email_guest_subject'] : 'Confirmación de Solicitud #{booking_id}'); ?>"
                        class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Email Huésped: Contenido', 'hotel-booking-system'); ?></th>
                <td>
                    <?php
                    $content_guest = !empty($opts['email_guest_content']) ? $opts['email_guest_content'] : '';
                    wp_editor($content_guest, 'email_guest_content', ['textarea_rows' => 10, 'media_buttons' => false]);
                    ?>
                    <p class="description">
                        <?php esc_html_e('Deje vacío para usar la plantilla predeterminada.', 'hotel-booking-system'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th colspan="2">
                    <h3><?php echo esc_html__('Otros', 'hotel-booking-system'); ?></h3>
                </th>
            </tr>

            <tr>
                <th scope="row"><?php echo esc_html__('Formulario Flotante', 'hotel-booking-system'); ?></th>
                <td>
                    <label for="floating_enabled">
                        <input name="floating_enabled" type="checkbox" id="floating_enabled" value="1" <?php checked($floating, true); ?>>
                        <?php echo esc_html__('Activar en footer (Desktop)', 'hotel-booking-system'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="floating_exceptions"><?php echo esc_html__('Excluir en IDs (Excepciones)', 'hotel-booking-system'); ?></label>
                </th>
                <td>
                    <?php
                    // Get pages for dropdown
                    $pages = get_pages();
                    $selected_ids = !empty($opts['floating_exceptions']) ? explode(',', $opts['floating_exceptions']) : [];
                    ?>
                    <select name="floating_exceptions[]" id="floating_exceptions" multiple="multiple"
                        style="width: 100%; max-width: 400px;">
                        <?php foreach ($pages as $page): ?>
                            <option value="<?php echo esc_attr($page->ID); ?>" <?php echo in_array($page->ID, $selected_ids) ? 'selected' : ''; ?>>
                                <?php echo esc_html($page->post_title); ?> (ID: <?php echo esc_html($page->ID); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php echo esc_html__('Seleccione las páginas donde NO desea mostrar el formulario flotante.', 'hotel-booking-system'); ?>
                    </p>

                    <script>
                        jQuery(document).ready(function ($) {
                            if ($.fn.select2) {
                                $('#floating_exceptions').select2({
                                    placeholder: "<?php echo esc_js(__('Seleccione páginas...', 'hotel-booking-system')); ?>",
                                    allowClear: true
                                });
                            }
                        });
                    </script>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="booking_page_url"><?php echo esc_html__('URL Página Reservación', 'hotel-booking-system'); ?></label>
                </th>
                <td>
                    <input name="booking_page_url" type="url" id="booking_page_url"
                        value="<?php echo esc_attr($book_url); ?>" class="regular-text">
                    <p class="description">
                        <?php echo esc_html__('Requerido para que funcione el formulario flotante (redirección).', 'hotel-booking-system'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="guest_email_note"><?php echo esc_html__('Nota Email Huésped', 'hotel-booking-system'); ?></label>
                </th>
                <td>
                    <textarea name="guest_email_note" id="guest_email_note" rows="3"
                        class="large-text code"><?php echo esc_textarea($note); ?></textarea>
                    <p class="description">
                        <?php echo esc_html__('Mensaje opcional al final del email de confirmación.', 'hotel-booking-system'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>

    <script>
        jQuery(document).ready(function ($) {
            $('.hbs-color-field').wpColorPicker();
        });
    </script>
</div>