/**
 * Agency Modal Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Agency
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/agency-modal-handler.js
 *
 * Description: Handles modal CRUD operations for Agency.
 *              Uses centralized modal system from wp-modal.
 *              Adapted from wp-customer customer-modal-handler.js
 *
 * Dependencies:
 * - jQuery
 * - WPModal (from wp-modal)
 * - wpAgencyConfig localized object
 *
 * Changelog:
 * 1.0.0 - 2025-12-28
 * - Initial version for Agency
 * - Adapted from wp-customer
 * - Edit and delete agency modal implementation
 */

(function($) {
    'use strict';

    /**
     * Agency Modal Handler
     */
    const AgencyModalHandler = {

        /**
         * Initialize modal handlers
         */
        init() {
            console.log('[AgencyModal] Initializing...');
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents() {
            // Edit Agency button
            $(document).on('click', '.wpdt-edit-agency', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const agencyId = $(e.currentTarget).data('id');
                console.log('[AgencyModal] Edit button clicked for agency:', agencyId);
                this.showEditModal(agencyId);
            });

            // Delete Agency button
            $(document).on('click', '.wpdt-delete-agency', (e) => {
                e.preventDefault();
                e.stopPropagation(); // Prevent row click
                const agencyId = $(e.currentTarget).data('id');
                console.log('[AgencyModal] Delete button clicked for agency:', agencyId);
                this.showDeleteConfirm(agencyId);
            });

            console.log('[AgencyModal] Events bound');
        },

        /**
         * Show Edit Agency Modal
         *
         * @param {number} agencyId Agency ID to edit
         */
        showEditModal(agencyId) {
            console.log('[AgencyModal] Opening edit agency modal for ID:', agencyId);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[AgencyModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            // Show modal with form (mode=edit)
            WPModal.show({
                type: 'form',
                title: 'Edit Agency',
                size: 'large',
                bodyUrl: wpAgencyConfig.ajaxUrl + '?action=get_agency_form&mode=edit&agency_id=' + agencyId + '&nonce=' + wpAgencyConfig.nonce,
                buttons: {
                    cancel: {
                        label: 'Cancel',
                        class: 'button'
                    },
                    submit: {
                        label: 'Update Agency',
                        class: 'button button-primary',
                        type: 'submit'
                    }
                },
                onSubmit: (formData, $form) => {
                    return this.handleSave(formData, $form);
                },
                onLoad: () => {
                    // Initialize province/regency cascade
                    this.initWilayahCascade();
                }
            });
        },

        /**
         * Show Delete Confirmation
         *
         * @param {number} agencyId Agency ID to delete
         */
        showDeleteConfirm(agencyId) {
            console.log('[AgencyModal] Showing delete confirm for agency ID:', agencyId);

            // Check if WPModal is available
            if (typeof WPModal === 'undefined') {
                console.error('[AgencyModal] WPModal not found!');
                alert('Modal system not available. Please refresh the page.');
                return;
            }

            WPModal.confirm({
                title: 'Delete Agency',
                message: 'Are you sure you want to delete this agency? This action cannot be undone.',
                confirmText: 'Delete',
                confirmClass: 'button-danger',
                onConfirm: () => {
                    this.handleDelete(agencyId);
                }
            });
        },

        /**
         * Initialize Wilayah (Province/Regency) cascade
         */
        initWilayahCascade() {
            // Province change handler
            $(document).off('change', '#agency-province').on('change', '#agency-province', (e) => {
                const provinceId = $(e.target).val();
                const $regencySelect = $('#agency-regency');

                if (!provinceId) {
                    $regencySelect.html('<option value="">Select province first</option>').prop('disabled', true);
                    return;
                }

                // Load regencies for selected province
                $.ajax({
                    url: ajaxurl,
                    method: 'GET',
                    data: {
                        action: 'get_regencies',
                        province_id: provinceId,
                        nonce: wpAgencyConfig.nonce
                    },
                    success: (response) => {
                        if (response.success && response.data.regencies) {
                            let options = '<option value="">Select City/Regency</option>';
                            response.data.regencies.forEach((regency) => {
                                options += `<option value="${regency.id}">${regency.name}</option>`;
                            });
                            $regencySelect.html(options).prop('disabled', false);
                        }
                    },
                    error: () => {
                        $regencySelect.html('<option value="">Error loading regencies</option>');
                    }
                });
            });
        },

        /**
         * Handle form save (update)
         *
         * @param {Object} formData Form data
         * @param {jQuery} $form Form element
         * @return {boolean} false to prevent default
         */
        handleSave(formData, $form) {
            console.log('[AgencyModal] Saving agency...');

            // Remove any existing error messages
            $('.wpapp-modal-error').remove();
            $('.wpapp-field-error').remove();
            $('.wpapp-form-field').removeClass('has-error');

            // Validate form first
            if (!this.validateForm($form)) {
                console.log('[AgencyModal] Form validation failed');
                return false;
            }

            // Create FormData from form element if not already FormData
            if (!(formData instanceof FormData)) {
                console.log('[AgencyModal] Creating new FormData from form');
                formData = new FormData($form[0]);
            }

            // Show loading
            WPModal.loading(true);

            // Submit via AJAX
            $.ajax({
                url: wpAgencyConfig.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success) {
                        console.log('[AgencyModal] Save successful:', response);

                        // Get agency ID from response
                        const agencyId = response.data.agency ? response.data.agency.id : null;
                        console.log('[AgencyModal] Agency ID from response:', agencyId);

                        // Stop loading FIRST
                        WPModal.loading(false);

                        // Hide modal immediately
                        WPModal.hide();

                        // Refresh DataTable
                        if (window.agencyDataTableInstance) {
                            console.log('[AgencyModal] Refreshing DataTable...');
                            window.agencyDataTableInstance.ajax.reload(null, false);
                        } else {
                            console.error('[AgencyModal] agencyDataTableInstance not available!');
                        }

                    } else {
                        console.error('[AgencyModal] Save failed:', response);
                        WPModal.loading(false);
                        this.showErrorInModal(response.data.message || 'Failed to save agency');
                    }
                },
                error: (xhr, status, error) => {
                    WPModal.loading(false);
                    console.error('[AgencyModal] AJAX error:', error);
                    this.showErrorInModal('Network error. Please try again.');
                }
            });

            return false; // Prevent default form submission
        },

        /**
         * Show error message inside modal (without closing it)
         *
         * @param {string} message Error message to display
         */
        showErrorInModal(message) {
            // Remove any existing error messages
            $('.wpapp-modal-error').remove();

            // Create error message element
            const $errorDiv = $('<div class="wpapp-modal-error" style="' +
                'background: #dc3232; ' +
                'color: white; ' +
                'padding: 12px 15px; ' +
                'margin: 0 0 15px 0; ' +
                'border-radius: 4px; ' +
                'font-size: 14px; ' +
                'line-height: 1.5;">' +
                '<strong>Error:</strong> ' + message +
                '</div>');

            // Insert error message at top of modal body
            $('.wpapp-modal-body').prepend($errorDiv);

            // Scroll to top of modal to show error
            $('.wpapp-modal-body').scrollTop(0);

            // Auto-remove after 10 seconds
            setTimeout(function() {
                $errorDiv.fadeOut(400, function() {
                    $(this).remove();
                });
            }, 10000);
        },

        /**
         * Validate form before submit
         *
         * @param {jQuery} $form Form element
         * @return {boolean} True if valid, false if invalid
         */
        validateForm($form) {
            let isValid = true;
            const errors = [];

            // Agency Name (required)
            const agencyName = $form.find('#agency-name').val();
            if (!agencyName || agencyName.trim() === '') {
                this.showFieldError('#agency-name', 'Agency name is required');
                errors.push('Agency name is required');
                isValid = false;
            }

            // Province validation (required)
            const provinceId = $form.find('#agency-province').val();
            if (!provinceId || provinceId === '') {
                this.showFieldError('#agency-province', 'Province is required');
                errors.push('Province is required');
                isValid = false;
            }

            // Regency validation (required)
            const regencyId = $form.find('#agency-regency').val();
            if (!regencyId || regencyId === '') {
                this.showFieldError('#agency-regency', 'City/Regency is required');
                errors.push('City/Regency is required');
                isValid = false;
            }

            // Show summary error if there are errors
            if (!isValid) {
                const errorMessage = 'Please fix the following errors:<br>• ' + errors.join('<br>• ');
                this.showErrorInModal(errorMessage);
            }

            return isValid;
        },

        /**
         * Show error message for specific field
         *
         * @param {string} fieldSelector Field selector
         * @param {string} message Error message
         */
        showFieldError(fieldSelector, message) {
            const $field = $(fieldSelector);
            const $wrapper = $field.closest('.wpapp-form-field');

            // Add error class to wrapper
            $wrapper.addClass('has-error');

            // Remove existing error message
            $wrapper.find('.wpapp-field-error').remove();

            // Add error message below field
            const $errorMsg = $('<span class="wpapp-field-error" style="' +
                'color: #dc3232; ' +
                'font-size: 12px; ' +
                'display: block; ' +
                'margin-top: 4px;">' +
                message +
                '</span>');

            $field.after($errorMsg);

            // Add red border to field
            $field.css('border-color', '#dc3232');

            // Remove error on input
            $field.one('input change', function() {
                $wrapper.removeClass('has-error');
                $wrapper.find('.wpapp-field-error').remove();
                $(this).css('border-color', '');
            });
        },

        /**
         * Handle agency deletion
         *
         * @param {number} agencyId Agency ID to delete
         */
        handleDelete(agencyId) {
            console.log('[AgencyModal] Deleting agency ID:', agencyId);

            // Show loading
            WPModal.loading(true, 'Deleting agency...');

            const deleteData = {
                action: 'delete_agency',
                agency_id: agencyId,
                nonce: wpAgencyConfig.nonce
            };

            $.ajax({
                url: wpAgencyConfig.ajaxUrl,
                method: 'POST',
                data: deleteData,
                success: (response) => {
                    WPModal.loading(false);

                    if (response.success) {
                        console.log('[AgencyModal] Delete successful:', response);

                        // Refresh DataTable
                        if (window.agencyDataTableInstance) {
                            console.log('[AgencyModal] Refreshing DataTable...');
                            window.agencyDataTableInstance.ajax.reload(null, false);
                        } else {
                            console.error('[AgencyModal] agencyDataTableInstance not available!');
                        }
                    } else {
                        console.error('[AgencyModal] Delete failed:', response);
                        WPModal.info({
                            infoType: 'error',
                            title: 'Error',
                            message: response.data.message || 'Failed to delete agency',
                            autoClose: 5000
                        });
                    }
                },
                error: (xhr, status, error) => {
                    WPModal.loading(false);
                    console.error('[AgencyModal] Delete AJAX error:', error);

                    WPModal.info({
                        infoType: 'error',
                        title: 'Error',
                        message: 'Network error. Please try again.',
                        autoClose: 5000
                    });
                }
            });
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('[AgencyModal] Document ready');
        AgencyModalHandler.init();
    });

    // Export to global scope
    window.AgencyModalHandler = AgencyModalHandler;

})(jQuery);
