/**
 * New Company Assignment Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Company
 * @version     6.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/company/new-company-datatable.js
 *
 * Description: Handler untuk cascade dropdowns dalam assign inspector form.
 *              Uses Auto-Wire modal system for all modal operations.
 *              DataTable initialization handled by agency-datatable.js.
 *              Modal managed by auto-wire modal system (wp-datatable/modal-integration.js).
 *
 * Dependencies:
 * - jQuery
 * - agency-datatable.js (handles table initialization)
 * - wp-datatable/modal-integration.js (auto-wire system)
 * - wpAgencyNewCompany object (localized data)
 *
 * Changelog:
 * 6.0.0 - 2026-01-02 (CRITICAL FIX: Remove Custom Modal Handler)
 * - REMOVED: showAssignModal() method (conflicts with auto-wire)
 * - REMOVED: handleAssignment() method (auto-wire handles submit)
 * - REMOVED: All manual AJAX calls for modal/form
 * - SIMPLIFIED: Only setup cascade dropdowns and table reload
 * - FIX: Modal now opens correctly via auto-wire system
 * - BENEFIT: No conflict, clean separation of concerns
 * 5.0.0 - 2026-01-02 (Auto-Wire Migration - INCOMPLETE)
 * - Attempted migration but kept custom handlers (caused bugs)
 * 4.0.0 - 2025-01-13
 * - BREAKING: All-in-One Assignment (agency + division + inspector)
 * - Implemented cascade dropdowns (Agency → Division → Inspector)
 * 3.0.0 - 2025-01-13
 * - BREAKING: Migrated to WPModal system (wp-modal plugin)
 */

