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
         statusFilter: 'active', // Default filter: active only

         init(agencyId) {
             console.log('[Division DataTable] init() called with agency:', agencyId);

             // Always reinitialize when called to ensure fresh data
             this.agencyId = agencyId;
             this.statusFilter = 'active'; // Reset to active on init

             this.initDataTable();
             this.bindEvents();

             console.log('[Division DataTable] Initialization complete');
         },

         bindEvents() {
             console.log('[Division DataTable] Binding events');

             // CRUD event listeners
             $(document)
                 .off('division:created.datatable division:updated.datatable division:deleted.datatable')
                 .on('division:created.datatable division:updated.datatable division:deleted.datatable',
                     () => this.refresh());

            // Status filter change handler
            $('#division-status-filter').off('change').on('change', (e) => {
                this.statusFilter = $(e.target).val();
                console.log('[Division DataTable] Status filter changed to:', this.statusFilter);
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
            console.log('[Division DataTable] initDataTable() called');

            if ($.fn.DataTable.isDataTable('#division-table')) {
                console.log('[Division DataTable] Destroying existing DataTable');
                $('#division-table').DataTable().destroy();
            }

            // DO NOT overwrite HTML - table structure already loaded from server
            console.log('[Division DataTable] Initializing DataTable with agency_id:', this.agencyId);

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
                            action: 'get_divisions_datatable',
                            agency_id: this.agencyId,
                            status_filter: this.statusFilter,
                            nonce: wpAgencyData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('[Division DataTable] AJAX Error:', error, thrown);
                    },
                    dataSrc: function(response) {
                        console.log('[Division DataTable] Data received:', response);
                        if (response.data && response.data.length > 0) {
                            console.log('[Division DataTable] Total rows:', response.data.length);
                        }
                        return response.data || [];
                    }
                },

                columns: [
                    {
                        data: 'code',
                        width: '15%',
                        className: 'column-code'
                    },
                    {
                        data: 'name',
                        width: '35%',
                        className: 'column-name'
                    },
                    {
                        data: 'wilayah_kerja',
                        width: '35%',
                        className: 'column-wilayah',
                        render: (data) => data || '-'
                    },
                    {
                        data: 'actions',
                        width: '15%',
                        orderable: false,
                        className: 'column-actions text-center',
                        render: function(data, type, row) {
                            // Return HTML directly without escaping
                            return data || '-';
                        }
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

         refresh() {
             if (this.table) {
                 console.log('[Division DataTable] Refreshing table');
                 this.table.ajax.reload(() => {
                     console.log('[Division DataTable] Table reloaded');
                     const info = this.table.page.info();
                     console.log('[Division DataTable] Total records:', info.recordsTotal);
                 }, false);
             }
         }
     };

     // Expose to window immediately (not inside document.ready)
     window.DivisionDataTable = DivisionDataTable;

     // Initialize when document is ready
     $(document).ready(() => {
         // Listen for tab content loaded event from wp-datatable framework
         // This event is triggered AFTER AJAX content is loaded into tab
         $(document).on('wpdt:tab-content-loaded', (e, data) => {
             // Initialize DataTable when divisions tab content loaded
             if (data.tabId === 'divisions') {
                 console.log('[Division DataTable] Tab content loaded, initializing...');

                 const $table = $('#division-table');

                 if (!$table.length) {
                     console.error('[Division DataTable] Table not found');
                     return;
                 }

                 if ($.fn.DataTable.isDataTable($table)) {
                     console.log('[Division DataTable] Table already initialized');
                     return;
                 }

                 const agencyId = $table.data('agency-id');

                 if (!agencyId) {
                     console.error('[Division DataTable] agency-id not found on table');
                     return;
                 }

                 console.log('[Division DataTable] Initializing for agency:', agencyId);
                 DivisionDataTable.init(agencyId);
             }
         });
     });

 })(jQuery);
