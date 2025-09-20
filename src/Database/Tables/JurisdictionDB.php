<?php
/**
 * Jurisdiction Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Tables/JurisdictionDB.php
 *
 * Description: Mendefinisikan struktur tabel jurisdictions.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Menyimpan relasi antara divisions dan regencies untuk wilayah kerja.
 *              Includes field is_primary untuk menandai regency utama yang tidak dapat dipindah.
 *
 * Fields:
 * - id             : Primary key
 * - division_id    : Foreign key ke app_divisions
 * - regency_id     : Foreign key ke wi_regencies
 * - is_primary     : Boolean, true jika regency ini adalah yang utama (tidak dapat dipindah)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Foreign Keys:
 * - division_id    : REFERENCES app_divisions(id) ON DELETE CASCADE
 * - regency_id     : REFERENCES wi_regencies(id) ON DELETE CASCADE
 *
 * Constraints:
 * - Unique constraint pada (division_id, regency_id) untuk mencegah duplikasi
 *
 * Changelog:
 * 1.0.0 - 2024-01-27
 * - Initial version
 */

namespace WPAgency\Database\Tables;

defined('ABSPATH') || exit;

class JurisdictionDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_jurisdictions';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            division_id bigint(20) UNSIGNED NOT NULL,
            regency_id bigint(20) UNSIGNED NOT NULL,
            is_primary tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 jika regency ini adalah yang utama dan tidak dapat dipindah',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY division_regency (division_id, regency_id),
            KEY division_id_index (division_id),
            KEY regency_id_index (regency_id),
            KEY created_by_index (created_by),
            CONSTRAINT `{$wpdb->prefix}app_agency_jurisdictions_ibfk_1`
                FOREIGN KEY (division_id)
                REFERENCES `{$wpdb->prefix}app_divisions` (id)
                ON DELETE CASCADE,
            CONSTRAINT `{$wpdb->prefix}app_agency_jurisdictions_ibfk_2`
                FOREIGN KEY (regency_id)
                REFERENCES `{$wpdb->prefix}wi_regencies` (id)
                ON DELETE CASCADE
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints after table creation
     * This method is called from Installer.php
     */
    public static function add_foreign_keys() {
        global $wpdb;

        // Foreign keys are already defined in get_schema()
        // This method can be used for additional constraints if needed
    }
}
