/**
 * Agency Filter JavaScript
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Agency
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/agency/agency-filter.js
 *
 * Description: Handles agency status filter functionality.
 *              Updates DataTable when filter changes.
 *
 * Changelog:
 * 1.0.0 - 2025-10-24
 * - Initial implementation
 * - Moved from inline script to separate JS file
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle status filter change
        $('#agency-status-filter').on('change', function() {
            const status = $(this).val();
            const $datatable = $('.wpdt-datatable').DataTable();

            if ($datatable) {
                // Store status in DataTable settings for AJAX requests
                $datatable.settings()[0].ajax.data = function(d) {
                    d.status_filter = status;
                    return d;
                };

                // Reload DataTable with new filter
                $datatable.ajax.reload();
            }
        });
    });

})(jQuery);
