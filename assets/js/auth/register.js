/**
 * Agency Registration Form Handler
 * 
 * @package     WP_Agency
 * @subpackage  Assets/JS/Auth
 * @version     1.0.0
 * @author      arisciwek
 * 
 * Path: /wp-agency/assets/js/auth/register.js
 * 
 * Description: Menangani form registrasi agency:
 *              - AJAX submission
 *              - Validasi form
 *              - Format NPWP
 *              - Toast notifications
 * 
 * Dependencies:
 * - jQuery
 * - wp-agency-toast
 * - WordPress AJAX
 *
 * Last modified: 2024-01-11
 */
(function($) {
    'use strict';

    // Main registration module
    const AgencyRegistration = {
        init() {
            this.form = $('#agency-register-form');
            this.submitButton = this.form.find('button[type="submit"]');
            
            this.bindEvents();
        },

        bindEvents() {
            // NPWP formatter
            $('#npwp').on('input', this.formatNPWP);
            
            // Form submission
            this.form.on('submit', this.handleSubmit.bind(this));
        },

        formatNPWP(e) {
            let value = $(this).val().replace(/\D/g, '');
            if (value.length > 15) value = value.substr(0, 15);
            value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{1})(\d{3})(\d{3})/, '$1.$2.$3.$4-$5.$6');
            $(this).val(value);
        },

        handleSubmit(e) {
            e.preventDefault();
            
            const formData = new FormData(this.form[0]);
            formData.append('action', 'wp_agency_register');
            
            this.submitButton
                .prop('disabled', true)
                .text(wpAgencyData.i18n.registering || 'Mendaftar...');

            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: this.handleSuccess.bind(this),
                error: this.handleError.bind(this),
                complete: this.handleComplete.bind(this)
            });
        },

        handleSuccess(response) {
            if (response.success) {
                wpAgencyToast.success(response.data.message);
                setTimeout(() => {
                    window.location.href = response.data.redirect;
                }, 1500);
            } else {
                wpAgencyToast.error(response.data.message);
            }
        },

        handleError() {
            wpAgencyToast.error(
                wpAgencyData.i18n.error || 'Terjadi kesalahan. Silakan coba lagi.'
            );
        },

        handleComplete() {
            this.submitButton
                .prop('disabled', false)
                .text(wpAgencyData.i18n.register || 'Daftar');
        }
    };

    // Initialize on document ready
    $(document).ready(() => {
        AgencyRegistration.init();
    });

})(jQuery);
