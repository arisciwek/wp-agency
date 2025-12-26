/**
 * New Company Assignment Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Company
 * @version     4.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/company/new-company-datatable.js
 *
 * Description: Handler untuk assign agency, division, dan inspector menggunakan WPModal system.
 *              Implements cascade dropdowns for all-in-one assignment.
 *              DataTable initialization handled by agency-datatable.js.
 *              Modal managed by wp-modal plugin (WPModal API).
 *
 * Dependencies:
 * - jQuery
 * - WPModal (wp-modal plugin)
 * - agency-datatable.js (handles table initialization)
 * - wpAgencyNewCompany object (localized data)
 *
 * Changelog:
 * 4.0.0 - 2025-01-13
 * - BREAKING: All-in-One Assignment (agency + division + inspector)
 * - Implemented cascade dropdowns (Agency → Division → Inspector)
 * - Agency auto-detected from page context and pre-selected (disabled)
 * - Auto-load divisions on modal open
 * - Added loadAgencies(), loadDivisions(), loadInspectorsByDivision()
 * - Updated handleAssignment() to submit all 3 fields
 * - Modal title changed to "Assign to Agency"
 * 3.0.0 - 2025-01-13
 * - BREAKING: Migrated to WPModal system (wp-modal plugin)
 * - Removed custom modal HTML and management
 * - Use WPModal.show() API for modal display
 * - Cleaner code, better maintainability
 * 2.0.0 - 2025-01-13
 * - Removed table initialization (now in agency-datatable.js)
 * 1.0.0 - 2025-01-13
 * - Initial implementation
 */

