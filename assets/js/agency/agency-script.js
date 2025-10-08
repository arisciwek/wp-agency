/**
 * Agency Management Interface
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/agency-script.js
 *
 * Description: Main JavaScript handler untuk halaman agency.
 *              Mengatur interaksi antar komponen seperti DataTable,
 *              form, panel kanan, dan notifikasi.
 *              Includes state management dan event handling.
 *              Terintegrasi dengan WordPress AJAX API.
 *
 * Dependencies:
 * - jQuery
 * - AgencyDataTable
 * - AgencyForm
 * - AgencyToast
 * - WordPress AJAX
 *
 * Changelog:
 * 1.0.0 - 2024-12-03
 * - Added proper jQuery no-conflict handling
 * - Added panel kanan integration
 * - Added CRUD event handlers
 * - Added toast notifications
 * - Improved error handling
 * - Added loading states
 *
 * Last modified: 2025-01-12 16:45:00
 */
 (function($) {
     'use strict';

     const Agency = {
         currentId: null,
         isLoading: false,
         components: {
             container: null,
             rightPanel: null,
             detailsPanel: null,
             stats: {
                 totalAgencys: null,
                 totalDivisions: null
             }
         },

        init() {
            this.components = {
                container: $('.wp-agency-container'),
                rightPanel: $('.wp-agency-right-panel'),
                detailsPanel: $('#agency-details'),
                stats: {
                    totalAgencys: $('#total-agencies'),
                    totalDivisions: $('#total-divisions')
                }
            };

            // Tambahkan load tombol tambah agency
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'create_agency_button',
                    nonce: wpAgencyData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $('#tombol-tambah-agency').html(response.data.button);
                        
                        // Bind click event using delegation
                        $('#tombol-tambah-agency').off('click', '#add-agency-btn')
                            .on('click', '#add-agency-btn', function() {
                                const $button = $(this);
                                const originalText = $button.html();

                                if (window.CreateAgencyForm) {
                                    window.CreateAgencyForm.showModal();
                                } else {
                                    // Show loading state
                                    $button.prop('disabled', true).html('<span class="dashicons dashicons-update rotating"></span> Memuat...');

                                    // Wait for CreateAgencyForm to be available
                                    const checkForm = setInterval(() => {
                                        if (window.CreateAgencyForm) {
                                            clearInterval(checkForm);
                                            $button.prop('disabled', false).html(originalText);
                                            window.CreateAgencyForm.showModal();
                                        }
                                    }, 100);

                                    // Timeout after 5 seconds
                                    setTimeout(() => {
                                        clearInterval(checkForm);
                                        $button.prop('disabled', false).html(originalText);
                                        console.error('CreateAgencyForm failed to initialize');
                                        AgencyToast.error('Form gagal dimuat. Silakan refresh halaman.');
                                    }, 5000);
                                }
                            });
                    }
                }
            });

            this.bindEvents();
            this.handleInitialState();
            this.loadStats();
            
            // Update stats setelah operasi CRUD
            $(document)
                .on('agency:created.Agency', () => this.loadStats())
                .on('agency:deleted.Agency', () => this.loadStats())
                .on('division:created.Agency', () => this.loadStats())
                .on('division:deleted.Agency', () => this.loadStats())
                .on('employee:created.Agency', () => this.loadStats())
                .on('employee:deleted.Agency', () => this.loadStats());
        },

         bindEvents() {
             // Unbind existing events first to prevent duplicates
             $(document)
                 .off('.Agency')
                 .on('agency:created.Agency', (e, data) => this.handleCreated(data))
                 .on('agency:updated.Agency', (e, data) => this.handleUpdated(data))
                 .on('agency:deleted.Agency', () => this.handleDeleted())
                 .on('agency:display.Agency', (e, data) => this.displayData(data))
                 .on('agency:loading.Agency', () => this.showLoading())
                 .on('agency:loaded.Agency', () => this.hideLoading());

             // Panel events
             $('.wp-agency-close-panel').off('click').on('click', () => this.closePanel());

             // Panel navigation
             $('.nav-tab').off('click').on('click', (e) => {
                 e.preventDefault();
                 this.switchTab($(e.currentTarget).data('tab'));
             });

             // Window events
             $(window).off('hashchange.Agency').on('hashchange.Agency', () => this.handleHashChange());
         },

            validateAgencyAccess(agencyId, onSuccess, onError) {
                $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'validate_agency_access',
                        id: agencyId,
                        nonce: wpAgencyData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            if (onSuccess) onSuccess(response.data);
                        } else {
                            if (onError) onError(response.data);
                        }
                    },
                    error: (xhr) => {
                        if (onError) onError({
                            message: 'Terjadi kesalahan saat validasi akses',
                            code: 'server_error'
                        });
                    }
                });
            },
            
        handleInitialState() {
            const hash = window.location.hash;
            if (hash && hash.startsWith('#')) {
                const agencyId = parseInt(hash.substring(1));
                if (agencyId) {
                    this.validateAgencyAccess(
                        agencyId,
                        (data) => this.loadAgencyData(agencyId),
                        (error) => {
                            window.location.href = 'admin.php?page=wp-agency';
                            AgencyToast.error(error.message);
                        }
                    );
                }
            }
        },

         handleHashChange() {
             console.log('Hash changed to:', window.location.hash); // Debug 4
             const hash = window.location.hash;
             if (!hash) {
                 this.closePanel();
                 return;
             }

             const id = hash.substring(1);
             if (id && id !== this.currentId) {
                 $('.tab-content').removeClass('active');
                 $('#agency-details').addClass('active');
                 $('.nav-tab').removeClass('nav-tab-active');
                 $('.nav-tab[data-tab="agency-details"]').addClass('nav-tab-active');
                 
                 console.log('Get agency data for ID:', id); // Debug 5

                 this.loadAgencyData(id);
             }
         },

        async loadAgencyData(id) {
            if (!id || this.isLoading) return;

            this.isLoading = true;
            this.showLoading();

            try {
                console.log('Loading agency data for ID:', id);

                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_agency',
                        id: id,
                        nonce: wpAgencyData.nonce
                    }
                });

                console.log('Agency data response:', response);

                if (response.success && response.data) {
                    // Update URL hash without triggering reload
                    const newHash = `#${id}`;
                    if (window.location.hash !== newHash) {
                        window.history.pushState(null, '', newHash);
                    }

                    // Reset tab to default (Data Agency)
                    $('.nav-tab').removeClass('nav-tab-active');
                    $('.nav-tab[data-tab="agency-details"]').addClass('nav-tab-active');
                    
                    // Hide all tab content first
                    $('.tab-content').removeClass('active').hide();
                    // Show agency details tab
                    $('#agency-details').addClass('active').show();

                    // Update agency data in UI
                    this.displayData(response.data);
                    this.currentId = id;

                    // Trigger success event
                    $(document).trigger('agency:loaded', [response.data]);
                } else {
                    throw new Error(response.data?.message || 'Failed to load agency data');
                }
            } catch (error) {
                console.error('Error loading agency:', error);
                AgencyToast.error(error.message || 'Failed to load agency data');
                this.handleLoadError();
            } finally {
                this.isLoading = false;
                this.hideLoading();
            }
        },

        displayData(data) {
            if (!data?.agency) {
                console.error('Invalid agency data:', data);
                return;
            }

            console.log('Displaying agency data:', data);

            // Show panel first
            this.components.container.addClass('with-right-panel');
            this.components.rightPanel.addClass('visible');

            try {
                // Basic Information
                $('#agency-header-name').text(data.agency.name);
                $('#agency-name').text(data.agency.name);
                $('#agency-code').text(data.agency.code || '-');
                $('#agency-npwp').text(data.agency.npwp || '-');
                $('#agency-nib').text(data.agency.nib || '-');

                // Status Badge
                const statusBadge = $('#agency-status');
                const status = data.agency.status || 'inactive';
                statusBadge
                    .text(status === 'active' ? 'Aktif' : 'Nonaktif')
                    .removeClass('status-active status-inactive')
                    .addClass(`status-${status}`);

                // Pusat (Head Office) Information
                $('#agency-pusat-address').text(data.agency.pusat_address || '-');
                $('#agency-pusat-postal-code').text(data.agency.pusat_postal_code || '-');

                // Location Information
                $('#agency-province').text(data.agency.province_name || '-');
                $('#agency-regency').text(data.agency.regency_name || '-');

                if (data.agency.latitude && data.agency.longitude) {
                    $('#agency-coordinates').text(`${data.agency.latitude}, ${data.agency.longitude}`);
                    const mapsUrl = `https://www.google.com/maps?q=${data.agency.latitude},${data.agency.longitude}`;
                    $('#agency-google-maps-link').attr('href', mapsUrl).show();
                } else {
                    $('#agency-coordinates').text('-');
                    $('#agency-google-maps-link').hide();
                }
                
                // Additional Information
                $('#agency-owner').text(data.agency.owner_name || '-');
                $('#agency-division-count').text(data.agency.division_count || '0');
                $('#agency-employee-count').text(data.agency.employee_count || '0');

                // Timeline Information
                const createdAt = data.agency.created_at ? 
                    new Date(data.agency.created_at).toLocaleString('id-ID') : '-';
                const updatedAt = data.agency.updated_at ? 
                    new Date(data.agency.updated_at).toLocaleString('id-ID') : '-';
                
                $('#agency-created-by').text(data.agency.created_by_name || '-');
                $('#agency-created-at').text(createdAt);
                $('#agency-updated-at').text(updatedAt);

                // Highlight DataTable row if exists
                if (window.AgencyDataTable) {
                    window.AgencyDataTable.highlightRow(data.agency.id);
                }

                // Trigger success event
                $(document).trigger('agency:displayed', [data]);

                // Tampilkan/sembunyikan tombol tambah karyawan berdasarkan izin
                const bolehTambahKaryawan = data.agency.can_create_employee;
                $('.tambah-karyawan').toggle(bolehTambahKaryawan);


                // Generate PDF button via AJAX
                $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'create_pdf_button',
                        id: data.agency.id,
                        nonce: wpAgencyData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            $('#generate-pdf-button').html(response.data.button);
                            
                            // Bind click event using delegation
                            $('#generate-pdf-button').off('click', '.wp-mpdf-agency-detail-export-pdf')
                                .on('click', '.wp-mpdf-agency-detail-export-pdf', function() {
                                    const $button = $(this);
                                    const originalText = $button.html();
                                    
                                    // Tambahkan loading state
                                    $button.prop('disabled', true)
                                           .html('<span class="dashicons dashicons-update rotating"></span> Generating PDF...');
                                    
                                    $.ajax({
                                        url: wpAgencyData.ajaxUrl,
                                        type: 'POST',
                                        data: {
                                            action: 'generate_agency_pdf',
                                            id: data.agency.id,
                                            nonce: wpAgencyData.nonce
                                        },
                                        xhrFields: {
                                            responseType: 'blob'
                                        },
                                        success: function(response) {
                                            if (response.type === 'application/json') {
                                                // Handle error response
                                                const reader = new FileReader();
                                                reader.onload = function() {
                                                    const errorResponse = JSON.parse(this.result);
                                                    AgencyToast.error(errorResponse.data.message || 'Failed to generate PDF');
                                                };
                                                reader.readAsText(response);
                                            } else {
                                                // Handle successful PDF generation
                                                const blob = new Blob([response], { type: 'application/pdf' });
                                                const url = window.URL.createObjectURL(blob);
                                                const a = document.createElement('a');
                                                a.href = url;
                                                a.download = `agency-${data.agency.code}.pdf`;
                                                document.body.appendChild(a);
                                                a.click();
                                                window.URL.revokeObjectURL(url);
                                                AgencyToast.success('PDF berhasil di-generate');
                                            }
                                        },
                                        error: function(xhr) {
                                            AgencyToast.error('Gagal generate PDF. Silakan coba lagi.');
                                        },
                                        complete: function() {
                                            // Kembalikan tombol ke keadaan semula
                                            $button.prop('disabled', false).html(originalText);
                                        }
                                    });
                                });
                        }
                    }
                });


            } catch (error) {
                console.error('Error displaying agency data:', error);
                AgencyToast.error('Error displaying agency data');
            }

        }, 

    handleLoadError() {
        this.components.detailsPanel.html(
            '<div class="error-message">' +
            '<p>Failed to load agency data. Please try again.</p>' +
            '<button class="button retry-load">Retry</button>' +
            '</div>'
        );
    },

              // Helper function untuk label capability
            getCapabilityLabel(cap) {
                const labels = {
                    'can_add_staff': 'Dapat menambah staff',
                    'can_export': 'Dapat export data',
                    'can_bulk_import': 'Dapat bulk import'
                };
                return labels[cap] || cap;
            },

            // Helper function untuk logika tampilan tombol upgrade
            shouldShowUpgradeOption(currentLevel, targetLevel) {
                const levels = ['regular', 'priority', 'utama'];
                const currentIdx = levels.indexOf(currentLevel);
                const targetIdx = levels.indexOf(targetLevel);
                return targetIdx > currentIdx;
            },

        switchTab(tabId) {
            console.log('Tab switched to:', tabId); // Add this debug line
            $('.nav-tab').removeClass('nav-tab-active');
            $(`.nav-tab[data-tab="${tabId}"]`).addClass('nav-tab-active');

            // Hide all tab content first
            $('.tab-content-panel').removeClass('active');
            $('.tab-content').hide();
            $(`#${tabId}`).show();
            $(`#${tabId}`).addClass('active');
            
            // Initialize specific tab content if needed

            // Initialize specific tab content if needed
            if (tabId === 'employee-list' && this.currentId) {
                // Get tombol tambah karyawan
                $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'create_employee_button',
                        agency_id: this.currentId,
                        nonce: wpAgencyData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            $('#tombol-tambah-karyawan').html(response.data.button);

                            // Bind click event using delegation
                            $('#tombol-tambah-karyawan').off('click', '#add-employee-btn')
                                .on('click', '#add-employee-btn', () => {
                                    if (window.CreateEmployeeForm) {
                                        window.CreateEmployeeForm.showModal(this.currentId);
                                    }
                                });
                        }
                    }
                });

                // Small delay to ensure tab visibility before initializing DataTable
                setTimeout(() => {
                    if (window.EmployeeDataTable) {
                        window.EmployeeDataTable.init(this.currentId);
                    }
                }, 100);
            }
            
            // Add division tab handling
            if (tabId === 'division-list' && this.currentId) {
                // Get tombol tambah division
                $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'create_division_button',
                        agency_id: this.currentId,
                        nonce: wpAgencyData.nonce
                    },
                    success: (response) => {
                        if (response.success) {
                            $('#tombol-tambah-division').html(response.data.button);
                            
                            // Bind click event using delegation
                            $('#tombol-tambah-division').off('click', '#add-division-btn')
                                .on('click', '#add-division-btn', () => {
                                    if (window.CreateDivisionForm) {
                                        window.CreateDivisionForm.showModal(this.currentId);
                                    }
                                });
                        }
                    }
                });

                if (window.DivisionDataTable) {
                    window.DivisionDataTable.init(this.currentId);
                }
            }
            
            // Add new company tab handling
            if (tabId === 'new-company' && this.currentId) {
                console.log('Initializing New Company tab for agency:', this.currentId);
                
                // Small delay to ensure tab visibility before initializing DataTable
                setTimeout(() => {
                    if (window.NewCompanyDataTable) {
                        window.NewCompanyDataTable.init(this.currentId);
                    } else {
                        console.error('NewCompanyDataTable not found');
                    }
                }, 100);
            }
        },

         closePanel() {
             this.components.container.removeClass('with-right-panel');
             this.components.rightPanel.removeClass('visible');
             this.currentId = null;
             window.location.hash = '';
             $(document).trigger('panel:closed');
         },

         showLoading() {
             this.components.rightPanel.addClass('loading');
         },

         hideLoading() {
             this.components.rightPanel.removeClass('loading');
         },

         handleCreated(data) {
            console.log('handleCreated called with data:', data); // Debug 1
            if (data && data.data && data.data.id) {  // Akses id dari data.data
                    console.log('Setting hash to:', data.id); // Debug 2
                    window.location.hash = data.data.id;
             }

             if (window.AgencyDataTable) {
                 console.log('Refreshing DataTable'); // Debug 3
                 window.AgencyDataTable.refresh();
             }

             if (window.Dashboard) {
                 window.Dashboard.refreshStats();
             }
         },
         
        handleUpdated(response) {
            if (response && response.data && response.data.agency) {
                const editedAgencyId = response.data.agency.id;
                const currentAgencyId = parseInt(window.location.hash.substring(1));

                if (editedAgencyId === currentAgencyId) {
                    // Jika agency yang diedit sama dengan yang sedang dilihat
                    // Reload data via AJAX untuk memastikan data terbaru
                    this.loadAgencyData(editedAgencyId);
                } else {
                    // Jika berbeda, ubah hash ke agency yang diedit
                    window.location.hash = editedAgencyId;
                }

                // Refresh DataTable setelah update
                if (window.AgencyDataTable) {
                    window.AgencyDataTable.refresh();
                }

            }
        },
        

         handleDeleted() {
             this.closePanel();
             if (window.AgencyDataTable) {
                 window.AgencyDataTable.refresh();
             }
             if (window.Dashboard) {
                window.Dashboard.loadStats(); // Gunakan loadStats() langsung
             }
         },


        /**
         * Load agency statistics including total agencies and divisions.
         * Uses getCurrentAgencyId() to determine which agency's stats to load.
         * Updates stats display via updateStats() when data is received.
         * 
         * @async
         * @fires agency:loading When stats loading begins
         * @fires agency:loaded When stats are successfully loaded
         * @see getCurrentAgencyId
         * @see updateStats
         * 
         * @example
         * // Load stats on page load 
         * Agency.loadStats();
         * 
         * // Load stats after agency creation
         * $(document).on('agency:created', () => Agency.loadStats());
         */
        async loadStats() {
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_agency_stats',
                    nonce: wpAgencyData.nonce,
                    id: 0
                },
                success: (response) => {
                    if (response.success) {
                        this.updateStats(response.data);
                    }
                }
            });
        },

        updateStats(stats) {
            $('#total-agencies').text(stats.total_agencies);
            $('#total-divisions').text(stats.total_divisions);
            $('#total-employees').text(stats.total_employees);
        }

     };

        // Document generation handlers
        $('.wp-docgen-agency-detail-expot-document').on('click', function() {
            const agencyId = $('#current-agency-id').val();
            
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'generate_wp_docgen_agency_detail_document',
                    id: agencyId,
                    nonce: wpAgencyData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create hidden link and trigger download
                        const a = document.createElement('a');
                        a.href = response.data.file_url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else {
                        AgencyToast.error(response.data.message || 'Failed to generate DOCX');
                    }
                },
                error: function() {
                    AgencyToast.error('Failed to generate DOCX');
                }
            });
        });

        $('.wp-docgen-agency-detail-expot-pdf').on('click', function() {
            const agencyId = $('#current-agency-id').val();
            
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'generate_wp_docgen_agency_detail_pdf',
                    id: agencyId,
                    nonce: wpAgencyData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Create hidden link and trigger download
                        const a = document.createElement('a');
                        a.href = response.data.file_url;
                        a.download = response.data.filename;
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    } else {
                        AgencyToast.error(response.data.message || 'Failed to generate PDF');
                    }
                },
                error: function() {
                    AgencyToast.error('Failed to generate PDF');
                }
            });
        });

        
     // Initialize when document is ready
     $(document).ready(() => {
         window.Agency = Agency;
         Agency.init();
     });

 })(jQuery);
 
