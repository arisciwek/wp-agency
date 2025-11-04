<?php
/**
 * Agencys Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Tables/AgencysDB.php
 *
 * Description: Mendefinisikan struktur tabel agencies.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes field untuk integrasi wilayah.
 *              Menyediakan foreign key untuk agency-division.
 *
 * Fields:
 * - id             : Primary key
 * - code           : Format
 * - name           : Nama agency
 * - nik            : Nomor Induk Kependudukan
 * - province_id    : Foreign key ke wi_provinces (nullable)
 * - regency_id     : Foreign key ke wi_regencies (nullable)
 * - user_id        : ID User WP sebagai Owner (nullable)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Foreign Keys:
 * - province_id    : REFERENCES wi_provinces(id) ON DELETE SET NULL
 * - regency_id     : REFERENCES wi_regencies(id) ON DELETE SET NULL
 *
 * Changelog:
 * 1.0.8 - 2025-01-04 (TODO-4014)
 * - Changed provinsi_code to province_id (bigint FK to wi_provinces)
 * - Changed regency_code to regency_id (bigint FK to wi_regencies)
 * - Added foreign key constraints to wilayah-indonesia tables
 * - Updated unique constraint to use province_id and regency_id
 *
 * 1.0.3 - 2024-01-20
 * - Removed npwp and nib fields as they are no longer used
 * - Removed unique constraints for npwp and nib
 *
 * 1.0.2 - 2024-01-19
 * - Modified code field to varchar(13) for new format CUST-TTTTRRRR
 * - Removed unique constraint from name field
 * - Added unique constraint for name+province+regency
 *
 * 1.0.1 - 2024-01-11
 * - Added nik field with unique constraint
 * - Added npwp field with unique constraint
 *
 * 1.0.0 - 2024-01-07
 * - Initial version
 */

namespace WPAgency\Database\Tables;

defined('ABSPATH') || exit;

class AgencysDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agencies';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            code varchar(10) NOT NULL,
            name varchar(100) NOT NULL,
            status enum('inactive','active') NOT NULL DEFAULT 'inactive',
            province_id bigint(20) UNSIGNED NULL,
            regency_id bigint(20) UNSIGNED NULL,
            user_id bigint(20) UNSIGNED NULL,
            reg_type enum('self','by_admin','generate') NOT NULL DEFAULT 'self',
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            UNIQUE KEY name_region (name, province_id, regency_id),
            KEY province_id_index (province_id),
            KEY regency_id_index (regency_id),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     * TODO-4014: Foreign keys ke wilayah-indonesia
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agencies';

        $constraints = [
            // FK to wi_provinces
            [
                'name' => 'fk_agency_province',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_agency_province
                         FOREIGN KEY (province_id)
                         REFERENCES {$wpdb->prefix}wi_provinces(id)
                         ON DELETE SET NULL"
            ],
            // FK to wi_regencies
            [
                'name' => 'fk_agency_regency',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_agency_regency
                         FOREIGN KEY (regency_id)
                         REFERENCES {$wpdb->prefix}wi_regencies(id)
                         ON DELETE SET NULL"
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
                error_log("[AgencysDB] Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
            }
        }
    }
}
