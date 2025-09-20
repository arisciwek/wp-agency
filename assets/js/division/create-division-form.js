/**
 * Createe Division Form Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Division
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/division/create-division-form.js
 *
 * Description: Handler untuk form tambah cabang.
 *              Includes form validation, AJAX submission,
 *              error handling, dan modal management.
 *              Terintegrasi dengan toast notifications.
 *
 * Dependencies:
 * - jQuery
 * - jQuery Validation
 * - DivisionToast for notifications
 * - WIModal for confirmations
 *
 * Last modified: 2024-12-10
 */

(function($) {
    'use strict';

    const CreateDivisionForm = {
        modal: null,
        form: null,
        agencyId: null,

        init() {
            this.modal = $('#create-division-modal');
            this.form = $('#create-division-form');
            this.bindEvents();
            this.initializeValidation();
        },

        bindEvents() {
            console.log('Starting bindEvents for Create.DivisionForm');
            this.form.on('submit', (e) => this.handleCreatee(e));
            this.form.on('input', 'input[name="name"]', (e) => {
                this.validateField(e.target);
            });

            console.log('Division Form element found:', this.form.length > 0);
            $('#add-division-btn').on('click', () => {
                const agencyId = window.Agency?.currentId;
                if (agencyId) {
                    this.showModal(agencyId);
                } else {
                    DivisionToast.error('Silakan pilih agency terlebih dahulu');
                }
            });

            $('.modal-close, .cancel-create', this.modal).on('click', () => this.hideModal());
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });

        },

        showModal(agencyId) {
            if (!agencyId) {
                DivisionToast.error('ID Agency tidak valid');
                return;
            }

            this.agencyId = agencyId;
            const agencyIdField = this.form.find('#agency_id');
            if (agencyIdField.length) {
                agencyIdField.val(agencyId);
            }

            this.resetForm();

            // Initialize Select2 for jurisdictions after agency ID is set
            this.initializeJurisdictionSelect();

            this.modal.addClass('division-modal').fadeIn(300, () => {
                const nameField = this.form.find('[name="name"]');
                if (nameField.length) {
                    nameField.focus();
                }
                $(document).trigger('division:modalOpened');
            });

        },

        hideModal() {
            this.modal.fadeOut(300, () => {
                this.resetForm();
                this.agencyId = null;
                $(document).trigger('division:modalClosed');
            });
        },

        initializeValidation() {
            this.form.validate({
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100
                    },
                    type: {
                        required: true
                    },
                    phone: {
                        required: true,
                        phoneID: true
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    postal_code: {
                        required: true,
                        digits: true,
                        minlength: 5,
                        maxlength: 5
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
                    }
                },
                messages: {
                    name: {
                        required: 'Nama cabang wajib diisi',
                        minlength: 'Nama cabang minimal 3 karakter',
                        maxlength: 'Nama cabang maksimal 100 karakter'
                    },
                    type: {
                        required: 'Tipe cabang wajib dipilih'
                    },
                    phone: {
                        required: 'Nomor telepon wajib diisi',
                        phoneID: 'Format nomor telepon tidak valid'
                    },
                    email: {
                        required: 'Email wajib diisi',
                        email: 'Format email tidak valid'
                    }
                },
                errorElement: 'span',
                errorClass: 'form-error',
                errorPlacement: (error, element) => {
                    error.insertAfter(element);
                },
                highlight: (element) => {
                    $(element).addClass('error');
                },
                unhighlight: (element) => {
                    $(element).removeClass('error');
                }
            });

            // Add custom phone validation for Indonesia
            $.validator.addMethod('phoneID', function(phone_number, element) {
                return this.optional(element) || phone_number.match(/^(\+62|62)?[\s-]?0?8[1-9]{1}\d{1}[\s-]?\d{4}[\s-]?\d{2,5}$/);
            }, 'Masukkan nomor telepon yang valid');
        },

        validateField(field) {
            const $field = $(field);
            if (!$field.length) return false;

            const value = $field.val()?.trim() ?? '';
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
            }

            $field.removeClass('error');
            $error.remove();
            return true;
        },

        getFieldValue(name) {
            const field = this.form.find(`[name="${name}"]`);
            return field.length ? field.val()?.trim() ?? '' : '';
        },

        async handleCreatee(e) {
            e.preventDefault();

            if (!this.form.valid()) return;

            const requestData = {
                action: 'create_division',
                nonce: wpAgencyData.nonce,
                agency_id: this.agencyId,
                name: this.getFieldValue('name'),
                type: this.getFieldValue('type'),
                nitku: this.getFieldValue('nitku'),
                postal_code: this.getFieldValue('postal_code'),
                latitude: this.getFieldValue('latitude'),
                longitude: this.getFieldValue('longitude'),
                address: this.getFieldValue('address'),
                phone: this.getFieldValue('phone'),
                email: this.getFieldValue('email'),
                provinsi_id: this.getFieldValue('provinsi_id'),
                regency_id: this.getFieldValue('regency_id'),
                jurisdictions: this.form.find('[name="jurisdictions[]"]').val(),

                // Admin data
                admin_username: this.getFieldValue('admin_username'),
                admin_email: this.getFieldValue('admin_email'),
                admin_firstname: this.getFieldValue('admin_firstname'),
                admin_lastname: this.getFieldValue('admin_lastname')
            };

            this.setLoadingState(true);

            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: requestData
                });

                if (response.success) {
                    DivisionToast.success('Cabang berhasil ditambahkan');
                    this.hideModal();

                    $(document).trigger('division:created', [response.data]);

                    if (window.DivisionDataTable) {
                        window.DivisionDataTable.refresh();
                    }
                } else {
                    DivisionToast.error(response.data?.message || 'Gagal menambah cabang');
                }
            } catch (error) {
                console.error('Createe division error:', error);
                DivisionToast.error('Gagal menghubungi server. Silakan coba lagi.');
            } finally {
                this.setLoadingState(false);
            }
        },

        setLoadingState(loading) {
            const submitBtn = this.form.find('[type="submit"]');
            const spinner = this.form.find('.spinner');

            if (loading) {
                submitBtn.prop('disabled', true);
                spinner.addClass('is-active');
                this.form.addClass('loading');
            } else {
                submitBtn.prop('disabled', false);
                spinner.removeClass('is-active');
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
                        agency_id: this.form.find('#agency_id').val(),
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
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.CreateDivisionForm = CreateDivisionForm;
        CreateDivisionForm.init();
    });

})(jQuery);
