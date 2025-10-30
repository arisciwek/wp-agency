/**
 * Permission Matrix Script
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Settings
 * @version     1.0.3
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
 * 1.0.3 - 2025-10-29
 * - CRITICAL HOTFIX: Fixed checkbox disable timing bug
 * - Split lockPage() into lockPageForSave() and lockPageForReset()
 * - lockPageForSave(): Disables buttons only (checkboxes must be enabled for form submit)
 * - lockPageForReset(): Disables everything (safe for AJAX operation)
 * - Fixed bug: disabled checkboxes were not being submitted in POST data
 * - Now permissions save correctly
 *
 * 1.0.2 - 2025-10-29
 * - CRITICAL FIX: Added race condition protection
 * - Implemented cross-disable buttons (reset disables save, save disables reset)
 * - Added lockPage() method to prevent all interactions during operations
 * - Added unlockPage() for error recovery
 * - Disabled all checkboxes during reset/save operations
 * - Added page-level loading state
 *
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

        /**
         * Lock page for form submission
         * Disables buttons only - checkboxes must remain enabled for form data
         */
        lockPageForSave() {
            // Disable ALL buttons (reset + save)
            $('#reset-permissions-btn, button[type="submit"]').prop('disabled', true);

            // DO NOT disable checkboxes - they need to be submitted!
            // Add visual loading indicator to body
            $('body').addClass('permission-operation-in-progress');
        },

        /**
         * Lock page for reset operation
         * Disables everything including checkboxes (AJAX operation, no form submit)
         */
        lockPageForReset() {
            // Disable ALL buttons (reset + save)
            $('#reset-permissions-btn, button[type="submit"]').prop('disabled', true);

            // Disable ALL checkboxes (safe for AJAX, not form submit)
            $('.permission-checkbox').prop('disabled', true);

            // Add visual loading indicator to body
            $('body').addClass('permission-operation-in-progress');
        },

        /**
         * Unlock page (for error recovery only)
         */
        unlockPage() {
            $('#reset-permissions-btn, button[type="submit"]').prop('disabled', false);
            $('.permission-checkbox').prop('disabled', false);
            $('body').removeClass('permission-operation-in-progress');
        },

        bindEvents() {
            const self = this;

            // Handle form submission with race condition protection
            $('#wp-agency-permissions-form').on('submit', function(e) {
                // Lock page for save (buttons only, NOT checkboxes)
                // Checkboxes must remain enabled so browser can serialize form data
                self.lockPageForSave();

                // Note: Form will continue submitting, page will be locked until reload
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
            const self = this;
            const $button = $('#reset-permissions-btn');
            const $icon = $button.find('.dashicons');
            const originalText = $button.text();

            // CRITICAL: Lock entire page to prevent race conditions
            // Use lockPageForReset() - disables checkboxes too (safe for AJAX)
            self.lockPageForReset();

            // Set loading state on reset button
            $button.addClass('loading')
                   .html(`<i class="dashicons dashicons-update"></i> Resetting...`);

            // Perform AJAX reset
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'reset_agency_permissions',
                    nonce: wpAgencyData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        wpAgencyToast.success(response.data.message || 'Permissions reset successfully');
                        // Reload page immediately (no delay to prevent user actions)
                        // Force reload from server, not cache
                        setTimeout(() => {
                            window.location.reload(true);
                        }, 500); // Small delay to show toast
                    } else {
                        wpAgencyToast.error(response.data.message || 'Failed to reset permissions');
                        // Unlock page on error
                        self.unlockPage();
                        // Reset button state
                        $button.removeClass('loading')
                               .html(`<i class="dashicons dashicons-image-rotate"></i> ${originalText}`);
                    }
                },
                error: function() {
                    wpAgencyToast.error('Server error while resetting permissions');
                    // Unlock page on error
                    self.unlockPage();
                    // Reset button state
                    $button.removeClass('loading')
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
