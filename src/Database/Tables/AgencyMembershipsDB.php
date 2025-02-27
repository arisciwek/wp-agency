<?php
/**
 * Agency Memberships Table Schema
 *
 * @package     WP_Agency
 * @subpackage  Database/Tables
 * @version     1.0.1
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Tables/AgencyMembershipsDB.php
 *
 * Description: Mendefinisikan struktur tabel untuk active memberships.
 *              Menyimpan data status membership aktif per agency:
 *              - Status dan periode membership
 *              - Informasi trial dan grace period
 *              - Data pembayaran dasar
 *              - Relasi ke division
 *              Table prefix yang digunakan adalah 'app_'.
 *
 * Dependencies:
 * - WordPress $wpdb
 * - app_agency_membership_levels table
 * - app_agencies table
 * - app_divisions table
 *
 * Changelog:
 * 1.0.1 - 2024-02-09
 * - Added division_id field with foreign key constraint
 * - Added division relationship tracking
 * 
 * 1.0.0 - 2024-02-08
 * - Initial version
 * - Added membership status tracking
 * - Added period management
 * - Added basic payment tracking
 */
namespace WPAgency\Database\Tables;

defined('ABSPATH') || exit;

class AgencyMembershipsDB {
    public static function get_schema() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_memberships';
        $charset_collate = $wpdb->get_charset_collate();

        return "CREATE TABLE {$table_name} (
            id bigint(20) UNSIGNED NOT NULL auto_increment,
            agency_id bigint(20) UNSIGNED NOT NULL,
            division_id bigint(20) UNSIGNED NOT NULL,
            level_id bigint(20) UNSIGNED NOT NULL,
            status enum('active','pending_payment','pending_upgrade','expired','in_grace_period') NOT NULL DEFAULT 'active',
            period_months int NOT NULL DEFAULT 1,
            start_date datetime NOT NULL,
            end_date datetime NOT NULL,
            trial_end_date datetime NULL,
            grace_period_end_date datetime NULL,
            price_paid decimal(10,2) NOT NULL DEFAULT 0.00,
            payment_method varchar(50) NULL,
            payment_status enum('paid','pending','failed','refunded') NOT NULL DEFAULT 'pending',
            payment_date datetime NULL,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY agency_id (agency_id),
            KEY division_id (division_id),
            KEY level_id (level_id),
            KEY status (status),
            KEY end_date (end_date)
        ) $charset_collate;";
    }
    
    /**
     * Add foreign key constraints yang tidak didukung oleh dbDelta
     * Harus dipanggil setelah tabel dibuat
     */
    public static function add_foreign_keys() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'app_agency_memberships';
        
        // Tambahkan foreign key constraint ke agencies table
        $wpdb->query("ALTER TABLE {$table_name} 
            ADD CONSTRAINT `fk_membership_agency` 
            FOREIGN KEY (agency_id) 
            REFERENCES `{$wpdb->prefix}app_agencies` (id)
            ON DELETE CASCADE");
            
        // Tambahkan foreign key constraint ke divisiones table
        $wpdb->query("ALTER TABLE {$table_name} 
            ADD CONSTRAINT `fk_membership_division`
            FOREIGN KEY (division_id)
            REFERENCES `{$wpdb->prefix}app_divisions` (id)
            ON DELETE CASCADE");
            
        // Tambahkan foreign key constraint ke membership levels table
        $wpdb->query("ALTER TABLE {$table_name} 
            ADD CONSTRAINT `fk_agency_membership_level`
            FOREIGN KEY (level_id)
            REFERENCES `{$wpdb->prefix}app_agency_membership_levels` (id)
            ON DELETE RESTRICT");
    }
}
