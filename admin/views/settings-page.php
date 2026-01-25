<?php
if (!defined('ABSPATH')) {
    exit;
}

$opts = get_option(HBS_Config::OPTION_KEY, []);

// Defaults
$staff_emails = isset($opts['staff_emails']) ? $opts['staff_emails'] : get_option('admin_email');
$p_single = isset($opts['price_single']) ? $opts['price_single'] : '1850.00';
$p_double = isset($opts['price_double']) ? $opts['price_double'] : '2100.00';
$p_ex_adult = isset($opts['price_extra_adult']) ? $opts['price_extra_adult'] : '450.00';
$p_ex_kid = isset($opts['price_extra_kid']) ? $opts['price_extra_kid'] : '250.00';
$floating = !empty($opts['floating_enabled']);
$note = isset($opts['guest_email_note']) ? $opts['guest_email_note'] : '';

// Page IDs for dropdowns (retrieve from DB or fallback to converting URLs)
// Policies Page
$policies_page_id = isset($opts['policies_page_id']) ? (int) $opts['policies_page_id'] : 0;
if ($policies_page_id === 0 && !empty($opts['policies_url'])) {
    // Migration: convert existing URL to page ID
    $policies_page_id = url_to_postid($opts['policies_url']);
}

// Booking Page
$book_page_id = isset($opts['book_page_id']) ? (int) $opts['book_page_id'] : 0;
if ($book_page_id === 0 && !empty($opts['booking_page_url'])) {
    // Migration: convert existing URL to page ID
    $book_page_id = url_to_postid($opts['booking_page_url']);
}

// Thank You Page
$thankyou_page_id = isset($opts['thankyou_page_id']) ? (int) $opts['thankyou_page_id'] : 0;
if ($thankyou_page_id === 0 && !empty($opts['thankyou_page_url'])) {
    // Migration: convert existing URL to page ID
    $thankyou_page_id = url_to_postid($opts['thankyou_page_url']);
}
?>

