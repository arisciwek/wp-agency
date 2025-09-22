/**
 * Division DataTable Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Division
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/division/division-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables cabang.
 *              Includes state management, export functions,
 *              dan error handling yang lebih baik.
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - AgencyToast for notifications
 *
 * Changelog:
 * 1.1.0 - 2024-12-10
 * - Added state management
 * - Added export functionality
 * - Enhanced error handling
 * - Improved loading states
 */

 /**
  * Division DataTable Handler - Fixed Implementation
  */
 (function($) {
     'use strict';

     const DivisionDataTable = {
         table: null,
         initialized: false,
         currentHighlight: null,
         agencyId: null,
         $container: null,
         $tableContainer: null,
         $loadingState: null,
         $emptyState: null,
         $errorState: null,

         init(agencyId) {
             // Cache DOM elements
             this.$container = $('#division-list');
             this.$tableContainer = this.$container.find('.wi-table-container');
             this.$loadingState = this.$container.find('.division-loading-state');
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
                 .off('division:created.datatable division:updated.datatable division:deleted.datatable')
                 .on('division:created.datatable division:updated.datatable division:deleted.datatable',
                     () => this.refresh());

             // Reload button handler
             this.$errorState.find('.reload-table').off('click').on('click', () => {
                 this.refresh();
             });

            // Event delegation for action buttons
            $('#division-table')
                .off('click', '.delete-division, .edit-division')
                .on('click', '.delete-division', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    if (id) {
                        this.handleDelete(id);
                    }
                })
                .on('click', '.edit-division', (e) => {
                    e.preventDefault();
                    const id = $(e.currentTarget).data('id');
                    if (id && window.EditDivisionForm) {
                        window.EditDivisionForm.loadDivisionData(id);
                    }
                });
                
         },

         async handleDelete(id) {
             if (!id) return;

             if (typeof WIModal === 'undefined') {
                 console.error('WIModal is not defined');
                 DivisionToast.error('Error: Modal component not found');
                 return;
             }

             WIModal.show({
                 title: 'Konfirmasi Hapus',
                 message: 'Yakin ingin menghapus cabang ini? Aksi ini tidak dapat dibatalkan.',
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
                                 action: 'delete_division',
                                 id: id,
                                 nonce: wpAgencyData.nonce
                             }
                         });

                         if (response.success) {
                             DivisionToast.success('Kabupaten/kota berhasil dihapus');
                             this.refresh();
                             $(document).trigger('division:deleted', [id]);
                         } else {
                             DivisionToast.error(response.data?.message || 'Gagal menghapus cabang');
                         }
                     } catch (error) {
                         console.error('Delete division error:', error);
                         DivisionToast.error('Gagal menghubungi server');
                     }
                 }
             });
         },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#division-table')) {
                $('#division-table').DataTable().destroy();
            }

            $('#division-table').empty().html(`
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Admin</th>
                        <th>Tipe</th>
                        <th>Yuridiksi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            const self = this;
            this.table = $('#division-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    cache: false, // Disable caching for immediate updates
                    data: (d) => {
                        return {
                            ...d,
                            action: 'handle_division_datatable',
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
                    { data: 'name', width: '25%', className: 'column-name' },
                    {
                        data: 'admin_name',
                        width: '15%',
                        className: 'column-admin',
                        render: (data) => data || '-'
                    },
                    {
                        data: 'type',
                        width: '10%',
                        className: 'column-type',
                        render: (data) => data === 'pusat' ? 'Pusat' : 'Cabang'
                    },
                    {
                        data: 'jurisdictions',
                        width: '25%',
                        className: 'column-jurisdictions',
                        render: (data) => data || '-'
                    },
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
                    this.bindActionButtons();
                }
            });

            this.initialized = true;
        },

         bindActionButtons() {
             // No need to rebind delete buttons as we're using event delegation above
             // Just handle other action buttons if needed
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
         }
     };

     // Initialize when document is ready
     $(document).ready(() => {
         window.DivisionDataTable = DivisionDataTable;
     });

 })(jQuery);
