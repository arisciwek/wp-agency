/**
 * New Company DataTable Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Company
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/company/new-company-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables company yang belum memiliki inspector.
 *              Includes state management, inspector assignment,
 *              dan error handling yang lebih baik.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - AgencyToast for notifications
 *
 * Changelog:
 * 1.0.0 - 2025-01-13
 * - Initial implementation
 * - Added state management
 * - Added inspector assignment functionality
 * - Enhanced error handling
 * - Improved loading states
 */

(function($) {
    'use strict';

    const NewCompanyDataTable = {
        table: null,
        initialized: false,
        agencyId: null,
        $container: null,
        $tableContainer: null,
        $loadingState: null,
        $emptyState: null,
        $errorState: null,
        $assignModal: null,
        $viewModal: null,
        selectedBranchId: null,
        selectedCompanyName: null,

        init(agencyId) {
            // Cache DOM elements
            this.$container = $('#new-company');
            this.$tableContainer = this.$container.find('.wi-table-container');
            this.$loadingState = this.$container.find('.new-company-loading-state');
            this.$emptyState = this.$container.find('.new-company-empty-state');
            this.$errorState = this.$container.find('.new-company-error-state');
            this.$assignModal = $('#assign-inspector-modal');
            this.$viewModal = $('#view-company-modal');

            // Always reinitialize when called to ensure fresh data
            this.agencyId = agencyId;
            this.showLoading();
            this.initDataTable();
            this.bindEvents();
        },

        bindEvents() {
            // Modal events
            this.$assignModal.find('.wp-agency-modal-close, .wp-agency-modal-cancel')
                .off('click')
                .on('click', () => this.closeAssignModal());

            this.$viewModal.find('.wp-agency-modal-close')
                .off('click')
                .on('click', () => this.closeViewModal());

            // Confirm assignment button
            $('#confirm-assign-inspector')
                .off('click')
                .on('click', () => this.confirmAssignment());

            // Inspector selection change
            $('#inspector-select')
                .off('change')
                .on('change', (e) => this.onInspectorChange(e));

            // Reload button handler
            this.$errorState.find('.reload-table')
                .off('click')
                .on('click', () => this.refresh());

            // Event delegation for action buttons
            $('#new-company-table')
                .off('click', '.assign-inspector, .view-company')
                .on('click', '.assign-inspector', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    const companyName = $(e.currentTarget).data('company');
                    if (id) {
                        this.showAssignModal(id, companyName);
                    }
                })
                .on('click', '.view-company', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    if (id) {
                        this.showViewModal(id);
                    }
                });

            // CRUD event listeners
            $(document)
                .off('inspector:assigned.datatable')
                .on('inspector:assigned.datatable', () => this.refresh());
        },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#new-company-table')) {
                $('#new-company-table').DataTable().destroy();
            }

            $('#new-company-table').empty().html(`
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Perusahaan</th>
                        <th>Unit</th>
                        <th>Yuridiksi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            const self = this;
            this.table = $('#new-company-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    cache: false,
                    data: (d) => {
                        return {
                            ...d,
                            action: 'handle_new_company_datatable',
                            agency_id: this.agencyId,
                            nonce: wpAgencyData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('DataTables Error:', error);
                        this.showError();
                    },
                    dataSrc: function(response) {
                        if (!response.data || response.data.length === 0) {
                            self.showEmpty();
                        } else {
                            self.showTable();
                        }
                        return response.data;
                    }
                },
                columns: [
                    { data: 'code', width: '10%', className: 'column-code' },
                    { data: 'company_name', width: '30%', className: 'column-company' },
                    { data: 'division_name', width: '25%', className: 'column-division' },
                    { data: 'regency_name', width: '20%', className: 'column-regency' },
                    {
                        data: 'actions',
                        width: '15%',
                        orderable: false,
                        className: 'column-actions text-center'
                    }
                ],
                order: [[0, 'asc']],
                pageLength: wpAgencyData.perPage || 10,
                language: {
                    "emptyTable": "Tidak ada data yang tersedia",
                    "info": "Menampilkan _START_ hingga _END_ dari _TOTAL_ entri",
                    "infoEmpty": "Menampilkan 0 hingga 0 dari 0 entri",
                    "infoFiltered": "(disaring dari _MAX_ total entri)",
                    "lengthMenu": "Tampilkan _MENU_ entri",
                    "loadingRecords": "Memuat...",
                    "processing": "Memproses...",
                    "search": "Cari:",
                    "zeroRecords": "Tidak ditemukan data yang sesuai",
                    "paginate": {
                        "first": "Pertama",
                        "last": "Terakhir",
                        "next": "Selanjutnya",
                        "previous": "Sebelumnya"
                    }
                },
                drawCallback: (settings) => {
                    // Any post-draw actions
                }
            });

            this.initialized = true;
        },



        showAssignModal(branchId, companyName) {
            this.selectedBranchId = branchId;
            this.selectedCompanyName = companyName;

            $('#assign-branch-id').val(branchId);
            $('#company-name-display').val(companyName);
            $('#inspector-select').val('').trigger('change');
            $('#inspector-info').hide();

            // Load inspectors for this specific branch
            this.loadInspectorsForBranch(branchId);

            this.$assignModal.fadeIn();
        },

        loadInspectorsForBranch(branchId) {
            const $select = $('#inspector-select');
            $select.empty().append('<option value="">Memuat pengawas...</option>');

            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_available_inspectors',
                    agency_id: this.agencyId,
                    branch_id: branchId,
                    nonce: wpAgencyData.nonce
                },
                success: (response) => {
                    $select.empty().append('<option value="">-- Pilih Pengawas --</option>');

                    if (response.success && response.data.inspectors) {
                        response.data.inspectors.forEach(inspector => {
                            $select.append(`<option value="${inspector.value}">${inspector.label}</option>`);
                        });
                    } else {
                        $select.append('<option value="" disabled>Tidak ada pengawas tersedia</option>');
                    }
                },
                error: () => {
                    $select.empty().append('<option value="" disabled>Gagal memuat pengawas</option>');
                    console.error('Failed to load inspectors for branch');
                }
            });
        },

        closeAssignModal() {
            this.$assignModal.fadeOut();
            this.selectedBranchId = null;
            this.selectedCompanyName = null;
            $('#assign-inspector-form')[0].reset();
        },

        showViewModal(branchId) {
            // Load company details via AJAX
            $('#company-details-content').html('<div class="spinner is-active"></div>');
            this.$viewModal.fadeIn();
            
            // Here you would typically load the company details
            // For now, just show a placeholder
            setTimeout(() => {
                $('#company-details-content').html(`
                    <p>Company details for ID: ${branchId}</p>
                    <p>This feature will be implemented to show full company information.</p>
                `);
            }, 500);
        },

        closeViewModal() {
            this.$viewModal.fadeOut();
            $('#company-details-content').empty();
        },

        onInspectorChange(e) {
            const inspectorId = $(e.target).val();
            
            if (!inspectorId) {
                $('#inspector-info').hide();
                return;
            }

            // You could load inspector's current assignments here
            // For now, just show a placeholder
            $('#inspector-assignments-count').text('Pengawas ini saat ini memiliki 0 penugasan.');
            $('#inspector-info').show();
        },

        confirmAssignment() {
            const branchId = $('#assign-branch-id').val();
            const inspectorId = $('#inspector-select').val();
            
            if (!branchId || !inspectorId) {
                this.showToast('error', 'Silakan pilih pengawas');
                return;
            }

            // Disable button and show loading
            const $button = $('#confirm-assign-inspector');
            const originalText = $button.html();
            $button.prop('disabled', true).html('<span class="spinner is-active"></span> Processing...');

            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'assign_inspector',
                    branch_id: branchId,
                    inspector_id: inspectorId,
                    nonce: wpAgencyData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showToast('success', response.data.message || 'Inspector berhasil ditugaskan');
                        this.closeAssignModal();
                        this.refresh();
                        $(document).trigger('inspector:assigned', [branchId, inspectorId]);
                    } else {
                        this.showToast('error', response.data.message || 'Gagal menugaskan inspector');
                    }
                },
                error: () => {
                    this.showToast('error', 'Terjadi kesalahan saat menugaskan inspector');
                },
                complete: () => {
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        showLoading() {
            this.$tableContainer.hide();
            this.$emptyState.hide();
            this.$errorState.hide();
            this.$loadingState.show();
        },

        showEmpty() {
            this.$tableContainer.hide();
            this.$loadingState.hide();
            this.$errorState.hide();
            this.$emptyState.show();
        },

        showError() {
            this.$tableContainer.hide();
            this.$loadingState.hide();
            this.$emptyState.hide();
            this.$errorState.show();
        },

        showTable() {
            this.$loadingState.hide();
            this.$emptyState.hide();
            this.$errorState.hide();
            this.$tableContainer.show();
        },

        refresh() {
            if (this.table) {
                this.showLoading();
                this.table.ajax.reload(() => {
                    const info = this.table.page.info();
                    if (info.recordsTotal === 0) {
                        this.showEmpty();
                    } else {
                        this.showTable();
                    }
                }, false);
            }
        },

        showToast(type, message) {
            // Use AgencyToast if available, otherwise use console
            if (typeof AgencyToast !== 'undefined') {
                if (type === 'success') {
                    AgencyToast.success(message);
                } else if (type === 'error') {
                    AgencyToast.error(message);
                } else {
                    AgencyToast.info(message);
                }
            } else {
                console.log(`[${type.toUpperCase()}] ${message}`);
                
                // Fallback to basic alert for errors
                if (type === 'error') {
                    alert(message);
                }
            }
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.NewCompanyDataTable = NewCompanyDataTable;
    });

})(jQuery);
