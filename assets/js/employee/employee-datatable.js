/**
 * Employee DataTable Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Employee
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/employee/employee-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables karyawan.
 *              Includes state management, export functions,
 *              dan error handling.
 *              Terintegrasi dengan form handlers dan toast.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - AgencyToast for notifications
 *
 * Changelog:
 * 1.0.0 - 2024-01-12
 * - Initial implementation
 * - Added state management
 * - Added export functionality
 * - Enhanced error handling
 */
(function($) {
    'use strict';

    const EmployeeDataTable = {
        table: null,
        initialized: false,
        currentHighlight: null,
        agencyId: null,
        isLoading: false,
        $container: null,
        $tableContainer: null,
        $loadingState: null,
        $emptyState: null,
        $errorState: null,

        init(agencyId) {
            // Cache DOM elements
            this.$container = $('#employee-list');
            this.$tableContainer = this.$container.find('.wi-table-container');
            this.$loadingState = this.$container.find('.employee-loading-state');
            this.$emptyState = this.$container.find('.empty-state');
            this.$errorState = this.$container.find('.error-state');

            // Always reinitialize when called to ensure fresh data
            this.agencyId = agencyId;
            this.showLoading();
            this.initDataTable();
            this.bindEvents();
        },

        bindEvents() {
            // CRUD event listeners
            $(document)
                .off('employee:created.datatable employee:updated.datatable employee:deleted.datatable employee:status_changed.datatable')
                .on('employee:created.datatable employee:updated.datatable employee:deleted.datatable employee:status_changed.datatable',
                    () => this.refresh());

            // Reload button handler
            this.$errorState.find('.reload-table').off('click').on('click', () => {
                this.refresh();
            });

            // Action buttons handlers using event delegation
            $('#employee-table').off('click', '.delete-employee, .toggle-status')
                .on('click', '.delete-employee', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    if (id) {
                        this.handleDelete(id);
                    }
                })
                .on('click', '.toggle-status', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    const status = $(e.currentTarget).data('status');
                    if (id && status) {
                        this.handleStatusToggle(id, status);
                    }
                });
        },

        async handleDelete(id) {
            if (!id) return;

            if (typeof WIModal === 'undefined') {
                console.error('WIModal is not defined');
                AgencyToast.error('Error: Modal component not found');
                return;
            }

            WIModal.show({
                title: 'Konfirmasi Hapus',
                message: 'Yakin ingin menghapus karyawan ini? Aksi ini tidak dapat dibatalkan.',
                icon: 'trash',
                type: 'danger',
                confirmText: 'Hapus',
                confirmClass: 'button-danger',
                cancelText: 'Batal',
                onConfirm: async () => {
                    try {
                        const response = await $.ajax({
                            url: wpAgencyData.ajaxUrl,
                            type: 'POST',
                            data: {
                                action: 'delete_employee',
                                id: id,
                                nonce: wpAgencyData.nonce
                            }
                        });

                        if (response.success) {
                            AgencyToast.success('Karyawan berhasil dihapus');
                            this.refresh();
                            $(document).trigger('employee:deleted', [id]);
                        } else {
                            AgencyToast.error(response.data?.message || 'Gagal menghapus karyawan');
                        }
                    } catch (error) {
                        console.error('Delete employee error:', error);
                        AgencyToast.error('Gagal menghubungi server');
                    }
                }
            });
        },

        async handleStatusToggle(id, status) {
            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'change_employee_status',
                        id: id,
                        status: status,
                        nonce: wpAgencyData.nonce
                    }
                });

                if (response.success) {
                    AgencyToast.success('Status karyawan berhasil diperbarui');
                    this.refresh();
                    $(document).trigger('employee:status_changed', [id, status]);
                } else {
                    AgencyToast.error(response.data?.message || 'Gagal mengubah status karyawan');
                }
            } catch (error) {
                console.error('Status toggle error:', error);
                AgencyToast.error('Gagal menghubungi server');
            }
        },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#employee-table')) {
                $('#employee-table').DataTable().destroy();
            }

            // Initialize clean table structure
            $('#employee-table').empty().html(`
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Jabatan</th>
                        <th>Wewenang</th>
                        <th>Cabang</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            const self = this;
            this.table = $('#employee-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    cache: false, // Disable caching for immediate updates
                    timeout: 10000,
                    data: (d) => {
                        if (!self.agencyId) {
                            console.error('Agency ID belum di-set');
                            self.showError();
                            return false;
                        }
                        return {
                            ...d,
                            action: 'handle_employee_datatable',
                            agency_id: self.agencyId,
                            nonce: wpAgencyData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('DataTables Error:', error);
                        console.error('XHR Response:', xhr.responseText);
                        console.error('XHR Status:', xhr.status);
                        if (window.EmployeeToast) {
                            EmployeeToast.error('Gagal memuat data');
                        }
                        self.showError();
                    },
                    dataSrc: (response) => {
                        if (response.success === false) {
                            // Handle server error response
                            console.error('Server error in DataTable response:', response.data);
                            self.showError();
                            return [];
                        }
                        if (!response.data || response.data.length === 0) {
                            self.showEmpty();
                        } else {
                            self.showTable();
                        }
                        return response.data;
                    }
                },
                columns: [
                    { data: 'name', width: '16%' },
                    { data: 'position', width: '16%' },
                    { data: 'role', width: '22%' },
                    { data: 'division_name', width: '16%' },
                    {
                        data: 'status',
                        width: '13%',
                        render: function(data, type, row) {
                            // Pastikan data status adalah string murni
                            // dan bukan HTML yang sudah di-generate
                            console.log('Raw status:', data);

                            // Normalisasi nilai status untuk perbandingan
                            let statusValue = data;
                            if (typeof data === 'string' && data.includes('status-badge')) {
                                // Jika data sudah dalam bentuk HTML, ekstrak nilai aslinya
                                statusValue = data.includes('Aktif') ? 'active' : 'inactive';
                            }

                            // Normalisasi untuk perbandingan
                            statusValue = String(statusValue).toLowerCase().trim();
                            const isActive = statusValue === 'active';

                            const statusClass = isActive ? 'status-active' : 'status-inactive';
                            const statusText = isActive ? 'Aktif' : 'Nonaktif';

                            return `<span class="status-badge ${statusClass}">${statusText}</span>`;
                        }
                    },
                    {
                        data: 'actions',
                        width: '15%',
                        orderable: false,
                        className: 'text-center nowrap'
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
                    self.bindActionButtons();
                }
            });

            this.initialized = true;
        },

        bindActionButtons() {
            // Using event delegation, no need to rebind
        },

        showLoading() {
            this.isLoading = true;
            this.$tableContainer.hide();
            this.$emptyState.hide();
            this.$errorState.hide();
            this.$loadingState.show();
        },

        showEmpty() {
            this.isLoading = false;
            this.$tableContainer.hide();
            this.$loadingState.hide();
            this.$errorState.hide();
            this.$emptyState.show();
        },

        showError() {
            this.isLoading = false;
            this.$tableContainer.hide();
            this.$loadingState.hide();
            this.$emptyState.hide();
            this.$errorState.show();
        },

        showTable() {
            this.isLoading = false;
            this.$loadingState.hide();
            this.$emptyState.hide();
            this.$errorState.hide();
            this.$tableContainer.show();
        },

        refresh() {
            if (this.table && !this.isLoading) {
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
        }
    };

    $(document).ready(() => {
        window.EmployeeDataTable = EmployeeDataTable;

        $(document).on('agency:selected', (event, agency) => {
            if (agency && agency.id) {
                EmployeeDataTable.init(agency.id);
            }
        });
    });

})(jQuery);
