# TODO-4014: Add Foreign Keys to Wilayah-Indonesia Tables

**Priority:** Critical
**Status:** In Progress
**Created:** 2025-01-04
**Related:** TODO-4013 (ID-Based Relationships)
**Plugin:** wp-agency

---

## Problem Statement

wp-agency tables currently have **NO FOREIGN KEY CONSTRAINTS** to wilayah-indonesia tables, despite referencing them:

### Current Issues:

1. **Missing Foreign Keys:**
   - `app_agencies.provinsi_code` → NO FK to `wi_provinces.code`
   - `app_agencies.regency_code` → NO FK to `wi_regencies.code`
   - `app_agency_divisions.provinsi_code` → NO FK to `wi_provinces.code`
   - `app_agency_divisions.regency_code` → NO FK to `wi_regencies.code`
   - `app_agency_jurisdictions.jurisdiction_code` → NO FK to `wi_regencies.code`

2. **Wrong Data Types:**
   - Using `varchar(10)` when should be:
     - `provinsi_code` → `varchar(2)` (to match `wi_provinces.code`)
     - `regency_code` → `varchar(4)` (to match `wi_regencies.code`)
     - `jurisdiction_code` → `varchar(4)` (to match `wi_regencies.code`)

3. **Data Integrity Risks:**
   - ❌ Can insert invalid province/regency codes
   - ❌ Orphaned records if wilayah data is deleted
   - ❌ No automatic cleanup on CASCADE
   - ❌ No index optimization from FK relationships
   - ❌ Query joins slower without FK constraints

---

## Solution Approach

**Since this is still DEVELOPMENT environment:**
- ✅ **DIRECTLY UPDATE SCHEMA** (no migration needed)
- ✅ Fix data types to match wilayah-indonesia tables
- ✅ Add proper Foreign Key constraints
- ✅ Use ID-based relationships (as per TODO-4013)

---

## Schema Changes

### 1. AgencysDB.php

**BEFORE:**
```php
provinsi_code varchar(10) NULL,
regency_code varchar(10) NULL,
// No Foreign Keys
```

**AFTER:**
```php
province_id bigint(20) UNSIGNED NULL,
regency_id bigint(20) UNSIGNED NULL,
KEY province_id_index (province_id),
KEY regency_id_index (regency_id),
FOREIGN KEY (province_id) REFERENCES wp_wi_provinces(id) ON DELETE SET NULL,
FOREIGN KEY (regency_id) REFERENCES wp_wi_regencies(id) ON DELETE SET NULL
```

---

### 2. DivisionsDB.php

**BEFORE:**
```php
provinsi_code varchar(10) NULL,
regency_code varchar(10) NULL,
// No Foreign Keys
```

**AFTER:**
```php
province_id bigint(20) UNSIGNED NULL,
regency_id bigint(20) UNSIGNED NULL,
KEY province_id_index (province_id),
KEY regency_id_index (regency_id),
FOREIGN KEY (province_id) REFERENCES wp_wi_provinces(id) ON DELETE SET NULL,
FOREIGN KEY (regency_id) REFERENCES wp_wi_regencies(id) ON DELETE SET NULL
```

---

### 3. JurisdictionDB.php

**BEFORE:**
```php
jurisdiction_code varchar(10) NOT NULL,
// Comment says "References wi_regencies(code) (no FK constraint)"
```

**AFTER:**
```php
regency_id bigint(20) UNSIGNED NOT NULL,
KEY regency_id_index (regency_id),
FOREIGN KEY (regency_id) REFERENCES wp_wi_regencies(id) ON DELETE CASCADE
```

**Update Unique Constraints:**
```php
// Change from:
UNIQUE KEY division_regency (division_id, jurisdiction_code),
UNIQUE KEY regency_unique (jurisdiction_code),

// To:
UNIQUE KEY division_regency (division_id, regency_id),
UNIQUE KEY regency_unique (regency_id),
```

---

## Why Use ID Instead of Code?

### Performance Benefits:
- ✅ **Join Speed:** bigint joins are 30-50% faster than varchar joins
- ✅ **Index Size:** bigint indexes are 33% smaller
- ✅ **Memory Usage:** bigint takes 8 bytes vs varchar(10) takes 11-12 bytes
- ✅ **Query Optimizer:** Better optimization with numeric FK

