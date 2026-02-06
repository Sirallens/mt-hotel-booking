<?php
/**
 * Import/Export Configuration Page
 *
 * @package Hotel_Booking_System
 */

if (!defined('ABSPATH')) {
    exit;
}

$success = isset($_GET['import_success']) ? sanitize_text_field($_GET['import_success']) : '';
$error = isset($_GET['import_error']) ? sanitize_text_field($_GET['import_error']) : '';
?>

<div class="wrap hbs-admin-wrap">
    <h1>
        <?php esc_html_e('Importar/Exportar Configuración', 'hotel-booking-system'); ?>
    </h1>

    <?php if ($success === 'true'): ?>
        <div class="hbs-notice hbs-notice-success">
            <?php esc_html_e('✅ Configuración importada correctamente.', 'hotel-booking-system'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="hbs-notice hbs-notice-error">
            <?php
            switch ($error) {
                case 'invalid_json':
                    echo esc_html__('❌ Error: El JSON no es válido.', 'hotel-booking-system');
                    break;
                case 'invalid_structure':
                    echo esc_html__('❌ Error: El JSON no tiene la estructura correcta.', 'hotel-booking-system');
                    break;
                case 'empty_json':
                    echo esc_html__('❌ Error: El campo está vacío.', 'hotel-booking-system');
                    break;
                default:
                    echo esc_html__('❌ Error desconocido al importar.', 'hotel-booking-system');
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <!-- Export Section -->
    <div class="hbs-card" style="margin-top: 20px;">
        <h2>📤
            <?php esc_html_e('Exportar Configuración', 'hotel-booking-system'); ?>
        </h2>
        <p>
            <?php esc_html_e('Copia la configuración actual para respaldarla o transferirla a otro sitio.', 'hotel-booking-system'); ?>
        </p>

        <div style="margin: 20px 0;">
            <textarea id="hbs-export-json" readonly rows="20" class="hbs-input"
                style="font-family: monospace; font-size: 12px; width: 100%; resize: vertical;"
                placeholder="<?php esc_attr_e('Click en "Generar JSON" para ver la configuración exportada...', 'hotel-booking-system'); ?>"
            ></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="button" id="hbs-generate-json-btn" class="hbs-button-primary">
                🔄
                <?php esc_html_e('Generar JSON', 'hotel-booking-system'); ?>
            </button>
            <button type="button" id="hbs-copy-json-btn" class="hbs-button-secondary" disabled>
                📋
                <?php esc_html_e('Copiar al Portapapeles', 'hotel-booking-system'); ?>
            </button>
            <span id="hbs-copy-feedback" style="display: none; color: #10b981; margin-left: 10px; line-height: 36px;">
                ✅
                <?php esc_html_e('Copiado!', 'hotel-booking-system'); ?>
            </span>
        </div>
    </div>

    <!-- Import Section -->
    <div class="hbs-card" style="margin-top: 30px;">
        <h2>📥
            <?php esc_html_e('Importar Configuración', 'hotel-booking-system'); ?>
        </h2>
        <p>
            <?php esc_html_e('Pega aquí el JSON exportado para restaurar o aplicar la configuración.', 'hotel-booking-system'); ?>
        </p>

        <div class="hbs-notice hbs-notice-warning" style="margin: 15px 0;">
            <strong>⚠️
                <?php esc_html_e('Importante:', 'hotel-booking-system'); ?>
            </strong>
            <?php esc_html_e('Se creará un backup automático antes de importar. Solo se actualizarán los campos presentes en el JSON.', 'hotel-booking-system'); ?>
        </div>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="hbs-import-form">
            <input type="hidden" name="action" value="hbs_import_settings">
            <?php wp_nonce_field('hbs_import_settings', 'hbs_import_nonce'); ?>

            <div style="margin: 20px 0;">
                <textarea name="hbs_import_json" id="hbs-import-json" rows="20" class="hbs-input" required
                    style="font-family: monospace; font-size: 12px; width: 100%; resize: vertical;"
                    placeholder="<?php esc_attr_e('Pega aquí el JSON exportado...', 'hotel-booking-system'); ?>"></textarea>
            </div>

            <button type="submit" class="hbs-button-primary"
                onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de importar esta configuración? Se sobrescribirán los valores actuales con los del JSON.', 'hotel-booking-system')); ?>');">
                📤
                <?php esc_html_e('Importar Ahora', 'hotel-booking-system'); ?>
            </button>
        </form>
    </div>

    <!-- Help Section -->
    <div class="hbs-card" style="margin-top: 30px; background: #f0f9ff; border-color: #3b82f6;">
        <h3>💡
            <?php esc_html_e('Información sobre Import/Export', 'hotel-booking-system'); ?>
        </h3>

        <div
            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 15px;">
            <div>
                <h4>
                    <?php esc_html_e('¿Qué se exporta?', 'hotel-booking-system'); ?>
                </h4>
                <ul style="margin: 5px 0 0 20px;">
                    <li>
                        <?php esc_html_e('✅ Configuración general', 'hotel-booking-system'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✅ Tipos de habitación', 'hotel-booking-system'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✅ CSS del formulario principal', 'hotel-booking-system'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✅ CSS del formulario flotante', 'hotel-booking-system'); ?>
                    </li>
                </ul>
            </div>

            <div>
                <h4>
                    <?php esc_html_e('Importación Selectiva', 'hotel-booking-system'); ?>
                </h4>
                <p style="margin: 5px 0;">
                    <?php esc_html_e('Solo se actualizan los campos presentes en el JSON. Los demás permanecen intactos.', 'hotel-booking-system'); ?>
                </p>
                <p style="margin: 5px 0;"><strong>
                        <?php esc_html_e('Para room types:', 'hotel-booking-system'); ?>
                    </strong></p>
                <ul style="margin: 5px 0 0 20px;">
                    <li>
                        <?php esc_html_e('Si el slug existe: actualiza campos', 'hotel-booking-system'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('Si el slug no existe: crea nuevo', 'hotel-booking-system'); ?>
                    </li>
                </ul>
            </div>

            <div>
                <h4>
                    <?php esc_html_e('Seguridad', 'hotel-booking-system'); ?>
                </h4>
                <ul style="margin: 5px 0 0 20px;">
                    <li>
                        <?php esc_html_e('✅ Backup automático antes de importar', 'hotel-booking-system'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✅ Validación de estructura JSON', 'hotel-booking-system'); ?>
                    </li>
                    <li>
                        <?php esc_html_e('✅ Confirmación antes de sobrescribir', 'hotel-booking-system'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const generateBtn = document.getElementById('hbs-generate-json-btn');
        const copyBtn = document.getElementById('hbs-copy-json-btn');
        const exportTextarea = document.getElementById('hbs-export-json');
        const copyFeedback = document.getElementById('hbs-copy-feedback');

        // Generate JSON
        generateBtn.addEventListener('click', async function () {
            generateBtn.disabled = true;
            generateBtn.textContent = '⏳ <?php echo esc_js(__('Generando...', 'hotel-booking-system')); ?>';

        try {
            const response = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=hbs_generate_export_json'
            });

            const data = await response.json();

            if (data.success) {
                exportTextarea.value = data.data;
                copyBtn.disabled = false;
                generateBtn.textContent = '✅ <?php echo esc_js(__('Generado', 'hotel-booking-system')); ?>';

                setTimeout(() => {
                    generateBtn.disabled = false;
                    generateBtn.textContent = '🔄 <?php echo esc_js(__('Generar JSON', 'hotel-booking-system')); ?>';
                }, 2000);
            } else {
                alert('<?php echo esc_js(__('Error al generar el JSON', 'hotel-booking-system')); ?>');
                generateBtn.disabled = false;
                generateBtn.textContent = '🔄 <?php echo esc_js(__('Generar JSON', 'hotel-booking-system')); ?>';
            }
        } catch (error) {
            console.error('Error:', error);
            alert('<?php echo esc_js(__('Error de conexión', 'hotel-booking-system')); ?>');
            generateBtn.disabled = false;
            generateBtn.textContent = '🔄 <?php echo esc_js(__('Generar JSON', 'hotel-booking-system')); ?>';
        }
    });

    // Copy to clipboard
    copyBtn.addEventListener('click', async function () {
        try {
            await navigator.clipboard.writeText(exportTextarea.value);

            // Show feedback
            copyBtn.style.display = 'none';
            copyFeedback.style.display = 'inline';

            setTimeout(() => {
                copyBtn.style.display = 'inline-block';
                copyFeedback.style.display = 'none';
            }, 2000);
        } catch (error) {
            // Fallback for older browsers
            exportTextarea.select();
            document.execCommand('copy');

            copyBtn.style.display = 'none';
            copyFeedback.style.display = 'inline';

            setTimeout(() => {
                copyBtn.style.display = 'inline-block';
                copyFeedback.style.display = 'none';
            }, 2000);
        }
    });
}) ();
</script>