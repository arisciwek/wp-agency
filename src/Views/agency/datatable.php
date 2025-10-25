<?php
/**
 * Agency DataTable View
 *
 * @package     WP_Agency
 * @subpackage  Views/Agency
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/agency/datatable.php
 *
 * Description: DataTable HTML structure untuk agencies list.
 *              Rendered in left panel via wpapp_left_panel_content hook.
 *              Uses server-side processing with AgencyDataTableModel.
 *
 * Changelog:
 * 1.0.0 - 2025-10-23
 * - Initial implementation (TODO-2071 Phase 4, Task 4.2)
 * - DataTable with 6 columns
 * - Server-side processing via get_agencies_datatable
 * - Integrated with base panel system
 */

defined('ABSPATH') || exit;
?>

<div class="wpapp-datatable-wrapper">
    <table id="agency-list-table" class="wpapp-datatable display" style="width:100%">
        <thead>
            <tr>
                <th><?php esc_html_e('Code', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Nama Disnaker', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Provinsi', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Kabupaten/Kota', 'wp-agency'); ?></th>
                <th><?php esc_html_e('Actions', 'wp-agency'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- DataTables will populate via AJAX -->
        </tbody>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    'use strict';

    // Initialize DataTable
    var agencyTable = $('#agency-list-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxurl,
            type: 'POST',
            data: function(d) {
                d.action = 'get_agencies_datatable';
                d.nonce = wpAgency.nonce;
                return d;
            },
            error: function(xhr, error, code) {
                console.error('DataTable AJAX Error:', error, code);
            }
        },
        columns: [
            {
                data: 'code',
                width: '12%'
            },
            {
                data: 'name',
                width: '35%'
            },
            {
                data: 'provinsi_name',
                width: '23%'
            },
            {
                data: 'regency_name',
                width: '23%'
            },
            {
                data: 'actions',
                width: '7%',
                orderable: false,
                searchable: false
            }
        ],
        order: [[1, 'asc']], // Default sort by name
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            processing: '<?php esc_html_e('Loading...', 'wp-agency'); ?>',
            search: '<?php esc_html_e('Search:', 'wp-agency'); ?>',
            lengthMenu: '<?php esc_html_e('Show _MENU_ entries', 'wp-agency'); ?>',
            info: '<?php esc_html_e('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-agency'); ?>',
            infoEmpty: '<?php esc_html_e('Showing 0 to 0 of 0 entries', 'wp-agency'); ?>',
            infoFiltered: '<?php esc_html_e('(filtered from _MAX_ total entries)', 'wp-agency'); ?>',
            zeroRecords: '<?php esc_html_e('No matching records found', 'wp-agency'); ?>',
            emptyTable: '<?php esc_html_e('No data available in table', 'wp-agency'); ?>',
            paginate: {
                first: '<?php esc_html_e('First', 'wp-agency'); ?>',
                previous: '<?php esc_html_e('Previous', 'wp-agency'); ?>',
                next: '<?php esc_html_e('Next', 'wp-agency'); ?>',
                last: '<?php esc_html_e('Last', 'wp-agency'); ?>'
            }
        }
    });

    // Handle row click to open panel (handled by base panel system via DT_RowId)
    $('#agency-list-table tbody').on('click', 'tr', function() {
        var data = agencyTable.row(this).data();
        if (data && data.DT_RowData && data.DT_RowData.id) {
            // Base panel system listens to this event
            $(document).trigger('wpapp:open-panel', {
                id: data.DT_RowData.id,
                entity: 'agency'
            });
        }
    });

    // Handle edit button click
    $(document).on('click', '.wpapp-edit-agency', function(e) {
        e.stopPropagation(); // Prevent row click
        var agencyId = $(this).data('id');

        // TODO: Open edit modal or navigate to edit page
        console.log('Edit agency:', agencyId);
    });

    // Handle delete button click
    $(document).on('click', '.wpapp-delete-agency', function(e) {
        e.stopPropagation(); // Prevent row click
        var agencyId = $(this).data('id');

        if (!confirm('<?php esc_html_e('Are you sure you want to delete this agency?', 'wp-agency'); ?>')) {
            return;
        }

        // TODO: Implement delete functionality
        console.log('Delete agency:', agencyId);
    });
});
</script>
