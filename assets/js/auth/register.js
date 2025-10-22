/**
 * Disnaker Registration Form Handler
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Auth
 * @version     1.2.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/auth/register.js
 *
 * Description: Menangani form registrasi disnaker:
 *              - AJAX submission
 *              - Validasi form
 *              - Toast notifications
 *              - Province/Regency handled by wilayah-indonesia plugin
 *
 * Dependencies:
 * - jQuery
 * - wp-agency-toast
 * - WordPress AJAX
 * - wilayah-indonesia plugin (for province/regency selects)
 *
 * Changelog:
 * 1.2.0 - 2025-01-22 (Task-2065-B Form Sync - Fix)
 * - Removed manual AJAX province/regency loading
 * - Now uses wilayah-indonesia plugin do_action
 * - Simplified code - plugin handles selects automatically
 *
 * 1.1.0 - 2025-01-22 (Task-2065-B Form Sync)
 * - Added province and regency select loading
 * - Added wilayah AJAX handlers
 * - Updated to work with shared form component
 *
 * 1.0.0 - 2024-01-11
 * - Initial version
 *
 * Last modified: 2025-01-22
 */
(function($) {
    'use strict';

    // Main registration module
    const AgencyRegistration = {
        init() {
            this.form = $('#agency-register-form');
            this.submitButton = this.form.find('button[type="submit"]');

            this.bindEvents();
            // Note: Province/Regency selects now handled by wilayah-indonesia plugin
        },

        bindEvents() {
            // Form submission
            this.form.on('submit', this.handleSubmit.bind(this));
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
