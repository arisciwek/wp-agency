/**
 * Agency DataTable Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Components
 * @version     1.0.2
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/components/agency-datatable.js
 *
 * Description: Komponen untuk mengelola DataTables agency.
 *              Menangani server-side processing, panel kanan,
 *              dan integrasi dengan komponen form terpisah.
 *
 * Form Integration:
 * - Create form handling sudah dipindahkan ke create-agency-form.js
 * - Component ini hanya menyediakan method refresh() untuk update table
 * - Event 'agency:created' digunakan sebagai trigger untuk refresh
 *
 * Dependencies:
 * - jQuery
 * - DataTables library
 * - AgencyToast for notifications
 * - CreateAgencyForm for handling create operations
 * - EditAgencyForm for handling edit operations
 *
 * Related Files:
 * - create-agency-form.js: Handles create form submission
 * - edit-agency-form.js: Handles edit form submission
 */
 (function($) {
     'use strict';

     const AgencyDataTable = {
         table: null,
         initialized: false,
         currentHighlight: null,

         init() {
             if (this.initialized) {
                 return;
             }

             // Wait for dependencies
             if (!window.Agency || !window.AgencyToast) {
                 setTimeout(() => this.init(), 100);
                 return;
             }

             this.initialized = true;
             this.initDataTable();
             this.bindEvents();
             this.handleInitialHash();
         },

        initDataTable() {
            if ($.fn.DataTable.isDataTable('#agencies-table')) {
                $('#agencies-table').DataTable().destroy();
            }

            // Initialize clean table structure
            $('#agencies-table').empty().html(`
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Disnaker</th>
                        <th>Admin</th>
                        <th>Unit Kerja</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            `);

            this.table = $('#agencies-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    cache: false,
                    timeout: 10000,
                    data: (d) => {
                        return {
                            ...d,
                            action: 'handle_agency_datatable',
                            nonce: wpAgencyData.nonce
                        };
                    },
                    error: (xhr, error, thrown) => {
                        console.error('DataTables Error:', error, thrown);
                        AgencyToast.error('Gagal memuat data agency');
                    }
                },
                // Di bagian columns, tambahkan setelah kolom code
                columns: [
                    {
                        data: 'code',
                        title: 'Kode',
                        width: '100px'
                    },
                    {
                        data: 'name',
                        title: 'Disnaker'
                    },
                    {
                        data: 'owner_name',
                        title: 'Admin',
                        defaultContent: '-'
                    },
                    {
                        data: 'division_count',
                        title: 'Unit Kerja',
                        className: 'text-center',
                        searchable: false,
                        width: '40px'
                    },
                    {
                        data: 'actions',
                        title: 'Aksi',
                        orderable: false,
                        searchable: false,
                        className: 'text-center nowrap'
                    }
                ],
                order: [[0, 'asc']], // Default sort by code
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

                    // Get current hash if any
                    const hash = window.location.hash;
                    if (hash && hash.startsWith('#')) {
                        const id = hash.substring(1);
                        if (id) {
                            this.highlightRow(id);
                        }
                    }
                },
                createdRow: (row, data) => {
                    $(row).attr('data-id', data.id);
                }
            });
        },

         bindEvents() {
             // Hash change event
             $(window).off('hashchange.agencyTable')
                     .on('hashchange.agencyTable', () => this.handleHashChange());

             // CRUD event listeners
             $(document).off('agency:created.datatable agency:updated.datatable agency:deleted.datatable agency:created agency:updated agency:deleted')
                       .on('agency:created agency:updated agency:deleted',
                           () => this.refresh());
         },

         bindActionButtons() {
             const $table = $('#agencies-table');
             $table.off('click', '.view-agency, .edit-agency, .delete-agency');

             // View action
             $table.on('click', '.view-agency', (e) => {
                 const id = $(e.currentTarget).data('id');
                 if (id) window.location.hash = id;

                 // Reset tab ke details
                 $('.tab-content').removeClass('active');
                 $('#agency-details').addClass('active');
                 $('.nav-tab').removeClass('nav-tab-active');
                 $('.nav-tab[data-tab="agency-details"]').addClass('nav-tab-active');

             });

             // Edit action
             $table.on('click', '.edit-agency', (e) => {
                 e.preventDefault();
                 const id = $(e.currentTarget).data('id');
                 this.loadAgencyForEdit(id);
             });

             // Delete action
             $table.on('click', '.delete-agency', (e) => {
                 const id = $(e.currentTarget).data('id');
                 this.handleDelete(id);
             });
         },

         async loadAgencyForEdit(id) {
            if (!id) return;

            try {
                console.log('Loading agency data for edit ID:', id); // Debug log

                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_agency',
                        id: id,
                        nonce: wpAgencyData.nonce
                    }
                });

                console.log('Response from get_agency:', response); // Debug response

                if (response.success) {
                    if (window.EditAgencyForm) {
                        window.EditAgencyForm.showEditForm(response.data);
                    } else {
                        console.error('EditAgencyForm component not found'); // Debug component
                        AgencyToast.error('Komponen form edit tidak tersedia');
                    }
                } else {
                    console.error('Failed to load agency data:', response); // Debug error
                    AgencyToast.error(response.data?.message || 'Gagal memuat data agency');
                }
            } catch (error) {
                console.error('Load agency error:', error);
                AgencyToast.error('Gagal menghubungi server');
            }
         },

         async handleDelete(id) {
             if (!id) return;

             // Tampilkan modal konfirmasi dengan WIModal
             WIModal.show({
                 title: 'Konfirmasi Hapus',
                 message: 'Yakin ingin menghapus agency ini? Aksi ini tidak dapat dibatalkan.',
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
                                 action: 'delete_agency',
                                 id: id,
                                 nonce: wpAgencyData.nonce
                             }
                         });

                         if (response.success) {
                             AgencyToast.success(response.data.message);

                             // Clear hash if deleted agency is currently viewed
                             if (window.location.hash === `#${id}`) {
                                 window.location.hash = '';
                             }

                             this.refresh();
                             $(document).trigger('agency:deleted');
                         } else {
                             AgencyToast.error(response.data?.message || 'Gagal menghapus agency');
                         }
                     } catch (error) {
                         console.error('Delete agency error:', error);
                         AgencyToast.error('Gagal menghubungi server');
                     }
                 }
             });
         },

         handleHashChange() {
             const hash = window.location.hash;
             if (hash) {
                 const id = hash.substring(1);
                 if (id) {
                     this.highlightRow(id);
                 }
             }
         },

         handleInitialHash() {
             const hash = window.location.hash;
             if (hash && hash.startsWith('#')) {
                 this.handleHashChange();
             }
         },

         highlightRow(id) {
             if (this.currentHighlight) {
                 $(`tr[data-id="${this.currentHighlight}"]`).removeClass('highlight');
             }

             const $row = $(`tr[data-id="${id}"]`);
             if ($row.length) {
                 $row.addClass('highlight');
                 this.currentHighlight = id;

                 // Scroll into view if needed
                 const container = this.table.table().container();
                 const rowTop = $row.position().top;
                 const containerHeight = $(container).height();
                 const scrollTop = $(container).scrollTop();

                 if (rowTop < scrollTop || rowTop > scrollTop + containerHeight) {
                     $row[0].scrollIntoView({behavior: 'smooth', block: 'center'});
                 }
             }
         },

         refresh() {
             if (this.table) {
                 this.table.ajax.reload(null, false);
             }
         }

     };

     // Initialize when document is ready
     $(document).ready(() => {
         window.AgencyDataTable = AgencyDataTable;
         AgencyDataTable.init();
     });

 })(jQuery);
