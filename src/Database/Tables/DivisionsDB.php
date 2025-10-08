<?php
/**
 * Divisiones Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.0.1
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
 * - agency_id    : Foreign key ke agency
 * - code           : Format
 * - name           : Nama division
 * - type           : Tipe wilayah (cabang)
 * - provinsi_code  : Kode provinsi (nullable)
 * - regency_code   : Kode cabang (nullable)
 * - created_by     : User ID pembuat
 * - created_at     : Timestamp pembuatan
 * - updated_at     : Timestamp update terakhir
 *
 * Foreign Keys:
 * - agency_id    : REFERENCES app_agencies(id) ON DELETE CASCADE
 *
 * Changelog:
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
            provinsi_code varchar(10) NULL,
            regency_code varchar(10) NULL,
            user_id bigint(20) UNSIGNED NULL,
            created_by bigint(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            status enum('active','inactive') DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY code (code),
            UNIQUE KEY agency_name (agency_id, name),
            KEY agency_id_index (agency_id),
            KEY created_by_index (created_by),
            KEY nitku_index (nitku),
            KEY postal_code_index (postal_code),
            KEY location_index (latitude, longitude)
        ) $charset_collate;";
    }
}
