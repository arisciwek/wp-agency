<?php
/**
 * Divisiones Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Tables/DivisionsDB.php
 *
 * Description: Mendefinisikan struktur tabel divisions.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes field untuk integrasi wilayah.
 *              Menyediakan foreign key ke agencies table.
 *
 * Fields:
 * - id             : Primary key
 * - agency_id      : Foreign key ke agency
 * - code           : Format
 * - name           : Nama division
 * - type           : Tipe wilayah (cabang)
 * - province_id    : Foreign key ke wi_provinces (nullable)
 * - regency_id     : Foreign key ke wi_regencies (nullable)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Foreign Keys:
 * - agency_id      : REFERENCES app_agencies(id) ON DELETE CASCADE
 * - province_id    : REFERENCES wi_provinces(id) ON DELETE SET NULL
 * - regency_id     : REFERENCES wi_regencies(id) ON DELETE SET NULL
 *
 * Changelog:
 * 1.0.8 - 2025-01-04 (TODO-4014)
 * - Changed provinsi_code to province_id (bigint FK to wi_provinces)
 * - Changed regency_code to regency_id (bigint FK to wi_regencies)
 * - Added foreign key constraints to wilayah-indonesia tables
 * - Added indexes for province_id and regency_id
 *
 * 1.0.1 - 2024-01-19
 * - Modified code field to varchar(17) for new format BR-TTTTRRRR-NNN
 * - Added unique constraint for agency_id + code
 *
 * 1.0.0 - 2024-01-07
 * - Initial version
 */

namespace WPAgency\Database\Tables;

defined('ABSPATH') || exit;

class DivisionsDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_divisions';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            agency_id bigint(20) UNSIGNED NOT NULL,
            code varchar(13) NOT NULL,
            name varchar(100) NOT NULL,
            type enum('cabang','pusat') NOT NULL,
            nitku varchar(20) NULL COMMENT 'Nomor Identitas Tempat Kegiatan Usaha',
            postal_code varchar(5) NULL COMMENT 'Kode pos',
            latitude decimal(10,8) NULL COMMENT 'Koordinat lokasi',
            longitude decimal(11,8) NULL COMMENT 'Koordinat lokasi',
            address text NULL,
            phone varchar(20) NULL,
            email varchar(100) NULL,
            province_id bigint(20) UNSIGNED NULL,
            regency_id bigint(20) UNSIGNED NULL,
            user_id bigint(20) UNSIGNED NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            UNIQUE KEY agency_name (agency_id, name),
            KEY agency_id_index (agency_id),
            KEY province_id_index (province_id),
            KEY regency_id_index (regency_id),
            KEY created_by_index (created_by),
            KEY nitku_index (nitku),
            KEY postal_code_index (postal_code),
            KEY location_index (latitude, longitude)
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     * TODO-4014: Foreign keys ke wilayah-indonesia
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_divisions';

        $constraints = [
            // FK to wi_provinces
            [
                'name' => 'fk_division_province',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_division_province
                         FOREIGN KEY (province_id)
                         REFERENCES {$wpdb->prefix}wi_provinces(id)
                         ON DELETE SET NULL"
            ],
            // FK to wi_regencies
            [
                'name' => 'fk_division_regency',
                'sql' => "ALTER TABLE {$table_name}
                         ADD CONSTRAINT fk_division_regency
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
                error_log("[DivisionsDB] Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
            }
        }
    }
}
