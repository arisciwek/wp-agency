# TODO-4013: Migrate wp-agency to ID-Based Relationships

**Priority:** High
**Status:** Pending Approval
**Created:** 2025-01-02
**Related:** TODO-4012 (Schema Analysis)
**Plugin:** wp-agency

---

## Problem Statement

wp-agency currently uses **CODE-based** relationships to wilayah-indonesia:
- `provinsi_code` varchar(10) → references `wi_provinces.code`
- `regency_code` varchar(10) → references `wi_regencies.code`

This is **inconsistent** with wp-customer which uses **ID-based** relationships:
- `provinsi_id` bigint(20) UNSIGNED → references `wi_provinces.id`
- `regency_id` bigint(20) UNSIGNED → references `wi_regencies.id`

**Impact:**
- ❌ Query performance slower (varchar JOINs vs bigint JOINs)
- ❌ Index size 33% larger
- ❌ Cannot create unified queries across plugins
- ❌ Code duplication for same operations

---

## Objective

Convert all wp-agency tables from CODE-based to ID-based relationships to wilayah-indonesia.

**Target Schema:**

| Current Field | New Field | Type | References |
|--------------|-----------|------|-----------|
| provinsi_code varchar(10) | provinsi_id | bigint(20) UNSIGNED NOT NULL | wi_provinces.id |
| regency_code varchar(10) | regency_id | bigint(20) UNSIGNED NOT NULL | wi_regencies.id |
| jurisdiction_code varchar(10) | regency_id | bigint(20) UNSIGNED NOT NULL | wi_regencies.id |

---

## Affected Tables

### 1. `app_agencies` (AgencysDB.php)

**Current Schema:**
```sql
provinsi_code varchar(10) NULL
regency_code varchar(10) NULL
```

**Target Schema:**
```sql
provinsi_id bigint(20) UNSIGNED NOT NULL
regency_id bigint(20) UNSIGNED NOT NULL
```

---

### 2. `app_agency_divisions` (DivisionsDB.php)

**Current Schema:**
```sql
provinsi_code varchar(10) NULL
regency_code varchar(10) NULL
```

**Target Schema:**
```sql
provinsi_id bigint(20) UNSIGNED NOT NULL
regency_id bigint(20) UNSIGNED NOT NULL
```

---

### 3. `app_agency_jurisdictions` (JurisdictionDB.php)

**Current Schema:**
```sql
jurisdiction_code varchar(10) NOT NULL  -- references wi_regencies.code
```

**Target Schema:**
```sql
regency_id bigint(20) UNSIGNED NOT NULL  -- references wi_regencies.id
```

---

## Migration Steps

### Phase 1: Database Schema Migration

#### Step 1.1: Backup Tables

```sql
-- Create backup tables
CREATE TABLE wp_app_agencies_backup LIKE wp_app_agencies;
INSERT INTO wp_app_agencies_backup SELECT * FROM wp_app_agencies;

CREATE TABLE wp_app_agency_divisions_backup LIKE wp_app_agency_divisions;
INSERT INTO wp_app_agency_divisions_backup SELECT * FROM wp_app_agency_divisions;

CREATE TABLE wp_app_agency_jurisdictions_backup LIKE wp_app_agency_jurisdictions;
INSERT INTO wp_app_agency_jurisdictions_backup SELECT * FROM wp_app_agency_jurisdictions;
```

---

#### Step 1.2: Add New ID Columns

```sql
-- app_agencies
ALTER TABLE wp_app_agencies
    ADD COLUMN provinsi_id bigint(20) UNSIGNED NULL AFTER provinsi_code,
    ADD COLUMN regency_id bigint(20) UNSIGNED NULL AFTER regency_code;

-- app_agency_divisions
ALTER TABLE wp_app_agency_divisions
    ADD COLUMN provinsi_id bigint(20) UNSIGNED NULL AFTER provinsi_code,
    ADD COLUMN regency_id bigint(20) UNSIGNED NULL AFTER regency_code;

-- app_agency_jurisdictions
ALTER TABLE wp_app_agency_jurisdictions
    ADD COLUMN regency_id bigint(20) UNSIGNED NULL AFTER jurisdiction_code;
```

---

#### Step 1.3: Migrate Data (Code → ID)