<div class="hbs-wrap">
    <div class="hbs-header">
        <h1 class="hbs-title"><?php echo esc_html__('Ajustes Hotel', 'hotel-booking-system'); ?></h1>
    </div>

    <?php if (isset($_GET['hbs_saved'])): ?>
        <div class="hbs-notice hbs-notice-success">
            <?php echo esc_html__('Ajustes guardados correctamente.', 'hotel-booking-system'); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="hbs_save_settings">
        <?php wp_nonce_field(HBS_Config::NONCE_ACTION, HBS_Config::NONCE_KEY); ?>

        <!-- Configuración General -->
        <div class="hbs-card">
            <div class="hbs-card-header">
                <h2 class="hbs-card-title"><?php esc_html_e('Configuración General', 'hotel-booking-system'); ?></h2>
            </div>
            <div class="hbs-card-body hbs-grid">
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="staff_emails"><?php esc_html_e('Emails Staff', 'hotel-booking-system'); ?></label>
                    <input name="staff_emails" type="text" id="staff_emails"
                        value="<?php echo esc_attr($staff_emails); ?>" class="hbs-input">
                    <p class="hbs-description">
                        <?php esc_html_e('Direcciones que recibirán copia de las reservas (separar por comas).', 'hotel-booking-system'); ?>
                    </p>
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="policies_page_id"><?php esc_html_e('Página de Políticas', 'hotel-booking-system'); ?></label>
                    <?php
                    wp_dropdown_pages(array(
                        'name' => 'policies_page_id',
                        'id' => 'policies_page_id',
                        'class' => 'hbs-input',
                        'show_option_none' => __('Seleccionar página', 'hotel-booking-system'),
                        'option_none_value' => '0',
                        'selected' => $policies_page_id
                    ));
                    ?>
                    <p class="hbs-description">
                        <?php esc_html_e('Enlace a términos y condiciones.', 'hotel-booking-system'); ?>
                    </p>
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="hotel_logo"><?php esc_html_e('Logo del Hotel', 'hotel-booking-system'); ?></label>
                    <?php
                    $logo_id = isset($opts['hotel_logo_id']) ? (int) $opts['hotel_logo_id'] : 0;
                    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'medium') : '';
                    ?>
                    <div class="hbs-logo-upload">
                        <input type="hidden" name="hotel_logo_id" id="hotel_logo_id"
                            value="<?php echo esc_attr($logo_id); ?>">
                        <div class="hbs-logo-preview" id="hbs-logo-preview"
                            style="<?php echo $logo_url ? '' : 'display:none;'; ?>">
                            <?php if ($logo_url): ?>
                                <img src="<?php echo esc_url($logo_url); ?>" alt="Logo"
                                    style="max-width: 200px; height: auto;">
                            <?php endif; ?>
                        </div>
                        <button type="button" class="hbs-button hbs-button-secondary" id="hbs-upload-logo">
                            <?php echo $logo_url ? esc_html__('Cambiar Logo', 'hotel-booking-system') : esc_html__('Subir Logo', 'hotel-booking-system'); ?>
                        </button>
                        <?php if ($logo_url): ?>
                            <button type="button" class="hbs-button hbs-button-danger" id="hbs-remove-logo">
                                <?php esc_html_e('Eliminar Logo', 'hotel-booking-system'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                    <p class="hbs-description">
                        <?php esc_html_e('Logo que aparecerá en las plantillas de correo electrónico.', 'hotel-booking-system'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Precios -->
        <div class="hbs-card">
            <div class="hbs-card-header">
                <h2 class="hbs-card-title"><?php esc_html_e('Precios Base (MXN)', 'hotel-booking-system'); ?></h2>
            </div>
            <div class="hbs-card-body hbs-grid">
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="price_single"><?php esc_html_e('Sencilla (Base 2)', 'hotel-booking-system'); ?></label>
                    <input name="price_single" type="number" step="0.01" id="price_single"
                        value="<?php echo esc_attr($p_single); ?>" class="hbs-input">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="price_double"><?php esc_html_e('Doble (Base 2)', 'hotel-booking-system'); ?></label>
                    <input name="price_double" type="number" step="0.01" id="price_double"
                        value="<?php echo esc_attr($p_double); ?>" class="hbs-input">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="price_extra_adult"><?php esc_html_e('Adulto Extra', 'hotel-booking-system'); ?></label>
                    <input name="price_extra_adult" type="number" step="0.01" id="price_extra_adult"
                        value="<?php echo esc_attr($p_ex_adult); ?>" class="hbs-input">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="price_extra_kid"><?php esc_html_e('Niño Extra (>4)', 'hotel-booking-system'); ?></label>
                    <input name="price_extra_kid" type="number" step="0.01" id="price_extra_kid"
                        value="<?php echo esc_attr($p_ex_kid); ?>" class="hbs-input">
                </div>
            </div>
        </div>


        <!-- Apariencia -->
        <div class="hbs-card">
            <div class="hbs-card-header">
                <h2 class="hbs-card-title"><?php esc_html_e('Apariencia y Colores', 'hotel-booking-system'); ?></h2>
            </div>
            <div class="hbs-card-body hbs-grid">
                <div class="hbs-field hbs-full-width">
                    <label class="hbs-toggle">
                        <input name="enable_custom_styles" type="checkbox" value="1" <?php checked(isset($opts['enable_custom_styles']) ? $opts['enable_custom_styles'] : 0, 1); ?>>
                        <span
                            class="hbs-label"><?php esc_html_e('Habilitar Estilos Personalizados', 'hotel-booking-system'); ?></span>
                    </label>
                    <p class="hbs-description" style="margin-left: 30px;">
                        <?php esc_html_e('Activar para usar los colores definidos a continuación.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Color Primario', 'hotel-booking-system'); ?></label>
                    <input type="text" name="main_color_primary"
                        value="<?php echo esc_attr(!empty($opts['main_color_primary']) ? $opts['main_color_primary'] : '#0f172a'); ?>"
                        class="hbs-color-field" data-default-color="#0f172a">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Color Acento', 'hotel-booking-system'); ?></label>
                    <input type="text" name="main_color_accent"
                        value="<?php echo esc_attr(!empty($opts['main_color_accent']) ? $opts['main_color_accent'] : '#3b82f6'); ?>"
                        class="hbs-color-field" data-default-color="#3b82f6">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Fondo Tarjeta', 'hotel-booking-system'); ?></label>
                    <input type="text" name="main_color_bg"
                        value="<?php echo esc_attr(!empty($opts['main_color_bg']) ? $opts['main_color_bg'] : '#ffffff'); ?>"
                        class="hbs-color-field" data-default-color="#ffffff">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Color Texto', 'hotel-booking-system'); ?></label>
                    <input type="text" name="main_color_text"
                        value="<?php echo esc_attr(!empty($opts['main_color_text']) ? $opts['main_color_text'] : '#334155'); ?>"
                        class="hbs-color-field" data-default-color="#334155">
                </div>
                <div class="hbs-field hbs-full-width">
                    <label class="hbs-label"><?php esc_html_e('Texto Botón Enviar', 'hotel-booking-system'); ?></label>
                    <input type="text" name="submit_btn_text"
                        value="<?php echo esc_attr(!empty($opts['submit_btn_text']) ? $opts['submit_btn_text'] : __('Reservar', 'hotel-booking-system')); ?>"
                        class="hbs-input">
                </div>
            </div>
        </div>

        <!-- Formulario Flotante -->
        <div class="hbs-card">

            <div class="hbs-card-header">
                <h2 class="hbs-card-title">
                    <?php esc_html_e('Formulario Flotante (Barra Inferior)', 'hotel-booking-system'); ?>
                </h2>
            </div>
            <div class="hbs-card-body hbs-grid">
                <div class="hbs-field hbs-full-width">
                    <label class="hbs-toggle">
                        <input name="floating_enabled" type="checkbox" id="floating_enabled" value="1" <?php checked($floating, true); ?>>
                        <span
                            class="hbs-label"><?php esc_html_e('Activar en footer (Desktop)', 'hotel-booking-system'); ?></span>
                    </label>
                </div>

                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Fondo Barra', 'hotel-booking-system'); ?></label>
                    <input type="text" name="float_color_bg"
                        value="<?php echo esc_attr(!empty($opts['float_color_bg']) ? $opts['float_color_bg'] : '#ffffff'); ?>"
                        class="hbs-color-field" data-default-color="#ffffff">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Color Texto', 'hotel-booking-system'); ?></label>
                    <input type="text" name="float_color_text"
                        value="<?php echo esc_attr(!empty($opts['float_color_text']) ? $opts['float_color_text'] : '#0f172a'); ?>"
                        class="hbs-color-field" data-default-color="#0f172a">
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Fondo Botón', 'hotel-booking-system'); ?></label>
                    <input type="text" name="float_color_btn"
                        value="<?php echo esc_attr(!empty($opts['float_color_btn']) ? $opts['float_color_btn'] : '#0f172a'); ?>"
                        class="hbs-color-field" data-default-color="#0f172a">
                </div>

                <div class="hbs-field hbs-full-width">
                    <label class="hbs-label"
                        for="book_page_id"><?php esc_html_e('URL Página Reservación (Destino)', 'hotel-booking-system'); ?></label>
                    <?php
                    wp_dropdown_pages(array(
                        'name' => 'book_page_id',
                        'id' => 'book_page_id',
                        'class' => 'hbs-input',
                        'show_option_none' => __('Seleccionar página', 'hotel-booking-system'),
                        'option_none_value' => '0',
                        'selected' => $book_page_id
                    ));
                    ?>
                </div>

                <div class="hbs-field hbs-full-width">
                    <label class="hbs-label"
                        for="floating_exceptions"><?php esc_html_e('Excluir en páginas (Excepciones)', 'hotel-booking-system'); ?></label>
                    <?php
                    $pages = get_pages();
                    $selected_ids = !empty($opts['floating_exceptions']) ? explode(',', $opts['floating_exceptions']) : [];
                    ?>
                    <select name="floating_exceptions[]" id="floating_exceptions" multiple="multiple"
                        style="width: 100%;">
                        <?php foreach ($pages as $page): ?>
                            <option value="<?php echo esc_attr($page->ID); ?>" <?php echo in_array($page->ID, $selected_ids) ? 'selected' : ''; ?>>
                                <?php echo esc_html($page->post_title); ?> (ID: <?php echo esc_html($page->ID); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="hbs-description">
                        <?php echo esc_html__('Seleccione las páginas donde NO desea mostrar el formulario flotante.', 'hotel-booking-system'); ?>
                    </p>
                </div>


                <div class="hbs-field hbs-full-width">
                    <label class="hbs-label"
                        for="thankyou_page_id"><?php esc_html_e('Página de Gracias', 'hotel-booking-system'); ?></label>
                    <?php
                    wp_dropdown_pages(array(
                        'name' => 'thankyou_page_id',
                        'id' => 'thankyou_page_id',
                        'class' => 'hbs-input',
                        'show_option_none' => __('Seleccionar página', 'hotel-booking-system'),
                        'option_none_value' => '0',
                        'selected' => $thankyou_page_id
                    ));
                    ?>
                    <p class="hbs-description">
                        <?php echo esc_html__('Página donde se redirige al usuario después de enviar una reservación. Use el shortcode [hotel_booking_confirmation] para mostrar los detalles.', 'hotel-booking-system'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Emails -->
        <div class="hbs-card">
            <div class="hbs-card-header">
                <h2 class="hbs-card-title"><?php esc_html_e('Configuración de Correos', 'hotel-booking-system'); ?></h2>
            </div>
            <div class="hbs-card-body">
                <p class="hbs-description" style="margin-bottom: 20px;">
                    <strong><?php esc_html_e('Variables disponibles:', 'hotel-booking-system'); ?></strong>
                    <code>{booking_id}</code>, <code>{guest_name}</code>, <code>{guest_email}</code>,
                    <code>{guest_phone}</code>, <code>{check_in_date}</code>, <code>{check_out_date}</code>,
                    <code>{room_type}</code>, <code>{adults_count}</code>, <code>{kids_count}</code>,
                    <code>{total_price}</code>
                </p>

                <!-- Staff -->
                <div class="hbs-field">
                    <label class="hbs-label"><?php esc_html_e('Asunto Email Staff', 'hotel-booking-system'); ?></label>
                    <input name="email_staff_subject" type="text"
                        value="<?php echo esc_attr(!empty($opts['email_staff_subject']) ? $opts['email_staff_subject'] : '[Nueva Reservación] Solicitud #{booking_id}'); ?>"
                        class="hbs-input">
                </div>
                
                <div class="hbs-field">
                    <label class="hbs-checkbox" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <input type="checkbox" name="use_custom_staff_template" value="1" 
                            <?php checked(!empty($opts['use_custom_staff_template']), true); ?>>
                        <span style="font-weight: 600; color: var(--hbs-text);">
                            <?php esc_html_e('Usar plantilla personalizada (Staff)', 'hotel-booking-system'); ?>
                        </span>
                    </label>
                    <p class="hbs-description" style="margin: -10px 0 10px 0;">
                        <?php esc_html_e('Si está desactivado, se usará la plantilla moderna predeterminada con el logo del hotel. Si está activado, podrás crear tu propia plantilla HTML personalizada abajo.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label
                        class="hbs-label"><?php esc_html_e('Contenido Email Staff', 'hotel-booking-system'); ?></label>
                    <?php
                    $content_staff = !empty($opts['email_staff_content']) ? $opts['email_staff_content'] : '';
                    wp_editor($content_staff, 'email_staff_content', ['textarea_rows' => 10, 'media_buttons' => true]);
                    ?>
                    <p class="hbs-description" style="margin-top: 10px;">
                        <?php esc_html_e('Placeholders disponibles:', 'hotel-booking-system'); ?>
                        <code>{booking_id}</code>, <code>{guest_name}</code>, <code>{guest_email}</code>, 
                        <code>{guest_phone}</code>, <code>{check_in_date}</code>, <code>{check_out_date}</code>, 
                        <code>{room_type}</code>, <code>{adults_count}</code>, <code>{kids_count}</code>, <code>{total_price}</code>
                    </p>
                </div>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--hbs-border);">

                <!-- Guest -->
                <div class="hbs-field">
                    <label
                        class="hbs-label"><?php esc_html_e('Asunto Email Huésped', 'hotel-booking-system'); ?></label>
                    <input name="email_guest_subject" type="text"
                        value="<?php echo esc_attr(!empty($opts['email_guest_subject']) ? $opts['email_guest_subject'] : 'Confirmación de Solicitud #{booking_id}'); ?>"
                        class="hbs-input">
                </div>

                <div class="hbs-field">
                    <label class="hbs-checkbox" style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                        <input type="checkbox" name="use_custom_guest_template" value="1" 
                            <?php checked(!empty($opts['use_custom_guest_template']), true); ?>>
                        <span style="font-weight: 600; color: var(--hbs-text);">
                            <?php esc_html_e('Usar plantilla personalizada (Huésped)', 'hotel-booking-system'); ?>
                        </span>
                    </label>
                    <p class="hbs-description" style="margin: -10px 0 10px 0;">
                        <?php esc_html_e('Si está desactivado, se usará la plantilla moderna predeterminada con el logo del hotel. Si está activado, podrás crear tu propia plantilla HTML personalizada abajo.', 'hotel-booking-system'); ?>
                    </p>
                </div>

                <div class="hbs-field">
                    <label
                        class="hbs-label"><?php esc_html_e('Contenido Email Huésped', 'hotel-booking-system'); ?></label>
                    <?php
                    $content_guest = !empty($opts['email_guest_content']) ? $opts['email_guest_content'] : '';
                    wp_editor($content_guest, 'email_guest_content', ['textarea_rows' => 10, 'media_buttons' => true]);
                    ?>
                    <p class="hbs-description" style="margin-top: 10px;">
                        <?php esc_html_e('Placeholders disponibles:', 'hotel-booking-system'); ?>
                        <code>{booking_id}</code>, <code>{guest_name}</code>, <code>{guest_email}</code>, 
                        <code>{guest_phone}</code>, <code>{check_in_date}</code>, <code>{check_out_date}</code>, 
                        <code>{room_type}</code>, <code>{adults_count}</code>, <code>{kids_count}</code>, <code>{total_price}</code>
                    </p>
                </div>
                <div class="hbs-field">
                    <label class="hbs-label"
                        for="guest_email_note"><?php esc_html_e('Nota Adicional (Email Huésped)', 'hotel-booking-system'); ?></label>
                    <textarea name="guest_email_note" id="guest_email_note" rows="3"
                        class="hbs-input"><?php echo esc_textarea($note); ?></textarea>
                </div>
            </div>
        </div>

        <!-- CSS Personalizado -->
        <div class="hbs-card">
            <div class="hbs-card-header">
                <h2 class="hbs-card-title"><?php esc_html_e('CSS Personalizado', 'hotel-booking-system'); ?></h2>
            </div>
            <div class="hbs-card-body">
                <p class="hbs-description" style="margin-bottom: 20px;">
                    <?php esc_html_e('Agrega CSS personalizado para los formularios. Los estilos se aplicarán después de los estilos predeterminados.', 'hotel-booking-system'); ?>
                </p>

                <!-- Booking Form CSS -->
                <div class="hbs-field">
                    <label
                        class="hbs-label"><?php esc_html_e('CSS Formulario Principal', 'hotel-booking-system'); ?></label>
                    <textarea name="custom_css_booking_form" rows="10" class="hbs-input"
                        style="font-family: monospace; font-size: 13px;"><?php echo esc_textarea(!empty($opts['custom_css_booking_form']) ? $opts['custom_css_booking_form'] : ''); ?></textarea>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; color: var(--hbs-accent); font-weight: 500;">
                            <?php esc_html_e('Ver Clases CSS Disponibles', 'hotel-booking-system'); ?>
                        </summary>
                        <div
                            style="background: #f8fafc; padding: 15px; margin-top: 10px; border-radius: 6px; font-family: monospace; font-size: 12px;">
                            <strong>Contenedor principal:</strong><br>
                            <code>.hbs-form</code> - Formulario completo<br><br>

                            <strong>Encabezado:</strong><br>
                            <code>.hbs-header</code> - Sección de encabezado<br>
                            <code>.hbs-form h3</code> - Título<br>
                            <code>.hbs-subtitle</code> - Subtítulo<br><br>

                            <strong>Campos:</strong><br>
                            <code>.hbs-field</code> - Contenedor de campo<br>
                            <code>.hbs-field label</code> - Etiquetas<br>
                            <code>.hbs-input-wrapper</code> - Contenedor de input con icono<br>
                            <code>.hbs-phone-wrapper</code> - Contenedor teléfono<br><br>

                            <strong>Selector de habitación:</strong><br>
                            <code>.hbs-room-selector</code> - Contenedor<br>
                            <code>.hbs-room-card</code> - Tarjeta de habitación<br>
                            <code>.hbs-room-title</code> - Título de habitación<br><br>

                            <strong>Botones:</strong><br>
                            <code>.hbs-btn-primary</code> - Botón principal<br>
                            <code>.hbs-checkbox-wrapper</code> - Checkbox de políticas<br><br>

                            <strong>Precio:</strong><br>
                            <code>.hbs-price-box</code> - Caja de precio<br>
                            <code>.hbs-breakdown</code> - Tabla de desglose<br><br>

                            <strong>Ejemplo:</strong><br>
                            <code style="display: block; background: white; padding: 10px; margin-top: 5px;">
.hbs-form {<br>
&nbsp;&nbsp;box-shadow: 0 10px 30px rgba(0,0,0,0.2);<br>
}<br>
.hbs-btn-primary {<br>
&nbsp;&nbsp;background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);<br>
}
                            </code>
                        </div>
                    </details>
                </div>

                <hr style="margin: 30px 0; border: 0; border-top: 1px solid var(--hbs-border);">

                <!-- Floating Form CSS -->
                <div class="hbs-field">
                    <label
                        class="hbs-label"><?php esc_html_e('CSS Formulario Flotante', 'hotel-booking-system'); ?></label>
                    <textarea name="custom_css_floating_form" rows="10" class="hbs-input"
                        style="font-family: monospace; font-size: 13px;"><?php echo esc_textarea(!empty($opts['custom_css_floating_form']) ? $opts['custom_css_floating_form'] : ''); ?></textarea>
                    <details style="margin-top: 10px;">
                        <summary style="cursor: pointer; color: var(--hbs-accent); font-weight: 500;">
                            <?php esc_html_e('Ver Clases CSS Disponibles', 'hotel-booking-system'); ?>
                        </summary>
                        <div
                            style="background: #f8fafc; padding: 15px; margin-top: 10px; border-radius: 6px; font-family: monospace; font-size: 12px;">
                            <strong>Contenedor principal:</strong><br>
                            <code>#hbs-floating-form</code> - Formulario flotante completo<br><br>

                            <strong>Estructura:</strong><br>
                            <code>#hbs-floating-form form</code> - Formulario interno<br>
                            <code>#hbs-floating-form label</code> - Etiquetas<br>
                            <code>#hbs-floating-form label span</code> - Texto de etiqueta<br><br>

                            <strong>Campos:</strong><br>
                            <code>.hbs-input-wrapper</code> - Contenedor de input<br>
                            <code>#hbs-floating-form input</code> - Inputs<br><br>

                            <strong>Botón:</strong><br>
                            <code>#hbs-floating-form button</code> - Botón de envío<br><br>

                            <strong>Ejemplo:</strong><br>
                            <code style="display: block; background: white; padding: 10px; margin-top: 5px;">
#hbs-floating-form {<br>
&nbsp;&nbsp;border-radius: 20px;<br>
&nbsp;&nbsp;backdrop-filter: blur(10px);<br>
}<br>
#hbs-floating-form button {<br>
&nbsp;&nbsp;background: #ff6b6b;<br>
&nbsp;&nbsp;transform: scale(1.1);<br>
}
                            </code>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <!-- Shortcodes Disponibles -->
        <div class="hbs-card">
            <div class="hbs-section-header">
                <h2>
                    <?php esc_html_e('📋 Shortcodes Disponibles', 'hotel-booking-system'); ?>
                </h2>
                <p class="hbs-description">
                    <?php esc_html_e('Utiliza estos shortcodes en tus páginas y entradas de WordPress para mostrar los formularios y confirmaciones.', 'hotel-booking-system'); ?>
                </p>
            </div>

            <div class="hbs-field-group">
                <!-- Shortcode 1: Booking Form -->
                <div class="hbs-shortcode-item"
                    style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #3b82f6;">
                    <h3 style="margin-top: 0; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">📝</span>
                        <?php esc_html_e('Formulario de Reservación', 'hotel-booking-system'); ?>
                    </h3>

                    <div
                        style="background: white; padding: 12px; border-radius: 6px; margin: 15px 0; font-family: 'Courier New', monospace; font-size: 14px; color: #0f172a; border: 1px solid #e2e8f0;">
                        <code style="user-select: all;">[hotel_booking_form]</code>
                    </div>

                    <p style="color: #64748b; margin: 10px 0 5px;">
                        <strong>
                            <?php esc_html_e('Función:', 'hotel-booking-system'); ?>
                        </strong>
                        <?php esc_html_e('Muestra el formulario completo de reservación con todos los campos necesarios para solicitar una reserva.', 'hotel-booking-system'); ?>
                    </p>

                    <p style="color: #64748b; margin: 5px 0;">
                        <strong>
                            <?php esc_html_e('Uso:', 'hotel-booking-system'); ?>
                        </strong>
                        <?php esc_html_e('Crea una página (ej. "Reservaciones") y pega este shortcode en el contenido.', 'hotel-booking-system'); ?>
                    </p>

                    <p style="color: #64748b; margin: 5px 0;">
                        <strong>
                            <?php esc_html_e('Características:', 'hotel-booking-system'); ?>
                        </strong>
                    </p>
                    <ul style="color: #64748b; margin: 5px 0; padding-left: 20px;">
                        <li>
                            <?php esc_html_e('Selección de fechas de check-in y noches', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Selección de número de huéspedes (adultos y niños)', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Selección de tipo de habitación (dinámico)', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Cálculo automático de precios', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Envío de solicitud por email', 'hotel-booking-system'); ?>
                        </li>
                    </ul>
                </div>

                <!-- Shortcode 2: Booking Confirmation -->
                <div class="hbs-shortcode-item"
                    style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #10b981;">
                    <h3 style="margin-top: 0; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">✅</span>
                        <?php esc_html_e('Confirmación de Reservación', 'hotel-booking-system'); ?>
                    </h3>

                    <div
                        style="background: white; padding: 12px; border-radius: 6px; margin: 15px 0; font-family: 'Courier New', monospace; font-size: 14px; color: #0f172a; border: 1px solid #e2e8f0;">
                        <code style="user-select: all;">[hotel_booking_confirmation]</code>
                    </div>

                    <p style="color: #64748b; margin: 10px 0 5px;">
                        <strong>
                            <?php esc_html_e('Función:', 'hotel-booking-system'); ?>
                        </strong>
                        <?php esc_html_e('Muestra los detalles de confirmación de una reserva después de que el usuario haya enviado el formulario.', 'hotel-booking-system'); ?>
                    </p>

                    <p style="color: #64748b; margin: 5px 0;">
                        <strong>
                            <?php esc_html_e('Uso:', 'hotel-booking-system'); ?>
                        </strong>
                        <?php esc_html_e('Crea una página (ej. "Gracias por tu Reserva") y pega este shortcode. Luego configura la URL de esta página en el campo "URL Página de Gracias" arriba.', 'hotel-booking-system'); ?>
                    </p>

                    <p style="color: #64748b; margin: 5px 0;">
                        <strong>
                            <?php esc_html_e('Información mostrada:', 'hotel-booking-system'); ?>
                        </strong>
                    </p>
                    <ul style="color: #64748b; margin: 5px 0; padding-left: 20px;">
                        <li>
                            <?php esc_html_e('Número de reservación', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Fechas de check-in y check-out', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Tipo de habitación seleccionada', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Número de huéspedes', 'hotel-booking-system'); ?>
                        </li>
                        <li>
                            <?php esc_html_e('Precio total estimado', 'hotel-booking-system'); ?>
                        </li>
                    </ul>

                    <div
                        style="background: #fef3c7; border: 1px solid #fbbf24; padding: 12px; border-radius: 6px; margin-top: 15px;">
                        <p style="margin: 0; color: #92400e; font-size: 13px;">
                            <strong>💡 Nota:</strong>
                            <?php esc_html_e('Este shortcode requiere el parámetro ?booking_id=X en la URL. El sistema redirige automáticamente a esta página después de una reserva exitosa.', 'hotel-booking-system'); ?>
                        </p>
                    </div>
                </div>

                <!-- Quick Copy Tip -->
                <div
                    style="background: #eff6ff; border: 1px solid #3b82f6; padding: 15px; border-radius: 6px; margin-top: 20px;">
                    <p style="margin: 0; color: #1e40af; font-size: 14px;">
                        <strong>💡 Tip:</strong>
                        <?php esc_html_e('Haz clic en los códigos para seleccionarlos fácilmente y copiarlos.', 'hotel-booking-system'); ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="hbs-actions">

            <button type="submit"
                class="hbs-button-primary"><?php esc_html_e('Guardar Cambios', 'hotel-booking-system'); ?></button>
        </div>
    </form>

    <script>
        jQuery(document).ready(function ($) {
            $('.hbs-color-field').wpColorPicker();

            if ($.fn.select2) {
                $('#floating_exceptions').select2({
                    placeholder: "<?php echo esc_js(__('Seleccione páginas...', 'hotel-booking-system')); ?>",
                    allowClear: true,
                    width: 'resolve'
                });
            }

            // Make color pickers floating to prevent layout shifts
            $('<style>')
                .text('.wp-picker-container { position: relative; } ' +
                    '.wp-picker-container .wp-picker-holder { position: absolute !important; z-index: 100 !important; margin-top: 5px; }')
                .appendTo('head');

            // Logo Upload Handler
            var logoFrame;
            $('#hbs-upload-logo').on('click', function (e) {
                e.preventDefault();

                // If the media frame already exists, reopen it.
                if (logoFrame) {
                    logoFrame.open();
                    return;
                }

                // Create a new media frame
                logoFrame = wp.media({
                    title: 'Seleccionar Logo del Hotel',
                    button: {
                        text: 'Usar este logo'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                // When an image is selected in the media frame...
                logoFrame.on('select', function () {
                    var attachment = logoFrame.state().get('selection').first().toJSON();

                    // Update hidden field with attachment ID
                    $('#hotel_logo_id').val(attachment.id);

                    // Update preview
                    $('#hbs-logo-preview').show().html(
                        '<img src="' + attachment.url + '" alt="Logo" style="max-width: 200px; height: auto;">'
                    );

                    // Update button text
                    $('#hbs-upload-logo').text('<?php echo esc_js(__("Cambiar Logo", "hotel-booking-system")); ?>');

                    // Show remove button if it doesn't exist
                    if (!$('#hbs-remove-logo').length) {
                        $('#hbs-upload-logo').after(
                            '<button type="button" class="hbs-button hbs-button-danger" id="hbs-remove-logo">' +
                            '<?php echo esc_js(__("Eliminar Logo", "hotel-booking-system")); ?>' +
                            '</button>'
                        );
                    }
                });

                // Finally, open the modal on click
                logoFrame.open();
            });

            // Logo Remove Handler (using delegation for dynamically added button)
            $(document).on('click', '#hbs-remove-logo', function (e) {
                e.preventDefault();

                // Clear the hidden field
                $('#hotel_logo_id').val('');

                // Hide and clear preview
                $('#hbs-logo-preview').hide().html('');

                // Update button text
                $('#hbs-upload-logo').text('<?php echo esc_js(__("Subir Logo", "hotel-booking-system")); ?>');

                // Remove the remove button
                $(this).remove();
            });
        });
    </script>
</div>