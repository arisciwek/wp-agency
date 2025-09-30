/**
 * Agency Form Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/components/create-agency-form.js
 *
 * Description: Handler untuk form agency.
 *              Menangani create dan update agency.
 *              Includes validasi form, error handling,
 *              dan integrasi dengan komponen lain.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - AgencyToast for notifications
 * - Agency main component
 * - WordPress AJAX API
 *
 * Changelog:
 * 1.0.0 - 2024-12-03
 * - Added proper form validation
 * - Added AJAX integration
 * - Added modal management
 * - Added loading states
 * - Added error handling
 * - Added toast notifications
 * - Added panel integration
 *
 * Last modified: 2024-12-03 16:30:00
 */
(function($) {
    'use strict';

    const CreateAgencyForm = {
        modal: null,
        form: null,

        init() {
            this.modal = $('#create-agency-modal');
            this.form = $('#create-agency-form');

            this.bindEvents();
            this.initializeValidation();
        },
        


        bindEvents() {
            // Form submission
            this.form.on('submit', (e) => this.handleCreate(e));
            
            // Field validation for name
            this.form.on('input', 'input[name="name"]', (e) => {
                this.validateNameField(e.target);
            });





            // Modal events
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-create', this.modal).on('click', () => this.hideModal());

            // Close modal when clicking outside
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });

            // Province change event to load regencies
            this.form.on('change', '[name="provinsi_code"]', () => this.loadRegenciesByProvince());
        },

        // Memisahkan validasi khusus untuk field nama
        validateNameField(field) {
            const $field = $(field);
            const value = $field.val().trim();
            const errors = [];

            if (!value) {
                errors.push('Nama agency wajib diisi');
            } else {
                if (value.length < 3) {
                    errors.push('Nama agency minimal 3 karakter');
                }
                if (value.length > 100) {
                    errors.push('Nama agency maksimal 100 karakter');
                }
                if (!/^[a-zA-Z\s]+$/.test(value)) {
                    errors.push('Nama agency hanya boleh mengandung huruf dan spasi');
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

        async handleCreate(e) {
            e.preventDefault();
            console.log('Form submitted'); // Debug 1

            if (!this.form.valid()) {
                console.log('Form validation failed');
                return;
            }

            // Collect form data
            const formData = {
                action: 'create_agency',
                nonce: wpAgencyData.nonce,
                name: this.form.find('[name="name"]').val().trim(),
                provinsi_code: this.form.find('[name="provinsi_code"]').val(),
                regency_code: this.form.find('[name="regency_code"]').val(),
                status: this.form.find('[name="status"]').val()
            };

            // Add user_id if available (admin only)
            const userIdField = this.form.find('[name="user_id"]');
            if (userIdField.length && userIdField.val()) {
                formData.user_id = userIdField.val();
            }

            this.setLoadingState(true);
        
            console.log('Form data:', formData);

            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: formData
                });
        
                console.log('Server response:', response); // Debug 3

                if (response.success) {
                    console.log('Success response data:', response.data); // Debug 4
                    AgencyToast.success('Agency berhasil ditambahkan');
                    this.hideModal();
                    $(document).trigger('agency:created', [response.data]);
    
                    console.log('Triggered agency:created event'); // Debug 5

                } else {
                    AgencyToast.error(response.data?.message || 'Gagal menambah agency');
                }
            } catch (error) {
                console.error('Create agency error:', error);
                AgencyToast.error('Gagal menghubungi server. Silakan coba lagi.');
            } finally {
                this.setLoadingState(false);
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
                    provinsi_code: {
                        required: true
                    },
                    regency_code: {
                        required: true
                    },
                    user_id: {
                        required: this.form.find('#agency-owner').length > 0
                    }
                },
                messages: {
                    name: {
                        required: 'Nama agency wajib diisi',
                        minlength: 'Nama agency minimal 3 karakter',
                        maxlength: 'Nama agency maksimal 100 karakter'
                    },
                    provinsi_code: {
                        required: 'Provinsi wajib dipilih'
                    },
                    regency_code: {
                        required: 'Kabupaten/Kota wajib dipilih'
                    },
                    user_id: {
                        required: 'Admin wajib dipilih'
                    }
                }
            });
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

        showModal() {
            if (!this.form) {
                console.error('Form not initialized');
                return;
            }
            this.resetForm();
            this.loadAvailableProvinces();
            this.modal.fadeIn(300, () => {
                this.form.find('[name="name"]').focus();
            });
        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
            });
        },

        resetForm() {
            if (!this.form || !this.form[0]) return;

            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            if (this.form.validate()) {
                this.form.validate().resetForm();
            }

            // Reset wilayah selects
            const $regencySelect = this.form.find('[name="regency_code"]');
            $regencySelect
                .html('<option value="">Pilih Kabupaten/Kota</option>')
                .prop('disabled', true);
        },

        async loadAvailableProvinces() {
            const $provinceSelect = this.form.find('[name="provinsi_code"]');

            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_available_provinces_for_agency_creation',
                        nonce: wpAgencyData.nonce
                    }
                });

                if (response.success && response.data.provinces) {
                    // Clear existing options except the default
                    $provinceSelect.find('option:not(:first)').remove();

                    // Add new options
                    response.data.provinces.forEach(province => {
                        $provinceSelect.append(
                            `<option value="${province.value}">${province.label}</option>`
                        );
                    });
                } else {
                    console.error('Failed to load provinces:', response.data?.message);
                }
            } catch (error) {
                console.error('Error loading provinces:', error);
            }
        },

        async loadRegenciesByProvince() {
            const $provinceSelect = this.form.find('[name="provinsi_code"]');
            const $regencySelect = this.form.find('[name="regency_code"]');
            const provinceCode = $provinceSelect.val();

            if (!provinceCode) {
                $regencySelect
                    .html('<option value="">Pilih Kabupaten/Kota</option>')
                    .prop('disabled', true);
                return;
            }

            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'get_available_regencies_for_agency_creation',
                        province_code: provinceCode,
                        nonce: wpAgencyData.nonce
                    }
                });

                if (response.success && response.data.regencies) {
                    // Clear existing options
                    $regencySelect.empty();

                    // Add default option
                    $regencySelect.append('<option value="">Pilih Kabupaten/Kota</option>');

                    // Add new options
                    response.data.regencies.forEach(regency => {
                        $regencySelect.append(
                            `<option value="${regency.value}">${regency.label}</option>`
                        );
                    });

                    // Enable the select
                    $regencySelect.prop('disabled', false);
                } else {
                    console.error('Failed to load regencies:', response.data?.message);
                    $regencySelect
                        .html('<option value="">Pilih Kabupaten/Kota</option>')
                        .prop('disabled', true);
                }
            } catch (error) {
                console.error('Error loading regencies:', error);
                $regencySelect
                    .html('<option value="">Pilih Kabupaten/Kota</option>')
                    .prop('disabled', true);
            }
        }

    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CreateAgencyForm = CreateAgencyForm;
        CreateAgencyForm.init();
    });

})(jQuery);
