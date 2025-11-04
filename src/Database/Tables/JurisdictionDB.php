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
 * - id                       : Primary key
 * - division_id              : Foreign key ke app_agency_divisions
 * - jurisdiction_regency_id  : Foreign key ke wi_regencies (wilayah kerja)
 * - is_primary               : Boolean, true jika regency ini adalah yang utama (tidak dapat dipindah)
 * - created_by               : User ID pembuat
 * - created_at               : Timestamp pembuatan
 * - updated_at               : Timestamp update terakhir
 *
 * Foreign Keys:
 * - division_id              : REFERENCES app_agency_divisions(id) ON DELETE CASCADE
 * - jurisdiction_regency_id  : REFERENCES wi_regencies(id) ON DELETE CASCADE
 *
 * Constraints:
 * - Unique constraint pada jurisdiction_regency_id untuk mencegah regency sama di division berbeda
 * - Unique constraint pada (division_id, jurisdiction_regency_id) untuk mencegah duplikasi dalam division
 *
 * Changelog:
 * 1.0.9 - 2025-01-04
 * - Renamed regency_id to jurisdiction_regency_id for clarity (avoid ambiguity with agencies/divisions)
 *
 * 1.0.8 - 2025-01-04 (TODO-4014)
 * - Changed jurisdiction_code to regency_id (bigint FK to wi_regencies)
 * - Added proper foreign key constraint to wi_regencies
 * - Updated unique constraints to use regency_id
 * - Fixed data type from varchar(10) to bigint(20) UNSIGNED
 *
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
            jurisdiction_regency_id bigint(20) UNSIGNED NOT NULL,
            is_primary tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 jika regency ini adalah yang utama dan tidak dapat dipindah',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY division_jurisdiction_regency (division_id, jurisdiction_regency_id),
            UNIQUE KEY jurisdiction_regency_unique (jurisdiction_regency_id),
            KEY division_id_index (division_id),
            KEY jurisdiction_regency_id_index (jurisdiction_regency_id),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints after table creation
     * This method is called from Installer.php
     *
     * Note: dbDelta doesn't support FK in CREATE TABLE, so we add them here
     * - FK to app_agency_divisions (division_id)
     * - FK to wi_regencies (jurisdiction_regency_id)
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_jurisdictions';

        $constraints = [
            // FK to app_agency_divisions
            [
                'name' => 'fk_jurisdiction_division',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_jurisdiction_division
                         FOREIGN KEY (division_id)
                         REFERENCES {$wpdb->prefix}app_agency_divisions(id)
                         ON DELETE CASCADE"
            ],
            // FK to wi_regencies
            [
                'name' => 'fk_jurisdiction_regency',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_jurisdiction_regency
                         FOREIGN KEY (jurisdiction_regency_id)
                         REFERENCES {$wpdb->prefix}wi_regencies(id)
                         ON DELETE CASCADE"
            ]
        ];

        foreach ($constraints as $constraint) {
            // Check if constraint already exists
            $constraint_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE()
                 AND TABLE_NAME = %s
                 AND CONSTRAINT_NAME = %s",
                $table_name,
                $constraint['name']
            ));

            // If constraint exists, drop it first
            if ($constraint_exists > 0) {
                $wpdb->query("ALTER TABLE {$table_name} DROP FOREIGN KEY `{$constraint['name']}`");
            }

            // Add foreign key constraint
            $result = $wpdb->query($constraint['sql']);
            if ($result === false) {
                error_log("[JurisdictionDB] Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
            }
        }
    }
}
