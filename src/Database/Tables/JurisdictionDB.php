<?php
/**
 * Jurisdiction Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.0.7
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
 * - division_id    : Foreign key ke app_agency_divisions
 * - jurisdiction_code   : Kode regency ke wi_regencies
 * - is_primary     : Boolean, true jika regency ini adalah yang utama (tidak dapat dipindah)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Foreign Keys:
 * - division_id    : REFERENCES app_agency_divisions(id) ON DELETE CASCADE
 * - jurisdiction_code   : References wi_regencies(code) (no FK constraint, lookup by code)
 *
 * Constraints:
 * - Unique constraint pada jurisdiction_code untuk mencegah regency sama di division berbeda
 * - Unique constraint pada (division_id, jurisdiction_code) untuk mencegah duplikasi dalam division
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
            jurisdiction_code varchar(10) NOT NULL,
            is_primary tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 jika regency ini adalah yang utama dan tidak dapat dipindah',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY division_regency (division_id, jurisdiction_code),
            UNIQUE KEY regency_unique (jurisdiction_code),
            KEY division_id_index (division_id),
            KEY jurisdiction_code_index (jurisdiction_code),
            KEY created_by_index (created_by),
            CONSTRAINT `{$wpdb->prefix}app_agency_jurisdictions_ibfk_1`
                FOREIGN KEY (division_id)
                REFERENCES `{$wpdb->prefix}app_agency_divisions` (id)
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
