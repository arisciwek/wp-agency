/**
 * Company Reassignment Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/company/company-reassign.js
 *
 * Description: Handle modal untuk ganti unit kerja & pengawas di company detail panel.
 *              Reuse cascade dropdown pattern dari new-company-datatable.js
 *              Terintegrasi dengan wp-customer company detail panel.
 *
 * Dependencies:
 * - jQuery
 * - WPModal plugin
 * - wp-agency AJAX endpoints
 *
 * Changelog:
 * 1.0.0 - 2025-12-26
 * - Initial implementation
 * - Cascade dropdown for division & inspector
 * - Auto-detect agency from current assignment
 * - AJAX save to update_company_assignment
 */

(function($) {
    'use strict';

    const CompanyReassign = {
        currentBranchId: null,
        currentAgencyId: null,

        init() {
            console.log('[CompanyReassign] Initializing...');
            this.bindEvents();
        },

        bindEvents() {
            // Click tombol "Ganti Unit Kerja & Pengawas"
            $(document).on('click', '.wp-agency-reassign-btn', (e) => {
                e.preventDefault();
                const $btn = $(e.currentTarget);

                this.currentBranchId = $btn.data('branch-id');
                this.currentAgencyId = $btn.data('agency-id');
                const companyName = $btn.data('company-name');
                const currentDivision = $btn.data('current-division');
                const currentInspector = $btn.data('current-inspector');

                console.log('[CompanyReassign] Opening modal for branch:', this.currentBranchId);
                console.log('[CompanyReassign] Agency ID from button:', this.currentAgencyId);

                this.showReassignModal(companyName, currentDivision, currentInspector);
            });
        },

        showReassignModal(companyName, currentDivision, currentInspector) {
            const self = this;

            console.log('[CompanyReassign] showReassignModal called with:', {
                companyName: companyName,
                currentDivision: currentDivision,
                currentInspector: currentInspector,
                agencyId: this.currentAgencyId
            });

            // Validate agency ID
            if (!this.currentAgencyId) {
                alert('Error: Agency ID not found. Please refresh the page.');
                return;
            }

            // Load form via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_reassign_form',
                    agency_id: this.currentAgencyId,
                    company_name: companyName,
                    current_division_id: currentDivision,
                    current_inspector_id: currentInspector,
                    nonce: wpdtConfig.nonce
                },
                success: (response) => {
                    console.log('[CompanyReassign] Form loaded:', response);

                    if (response.success && response.data.html) {
                        // Show modal with loaded form
                        WPModal.show({
                            type: 'form',
                            title: 'Ganti Unit Kerja & Pengawas',
                            body: response.data.html,
                            size: 'medium',
                            buttons: {
                                cancel: {
                                    label: 'Batal',
                                    class: 'button',
                                    action: 'cancel'
                                },
                                submit: {
                                    label: 'Simpan',
                                    class: 'button button-primary',
                                    type: 'submit',
                                    action: 'submit'
                                }
                            },
                            onSubmit: (formData, $form) => {
                                return self.handleSave();
                            }
                        });

                        console.log('[CompanyReassign] Modal displayed');

                        // Wait for modal to be rendered, then bind events
                        setTimeout(() => {
                            // Bind cascade dropdown events
                            self.bindModalEvents();

                            // Trigger division change to load inspectors
                            if (currentDivision) {
                                $('#reassign-division').trigger('change');
                            }
                        }, 100);
                    } else {
                        alert('Error loading form: ' + (response.data?.message || 'Unknown error'));
                    }
                },
                error: (xhr) => {
                    console.error('[CompanyReassign] Error loading form:', xhr);
                    alert('Error: ' + (xhr.responseJSON?.data?.message || 'Failed to load form'));
                }
            });
        },

        buildModalContent(companyName) {
            return `
                <div class="wp-agency-reassign-form">
                    <p style="margin-bottom: 20px;">
                        <strong>Perusahaan:</strong> ${companyName}
                    </p>

                    <div class="form-row">
                        <label for="reassign-division">
                            Unit Kerja <span class="required">*</span>
                        </label>
                        <select id="reassign-division" class="widefat" required>
                            <option value="">Pilih Unit Kerja...</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <label for="reassign-inspector">
                            Pengawas
                        </label>
                        <select id="reassign-inspector" class="widefat">
                            <option value="">Pilih Pengawas...</option>
                        </select>
                    </div>
                </div>

                <style>
                .wp-agency-reassign-form {
                    padding: 10px 0;
                }
                .wp-agency-reassign-form .form-row {
                    margin-bottom: 15px;
                }
                .wp-agency-reassign-form label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                }
                .wp-agency-reassign-form .required {
                    color: #dc3232;
                }
                .wp-agency-reassign-form select {
                    width: 100%;
                    max-width: 100%;
                }
                </style>
            `;
        },

        bindModalEvents() {
            const self = this;

            // Division change -> load inspectors
            $('#reassign-division').off('change').on('change', function() {
                const divisionId = $(this).val();
                console.log('[CompanyReassign] Division changed to:', divisionId);

                // Reset inspector dropdown
                $('#reassign-inspector').html('<option value="">Memuat pengawas...</option>').prop('disabled', true);
                $('#inspector-info').hide();

                if (divisionId) {
                    self.loadInspectors(divisionId);
                } else {
                    $('#reassign-inspector').html('<option value="">-- Pilih Unit Kerja Dulu --</option>');
                }
            });

            // Inspector change -> show assignment count
            $('#reassign-inspector').off('change').on('change', function() {
                self.onInspectorChange();
            });
        },

        getAgencyFromDivision(divisionId, callback) {
            console.log('[CompanyReassign] getAgencyFromDivision called with:', divisionId);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_agency_divisions',
                    nonce: wpdtConfig.nonce
                },
                success: (response) => {
                    console.log('[CompanyReassign] getAgencyFromDivision response:', response);

                    if (response.success && response.data) {
                        const division = response.data.find(d => d.id == divisionId);
                        console.log('[CompanyReassign] Found division:', division);

                        if (division && callback) {
                            callback(division.agency_id);
                        } else {
                            console.error('[CompanyReassign] Division not found in response');
                            alert('Error: Division not found');
                        }
                    } else {
                        console.error('[CompanyReassign] Invalid response:', response);
                        alert('Error loading agency data');
                    }
                },
                error: (xhr) => {
                    console.error('[CompanyReassign] AJAX Error getting agency:', xhr);
                    alert('Error: ' + (xhr.responseJSON?.data?.message || 'Failed to load agency data'));
                }
            });
        },

        loadDivisions(agencyId, callback) {
            console.log('[CompanyReassign] loadDivisions called with agency:', agencyId);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_agency_divisions',
                    agency_id: agencyId,
                    nonce: wpdtConfig.nonce
                },
                success: (response) => {
                    console.log('[CompanyReassign] loadDivisions response:', response);

                    if (response.success && response.data) {
                        const $select = $('#reassign-division');
                        $select.html('<option value="">Pilih Unit Kerja...</option>');

                        response.data.forEach(division => {
                            $select.append(`<option value="${division.id}">${division.name}</option>`);
                        });

                        console.log('[CompanyReassign] Divisions populated, count:', response.data.length);

                        if (callback) callback();
                    } else {
                        console.error('[CompanyReassign] Invalid divisions response:', response);
                        alert('Error loading divisions');
                    }
                },
                error: (xhr) => {
                    console.error('[CompanyReassign] AJAX Error loading divisions:', xhr);
                    alert('Error: ' + (xhr.responseJSON?.data?.message || 'Failed to load divisions'));
                }
            });
        },

        loadInspectors(divisionId) {
            const $select = $('#reassign-inspector');
            const currentInspectorId = $('#current-inspector-id').val();

            $select.html('<option value="">Memuat pengawas...</option>').prop('disabled', true);
            $('#inspector-info').hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_inspectors_by_division',
                    division_id: divisionId,
                    nonce: wpdtConfig.nonce
                },
                success: (response) => {
                    console.log('[CompanyReassign] Inspectors loaded:', response);

                    $select.html('<option value="">-- Pilih Pengawas --</option>');

                    if (response.success && response.data && response.data.inspectors && response.data.inspectors.length > 0) {
                        response.data.inspectors.forEach(inspector => {
                            const count = inspector.assignment_count || 0;
                            const selected = (currentInspectorId && inspector.value == currentInspectorId) ? ' selected' : '';
                            $select.append(`<option value="${inspector.value}" data-count="${count}"${selected}>${inspector.label} (${count} penugasan)</option>`);
                        });

                        $select.prop('disabled', false);

                        // Auto-select current inspector if exists
                        if (currentInspectorId) {
                            $select.val(currentInspectorId).trigger('change');
                        }
                    } else {
                        $select.html('<option value="">-- Tidak Ada Pengawas --</option>');
                    }
                },
                error: (xhr) => {
                    console.error('[CompanyReassign] Error loading inspectors:', xhr);
                    $select.html('<option value="">-- Error Loading --</option>');
                }
            });
        },

        onInspectorChange() {
            const $selected = $('#reassign-inspector option:selected');
            const assignmentCount = $selected.data('count');

            if (assignmentCount !== undefined && $selected.val()) {
                $('#inspector-assignments-count').text(`Pengawas ini saat ini mengawasi ${assignmentCount} perusahaan.`);
                $('#inspector-info').show();
            } else {
                $('#inspector-info').hide();
            }
        },

        async handleSave() {
            const divisionId = $('#reassign-division').val();
            const inspectorId = $('#reassign-inspector').val();

            if (!divisionId) {
                alert('Unit Kerja harus dipilih!');
                return false;
            }

            console.log('[CompanyReassign] Saving assignment:', {
                branch_id: this.currentBranchId,
                division_id: divisionId,
                inspector_id: inspectorId
            });

            try {
                const response = await $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'update_company_assignment',
                        branch_id: this.currentBranchId,
                        division_id: divisionId,
                        inspector_id: inspectorId || '',
                        nonce: wpdtConfig.nonce
                    }
                });

                if (response.success) {
                    console.log('[CompanyReassign] Save successful');

                    // Close modal explicitly
                    if (typeof WPModal !== 'undefined') {
                        WPModal.hide();
                    }

                    // Show success message
                    if (typeof WPToast !== 'undefined') {
                        WPToast.success(response.data.message || 'Assignment updated successfully');
                    }

                    // Reload panel to show updated data
                    setTimeout(() => {
                        if (window.wpdtPanelManager) {
                            window.wpdtPanelManager.loadPanelData(this.currentBranchId);
                        }
                    }, 500);

                    return true;
                } else {
                    console.error('[CompanyReassign] Save failed:', response.data);

                    if (typeof WPToast !== 'undefined') {
                        WPToast.error(response.data?.message || 'Failed to update assignment');
                    } else {
                        alert(response.data?.message || 'Failed to update assignment');
                    }

                    return false;
                }

            } catch (error) {
                console.error('[CompanyReassign] Error saving:', error);

                let errorMessage = 'An error occurred while saving';
                if (error.responseJSON && error.responseJSON.data) {
                    errorMessage = error.responseJSON.data.message || errorMessage;
                }

                if (typeof WPToast !== 'undefined') {
                    WPToast.error(errorMessage);
                } else {
                    alert(errorMessage);
                }

                return false;
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CompanyReassign = CompanyReassign;
        CompanyReassign.init();
    });

})(jQuery);