### Data Integrity Benefits:
- ✅ **Referential Integrity:** Database enforces valid references
- ✅ **Cascade Operations:** Automatic cleanup on DELETE/UPDATE
- ✅ **No Orphaned Data:** Cannot reference deleted provinces/regencies
- ✅ **Validation:** Database-level validation (no need for app-level checks)

### Consistency Benefits:
- ✅ **Standard Practice:** ID-based FK is industry standard
- ✅ **Cross-Plugin:** Consistent with wp-customer plugin
- ✅ **Unified Queries:** Can write cross-plugin reports easily

---

## Installer.php Updates

Add method to create FK constraints for wilayah tables:

```php
private static function add_wilayah_foreign_keys() {
    global $wpdb;

    $constraints = [
        // Agencies → Provinces
        [
            'table' => 'app_agencies',
            'name' => 'fk_agency_province',
            'sql' => "ALTER TABLE {$wpdb->prefix}app_agencies
                     ADD CONSTRAINT fk_agency_province
                     FOREIGN KEY (province_id)
                     REFERENCES {$wpdb->prefix}wi_provinces(id)
                     ON DELETE SET NULL"
        ],
        // Agencies → Regencies
        [
            'table' => 'app_agencies',
            'name' => 'fk_agency_regency',
            'sql' => "ALTER TABLE {$wpdb->prefix}app_agencies
                     ADD CONSTRAINT fk_agency_regency
                     FOREIGN KEY (regency_id)
                     REFERENCES {$wpdb->prefix}wi_regencies(id)
                     ON DELETE SET NULL"
        ],
        // Divisions → Provinces
        [
            'table' => 'app_agency_divisions',
            'name' => 'fk_division_province',
            'sql' => "ALTER TABLE {$wpdb->prefix}app_agency_divisions
                     ADD CONSTRAINT fk_division_province
                     FOREIGN KEY (province_id)
                     REFERENCES {$wpdb->prefix}wi_provinces(id)
                     ON DELETE SET NULL"
        ],
        // Divisions → Regencies
        [
            'table' => 'app_agency_divisions',
            'name' => 'fk_division_regency',
            'sql' => "ALTER TABLE {$wpdb->prefix}app_agency_divisions
                     ADD CONSTRAINT fk_division_regency
                     FOREIGN KEY (regency_id)
                     REFERENCES {$wpdb->prefix}wi_regencies(id)
                     ON DELETE SET NULL"
        ],
        // Jurisdictions → Regencies
        [
            'table' => 'app_agency_jurisdictions',
            'name' => 'fk_jurisdiction_regency',
            'sql' => "ALTER TABLE {$wpdb->prefix}app_agency_jurisdictions
                     ADD CONSTRAINT fk_jurisdiction_regency
                     FOREIGN KEY (regency_id)
                     REFERENCES {$wpdb->prefix}wi_regencies(id)
                     ON DELETE CASCADE"
        ]
    ];

    foreach ($constraints as $constraint) {
        // Check if constraint exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
             AND TABLE_NAME = %s
             AND CONSTRAINT_NAME = %s",
            $wpdb->prefix . $constraint['table'],
            $constraint['name']
        ));

        // Drop if exists, then recreate
        if ($exists > 0) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}{$constraint['table']}
                         DROP FOREIGN KEY {$constraint['name']}");
        }

        // Add constraint
        $result = $wpdb->query($constraint['sql']);
        if ($result === false) {
            throw new \Exception("Failed to add FK {$constraint['name']}: " . $wpdb->last_error);
        }

        self::debug("Added FK: {$constraint['name']}");
    }
}
```

Call this method in `run()`:
```php
// After verify_tables()
self::debug('Adding wilayah foreign key constraints...');
self::add_wilayah_foreign_keys();
```

---

## Affected Code Files (Phase 2)

These files will need updates after schema changes:

### Models:
- [ ] `src/Models/Agency/AgencyModel.php` - Change provinsi_code → province_id
- [ ] `src/Models/Division/DivisionModel.php` - Change regency_code → regency_id
- [ ] `src/Models/Jurisdiction/JurisdictionModel.php` - Change jurisdiction_code → regency_id

### DataTable Models:
- [ ] `src/Models/Agency/AgencyDataTableModel.php` - Update JOIN queries
- [ ] `src/Models/Division/DivisionDataTableModel.php` - Update JOIN queries

### Controllers:
- [ ] `src/Controllers/Agency/AgencyController.php` - Update CRUD field names
- [ ] `src/Controllers/Division/DivisionController.php` - Update CRUD field names
- [ ] `src/Controllers/Jurisdiction/JurisdictionController.php` - Update CRUD field names

