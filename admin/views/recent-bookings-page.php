<?php
if (!defined('ABSPATH')) {
    exit;
}

$bookings = HBS_Booking::get_recent(10);
?>

<div class="wrap">
    <h1><?php echo esc_html__('Reservaciones recientes', 'hotel-booking-system'); ?></h1>

    <?php if (isset($_GET['deleted']) && $_GET['deleted'] === 'true'): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('Reservación eliminada correctamente.', 'hotel-booking-system'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['bulk_deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf(esc_html__('%d reservaciones eliminadas correctamente.', 'hotel-booking-system'), intval($_GET['bulk_deleted'])); ?>
            </p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="hbs_bulk_delete_bookings">
        <?php wp_nonce_field('hbs_bulk_delete', 'hbs_bulk_nonce'); ?>

        <div class="tablenav top">
            <div class="alignleft actions bulkactions">
                <label for="bulk-action-selector-top"
                    class="screen-reader-text"><?php echo esc_html__('Seleccionar acción en lote', 'hotel-booking-system'); ?></label>
                <select name="bulk_action" id="bulk-action-selector-top">
                    <option value="-1"><?php echo esc_html__('Acciones en lote', 'hotel-booking-system'); ?></option>
                    <option value="delete"><?php echo esc_html__('Borrar', 'hotel-booking-system'); ?></option>
                </select>
                <input type="submit" id="doaction" class="button action"
                    value="<?php echo esc_attr__('Aplicar', 'hotel-booking-system'); ?>"
                    onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas borrar las reservaciones seleccionadas?', 'hotel-booking-system')); ?>');">
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="cb-select-all-1">
                    </td>
                    <th>ID</th>
                    <th><?php echo esc_html__('Fecha Solicitud', 'hotel-booking-system'); ?></th>
                    <th><?php echo esc_html__('Status', 'hotel-booking-system'); ?></th>
                    <th><?php echo esc_html__('Huésped', 'hotel-booking-system'); ?></th>
                    <th><?php echo esc_html__('Fechas Estancia', 'hotel-booking-system'); ?></th>
                    <th><?php echo esc_html__('Detalles', 'hotel-booking-system'); ?></th>
                    <th><?php echo esc_html__('Total (MXN)', 'hotel-booking-system'); ?></th>
                    <th><?php echo esc_html__('Acciones', 'hotel-booking-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($bookings)): ?>
                    <?php foreach ($bookings as $b): ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="booking_ids[]" value="<?php echo intval($b['booking_id']); ?>">
                            </th>
                            <td>#<?php echo intval($b['booking_id']); ?></td>
                            <td><?php echo esc_html($b['created_date']); ?></td>
                            <td>
                                <?php
                                $status_color = '#eee'; // default
                                if ('pending' === $b['booking_status'])
                                    $status_color = '#ffeeba'; // yellow
                                elseif ('confirmed' === $b['booking_status'])
                                    $status_color = '#c3e6cb'; // green
                                elseif ('cancelled' === $b['booking_status'])
                                    $status_color = '#f5c6cb'; // red
                                ?>
                                <span
                                    style="background:<?php echo $status_color; ?>;padding:4px 8px;border-radius:4px;font-weight:bold;font-size:11px;">
                                    <?php echo esc_html(ucfirst($b['booking_status'])); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo esc_html($b['guest_name']); ?></strong><br>
                                <a
                                    href="mailto:<?php echo esc_attr($b['guest_email']); ?>"><?php echo esc_html($b['guest_email']); ?></a><br>
                                <?php echo esc_html($b['guest_phone']); ?>
                            </td>
                            <td>
                                In: <?php echo esc_html($b['check_in_date']); ?><br>
                                Out: <?php echo esc_html($b['check_out_date']); ?>
                            </td>
                            <td>
                                <?php
                                echo sprintf(
                                    esc_html__('%s | A: %d | N: %d', 'hotel-booking-system'),
                                    ('single' === $b['room_type'] ? 'Sencilla' : 'Doble'),
                                    $b['adults_count'],
                                    $b['kids_count']
                                );
                                ?>
                            </td>
                            <td>$<?php echo number_format($b['total_price'], 2); ?></td>
                            <td>
                                <?php
                                $delete_url = wp_nonce_url(
                                    admin_url('admin-post.php?action=hbs_delete_booking&booking_id=' . $b['booking_id']),
                                    'hbs_delete_booking',
                                    'hbs_delete_nonce'
                                );
                                ?>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete"
                                    onclick="return confirm('<?php echo esc_js(__('¿Estás seguro de que deseas borrar esta solicitud? Esta acción no se puede deshacer.', 'hotel-booking-system')); ?>');">
                                    <?php echo esc_html__('Borrar', 'hotel-booking-system'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9"><?php echo esc_html__('No hay reservaciones recientes.', 'hotel-booking-system'); ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>

<script>
    jQuery(document).ready(function ($) {
        // Select all checkbox functionality
        $('#cb-select-all-1').on('click', function () {
            var isChecked = $(this).prop('checked');
            $('input[name="booking_ids[]"]').prop('checked', isChecked);
        });
    });
</script>