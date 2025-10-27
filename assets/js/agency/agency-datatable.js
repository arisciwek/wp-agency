/**
 * Agency DataTable Handler - Base Panel System Integration
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Agency
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/agency/agency-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables agency.
 *              Terintegrasi dengan base panel system dari wp-app-core.
 *              Menangani server-side processing dan event handling.
 *
 * Changelog:
 * 2.0.0 - 2025-10-25
 * - Migrated from inline script in datatable.php (TODO-3077)
 * - Integrated with wp-app-core base panel system
 * - Uses wpapp:open-panel event for panel integration
 * - Table ID: #agency-list-table (singular + list)
 * - AJAX action: get_agencies_datatable
 * - Columns: code, name, provinsi_name, regency_name, actions
 * - Localized strings via wpAgencyDataTable object
 * 1.0.2 - 2025-10-22
 * - Old implementation (backed up as agency-datatable.js.backup)
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - wp-app-core base panel system
 * - wpAgencyDataTable localized object (translations, ajaxurl, nonce)
 *
 * Global Variables Required:
 * - wpAgencyDataTable.ajaxurl: WordPress AJAX URL
 * - wpAgencyDataTable.nonce: Security nonce
 * - wpAgencyDataTable.i18n: Translation strings
 */

(function($) {
    'use strict';

    /**
     * Agency DataTable Module
     */
    const AgencyDataTable = {

        /**
         * DataTable instance
         */
        table: null,

        /**
         * Initialization flag
         */
        initialized: false,

        /**
         * Initialize DataTable
         */
        init() {
            if (this.initialized) {
                console.log('[AgencyDataTable] Already initialized');
                return;
            }

            // Check if table element exists
            if ($('#agency-list-table').length === 0) {
                console.log('[AgencyDataTable] Table element not found');
                return;
            }

            // Check dependencies
            if (typeof wpAgencyDataTable === 'undefined') {
                console.error('[AgencyDataTable] wpAgencyDataTable object not found. Script not localized properly.');
                return;
            }

            console.log('[AgencyDataTable] Initializing...');

            this.initDataTable();
            this.bindEvents();

            this.initialized = true;
            console.log('[AgencyDataTable] Initialized successfully');
        },

        /**
         * Initialize DataTable with server-side processing
         */
        initDataTable() {
            const self = this;

            this.table = $('#agency-list-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAgencyDataTable.ajaxurl,
                    type: 'POST',
                    data: function(d) {
                        d.action = 'get_agencies_datatable';
                        d.nonce = wpAgencyDataTable.nonce;
                        return d;
                    },
                    error: function(xhr, error, code) {
                        console.error('[AgencyDataTable] AJAX Error:', error, code);
                        console.error('[AgencyDataTable] Response:', xhr.responseText);
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
                language: wpAgencyDataTable.i18n || {
                    processing: 'Loading...',
                    search: 'Search:',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoEmpty: 'Showing 0 to 0 of 0 entries',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    zeroRecords: 'No matching records found',
                    emptyTable: 'No data available in table',
                    paginate: {
                        first: 'First',
                        previous: 'Previous',
                        next: 'Next',
                        last: 'Last'
                    }
                }
            });

            console.log('[AgencyDataTable] DataTable initialized');
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            const self = this;

            /**
             * Handle row click to open detail panel
             *
             * DEPRECATED (TODO-3080): Row click now handled by wpapp-panel-manager.js
             * Disabled to prevent conflict with NEW panel system
             * OLD event 'wpapp:open-panel' was for panel-handler.js (now disabled)
             * NEW system (wpapp-panel-manager.js) handles row click automatically
             */
            /*
            $('#agency-list-table tbody').on('click', 'tr', function() {
                const data = self.table.row(this).data();

                if (data && data.DT_RowData && data.DT_RowData.id) {
                    console.log('[AgencyDataTable] Row clicked, opening panel for ID:', data.DT_RowData.id);

                    // Trigger event for base panel system
                    $(document).trigger('wpapp:open-panel', {
                        id: data.DT_RowData.id,
                        entity: 'agency'
                    });
                }
            });
            */

            /**
             * Handle edit button click
             */
            $(document).on('click', '.wpapp-edit-agency', function(e) {
                e.stopPropagation(); // Prevent row click

                const agencyId = $(this).data('id');
                console.log('[AgencyDataTable] Edit button clicked for ID:', agencyId);

                // TODO: Open edit modal or navigate to edit page
                console.log('[AgencyDataTable] Edit functionality not yet implemented');
            });

            /**
             * Handle delete button click
             */
            $(document).on('click', '.wpapp-delete-agency', function(e) {
                e.stopPropagation(); // Prevent row click

                const agencyId = $(this).data('id');
                const confirmMessage = wpAgencyDataTable.i18n?.confirmDelete ||
                                     'Are you sure you want to delete this agency?';

                if (!confirm(confirmMessage)) {
                    return;
                }

                console.log('[AgencyDataTable] Delete button clicked for ID:', agencyId);

                // TODO: Implement delete functionality
                console.log('[AgencyDataTable] Delete functionality not yet implemented');
            });

            console.log('[AgencyDataTable] Events bound');
        },

        /**
         * Refresh DataTable
         */
        refresh() {
            if (this.table) {
                console.log('[AgencyDataTable] Refreshing table...');
                this.table.ajax.reload(null, false); // Keep current page
            }
        },

        /**
         * Destroy DataTable instance
         */
        destroy() {
            if (this.table) {
                console.log('[AgencyDataTable] Destroying table...');
                this.table.destroy();
                this.table = null;
                this.initialized = false;
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        AgencyDataTable.init();
    });

    /**
     * Expose to global scope for external access
     */
    window.AgencyDataTable = AgencyDataTable;

})(jQuery);
