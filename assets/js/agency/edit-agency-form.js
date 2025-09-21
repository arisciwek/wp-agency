/**
 * Agency Form Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Components
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/components/edit-agency-form.js
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

// Edit Agency Form Handler
(function($) {
    'use strict';

    const EditAgencyForm = {
        modal: null,
        form: null,

        init() {
            this.modal = $('#edit-agency-modal');
            this.form = $('#edit-agency-form');

            this.bindEvents();
            this.initializeValidation();
            this.setupNPWPInput();

        },

        // Replace the setupNPWPInput function in edit-agency-form.js with this:
        setupNPWPInput() {
            const $npwpInput = $('#edit-npwp');
            
            $npwpInput.off('input keydown blur').inputmask('remove');  // Remove any existing inputmask
            
            let currentValue = $npwpInput.val();  // Store initial value
            
            $npwpInput.inputmask({
                mask: '99.999.999.9-999.999',
                placeholder: '_',
                clearMaskOnLostFocus: false,     // Don't clear mask when focus is lost
                removeMaskOnSubmit: false,       // Keep mask when form is submitted
                showMaskOnFocus: true,           // Show mask when field gets focus
                showMaskOnHover: true,           // Show mask on hover
                autoUnmask: false,               // Don't automatically unmask
                onBeforePaste: function(pastedValue, opts) {
                    return pastedValue.replace(/[^\d]/g, '');
                },
                onBeforeWrite: function(event, buffer, caretPos, opts) {
                    // Prevent clearing of existing value
                    if (buffer.join('').replace(/[^0-9]/g, '').length === 0) {
                        return {
                            refreshFromBuffer: true,
                            buffer: currentValue.split('')
                        };
                    }
                    return true;
                },
                onKeyDown: function(event, buffer, caretPos, opts) {
                    currentValue = $(event.target).val();
                }
            });

            // Restore initial value if exists
            if (currentValue) {
                $npwpInput.val(currentValue);
            }
        },

        // Add this function to validate NPWP format
        isValidNPWP(npwp) {
            // Check if matches format: 99.999.999.9-999.999
            return /^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/.test(npwp);
        },

        bindEvents() {
            // 1. Form submission handler
            $(document).on('submit', '#edit-agency-form', async (e) => {
                e.preventDefault();
                if (!this.form.valid()) {
                    return;
                }
                await this.handleUpdate();
            });

            // 2. NIB validation
            this.form.find('[name="nib"]').on('input', function() {
                let val = $(this).val().replace(/\D/g, '');
                if (val.length > 13) {
                    val = val.substr(0, 13);
                }
                $(this).val(val);
            });

            // 3. Province change handler
            this.form.find('[name="provinsi_code"]').on('change', function() {
                const $regencySelect = $('#edit-regency');
                $regencySelect
                    .html('<option value="">Pilih Kabupaten/Kota</option>')
                    .prop('disabled', true);

                if ($(this).val()) {
                    $regencySelect.prop('disabled', false);
                }
            });

            // 4. Status change handler dengan konfirmasi
            this.form.find('[name="status"]').on('change', function() {
                const status = $(this).val();
                if (status === 'inactive') {
                    if (!confirm('Apakah Anda yakin ingin menonaktifkan agency ini?')) {
                        $(this).val('active');
                        return false;
                    }
                }
            });

            // 5. User select handler (admin only)
            if (this.form.find('#edit-user').length) {
                this.form.find('#edit-user').on('change', function() {
                    const userId = $(this).val();
                    if (userId && !confirm('Mengubah admin akan mempengaruhi akses ke agency ini. Lanjutkan?')) {
                        $(this).val('');
                        return false;
                    }
                });
            }

            // 6. Modal close handlers
            $('.modal-close', this.modal).on('click', () => this.hideModal());
            $('.cancel-edit', this.modal).on('click', () => this.hideModal());

            // 7. Modal overlay click handler
            this.modal.on('click', (e) => {
                if ($(e.target).is('.modal-overlay')) {
                    this.hideModal();
                }
            });

            // 8. NPWP input handler
            $('#edit-npwp').on('input', function() {
                $(this).val(function(_, v) {
                    const digits = v.replace(/\D/g, '').slice(0, 15);
                    if (!digits) return '';
                    
                    if (digits.length === 15) {
                        AgencyToast.success('Format NPWP lengkap');
                    }
                });
            });

            // 9. Enter key handler dalam form fields
            this.form.find('input, select').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const $inputs = $('#edit-agency-form').find('input, select');
                    const nextIndex = $inputs.index(this) + 1;
                    if (nextIndex < $inputs.length) {
                        $inputs.eq(nextIndex).focus();
                    } else {
                        $('#edit-agency-form').submit();
                    }
                }
            });

            // 10. Validasi fields yang required
            this.form.find('[required]').on('blur', function() {
                if (!$(this).val()) {
                    $(this).addClass('error');
                    if (!$(this).next('.form-error').length) {
                        $('<span class="form-error">Field ini wajib diisi</span>').insertAfter(this);
                    }
                } else {
                    $(this).removeClass('error');
                    $(this).next('.form-error').remove();
                }
            });

            // 11. Remove error state on input
            this.form.find('input, select').on('input change', function() {
                $(this).removeClass('error');
                $(this).next('.form-error').remove();
            });
        },

        showEditForm(data) {
            if (!data || !data.agency) {
                AgencyToast.error('Data agency tidak valid');
                return;
            }

            // Reset form first
            this.resetForm();
            
            const agency = data.agency;
            
            try {
                // Debug log
                console.log('Loading agency data:', agency);

                // Basic Information
                this.form.find('#agency-id').val(agency.id);
                this.form.find('[name="name"]').val(agency.name);

                // NPWP Handling with InputMask
                if (agency.npwp) {
                    const $npwpInput = $('#edit-npwp');
                    // Remove any non-digit characters and format the NPWP
                    const cleanNpwp = agency.npwp.replace(/\D/g, '');
                    if (cleanNpwp.length === 15) {
                        $npwpInput.val(agency.npwp);
                    }
                }

                // NIB Handling
                if (agency.nib) {
                    this.form.find('[name="nib"]')
                        .val(agency.nib)
                        .trigger('input'); // Trigger input event for validation
                }

                // Status Handling
                const status = agency.status || 'active';
                this.form.find('[name="status"]').val(status);
                
                // Location (Province & Regency)
                if (agency.provinsi_code) {
                    const $provinsiSelect = this.form.find('[name="provinsi_code"]');
                    const $regencySelect = this.form.find('[name="regency_code"]');

                    // Set province and trigger change
                    $provinsiSelect
                        .val(agency.provinsi_code)
                        .trigger('change')
                        .prop('disabled', true); // Temporarily disable while loading regencies

                    // Handle regency selection after province change
                    if (agency.regency_code) {
                        // Use one-time event handler
                        $regencySelect.one('wilayah:loaded', () => {
                            $regencySelect
                                .val(agency.regency_code)
                                .trigger('change');

                            // Re-enable province select
                            $provinsiSelect.prop('disabled', false);
                        });
                    }
                }

                // User Assignment (Admin only)
                const $userSelect = this.form.find('#edit-user');
                if ($userSelect.length && agency.user_id) {
                    $userSelect.val(agency.user_id);
                }

                // Update modal title
                this.modal.find('.modal-header h3')
                    .text(`Edit Agency: ${agency.name}`);

                // Show the modal
                this.modal.fadeIn(300, () => {
                    // Focus first input after modal is visible
                    this.form.find('[name="name"]').focus();
                });

                // Add editing class to form
                this.form.addClass('editing');
                this.form.data('agency-id', agency.id);

                // Trigger event for other components
                $(document).trigger('agency:edit:shown', [agency]);

                // Log success
                console.log('Agency data loaded successfully');

            } catch (error) {
                // Handle any errors during data population
                console.error('Error populating edit form:', error);
                AgencyToast.error('Gagal memuat data agency');
                this.hideModal();
            }
        },

        hideModal() {
            this.modal
                .removeClass('active')
                .fadeOut(300, () => {
                    this.resetForm();
                    $('#edit-mode').hide();
                });
        },

        initializeValidation() {
            // Extend jQuery validation with custom methods
            $.validator.addMethod("validNpwp", function(value, element) {
                if (!value) return true; // Optional field
                return /^\d{2}\.\d{3}\.\d{3}\.\d{1}-\d{3}\.\d{3}$/.test(value);
            }, "Format NPWP tidak valid (99.999.999.9-999.999)");

            $.validator.addMethod("validNib", function(value, element) {
                if (!value) return true; // Optional field
                return /^\d{13}$/.test(value);
            }, "NIB harus 13 digit angka");

            $.validator.addMethod("validName", function(value, element) {
                return this.optional(element) || /^[a-zA-Z0-9\s.,'-]+$/.test(value);
            }, "Nama hanya boleh mengandung huruf, angka, dan tanda baca umum");

            // Initialize form validation
            this.form.validate({
                // Validation rules
                rules: {
                    name: {
                        required: true,
                        minlength: 3,
                        maxlength: 100,
                        validName: true
                    },
                    npwp: {
                        validNpwp: true
                    },
                    nib: {
                        validNib: true
                    },
                    provinsi_code: {
                        required: true
                    },
                    regency_code: {
                        required: true
                    },
                    status: {
                        required: true
                    },
                    user_id: {
                        required: this.form.find('#edit-user').length > 0
                    }
                },

                // Error messages
                messages: {
                    name: {
                        required: "Nama agency wajib diisi",
                        minlength: "Nama agency minimal 3 karakter",
                        maxlength: "Nama agency maksimal 100 karakter"
                    },
                    npwp: {
                        validNpwp: "Format NPWP tidak valid (99.999.999.9-999.999)"
                    },
                    nib: {
                        validNib: "NIB harus 13 digit angka"
                    },
                    provinsi_code: {
                        required: "Provinsi wajib dipilih"
                    },
                    regency_code: {
                        required: "Kabupaten/Kota wajib dipilih"
                    },
                    status: {
                        required: "Status wajib dipilih"
                    },
                    user_id: {
                        required: "Admin wajib dipilih"
                    }
                },

                // Validation options
                errorElement: "span",
                errorClass: "form-error",
                validClass: "form-valid",
                
                // Error placement
                errorPlacement: function(error, element) {
                    error.insertAfter(element);
                    element.addClass('error');
                },
                
                // Success handling
                success: function(label, element) {
                    $(element).removeClass('error');
                    label.remove();
                },

                // Submit handler
                submitHandler: function(form) {
                    // Form is valid, let the normal submit handler take over
                    return true;
                },

                // Invalid form handler
                invalidHandler: function(event, validator) {
                    const errors = validator.numberOfInvalids();
                    if (errors) {
                        AgencyToast.error(`Terdapat ${errors} field yang belum valid`);
                        
                        // Focus first error
                        validator.errorList[0].element.focus();
                    }
                },

                // Highlight error fields
                highlight: function(element, errorClass, validClass) {
                    $(element)
                        .addClass('error')
                        .removeClass(validClass);
                },

                // Unhighlight valid fields
                unhighlight: function(element, errorClass, validClass) {
                    $(element)
                        .removeClass('error')
                        .addClass(validClass);
                },

                // Ignore hidden and disabled fields
                ignore: ":hidden, :disabled",

                // Validate on specific events
                onkeyup: function(element) {
                    // Delay validation until typing stops
                    clearTimeout($(element).data('timer'));
                    $(element).data('timer', setTimeout(function() {
                        $(element).valid();
                    }, 500));
                },

                // Focus out validation
                onfocusout: function(element) {
                    $(element).valid();
                },

                // Change event validation
                onchange: function(element) {
                    $(element).valid();
                }
            });

            // Add custom validation handling for NPWP
            $('#edit-npwp').on('blur', function() {
                const value = $(this).val();
                if (value && !$.validator.methods.validNpwp(value)) {
                    $(this).addClass('error');
                    if (!$(this).next('.form-error').length) {
                        $('<span class="form-error">Format NPWP tidak valid</span>').insertAfter(this);
                    }
                } else {
                    $(this).removeClass('error');
                    $(this).next('.form-error').remove();
                }
            });

            // Add real-time NIB validation
            $('[name="nib"]').on('input', function() {
                const value = $(this).val();
                if (value && !$.validator.methods.validNib(value)) {
                    $(this).addClass('error');
                    if (!$(this).next('.form-error').length) {
                        $('<span class="form-error">NIB harus 13 digit angka</span>').insertAfter(this);
                    }
                } else {
                    $(this).removeClass('error');
                    $(this).next('.form-error').remove();
                }
            });

            // Log validation initialization
            console.log('Form validation initialized');
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

        async handleUpdate() {
            const id = this.form.find('#agency-id').val();
            const requestData = {
                action: 'update_agency',
                nonce: wpAgencyData.nonce,
                id: id,
                name: this.form.find('[name="name"]').val().trim(),
                npwp: this.form.find('#edit-npwp').val(),
                nib: this.form.find('[name="nib"]').val().trim(),
                status: this.form.find('[name="status"]').val(),
                provinsi_code: this.form.find('[name="provinsi_code"]').val(),
                regency_code: this.form.find('[name="regency_code"]').val(),
                user_id: this.form.find('#edit-user').val()
            };

            this.setLoadingState(true);

            try {
                const response = await $.ajax({
                    url: wpAgencyData.ajaxUrl,
                    type: 'POST',
                    data: requestData
                });

                if (response.success) {
                    AgencyToast.success('Agency berhasil diperbarui');
                    this.hideModal();

                    if (id) {
                        window.location.hash = id;
                    }

                    $(document).trigger('agency:updated', [response]);
                } else {
                    AgencyToast.error(response.data?.message || 'Gagal memperbarui agency');
                }
            } catch (error) {
                console.error('Update agency error:', error);
                AgencyToast.error('Gagal menghubungi server');
            } finally {
                this.setLoadingState(false);
            }
        },
        resetForm() {
            this.form[0].reset();
            this.form.find('.form-error').remove();
            this.form.find('.error').removeClass('error');
            this.form.validate().resetForm();

            // Clear NPWP input
            $('#edit-npwp').val('');
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        window.EditAgencyForm = EditAgencyForm;
        EditAgencyForm.init();
    });

})(jQuery);
