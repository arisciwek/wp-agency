<?php
/**
 * Database Migration for Code-based Wilayah References
 *
 * @package     WP_Agency
 * @subpackage  Database
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Migration.php
 *
 * Description: Migrasi database untuk mengubah referensi wilayah dari ID ke code.
 *              Mengubah kolom provinsi_id dan regency_id menjadi provinsi_code dan regency_code.
 *              Menambahkan data lookup untuk mengisi code berdasarkan ID yang ada.
 *
 * Tables affected:
 * - app_agencies: provinsi_id -> provinsi_code, regency_id -> regency_code
 * - app_divisions: provinsi_id -> provinsi_code, regency_id -> regency_code
 * - app_agency_jurisdictions: regency_id -> jurisdiction_code
 *
 * Migration steps:
 * 1. Add new code columns
 * 2. Populate code columns from existing ID data
 * 3. Drop old ID columns
 * 4. Rename code columns to final names
 * 5. Update foreign key constraints
 */

namespace WPAgency\Database;

defined('ABSPATH') || exit;

class Migration {
    private static function debug($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("[Migration] " . $message);
        }
    }

    /**
     * Run the migration to convert ID-based to code-based wilayah references
     */
    public static function runWilayahCodeMigration() {
        global $wpdb;

        try {
            $wpdb->query('START TRANSACTION');
            self::debug("Starting wilayah code migration...");

            // Step 1: Migrate app_agencies table
            self::migrateAgenciesTable();

            // Step 2: Migrate app_divisions table
            self::migrateDivisionsTable();

            // Step 3: Migrate app_agency_jurisdictions table
            self::migrateJurisdictionsTable();

            self::debug("Wilayah code migration completed successfully.");
            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug('Wilayah code migration failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Migrate app_agencies table
     */
    private static function migrateAgenciesTable() {
        global $wpdb;
        $table = $wpdb->prefix . 'app_agencies';

        self::debug("Migrating agencies table...");

        // Check if migration already done
        $columns = $wpdb->get_results("DESCRIBE {$table}");
        $has_provinsi_code = false;
        $has_regency_code = false;

        foreach ($columns as $column) {
            if ($column->Field === 'provinsi_code') $has_provinsi_code = true;
            if ($column->Field === 'regency_code') $has_regency_code = true;
        }

        if ($has_provinsi_code && $has_regency_code) {
            self::debug("Agencies table already migrated");
            return;
        }

        // Add new code columns
        if (!$has_provinsi_code) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN provinsi_code_temp VARCHAR(10) NULL AFTER provinsi_id");
            $wpdb->query("UPDATE {$table} SET provinsi_code_temp = (SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = provinsi_id) WHERE provinsi_id IS NOT NULL");
        }

        if (!$has_regency_code) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN regency_code_temp VARCHAR(10) NULL AFTER regency_id");
            $wpdb->query("UPDATE {$table} SET regency_code_temp = (SELECT code FROM {$wpdb->prefix}wi_regencies WHERE id = regency_id) WHERE regency_id IS NOT NULL");
        }

        // Drop old columns and rename new ones
        if (!$has_provinsi_code) {
            $wpdb->query("ALTER TABLE {$table} DROP COLUMN provinsi_id, CHANGE provinsi_code_temp provinsi_code VARCHAR(10) NULL");
        }

        if (!$has_regency_code) {
            $wpdb->query("ALTER TABLE {$table} DROP COLUMN regency_id, CHANGE regency_code_temp regency_code VARCHAR(10) NULL");
        }

        self::debug("Agencies table migration completed");
    }

    /**
     * Migrate app_divisions table
     */
    private static function migrateDivisionsTable() {
        global $wpdb;
        $table = $wpdb->prefix . 'app_divisions';

        self::debug("Migrating divisions table...");

        // Check if migration already done
        $columns = $wpdb->get_results("DESCRIBE {$table}");
        $has_provinsi_code = false;
        $has_regency_code = false;

        foreach ($columns as $column) {
            if ($column->Field === 'provinsi_code') $has_provinsi_code = true;
            if ($column->Field === 'regency_code') $has_regency_code = true;
        }

        if ($has_provinsi_code && $has_regency_code) {
            self::debug("Divisions table already migrated");
            return;
        }

        // Add new code columns
        if (!$has_provinsi_code) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN provinsi_code_temp VARCHAR(10) NULL AFTER provinsi_id");
            $wpdb->query("UPDATE {$table} SET provinsi_code_temp = (SELECT code FROM {$wpdb->prefix}wi_provinces WHERE id = provinsi_id) WHERE provinsi_id IS NOT NULL");
        }

        if (!$has_regency_code) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN regency_code_temp VARCHAR(10) NULL AFTER regency_id");
            $wpdb->query("UPDATE {$table} SET regency_code_temp = (SELECT code FROM {$wpdb->prefix}wi_regencies WHERE id = regency_id) WHERE regency_id IS NOT NULL");
        }

        // Drop old columns and rename new ones
        if (!$has_provinsi_code) {
            $wpdb->query("ALTER TABLE {$table} DROP COLUMN provinsi_id, CHANGE provinsi_code_temp provinsi_code VARCHAR(10) NULL");
        }

        if (!$has_regency_code) {
            $wpdb->query("ALTER TABLE {$table} DROP COLUMN regency_id, CHANGE regency_code_temp regency_code VARCHAR(10) NULL");
        }

        self::debug("Divisions table migration completed");
    }

    /**
     * Migrate app_agency_jurisdictions table
     */
    private static function migrateJurisdictionsTable() {
        global $wpdb;
        $table = $wpdb->prefix . 'app_agency_jurisdictions';

        self::debug("Migrating jurisdictions table...");

        // Check if migration already done
        $columns = $wpdb->get_results("DESCRIBE {$table}");
        $has_regency_code = false;

        foreach ($columns as $column) {
            if ($column->Field === 'regency_code') $has_regency_code = true;
        }

        if ($has_regency_code) {
            self::debug("Jurisdictions table already migrated");
            return;
        }

        // Add new code column
        $wpdb->query("ALTER TABLE {$table} ADD COLUMN regency_code_temp VARCHAR(10) NULL AFTER regency_id");
        $wpdb->query("UPDATE {$table} SET regency_code_temp = (SELECT code FROM {$wpdb->prefix}wi_regencies WHERE id = regency_id) WHERE regency_id IS NOT NULL");

        // Drop old column and rename new one
        $wpdb->query("ALTER TABLE {$table} DROP COLUMN regency_id, CHANGE regency_code_temp regency_code VARCHAR(10) NOT NULL");

        // Update foreign key constraint
        $wpdb->query("ALTER TABLE {$table} DROP FOREIGN KEY `{$wpdb->prefix}app_agency_jurisdictions_ibfk_1`");

        self::debug("Jurisdictions table migration completed");
    }

    /**
     * Run migration to remove unused npwp and nib fields from agencies table
     */
    public static function runRemoveUnusedFieldsMigration() {
        global $wpdb;
        $table = $wpdb->prefix . 'app_agencies';

        try {
            $wpdb->query('START TRANSACTION');
            self::debug("Starting removal of unused npwp and nib fields...");

            // Check and drop npwp column
            $has_npwp = false;
            $columns = $wpdb->get_results("DESCRIBE {$table}");
            foreach ($columns as $column) {
                if ($column->Field === 'npwp') {
                    $has_npwp = true;
                    break;
                }
            }

            if ($has_npwp) {
                $wpdb->query("ALTER TABLE {$table} DROP COLUMN npwp");
                self::debug("Dropped npwp column");
            }

            // Check and drop nib column
            $has_nib = false;
            $columns = $wpdb->get_results("DESCRIBE {$table}");
            foreach ($columns as $column) {
                if ($column->Field === 'nib') {
                    $has_nib = true;
                    break;
                }
            }

            if ($has_nib) {
                $wpdb->query("ALTER TABLE {$table} DROP COLUMN nib");
                self::debug("Dropped nib column");
            }

            // Remove unique indexes for npwp and nib
            $indexes = $wpdb->get_results("SHOW INDEX FROM {$table} WHERE Column_name IN ('npwp', 'nib')");
            foreach ($indexes as $index) {
                if ($index->Key_name === 'npwp' || $index->Key_name === 'nib') {
                    $wpdb->query("ALTER TABLE {$table} DROP INDEX {$index->Key_name}");
                    self::debug("Dropped index {$index->Key_name}");
                }
            }

            self::debug("Removal of unused npwp and nib fields completed successfully.");
            $wpdb->query('COMMIT');
            return true;

        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
            self::debug('Removal of unused fields failed: ' . $e->getMessage());
            throw $e;
        }
    }
}
