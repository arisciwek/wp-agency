<?php
/**
 * Agency Employees Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Tables/AgencyEmployeesDB.php
 *
 * Description: Mendefinisikan struktur tabel employees.
 *              Table prefix yang digunakan adalah 'app_'.
 *              Includes relasi dengan tabel agencies.
 *              Menyediakan data karyawan agency.
 *
 * Fields:
 * - id             : Primary key
 * - agency_id    : Foreign key ke agency
 * - division_id      : Foreign key ke division
 * - user_id        : Foreign key ke user
 * - name           : Nama karyawan
 * - position       : Jabatan karyawan
 * - finance        : Department finance (boolean)
 * - operation      : Department operation (boolean)
 * - legal          : Department legal (boolean)
 * - purchase       : Department purchase (boolean)
 * - email          : Email karyawan (unique)
 * - phone          : Nomor telepon
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 * - status         : Status aktif/nonaktif
 *
 * Foreign Keys:
 * - agency_id    : REFERENCES app_agencies(id) ON DELETE CASCADE
 *
 * Changelog:
 * 1.0.1 - 2024-01-27
 * - Removed department field
 * - Added boolean fields for specific departments: finance, operation, legal, purchase
 * 
 * 1.0.0 - 2024-01-07
 * - Initial version
 * - Added basic employee fields
 * - Added agency relation
 * - Added contact information fields
 */

namespace WPAgency\Database\Tables;

defined('ABSPATH') || exit;

class AgencyEmployeesDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_employees';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            agency_id bigint(20) UNSIGNED NOT NULL,
            division_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            name varchar(100) NOT NULL,
            position varchar(100) NULL,
            finance boolean NOT NULL DEFAULT 0,
            operation boolean NOT NULL DEFAULT 0,
            legal boolean NOT NULL DEFAULT 0,
            purchase boolean NOT NULL DEFAULT 0,
            keterangan varchar(200) NULL,
            email varchar(100) NOT NULL,
            phone varchar(20) NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY agency_id_index (agency_id),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }

    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_employees';
        $agencies_table = $wpdb->prefix . 'app_agencies';
        $constraint_name = $wpdb->prefix . 'app_agency_employees_ibfk_1';

        // Check if constraint already exists
        $constraint_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
             AND TABLE_NAME = %s
             AND CONSTRAINT_NAME = %s",
            $table_name,
            $constraint_name
        ));

        // If constraint exists, drop it first
        if ($constraint_exists > 0) {
            $wpdb->query("ALTER TABLE {$table_name} DROP FOREIGN KEY `{$constraint_name}`");
        }

        // Add foreign key constraint
        $wpdb->query("ALTER TABLE {$table_name}
            ADD CONSTRAINT `{$constraint_name}`
            FOREIGN KEY (agency_id)
            REFERENCES `{$agencies_table}` (id)
            ON DELETE CASCADE");
    }
}
