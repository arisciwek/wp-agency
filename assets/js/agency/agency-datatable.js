/**
 * Agency DataTable Handler - Base Panel System Integration
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Agency
 * @version     2.2.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/agency/agency-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables agency.
 *              Terintegrasi dengan base panel system dari wp-app-core.
 *              Menangani server-side processing dan event handling.
 *
 * Changelog:
 * 2.2.0 - 2025-11-01 (TODO-3097)
 * - Added support for new-company entity (Tab 4: Perusahaan Baru)
 * - Added column configuration for branches without inspector
 * - Columns: code, company_name, division_name, regency_name
 * - Integrated with lazy-load DataTable pattern
 * 2.1.0 - 2025-10-27
 * - Added lazy-load DataTable initialization (Review-01 task-1185)
 * - Handles divisions and employees DataTables
 * - Event-driven pattern using data-* attributes
 * - Removes inline JavaScript from PHP templates
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
            this.watchForLazyTables();

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
                },

                /**
                 * Callback after DataTable draw
                 * Updates statistics cards with real count from DataTable response
                 */
                drawCallback: function(settings) {
                    const json = settings.json;

                    if (json) {
                        // Update statistics from DataTable response (no duplicate query!)
                        self.updateStatistics(json);
                    }
                }
            });

            console.log('[AgencyDataTable] DataTable initialized');
        },

        /**
         * Update statistics cards from DataTable response
         *
         * Reuses count from DataTable instead of separate COUNT query
         *
         * @param {Object} json - DataTable AJAX response
         * @param {number} json.recordsTotal - Total records (unfiltered)
         * @param {number} json.recordsFiltered - Filtered records (with access control)
         */
        updateStatistics(json) {
            console.log('[AgencyDataTable] Updating statistics from DataTable response');
            console.log('[AgencyDataTable] Records Total:', json.recordsTotal);
            console.log('[AgencyDataTable] Records Filtered:', json.recordsFiltered);

            // Update "Total Disnaker" card with filtered count
            // This reflects actual accessible records for current user
            const $totalCard = $('.agency-stat-card[data-card-id="total-agencies"] .agency-stat-number');
            if ($totalCard.length) {
                $totalCard.text(json.recordsFiltered || 0);
                console.log('[AgencyDataTable] Updated Total card:', json.recordsFiltered);
            }

            // Note: Active/Inactive counts would need additional data from response
            // For now, we only update total count to avoid inconsistency
            // TODO: Add status breakdown in DataTable response if needed
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
         * Watch for lazy-load tables being added to DOM
         *
         * Uses MutationObserver to detect when AJAX content containing
         * .agency-lazy-datatable tables is loaded into the page.
         */
        watchForLazyTables() {
            const self = this;

            // Create observer to watch for lazy-load tables
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    // Check if new nodes were added
                    if (mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            // Only process element nodes
                            if (node.nodeType === 1) {
                                const $node = $(node);

                                // Check if the node itself is a lazy table
                                if ($node.hasClass('agency-lazy-datatable')) {
                                    console.log('[AgencyDataTable] Lazy table detected in DOM');
                                    self.initLazyDataTables($node.parent());
                                }
                                // Or if it contains lazy tables
                                else if ($node.find('.agency-lazy-datatable').length > 0) {
                                    console.log('[AgencyDataTable] Container with lazy table(s) detected in DOM');
                                    self.initLazyDataTables($node);
                                }
                            }
                        });
                    }
                });
            });

            // Start observing the document body for changes
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            console.log('[AgencyDataTable] Watching for lazy-load tables');
        },

        /**
         * Initialize lazy-load DataTables (divisions, employees)
         *
         * Called when AJAX content is loaded into tab panels.
         * Uses data-* attributes for configuration instead of inline scripts.
         *
         * @param {jQuery} $container - Container element to search within
         */
        initLazyDataTables($container) {
            const self = this;
            const $lazyTables = $container.find('.agency-lazy-datatable');

            if ($lazyTables.length === 0) {
                return;
            }

            console.log('[AgencyDataTable] Found ' + $lazyTables.length + ' lazy-load table(s)');

            $lazyTables.each(function() {
                const $table = $(this);
                const tableId = $table.attr('id');

                // Skip if already initialized
                if ($.fn.DataTable.isDataTable('#' + tableId)) {
                    console.log('[AgencyDataTable] Table already initialized:', tableId);
                    return;
                }

                // Read configuration from data-* attributes (use .attr() to avoid jQuery caching)
                const entity = $table.attr('data-entity');
                const agencyId = $table.attr('data-agency-id');
                const ajaxAction = $table.attr('data-ajax-action');

                console.log('[AgencyDataTable] Initializing lazy table:', {
                    tableId: tableId,
                    entity: entity,
                    agencyId: agencyId,
                    ajaxAction: ajaxAction
                });

                // Get column configuration based on entity type
                const columns = self.getLazyTableColumns(entity);

                // Initialize DataTable
                const dataTable = $table.DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: {
                        url: wpAgencyDataTable.ajaxurl,
                        type: 'POST',
                        data: function(d) {
                            d.action = ajaxAction;
                            d.agency_id = agencyId;
                            d.nonce = wpAgencyDataTable.nonce;

                            // Include status filter for divisions table
                            if (entity === 'division') {
                                const $statusFilter = $('#division-status-filter');
                                if ($statusFilter.length > 0) {
                                    d.status_filter = $statusFilter.val();
                                }
                            }

                            return d;
                        },
                        error: function(xhr, error, code) {
                            console.error('[AgencyDataTable] AJAX Error for ' + tableId + ':', error, code);
                            console.error('[AgencyDataTable] Response:', xhr.responseText);
                        },
                        dataSrc: function(json) {
                            console.log('[AgencyDataTable] DataTable Response for ' + tableId + ':', json);
                            console.log('[AgencyDataTable] Records Total:', json.recordsTotal);
                            console.log('[AgencyDataTable] Records Filtered:', json.recordsFiltered);
                            console.log('[AgencyDataTable] Data rows:', json.data ? json.data.length : 0);
                            if (json.data && json.data.length > 0) {
                                console.log('[AgencyDataTable] First row sample:', json.data[0]);
                            }
                            return json.data;
                        }
                    },
                    columns: columns,
                    pageLength: 10,
                    lengthMenu: [[10, 25, 50], [10, 25, 50]],
                    language: wpAgencyDataTable.i18n || {}
                });

                console.log('[AgencyDataTable] Lazy table initialized:', tableId);

                // Initialize status filter handler for divisions
                if (entity === 'division') {
                    self.initStatusFilter(dataTable);
                }
            });
        },

        /**
         * Initialize status filter for divisions table
         *
         * @param {DataTable} dataTable DataTable instance
         */
        initStatusFilter(dataTable) {
            const $filter = $('#division-status-filter');

            if ($filter.length === 0) {
                return;
            }

            console.log('[AgencyDataTable] Initializing status filter');

            $filter.on('change', function() {
                const status = $(this).val();
                console.log('[AgencyDataTable] Status filter changed:', status);

                // Reload table with new status filter
                dataTable.ajax.reload();
            });
        },

        /**
         * Get column configuration for lazy-load tables
         *
         * @param {string} entity - Entity type (division, employee, new-company)
         * @return {Array} Column configuration for DataTable
         */
        getLazyTableColumns(entity) {
            switch(entity) {
                case 'division':
                    return [
                        { data: 'code', width: '15%' },
                        { data: 'name', width: '40%' },
                        { data: 'wilayah_kerja', width: '45%' }
                    ];

                case 'employee':
                    return [
                        { data: 'name' },
                        { data: 'position' },
                        { data: 'email' },
                        { data: 'phone' },
                        { data: 'status' }
                    ];

                case 'new-company':
                    return [
                        { data: 'code', width: '15%' },
                        { data: 'company_name', width: '25%' },
                        { data: 'division_name', width: '20%' },
                        { data: 'regency_name', width: '25%' },
                        {
                            data: 'actions',
                            width: '15%',
                            orderable: false,
                            searchable: false,
                            className: 'text-center'
                        }
                    ];

                default:
                    console.warn('[AgencyDataTable] Unknown entity type:', entity);
                    return [];
            }
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
