/**
 * Permission Matrix Script
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/settings/permissions-script.js
 *
 * Description: Handler untuk matrix permission
 *              Menangani update dan reset permission matrix
 *              Terintegrasi dengan modal konfirmasi dan toast notifications
 *
 * Dependencies:
 * - jQuery
 * - wpAgencyToast
 * - WIModal component
 *
 * Changelog:
 * 1.0.1 - 2024-12-08
 * - Replaced native confirm with WIModal for reset confirmation
 * - Added warning type modal styling
 * - Enhanced UX for reset operation
 * - Improved error handling and feedback
 *
 * 1.0.0 - 2024-12-02
 * - Initial implementation
 * - Basic permission matrix handling
 * - AJAX integration
 * - Toast notifications
 */
(function($) {
    'use strict';

    const PermissionMatrix = {
        init() {
            this.bindEvents();
            this.initTooltips();
            this.initResetButton();
        },

        bindEvents() {
            // Add any UI event handlers here
            $('#wp-agency-permissions-form').on('submit', function() {
                $(this).find('button[type="submit"]').prop('disabled', true);
            });
        },

        initTooltips() {
            if ($.fn.tooltip) {
                $('.tooltip-icon').tooltip({
                    position: { my: "center bottom", at: "center top-10" }
                });
            }
        },
        
        initResetButton() {
            const self = this;
            $('#reset-permissions-btn').on('click', function(e) {
                e.preventDefault();
                
                // Show confirmation modal
                WIModal.show({
                    title: 'Reset Permissions?',
                    message: 'This will restore all permissions to their default settings. This action cannot be undone.',
                    icon: 'alert-triangle',
                    type: 'warning',
                    confirmText: 'Reset Permissions',
                    confirmClass: 'button-warning',
                    cancelText: 'Cancel',
                    onConfirm: () => self.performReset()
                });
            });
        },

        performReset() {
            const $button = $('#reset-permissions-btn');
            const $icon = $button.find('.dashicons');
            const originalText = $button.text();

            // Set loading state
            $button.addClass('loading')
                   .prop('disabled', true)
                   .html(`<i class="dashicons dashicons-update"></i> Resetting...`);

            // Perform AJAX reset
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reset_agency_permissions',
                    nonce: wpAgencyData.nonce  // Changed this line
                },
                success: function(response) {
                    if (response.success) {
                        wpAgencyToast.success(response.data.message || 'Permissions reset successfully');
                        // Reload page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    } else {
                        wpAgencyToast.error(response.data.message || 'Failed to reset permissions');
                        // Reset button state
                        $button.removeClass('loading')
                               .prop('disabled', false)
                               .html(`<i class="dashicons dashicons-image-rotate"></i> ${originalText}`);
                    }
                },
                error: function() {
                    wpAgencyToast.error('Server error while resetting permissions');
                    // Reset button state
                    $button.removeClass('loading')
                           .prop('disabled', false)
                           .html(`<i class="dashicons dashicons-image-rotate"></i> ${originalText}`);
                }
            });
        }
    };

    // Initialize when document is ready
    $(document).ready(() => {
        if ($('#wp-agency-permissions-form').length) {
            PermissionMatrix.init();
        }
    });

})(jQuery);
