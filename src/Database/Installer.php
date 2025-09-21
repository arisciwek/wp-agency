<?php
/**
 * Database Installer
 *
 * @package     WP_Agency
 * @subpackage  Database
 * @version     1.1.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Installer.php
 *
 * Description: Mengelola instalasi dan pembaruan struktur database plugin.
 *              Mendukung pembuatan tabel dengan dependencies dan foreign keys.
 *              Menggunakan dbDelta untuk membuat/mengubah struktur tabel.
 *              Menambahkan foreign key constraints secara terpisah.
 *
 * Struktur tabel:
 * - app_agencies       : Data agency
 * - app_divisions     : Data division per agency
 * - app_agency_membership_feature_groups : Grup fitur membership
 * - app_agency_membership_features       : Fitur-fitur membership
 * - app_agency_membership_levels         : Level membership
 * - app_agency_memberships               : Data membership aktif
 * - app_agency_employees                 : Data karyawan agency
 *
 * Changelog:
 * 1.1.0 - 2025-02-27
 * - Perbaikan metode instalasi untuk mendukung foreign keys
 * - Memisahkan pembuatan tabel dan penambahan foreign key
 * - Penanganan error dan rollback transaction
 * - Tambahan verifikasi tabel setelah instalasi
 * 
 * 1.0.1 - 2025-02-14
 * - Penambahan tabel fitur membership
 * - Peningkatan penanganan error
 * 
 * 1.0.0 - 2025-01-07
 * - Versi awal
 * - Instalasi tabel dasar
 */

namespace WPAgency\Database;

defined('ABSPATH') || exit;

class Installer {
    // Complete list of tables to install, in dependency order
    private static $tables = [
        'app_agencies',
        'app_divisions',
        'app_agency_jurisdictions',
        'app_agency_membership_feature_groups',
        'app_agency_membership_features',
        'app_agency_membership_levels',
        'app_agency_memberships',
        'app_agency_employees'
    ];

    // Table class mappings for easier maintenance
    private static $table_classes = [
        'app_agencies' => Tables\AgencysDB::class,
        'app_agency_membership_feature_groups' => Tables\AgencyMembershipFeaturesDB::class,
        'app_agency_membership_features' => Tables\AgencyMembershipFeaturesDB::class,
        'app_agency_membership_levels' => Tables\AgencyMembershipLevelsDB::class,
        'app_agency_memberships' => Tables\AgencyMembershipsDB::class,
        'app_divisions' => Tables\DivisionsDB::class,
        'app_agency_jurisdictions' => Tables\JurisdictionDB::class,
        'app_agency_employees' => Tables\AgencyEmployeesDB::class
    ];

    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Installer] " . $message);
        }
    }

    private static function verify_tables() {
        global $wpdb;
        foreach (self::$tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $table_name
            ));
            if (!$table_exists) {
                self::debug("Table not found: {$table_name}");
                throw new \Exception("Failed to create table: {$table_name}");
            }
            self::debug("Verified table exists: {$table_name}");
        }
    }

    /**
     * Installs or updates the database tables
     */
    public static function run() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');
            self::debug("Starting database installation...");

            // Create tables in proper order (tanpa foreign keys dulu)
            foreach (self::$tables as $table) {
                $class = self::$table_classes[$table];
                self::debug("Creating {$table} table using {$class}...");
                dbDelta($class::get_schema());
            }

            // Verify all tables were created
            self::verify_tables();

            // Tambahkan foreign key constraints setelah semua tabel dibuat
            self::debug("Adding foreign key constraints...");

            // Tambahkan foreign keys untuk AgencyMembershipFeatures
            if (method_exists(Tables\AgencyMembershipFeaturesDB::class, 'add_foreign_keys')) {
                Tables\AgencyMembershipFeaturesDB::add_foreign_keys();
            }

            // Tambahkan foreign keys untuk AgencyMemberships
            if (method_exists(Tables\AgencyMembershipsDB::class, 'add_foreign_keys')) {
                Tables\AgencyMembershipsDB::add_foreign_keys();
            }

            // Tambahkan foreign keys untuk Agency Jurisdictions
            if (method_exists(Tables\JurisdictionDB::class, 'add_foreign_keys')) {
                Tables\JurisdictionDB::add_foreign_keys();
            }

            // Tambahkan foreign keys untuk AgencyEmployees
            if (method_exists(Tables\AgencyEmployeesDB::class, 'add_foreign_keys')) {
                Tables\AgencyEmployeesDB::add_foreign_keys();
            }

            self::debug("Database installation completed successfully.");
            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug('Database installation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Run database migration for wilayah code changes
     */
    public static function runMigration() {
        try {
            self::debug("Starting database migration...");
            $result = Migration::runWilayahCodeMigration();
            self::debug("Database migration completed successfully.");
            return $result;
        } catch (\Exception $e) {
            self::debug('Database migration failed: ' . $e->getMessage());
            return false;
        }
    }
}
