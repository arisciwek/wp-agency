/**
 * Wilayah Sync Helper
 *
 * @package     WP_Agency
 * @subpackage  Assets/JS/Auth
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/assets/js/auth/wilayah-sync.js
 *
 * Description: Helper untuk sync wilayah code ke id fields.
 *              Mengisi hidden fields provinsi_id dan regency_id
 *              berdasarkan pilihan user di select provinsi dan regency.
 *
 * Dependencies:
 * - jQuery
 * - wilayah-indonesia plugin
 *
 * Changelog:
 * 1.0.0 - 2025-01-22
 * - Initial version
 * - Auto-populate provinsi_id from provinsi_code select
 * - Auto-populate regency_id from regency_code select
 */
(function($) {
    'use strict';

    const WilayahSync = {
        init() {
            this.bindEvents();
        },

        bindEvents() {
            // Listen to province select change
            $(document).on('change', '.wilayah-province-select', function() {
                WilayahSync.syncProvinceId($(this));
            });

            // Listen to regency select change
            $(document).on('change', '.wilayah-regency-select', function() {
                WilayahSync.syncRegencyId($(this));
            });

            // Initialize on load if already have values
            $(document).ready(function() {
                $('.wilayah-province-select').each(function() {
                    if ($(this).val()) {
                        WilayahSync.syncProvinceId($(this));
                    }
                });

                $('.wilayah-regency-select').each(function() {
                    if ($(this).val()) {
                        WilayahSync.syncRegencyId($(this));
                    }
                });
            });
        },

        /**
         * Sync province code to province id
         * Fetches ID from wilayah-indonesia plugin via AJAX
         */
        syncProvinceId($select) {
            const code = $select.val();
            const $form = $select.closest('form');
            const $hiddenField = $form.find('input[name="provinsi_id"]');

            if (!code || !$hiddenField.length) {
                return;
            }

            // Get ID from wilayah-indonesia plugin
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_wilayah_id_from_code',
                    type: 'province',
                    code: code,
                    nonce: wpAgencyData.nonce
                },
                success(response) {
                    if (response.success && response.data.id) {
                        $hiddenField.val(response.data.id);
                        console.log('Province ID synced:', response.data.id);
                    }
                },
                error(xhr, status, error) {
                    console.error('Failed to sync province ID:', error);
                }
            });
        },

        /**
         * Sync regency code to regency id
         * Fetches ID from wilayah-indonesia plugin via AJAX
         */
        syncRegencyId($select) {
            const code = $select.val();
            const $form = $select.closest('form');
            const $hiddenField = $form.find('input[name="regency_id"]');

            if (!code || !$hiddenField.length) {
                return;
            }

            // Get ID from wilayah-indonesia plugin
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_wilayah_id_from_code',
                    type: 'regency',
                    code: code,
                    nonce: wpAgencyData.nonce
                },
                success(response) {
                    if (response.success && response.data.id) {
                        $hiddenField.val(response.data.id);
                        console.log('Regency ID synced:', response.data.id);
                    }
                },
                error(xhr, status, error) {
                    console.error('Failed to sync regency ID:', error);
                }
            });
        }
    };

    // Initialize
    WilayahSync.init();

})(jQuery);