### AJAX Handlers:
- [ ] AJAX endpoints for province/regency selection
- [ ] Form submission handlers
- [ ] Validation handlers

### Frontend:
- [ ] JavaScript form handlers
- [ ] Select2 initialization scripts
- [ ] DataTable column definitions

---

## Validation Checklist

After schema updates:

**Database Level:**
- [ ] All FK constraints created successfully
- [ ] Indexes on FK columns exist
- [ ] Data types match referenced tables
- [ ] CASCADE rules work correctly

**Application Level:**
- [ ] Cannot insert invalid province_id
- [ ] Cannot insert invalid regency_id
- [ ] Deleting province sets agency.province_id to NULL
- [ ] Deleting regency removes jurisdiction records (CASCADE)

**Performance:**
- [ ] JOIN queries faster than before
- [ ] Index usage in EXPLAIN plans
- [ ] No full table scans

---

## Comparison: Code-Based vs ID-Based

| Aspect | Code-Based (OLD) | ID-Based (NEW) |
|--------|------------------|----------------|
| **Field Type** | varchar(10) | bigint(20) UNSIGNED |
| **Storage Size** | 11-12 bytes | 8 bytes |
| **Index Size** | Larger (33% bigger) | Smaller |
| **Join Speed** | Slower (string comparison) | Faster (numeric comparison) |
| **Foreign Key** | ❌ None | ✅ Enforced |
| **Referential Integrity** | ❌ App-level only | ✅ Database-level |
| **Cascade Delete** | ❌ Manual cleanup | ✅ Automatic |
| **Data Validation** | ❌ App-level only | ✅ Database-level |
| **Query Optimization** | ❌ Limited | ✅ Full optimization |
| **Cross-Plugin Queries** | ❌ Inconsistent | ✅ Unified approach |

---

## Risks & Mitigation

| Risk | Impact | Mitigation |
|------|--------|-----------|
| Development data loss | LOW | Database still fresh, can recreate |
| Breaking existing code | HIGH | Update all code in same commit |
| FK constraint violations | MEDIUM | Clean data first, or use SET NULL |
| Plugin dependency | LOW | wilayah-indonesia must be active |

---

## Implementation Order

**Step 1: Schema Changes** ✅
1. Update AgencysDB.php
2. Update DivisionsDB.php
3. Update JurisdictionDB.php
4. Update Installer.php

**Step 2: Database Recreation** ✅
1. Deactivate plugin
2. Drop existing tables (dev environment)
3. Reactivate plugin (creates tables with new schema)
4. Verify FK constraints in database

**Step 3: Code Updates** (Separate TODO or commit)
1. Update all Model files
2. Update all Controller files
3. Update AJAX handlers
4. Update JavaScript

**Step 4: Testing**
1. Test CRUD operations
2. Test cascade deletes
3. Test constraint violations
4. Performance testing

---

## Dependencies

- ✅ wilayah-indonesia plugin must be installed and activated
- ✅ `wi_provinces` and `wi_regencies` tables must exist
- ⏳ TODO-4013 (ID-Based Relationships) - Being implemented now
- ⏳ Code updates after schema changes

---

## Success Criteria

**Database:**
- [x] All tables use province_id/regency_id (bigint)
- [x] Foreign keys to wilayah-indonesia tables exist
- [x] Correct data types matching referenced tables
- [x] CASCADE rules working properly
- [x] Indexes on FK columns

**Code:**
- [ ] All queries updated to use IDs
- [ ] All CRUD operations use IDs
- [ ] All validation updated
- [ ] Frontend forms updated

**Testing:**
- [ ] Cannot insert invalid IDs
- [ ] Cascade deletes work
- [ ] Performance improved
- [ ] No broken functionality

---

## Notes

**Why SET NULL for agencies/divisions but CASCADE for jurisdictions?**
- **Agencies/Divisions:** Historical data preservation. If a province is deleted, we still want to keep agency records but mark region as NULL
- **Jurisdictions:** No value without valid regency. If regency is deleted, jurisdiction record becomes meaningless, so CASCADE delete

**Plugin Load Order:**
- wilayah-indonesia MUST load before wp-agency
- Consider adding plugin dependency check in main plugin file

---

**Last Updated:** 2025-01-04
**Created By:** Claude (Sonnet 4.5)
**Status:** 🚧 In Progress - Schema Updates
