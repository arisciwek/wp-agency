/**
 * Edit Division Form Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Division
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/division/edit-division-form.js
 *
 * Description: Handler untuk form edit cabang.
 *              Includes form validation, AJAX submission,
 *              error handling, dan modal management.
 *              Terintegrasi dengan toast notifications.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - AgencyToast for notifications
 * - WIModal for confirmations
 *
 * Last modified: 2024-12-10
 */
(function($) {
    'use strict';

    const EditDivisionForm = {
        modal: null,
        form: null,

        init() {
            this.modal = $('#edit-division-modal');
            this.form = $('#edit-division-form');

            this.bindEvents();
            this.initializeValidation();
        },

        bindEvents() {
            // Form submission event
            this.form.on('submit', (e) => this.handleUpdate(e));

            // Edit button handler for DataTable rows
            $(document).on('click', '.edit-division', (e) => {
                const id = $(e.currentTarget).data('id');
                if (id) {
                    this.loadDivisionData(id);
                }
            });

            // Modal events
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-edit', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });

        },

        async loadDivisionData(id) {
            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_division',
                        id: id,
                        nonce: wpAgencyData.nonce
                    }
                });

                if (response.success) {
                    this.showEditForm(response.data);
                } else {
                    AgencyToast.error(response.data?.message || 'Gagal memuat data cabang');
                }
            } catch (error) {
                console.error('Load division error:', error);
                AgencyToast.error('Gagal menghubungi server');
            }
        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
                $(document).trigger('division:modalClosed');
            });
        },

        validateField(field) {
            const $field = $(field);
            const value = $field.val().trim();
            const errors = [];

            if (!value) {
                errors.push('Nama cabang wajib diisi');
            } else {
                if (value.length < 3) {
                    errors.push('Nama cabang minimal 3 karakter');
                }
                if (value.length > 100) {
                    errors.push('Nama cabang maksimal 100 karakter');
                }
                if (!/^[a-zA-Z\s]+$/.test(value)) {
                    errors.push('Nama cabang hanya boleh mengandung huruf dan spasi');
                }
            }

            const $error = $field.next('.form-error');
            if (errors.length > 0) {
                $field.addClass('error');
                if ($error.length) {
                    $error.text(errors[0]);
                } else {
                    $('<span class="form-error"></span>')
                        .text(errors[0])
                        .insertAfter($field);
                }
                return false;
            } else {
                $field.removeClass('error');
                $error.remove();
                return true;
            }
        },

        showEditForm(data) {
            if (!data?.division) {
                AgencyToast.error('Data cabang tidak valid');
                return;
            }

            this.resetForm();

            // Populate all form fields
            const division = data.division;
            this.form.find('#division-id').val(division.id);
            this.form.find('[name="name"]').val(division.name);
            this.form.find('[name="type"]').val(division.type);
            this.form.find('[name="nitku"]').val(division.nitku);
            this.form.find('[name="postal_code"]').val(division.postal_code);
            this.form.find('[name="latitude"]').val(division.latitude);
            this.form.find('[name="longitude"]').val(division.longitude);
            this.form.find('[name="address"]').val(division.address);
            this.form.find('[name="phone"]').val(division.phone);
            this.form.find('[name="email"]').val(division.email);
            this.form.find('[name="provinsi_id"]').val(division.provinsi_id);
            this.form.find('[name="regency_id"]').val(division.regency_id);
            this.form.find('[name="status"]').val(division.status);

            // Province and Regency fields
            if (division.provinsi_id) {
                this.form.find('[name="provinsi_id"]').val(division.provinsi_id).trigger('change');

                // Wait for province change to complete before setting regency
                setTimeout(() => {
                    if (division.regency_id) {
                        this.form.find('[name="regency_id"]').val(division.regency_id);
                    }
                }, 500);
            }

            // Initialize Select2 for jurisdictions
            this.initializeJurisdictionSelect();

            // Load current jurisdictions
            if (data.jurisdictions) {
                const $select = this.form.find('.jurisdiction-select');
                data.jurisdictions.forEach(jurisdiction => {
                    // Add current jurisdictions as options
                    if (!$select.find(`option[value="${jurisdiction.regency_id}"]`).length) {
                        $select.append(new Option(`${jurisdiction.regency_name} (${jurisdiction.province_name})`, jurisdiction.regency_id, true, true));
                    }
                });
                // Set selected values
                const jurisdictionIds = data.jurisdictions.map(j => j.regency_id);
                $select.val(jurisdictionIds).trigger('change');
            }

            this.modal.find('.modal-header h3').text(`Edit Division: ${division.name}`);

            // Show modal with animation and trigger events
            this.modal.fadeIn(300, () => {
                this.form.find('[name="name"]').focus();
                $(document).trigger('division:modalOpened');

                // Add additional trigger after modal is fully visible
                setTimeout(() => {
                    $(document).trigger('division:modalFullyOpen');
                }, 350);
            });
        if ($('#edit-division-modal:visible').length) {
            MapPicker.init('edit-division-modal');
        }
        
        // If map exists, update marker position
        if (window.MapPicker && window.MapPicker.map) {
            const lat = parseFloat(division.latitude);
            const lng = parseFloat(division.longitude);
            if (!isNaN(lat) && !isNaN(lng)) {
                window.MapPicker.marker.setLatLng([lat, lng]);
                window.MapPicker.map.setView([lat, lng]);
            }
        }
    },

    initializeValidation() {
        this.form.validate({
            rules: {
                name: {
                    required: true,
                    minlength: 3,
                    maxlength: 100
                },
                type: { required: true },
                nitku: { maxlength: 20 },
                postal_code: { 
                    required: true,
                    maxlength: 5,
                    digits: true
                },
                latitude: {
                    required: true,
                    number: true,
                    range: [-90, 90]
                },
                longitude: {
                    required: true,
                    number: true,
                    range: [-180, 180]
                },
                phone: {
                    required: true,
                    maxlength: 20,
                    phoneID: true
                },
                email: {
                    required: true,
                    email: true,
                    maxlength: 100
                }
            },
            messages: {
                name: {
                    required: 'Nama cabang wajib diisi',
                    minlength: 'Nama cabang minimal 3 karakter',
                    maxlength: 'Nama cabang maksimal 100 karakter'
                },
                type: { required: 'Tipe cabang wajib dipilih' },
                provinsi_id: { required: 'Provinsi wajib dipilih' },
                regency_id: { required: 'Kabupaten/Kota wajib dipilih' }
                // ... other validation messages
            }
        });

        // Add custom phone validation for Indonesia
        $.validator.addMethod('phoneID', function(phone_number, element) {
            // Support both mobile and landline formats
            return this.optional(element) || phone_number.match(/^(\+62|62)?[\s-]?(0?8[1-9]\d{8,10}|0?[1-9][0-9]{2}\d{6,8})$/);
        }, 'Masukkan nomor telepon yang valid (format: +628xx, 08xx untuk HP, atau +62xxx, 0xxx untuk telepon kabel)');
    },

    async validateDivisionTypeChange(newType) {
        const divisionId = this.form.find('#division-id').val();
        
        try {
            const response = await $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'validate_division_type_change',
                    id: divisionId,
                    new_type: newType,
                    nonce: wpAgencyData.nonce
                }
            });

            return response;
        } catch (error) {
            console.error('Validate division type error:', error);
            throw new Error('Gagal memvalidasi perubahan tipe cabang');
        }
    },

    async handleUpdate(e) {
        e.preventDefault();
        if (!this.form.valid()) return;

        const formData = {
            action: 'update_division',
            nonce: wpAgencyData.nonce,
            id: this.form.find('#division-id').val(),
            name: this.form.find('[name="name"]').val().trim(),
            type: this.form.find('[name="type"]').val(),
            nitku: this.form.find('[name="nitku"]').val().trim(),
            postal_code: this.form.find('[name="postal_code"]').val().trim(),
            latitude: this.form.find('[name="latitude"]').val(),
            longitude: this.form.find('[name="longitude"]').val(),
            address: this.form.find('[name="address"]').val().trim(),
            phone: this.form.find('[name="phone"]').val().trim(),
            email: this.form.find('[name="email"]').val().trim(),
            provinsi_id: this.form.find('[name="provinsi_id"]').val(),
            regency_id: this.form.find('[name="regency_id"]').val(),
            jurisdictions: this.form.find('[name="jurisdictions[]"]').val(),
            status: this.form.find('[name="status"]').val()
        };

        this.setLoadingState(true);

        // Validate type change first
        try {
            const typeValidation = await this.validateDivisionTypeChange(formData.type);
            
        if (!typeValidation.success) {
            DivisionToast.error(typeValidation.data?.message || 'Tipe cabang tidak dapat diubah.');
            
            const $typeSelect = this.form.find('[name="type"]');
            $typeSelect.addClass('error');
            
            if (typeValidation.data?.original_type) {
                $typeSelect.val(typeValidation.data.original_type);
            }
            
            // Remove error class after 2 seconds
            setTimeout(() => {
                $typeSelect.removeClass('error');
            }, 2000);
            
            return;
        }
            // If validation passes, proceed with update
            this.setLoadingState(true);

            const response = await $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: formData
            });

            if (response.success) {
                AgencyToast.success('Cabang berhasil diperbarui');
                this.hideModal();
                $(document).trigger('division:updated', [response.data]);
                if (window.DivisionDataTable) {
                    window.DivisionDataTable.refresh();
                }
            } else {
                AgencyToast.error(response.data?.message || 'Gagal memperbarui cabang');
            }
        } catch (error) {
            console.error('Update division error:', error);
            AgencyToast.error('Gagal menghubungi server');
        } finally {
            this.setLoadingState(false);
        }
    },

        setLoadingState(loading) {
            const $submitBtn = this.form.find('[type="submit"]');
            const $spinner = this.form.find('.spinner');

            if (loading) {
                $submitBtn.prop('disabled', true);
                $spinner.addClass('is-active');
                this.form.addClass('loading');
            } else {
                $submitBtn.prop('disabled', false);
                $spinner.removeClass('is-active');
                this.form.removeClass('loading');
            }
        },

        resetForm() {
            if (!this.form || !this.form[0]) return;

            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');

            if (this.form.data('validator')) {
                this.form.validate().resetForm();
            }

            // Reset Select2
            this.form.find('.jurisdiction-select').val(null).trigger('change');
        },

        initializeJurisdictionSelect() {
            const $select = this.form.find('.jurisdiction-select');

            if (!$select.length || typeof $select.select2 === 'undefined') {
                console.warn('Select2 not available or jurisdiction select not found');
                return;
            }

            $select.select2({
                placeholder: 'Pilih kabupaten/kota...',
                allowClear: true,
                ajax: {
                    url: wpAgencyData.ajaxUrl,
                    dataType: 'json',
                    delay: 300,
                    data: (params) => ({
                        action: 'get_available_jurisdictions',
                        agency_id: this.getCurrentAgencyId(),
                        division_id: this.form.find('#division-id').val(),
                        search: params.term,
                        nonce: wpAgencyData.nonce
                    }),
                    processResults: (data) => {
                        if (data.success && data.data.jurisdictions) {
                            return {
                                results: data.data.jurisdictions.map(jurisdiction => ({
                                    id: jurisdiction.id,
                                    text: `${jurisdiction.name} (${jurisdiction.province_name})`
                                }))
                            };
                        }
                        return { results: [] };
                    },
                    cache: true
                },
                minimumInputLength: 0,
                escapeMarkup: (markup) => markup,
                templateResult: (item) => item.loading ? 'Mencari...' : item.text,
                templateSelection: (item) => item.text || item.id
            });
        },

        getCurrentAgencyId() {
            // Try to get agency ID from various sources
            return window.Agency?.currentId || this.form.find('[name="agency_id"]').val();
        },


    };

    // Initialize when document is ready
    $(document).ready(() => {
        console.log('Edit modal visibility:', $('#edit-division-modal').is(':visible'));
        window.EditDivisionForm = EditDivisionForm;
        EditDivisionForm.init();
    });

})(jQuery);