```sql
-- Migrate app_agencies
UPDATE wp_app_agencies a
INNER JOIN wp_wi_provinces p ON a.provinsi_code = p.code
SET a.provinsi_id = p.id
WHERE a.provinsi_code IS NOT NULL;

UPDATE wp_app_agencies a
INNER JOIN wp_wi_regencies r ON a.regency_code = r.code
SET a.regency_id = r.id
WHERE a.regency_code IS NOT NULL;

-- Migrate app_agency_divisions
UPDATE wp_app_agency_divisions d
INNER JOIN wp_wi_provinces p ON d.provinsi_code = p.code
SET d.provinsi_id = p.id
WHERE d.provinsi_code IS NOT NULL;

UPDATE wp_app_agency_divisions d
INNER JOIN wp_wi_regencies r ON d.regency_code = r.code
SET d.regency_id = r.id
WHERE d.regency_code IS NOT NULL;

-- Migrate app_agency_jurisdictions
UPDATE wp_app_agency_jurisdictions j
INNER JOIN wp_wi_regencies r ON j.jurisdiction_code = r.code
SET j.regency_id = r.id;
```

---

#### Step 1.4: Verify Data Migration

```sql
-- Check for unmigrated records (should be 0)
SELECT COUNT(*) as unmigrated_agencies
FROM wp_app_agencies
WHERE provinsi_code IS NOT NULL AND provinsi_id IS NULL;

SELECT COUNT(*) as unmigrated_divisions
FROM wp_app_agency_divisions
WHERE provinsi_code IS NOT NULL AND provinsi_id IS NULL;

SELECT COUNT(*) as unmigrated_jurisdictions
FROM wp_app_agency_jurisdictions
WHERE regency_id IS NULL;
```

---

#### Step 1.5: Make ID Columns NOT NULL

```sql
-- app_agencies
ALTER TABLE wp_app_agencies
    MODIFY provinsi_id bigint(20) UNSIGNED NOT NULL,
    MODIFY regency_id bigint(20) UNSIGNED NOT NULL;

-- app_agency_divisions
ALTER TABLE wp_app_agency_divisions
    MODIFY provinsi_id bigint(20) UNSIGNED NOT NULL,
    MODIFY regency_id bigint(20) UNSIGNED NOT NULL;

-- app_agency_jurisdictions
ALTER TABLE wp_app_agency_jurisdictions
    MODIFY regency_id bigint(20) UNSIGNED NOT NULL;
```

---

#### Step 1.6: Drop Old Code Columns

```sql
-- app_agencies
ALTER TABLE wp_app_agencies
    DROP COLUMN provinsi_code,
    DROP COLUMN regency_code;

-- app_agency_divisions
ALTER TABLE wp_app_agency_divisions
    DROP COLUMN provinsi_code,
    DROP COLUMN regency_code;

-- app_agency_jurisdictions
ALTER TABLE wp_app_agency_jurisdictions
    DROP COLUMN jurisdiction_code;
```

---

#### Step 1.7: Update Unique Constraints

```sql
-- app_agencies (update name_region constraint)
ALTER TABLE wp_app_agencies
    DROP INDEX name_region,
    ADD UNIQUE KEY name_region (name, provinsi_id, regency_id);

-- app_agency_jurisdictions (update regency_unique)
ALTER TABLE wp_app_agency_jurisdictions
    DROP INDEX regency_unique,
    ADD UNIQUE KEY regency_unique (regency_id);

ALTER TABLE wp_app_agency_jurisdictions
    DROP INDEX division_regency,
    ADD UNIQUE KEY division_regency (division_id, regency_id);
```

---

### Phase 2: Code Migration

#### Affected Files (Estimated)

**Models:**
- `src/Models/Agency/AgencyModel.php` - create(), update(), queries
- `src/Models/Division/DivisionModel.php` - create(), update(), queries
- `src/Models/Jurisdiction/JurisdictionModel.php` - create(), update(), queries

**DataTable Models:**
- `src/Models/Agency/AgencyDataTableModel.php` - JOIN queries
- `src/Models/Division/DivisionDataTableModel.php` - JOIN queries

**Controllers:**
- `src/Controllers/Agency/AgencyController.php` - CRUD operations
- `src/Controllers/Division/DivisionController.php` - CRUD operations
- `src/Controllers/Jurisdiction/JurisdictionController.php` - CRUD operations

**AJAX Handlers:**
- AJAX endpoints for region selection
- Form validation handlers

---

#### Example Code Changes

**Before (CODE-based):**
```php
// AgencyModel.php - create()
$insert_data = [
    'code' => $data['code'],
    'name' => $data['name'],
    'provinsi_code' => $data['provinsi'],      // ❌ CODE
    'regency_code' => $data['regency'],        // ❌ CODE
    'status' => 'active'
];

// AgencyDataTableModel.php - JOIN query
$this->base_joins = [
    "LEFT JOIN {$wpdb->prefix}wi_provinces p ON a.provinsi_code = p.code",  // ❌ CODE
    "LEFT JOIN {$wpdb->prefix}wi_regencies r ON a.regency_code = r.code"    // ❌ CODE
];
```