(function($) {
    'use strict';

    const NewCompanyAssignment = {
        initialized: false,
        selectedBranchId: null,
        selectedCompanyName: null,
        inspectorsList: [],

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

            if (typeof WPModal === 'undefined') {
                console.error('[NewCompanyAssignment] WPModal not found - wp-modal plugin required');
                return;
            }

            this.bindEvents();
            this.initialized = true;

            console.log('[NewCompanyAssignment] Initialized successfully');
        },

        /**
         * Get agency ID from table data attribute
         */
        getAgencyId() {
            const $table = $('#new-companies-datatable');
            if ($table.length > 0) {
                return parseInt($table.attr('data-agency-id')) || 0;
            }
            return 0;
        },

        bindEvents() {
            const self = this;

            console.log('[NewCompanyAssignment] Binding events...');

            // Event delegation for assign button on the DataTable
            $(document)
                .off('click.newcompany', '#new-companies-datatable .assign-inspector')
                .on('click.newcompany', '#new-companies-datatable .assign-inspector', function(e) {
                    e.preventDefault();
                    e.stopPropagation();

                    const id = $(this).data('id');
                    const companyName = $(this).data('company');

                    console.log('[NewCompanyAssignment] Assign button clicked:', { id, companyName });

                    if (id) {
                        self.showAssignModal(id, companyName);
                    }
                });

            // Listen for inspector assignment success to reload table
            $(document)
                .off('inspector:assigned.newcompany')
                .on('inspector:assigned.newcompany', function(e, branchId, inspectorId) {
                    console.log('[NewCompanyAssignment] Inspector assigned, reloading table...');
                    self.reloadTable();
                });

            console.log('[NewCompanyAssignment] Events bound successfully');
        },

        showAssignModal(branchId, companyName) {
            const self = this;

            console.log('[NewCompanyAssignment] Opening assign modal:', { branchId, companyName });

            this.selectedBranchId = branchId;
            this.selectedCompanyName = companyName;

            // Get current agency ID from context
            const currentAgencyId = this.getAgencyId();

            // Load form via AJAX
            $.ajax({
                url: wpAgencyNewCompany.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_assignment_form',
                    branch_id: branchId,
                    company_name: companyName,
                    agency_id: currentAgencyId,
                    nonce: wpAgencyNewCompany.nonce
                },
                success: function(response) {
                    console.log('[NewCompanyAssignment] Form loaded:', response);

                    if (response.success && response.data.html) {
                        // Show modal using WPModal API
                        WPModal.show({
                            type: 'form',
                            title: 'Assign to Agency',
                            body: response.data.html,
                            size: 'medium',
                            buttons: {
                                cancel: {
                                    label: 'Batal',
                                    class: 'button',
                                    action: 'cancel'
                                },
                                submit: {
                                    label: 'Assign',
                                    class: 'button button-primary',
                                    type: 'submit',
                                    action: 'submit'
                                }
                            },
                            onSubmit: function() {
                                return self.handleAssignment();
                            }
                        });

                        console.log('[NewCompanyAssignment] Modal displayed');

                        // Wait for modal to be rendered, then bind events
                        setTimeout(function() {
                            // Bind cascade dropdown events
                            self.bindModalEvents();

                            // Auto-load divisions for current agency
                            if (currentAgencyId) {
                                console.log('[NewCompanyAssignment] Auto-loading divisions for agency:', currentAgencyId);
                                self.loadDivisions(currentAgencyId);
                            }
                        }, 100);
                    } else {
                        alert('Error loading form: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    console.error('[NewCompanyAssignment] Error loading form:', xhr);
                    alert('Error: ' + (xhr.responseJSON?.data?.message || 'Failed to load form'));
                }
            });
        },

        bindModalEvents() {
            const self = this;

            // Division change → Load inspectors
            $('#division-select').off('change').on('change', function() {
                const divisionId = $(this).val();

                // Reset inspector dropdown
                $('#inspector-select').html('<option value="">Memuat pengawas...</option>').prop('disabled', true);
                $('#inspector-info').hide();

                if (divisionId) {
                    self.loadInspectorsByDivision(divisionId);
                } else {
                    $('#inspector-select').html('<option value="">-- Pilih Unit Kerja Dulu --</option>');
                }
            });

            // Inspector change → Show assignment count
            $('#inspector-select').off('change').on('change', function() {
                self.onInspectorChange();
            });
        },

        loadDivisions(agencyId) {
            const self = this;

            console.log('[NewCompanyAssignment] Loading divisions for agency:', agencyId);

            $.ajax({
                url: wpAgencyNewCompany.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_divisions_by_agency',
                    agency_id: agencyId,
                    nonce: wpAgencyNewCompany.nonce
                },
                success: function(response) {
                    console.log('[NewCompanyAssignment] Divisions loaded:', response);

                    const $select = $('#division-select');
                    $select.empty().append('<option value="">-- Pilih Unit Kerja --</option>');

                    if (response.success && response.data.divisions && response.data.divisions.length > 0) {
                        response.data.divisions.forEach(function(division) {
                            $select.append('<option value="' + division.id + '">' + division.name + '</option>');
                        });
                        $select.prop('disabled', false);
                    } else {
                        $select.append('<option value="" disabled>Tidak ada unit kerja tersedia</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[NewCompanyAssignment] Failed to load divisions:', error);
                    $('#division-select').html('<option value="" disabled>Gagal memuat unit kerja</option>');
                }
            });
        },

        loadInspectorsByDivision(divisionId) {
            const self = this;

            console.log('[NewCompanyAssignment] Loading inspectors for division:', divisionId);

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

                    const $select = $('#inspector-select');
                    $select.empty().append('<option value="">-- Pilih Pengawas --</option>');

                    if (response.success && response.data.inspectors && response.data.inspectors.length > 0) {
                        response.data.inspectors.forEach(function(inspector) {
                            const count = inspector.assignment_count || 0;
                            $select.append('<option value="' + inspector.value + '" data-count="' + count + '">' + inspector.label + ' (' + count + ' penugasan)</option>');
                        });
                        $select.prop('disabled', false);
                    } else {
                        $select.append('<option value="" disabled>Tidak ada pengawas tersedia</option>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[NewCompanyAssignment] Failed to load inspectors:', error);
                    $('#inspector-select').html('<option value="" disabled>Gagal memuat pengawas</option>');
                }
            });
        },

        loadInspectors(branchId, callback) {
            // DEPRECATED: Old method, kept for backward compatibility
            const self = this;
            const agencyId = this.getAgencyId();

            console.log('[NewCompanyAssignment] Loading inspectors for branch:', branchId, 'agency:', agencyId);

            $.ajax({
                url: wpAgencyNewCompany.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_available_inspectors',
                    agency_id: agencyId,
                    branch_id: branchId,
                    nonce: wpAgencyNewCompany.nonce
                },
                success: function(response) {
                    console.log('[NewCompanyAssignment] Inspectors loaded:', response);

                    if (response.success && response.data.inspectors) {
                        self.inspectorsList = response.data.inspectors;
                    } else {
                        self.inspectorsList = [];
                    }

                    if (callback) callback();
                },
                error: function(xhr, status, error) {
                    console.error('[NewCompanyAssignment] Failed to load inspectors:', error);
                    self.inspectorsList = [];
                    if (callback) callback();
                }
            });
        },

        onInspectorChange() {
            const inspectorId = $('#inspector-select').val();

            if (!inspectorId) {
                $('#inspector-info').hide();
                return;
            }

            // Get assignment count from the selected option's data attribute
            const $selectedOption = $('#inspector-select option:selected');
            const count = $selectedOption.data('count') || 0;

            $('#inspector-assignments-count').text('Pengawas ini saat ini memiliki ' + count + ' penugasan.');
            $('#inspector-info').show();
        },

        async handleAssignment() {
            const self = this;
            const branchId = $('#assign-branch-id').val();
            const agencyId = $('#assign-agency-id').val();
            const divisionId = $('#division-select').val();
            const inspectorId = $('#inspector-select').val();

            console.log('[NewCompanyAssignment] Handling assignment:', { branchId, agencyId, divisionId, inspectorId });

            if (!branchId || !agencyId || !divisionId || !inspectorId) {
                this.showToast('error', 'Silakan lengkapi semua field');
                return false;
            }

            // Disable submit button
            const $submitBtn = $('.wpmodal-footer button[data-action="submit"]');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('Processing...');

            try {
                const response = await $.ajax({
                    url: wpAgencyNewCompany.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'assign_inspector',
                        branch_id: branchId,
                        agency_id: agencyId,
                        division_id: divisionId,
                        inspector_id: inspectorId,
                        nonce: wpAgencyNewCompany.nonce
                    }
                });

                console.log('[NewCompanyAssignment] Assignment response:', response);

                if (response.success) {
                    // Close modal first
                    WPModal.hide();

                    // Show success message
                    self.showToast('success', response.data.message || 'Agency, division, dan inspector berhasil ditugaskan');

                    // Trigger event for table reload
                    $(document).trigger('inspector:assigned.newcompany', [branchId, inspectorId]);

                    return true;
                } else {
                    self.showToast('error', response.data.message || 'Gagal menugaskan agency, division, dan inspector');
                    $submitBtn.prop('disabled', false).html(originalText);
                    return false;
                }

            } catch (error) {
                console.error('[NewCompanyAssignment] Assignment error:', error);
                self.showToast('error', 'Terjadi kesalahan saat menugaskan');
                $submitBtn.prop('disabled', false).html(originalText);
                return false;
            }
        },

        reloadTable() {
            const $table = $('#new-companies-datatable');

            if ($.fn.DataTable.isDataTable($table)) {
                console.log('[NewCompanyAssignment] Reloading table...');
                $table.DataTable().ajax.reload(null, false);
            } else {
                console.warn('[NewCompanyAssignment] Table not initialized yet');
            }
        },

        showToast(type, message) {
            console.log('[NewCompanyAssignment] Toast [' + type + ']:', message);

            // Use AgencyToast if available
            if (typeof AgencyToast !== 'undefined') {
                if (type === 'success') {
                    AgencyToast.success(message);
                } else if (type === 'error') {
                    AgencyToast.error(message);
                } else {
                    AgencyToast.info(message);
                }
            } else {
                // Fallback to basic alert
                if (type === 'error' || type === 'success') {
                    alert(message);
                }
            }
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[NewCompanyAssignment] Document ready, initializing...');
        NewCompanyAssignment.init();
    });

    /**
     * Expose to global scope
     */
    window.NewCompanyAssignment = NewCompanyAssignment;

})(jQuery);
