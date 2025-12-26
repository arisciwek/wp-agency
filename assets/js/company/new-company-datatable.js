/**
 * New Company Assignment Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Company
 * @version     3.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/company/new-company-datatable.js
 *
 * Description: Handler untuk assign inspector menggunakan WPModal system.
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

            // Load inspectors first, then show modal
            this.loadInspectors(branchId, function() {
                // Build modal content
                const content = self.buildModalContent(companyName);

                // Show modal using WPModal API
                WPModal.show({
                    type: 'form',
                    title: 'Assign Inspector',
                    body: content,
                    size: 'medium',
                    buttons: {
                        cancel: {
                            label: 'Batal',
                            class: 'button',
                            data: { action: 'cancel' }
                        },
                        submit: {
                            label: 'Assign',
                            class: 'button button-primary',
                            data: { action: 'submit' }
                        }
                    },
                    onSubmit: function() {
                        self.handleAssignment();
                        return false; // Prevent modal from closing automatically
                    }
                });

                // Bind inspector select change event
                $('#inspector-select').off('change').on('change', function() {
                    self.onInspectorChange();
                });
            });
        },

        buildModalContent(companyName) {
            let html = '<form id="assign-inspector-form">';
            html += '<input type="hidden" id="assign-branch-id" value="' + this.selectedBranchId + '" />';

            html += '<div class="form-group" style="margin-bottom: 15px;">';
            html += '<label for="company-name-display">Perusahaan:</label>';
            html += '<input type="text" id="company-name-display" value="' + companyName + '" readonly class="regular-text" style="width: 100%;" />';
            html += '</div>';

            html += '<div class="form-group" style="margin-bottom: 15px;">';
            html += '<label for="inspector-select">Pilih Pengawas:</label>';
            html += '<select id="inspector-select" name="inspector_id" class="regular-text" required style="width: 100%;">';
            html += '<option value="">-- Pilih Pengawas --</option>';

            // Add inspector options
            if (this.inspectorsList && this.inspectorsList.length > 0) {
                this.inspectorsList.forEach(function(inspector) {
                    const count = inspector.assignment_count || 0;
                    html += '<option value="' + inspector.value + '" data-count="' + count + '">';
                    html += inspector.label + ' (' + count + ' penugasan)';
                    html += '</option>';
                });
            }

            html += '</select>';
            html += '<p class="description">Pilih karyawan yang akan menjadi pengawas untuk perusahaan ini.</p>';
            html += '</div>';

            html += '<div class="form-group" id="inspector-info" style="display: none; margin-bottom: 15px;">';
            html += '<div class="notice notice-info inline" style="padding: 10px;">';
            html += '<p id="inspector-assignments-count"></p>';
            html += '</div>';
            html += '</div>';

            html += '</form>';

            return html;
        },

        loadInspectors(branchId, callback) {
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

        handleAssignment() {
            const self = this;
            const branchId = $('#assign-branch-id').val();
            const inspectorId = $('#inspector-select').val();

            console.log('[NewCompanyAssignment] Handling assignment:', { branchId, inspectorId });

            if (!branchId || !inspectorId) {
                this.showToast('error', 'Silakan pilih pengawas');
                return;
            }

            // Disable submit button
            const $submitBtn = $('.wpmodal-footer button[data-action="submit"]');
            const originalText = $submitBtn.html();
            $submitBtn.prop('disabled', true).html('Processing...');

            $.ajax({
                url: wpAgencyNewCompany.ajax_url,
                type: 'POST',
                data: {
                    action: 'assign_inspector',
                    branch_id: branchId,
                    inspector_id: inspectorId,
                    nonce: wpAgencyNewCompany.nonce
                },
                success: function(response) {
                    console.log('[NewCompanyAssignment] Assignment response:', response);

                    if (response.success) {
                        self.showToast('success', response.data.message || 'Inspector berhasil ditugaskan');

                        // Close modal
                        WPModal.hide();

                        // Trigger event for table reload
                        $(document).trigger('inspector:assigned.newcompany', [branchId, inspectorId]);
                    } else {
                        self.showToast('error', response.data.message || 'Gagal menugaskan inspector');
                        $submitBtn.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('[NewCompanyAssignment] Assignment error:', error);
                    self.showToast('error', 'Terjadi kesalahan saat menugaskan inspector');
                    $submitBtn.prop('disabled', false).html(originalText);
                }
            });
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