**After (ID-based):**
```php
// AgencyModel.php - create()
$insert_data = [
    'code' => $data['code'],
    'name' => $data['name'],
    'provinsi_id' => $data['provinsi'],        // ✅ ID
    'regency_id' => $data['regency'],          // ✅ ID
    'status' => 'active'
];

// AgencyDataTableModel.php - JOIN query
$this->base_joins = [
    "INNER JOIN {$wpdb->prefix}wi_provinces p ON a.provinsi_id = p.id",    // ✅ ID
    "INNER JOIN {$wpdb->prefix}wi_regencies r ON a.regency_id = r.id"      // ✅ ID
];
```

---

### Phase 3: Testing

#### Test Cases

**1. CRUD Operations:**
- [ ] Create agency with provinsi_id and regency_id
- [ ] Update agency region (change provinsi_id/regency_id)
- [ ] Delete agency
- [ ] Create division with region IDs
- [ ] Create jurisdiction with regency_id

**2. Query Operations:**
- [ ] List agencies with region names (JOIN query)
- [ ] Filter agencies by province
- [ ] Filter agencies by regency
- [ ] DataTable server-side processing

**3. Validation:**
- [ ] Prevent NULL region values
- [ ] Prevent invalid region IDs (non-existent)
- [ ] Check unique constraints work (name + provinsi_id + regency_id)

**4. Performance:**
- [ ] Benchmark query speed before/after
- [ ] Verify query plans use indexes
- [ ] Check memory usage

---

### Phase 4: Documentation Updates

**Files to Update:**

1. **Schema Documentation:**
   - `/wp-agency/src/Database/Tables/AgencysDB.php` - Update schema and comments
   - `/wp-agency/src/Database/Tables/DivisionsDB.php` - Update schema
   - `/wp-agency/src/Database/Tables/JurisdictionDB.php` - Update schema

2. **API Documentation:**
   - Update field names in API docs (provinsi → provinsi_id)

3. **Changelog:**
   - Document breaking changes
   - Migration guide for developers

---

## Risks & Mitigation

| Risk | Impact | Probability | Mitigation |
|------|--------|------------|-----------|
| Data loss during migration | HIGH | LOW | Full backup before migration |
| Unmigrated records (NULL IDs) | HIGH | MEDIUM | Validate all records before making NOT NULL |
| Code breaks after migration | HIGH | MEDIUM | Comprehensive testing, gradual rollout |
| Performance issues | MEDIUM | LOW | Benchmark before/after |
| Orphaned data (invalid codes) | MEDIUM | MEDIUM | Audit data first, clean up invalid codes |

---

## Rollback Plan

**If migration fails:**

1. Stop migration immediately
2. Restore from backup:
   ```sql
   DROP TABLE wp_app_agencies;
   RENAME TABLE wp_app_agencies_backup TO wp_app_agencies;

   DROP TABLE wp_app_agency_divisions;
   RENAME TABLE wp_app_agency_divisions_backup TO wp_app_agency_divisions;

   DROP TABLE wp_app_agency_jurisdictions;
   RENAME TABLE wp_app_agency_jurisdictions_backup TO wp_app_agency_jurisdictions;
   ```
3. Revert code changes (git revert)
4. Analyze failure root cause
5. Adjust migration plan and retry

---

## Success Criteria

**Database:**
- [ ] All tables use provinsi_id/regency_id (bigint)
- [ ] No code-based fields remain
- [ ] All constraints updated
- [ ] No NULL region values

**Code:**
- [ ] All queries use ID-based JOINs
- [ ] All CRUD operations use IDs
- [ ] All validation updated
- [ ] All tests passing

**Performance:**
- [ ] Query speed improved 30-50%
- [ ] Index size reduced 33%
- [ ] No performance degradation

**Documentation:**
- [ ] Schema documentation updated
- [ ] API documentation updated
- [ ] Migration guide created
- [ ] Changelog updated

---

## Estimated Effort

**Database Migration:** 2-3 hours
**Code Migration:** 6-8 hours
**Testing:** 3-4 hours
**Documentation:** 1-2 hours

**Total:** 12-17 hours

---

## Dependencies

- ✅ TODO-4012 (Schema Analysis) - COMPLETE
- ⏳ TODO-4015 (Data Audit) - Should run first to identify issues
- ⏳ Approval from team lead

---

## Next Steps

1. **Get approval** for migration plan
2. **Schedule migration** during low-traffic period
3. **Run TODO-4015** (Data Audit) to identify data quality issues
4. **Create migration script**
5. **Test on staging environment**
6. **Execute migration on production**

---

**Last Updated:** 2025-01-02
**Created By:** Claude (Sonnet 4.5)
**Status:** ⏳ Pending Approval
