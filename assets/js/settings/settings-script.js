/**
* File: settings-script.js 
* Path: /wp-agency/assets/js/settings/settings-script.js
* 
* @package     WP_Agency 
* @subpackage  Admin/Assets/JS
* @version     1.0.0
* @author      arisciwek
*
* Description: Handles functionality for settings page including:
* - Form submission via AJAX
* - Cache settings toggle
* - Form validation
* - Success/error notifications
* 
* Dependencies:
* - jQuery
* - irToast (for notifications)
* - wpAgencySettings object (localized)
* 
* Last modified: 2024-11-26
* 
* Changelog:
* v1.0.0 - 2024-11-26
* - Initial implementation
* - Added form AJAX submission
* - Added cache duration toggle
* - Added validation
* - Added toast notifications
*/

jQuery(document).ready(function($) {
    // Handle enable/disable cache duration field
    $('#enable_caching').on('change', function() {
        const $durationRow = $('#cache_duration_row');
        if ($(this).is(':checked')) {
            $durationRow.removeClass('hidden');
        } else {
            $durationRow.addClass('hidden');
        }
    });

    // Handle form submission
    $('#wp-agency-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find(':submit');
        const formData = $form.serialize();

        // Disable submit button
        $submitButton.prop('disabled', true);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'save_wp_agency_settings',
                nonce: wpAgencySettings.nonce,
                formData: formData
            },
            success: function(response) {
                if (response.success) {
                    irToast.success(wpAgencySettings.strings.saved);
                } else {
                    irToast.error(response.data.message || wpAgencySettings.strings.saveError);
                }
            },
            error: function() {
                irToast.error(wpAgencySettings.strings.saveError);
            },
            complete: function() {
                $submitButton.prop('disabled', false);
            }
        });
    });

    // Validate form inputs
    function validateSettings() {
        const recordsPerPage = parseInt($('#records_per_page').val());
        if (recordsPerPage < 5 || recordsPerPage > 100) {
            irToast.error('Data per halaman harus antara 5-100');
            return false;
        }
        return true;
    }

    // Add form validation
    $('#wp-agency-settings-form').on('submit', function(e) {
        if (!validateSettings()) {
            e.preventDefault();
            return false;
        }
    });
});