(function($) {
    'use strict';

    const NewCompanyAssignment = {
        initialized: false,

        init() {
            if (this.initialized) {
                console.log('[NewCompanyAssignment] Already initialized');
                return;
            }

            console.log('[NewCompanyAssignment] Initializing...');

            // Check dependencies
            if (typeof wpAgencyNewCompany === 'undefined') {
                console.error('[NewCompanyAssignment] wpAgencyNewCompany not found');
                return;
            }

            this.bindEvents();
            this.initialized = true;

            console.log('[NewCompanyAssignment] Initialized successfully');
            console.log('[NewCompanyAssignment] Modal handling: AUTO-WIRE SYSTEM');
            console.log('[NewCompanyAssignment] This script only handles: cascade dropdowns + table reload');
        },

        bindEvents() {
            const self = this;

            console.log('[NewCompanyAssignment] Binding events...');

            // AUTO-WIRE SYSTEM: Listen to modal opened event
            // Auto-wire handles: button click → load form → show modal → submit
            // We only setup cascade dropdowns after modal opens
            $(document)
                .off('wpmodal:modal-opened.newcompany')
                .on('wpmodal:modal-opened.newcompany', function(e, modalData) {
                    console.log('[NewCompanyAssignment] Modal opened event received');

                    // Poll for division-select element (AJAX content loads after modal opened)
                    self.waitForElement('#division-select', function($element) {
                        console.log('[NewCompanyAssignment] Assignment form detected, setting up cascade');
                        self.setupCascadeDropdowns();
                    }, 3000);
                });

            // Listen for successful assignment from auto-wire system
            // Event triggered by modal-integration.js after successful submit
            $(document)
                .off('wpdt:entity-updated.newcompany')
                .on('wpdt:entity-updated.newcompany', function(e, data) {
                    if (data.entity === 'new-company') {
                        console.log('[NewCompanyAssignment] Inspector assigned, reloading table');
                        self.reloadTable();
                    }
                });

            console.log('[NewCompanyAssignment] Events bound successfully');
        },

        /**
         * Wait for element to appear in DOM
         * Uses polling with configurable timeout
         */
        waitForElement(selector, callback, timeout = 3000) {
            const startTime = Date.now();
            const interval = 50; // Check every 50ms

            console.log('[NewCompanyAssignment] Waiting for element:', selector);

            const checkElement = () => {
                const $element = $(selector);

                if ($element.length > 0) {
                    console.log('[NewCompanyAssignment] Element found:', selector);
                    clearInterval(intervalId);
                    callback($element);
                    return;
                }

                // Check timeout
                if (Date.now() - startTime > timeout) {
                    console.warn('[NewCompanyAssignment] Timeout waiting for element:', selector);
                    clearInterval(intervalId);
                    return;
                }
            };

            const intervalId = setInterval(checkElement, interval);
            checkElement(); // Check immediately once
        },

        /**
         * Setup cascade dropdowns after modal opened
         * Division → Inspector cascade
         */
        setupCascadeDropdowns() {
            const self = this;

            console.log('[NewCompanyAssignment] Setting up cascade dropdowns');

            const $divisionSelect = $('#division-select');
            const $inspectorSelect = $('#inspector-select');

            if ($divisionSelect.length === 0 || $inspectorSelect.length === 0) {
                console.error('[NewCompanyAssignment] Dropdown(s) not found!');
                return;
            }

            // Division change handler - load inspectors
            $divisionSelect.off('change.cascade').on('change.cascade', function() {
                const divisionId = $(this).val();

                console.log('[NewCompanyAssignment] Division changed:', divisionId);

                if (!divisionId) {
                    $inspectorSelect
                        .html('<option value="">-- Pilih Unit Kerja Dulu --</option>')
                        .prop('disabled', true);
                    $('#inspector-info').hide();
                    return;
                }

                // Show loading state
                $inspectorSelect
                    .html('<option value="">Memuat pengawas...</option>')
                    .prop('disabled', true);

                self.loadInspectorsByDivision(divisionId);
            });

            // Inspector change handler - show assignment count
            $inspectorSelect.off('change.cascade').on('change.cascade', function() {
                const inspectorId = $(this).val();

                if (!inspectorId) {
                    $('#inspector-info').hide();
                    return;
                }

                const $option = $(this).find('option:selected');
                const assignedCount = $option.data('count') || 0;

                $('#inspector-assignments-count').text(
                    'Pengawas ini saat ini bertanggung jawab atas ' + assignedCount + ' perusahaan.'
                );
                $('#inspector-info').show();
            });

            console.log('[NewCompanyAssignment] Cascade dropdowns setup complete');
        },

        /**
         * Load inspectors by division
         */
        loadInspectorsByDivision(divisionId) {
            const self = this;

            $.ajax({
                url: wpAgencyNewCompany.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_inspectors_by_division',
                    division_id: divisionId,
                    nonce: wpAgencyNewCompany.nonce
                },
                success: function(response) {
                    console.log('[NewCompanyAssignment] Inspectors loaded:', response);

                    const $inspectorSelect = $('#inspector-select');

                    if (response.success && response.data.inspectors) {
                        const inspectors = response.data.inspectors;

                        if (inspectors.length === 0) {
                            $inspectorSelect
                                .html('<option value="">-- Tidak Ada Pengawas --</option>')
                                .prop('disabled', true);
                            return;
                        }

                        // Build options
                        let options = '<option value="">-- Pilih Pengawas --</option>';
                        inspectors.forEach(function(inspector) {
                            options += '<option value="' + inspector.value + '" data-count="' +
                                      inspector.assignment_count + '">' + inspector.label + '</option>';
                        });

                        $inspectorSelect
                            .html(options)
                            .prop('disabled', false);

                        console.log('[NewCompanyAssignment] ' + inspectors.length + ' inspectors loaded');
                    } else {
                        $inspectorSelect
                            .html('<option value="">-- Error Loading --</option>')
                            .prop('disabled', true);
                        console.error('[NewCompanyAssignment] Failed to load inspectors');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[NewCompanyAssignment] AJAX error:', error);
                    $('#inspector-select')
                        .html('<option value="">-- Error Loading --</option>')
                        .prop('disabled', true);
                }
            });
        },

        /**
         * Reload DataTable after successful assignment
         */
        reloadTable() {
            const $table = $('#new-companies-datatable');
            if ($table.length > 0 && $.fn.DataTable.isDataTable('#new-companies-datatable')) {
                console.log('[NewCompanyAssignment] Reloading DataTable');
                $table.DataTable().ajax.reload(null, false); // Keep current page
            }
        }
    };

    // Initialize when document ready
    $(document).ready(function() {
        NewCompanyAssignment.init();
    });

})(jQuery);
