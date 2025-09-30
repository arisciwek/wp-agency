<?php
/**
 * Agencys Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.0.3
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
 * - provinsi_code  : Kode provinsi (nullable)
 * - regency_code   : Kode cabang (nullable)
 * - user_id        : ID User WP sebagai Owner (nullable)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Changelog:
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
            provinsi_code varchar(10) NULL,
            regency_code varchar(10) NULL,
            user_id bigint(20) UNSIGNED NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            UNIQUE KEY name_region (name, provinsi_code, regency_code),
            KEY created_by_index (created_by)
        ) $charset_collate;";
    }
}
