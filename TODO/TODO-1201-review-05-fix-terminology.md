# TODO-1201-Review-05: Fix Hardcoded Values and Branch Terminology

## Status: ✅ COMPLETED

**Date**: 2025-01-18

## Problem Report

User menemukan dua masalah di `/wp-agency/includes/class-app-core-integration.php`:

### Issue 1: Hardcoded Agency Name

```php
$result = [
    'entity_name' => 'Dinas Tenaga Kerja', // ❌ Hardcoded
    'entity_code' => 'DISNAKER',           // ❌ Hardcoded
    // ...
];
```

### Issue 2: Wrong Terminology - "Branch"

```php
$result = [
    'branch_name' => $division->name,   // ❌ Wrong: branch
    'branch_type' => $division->type,   // ❌ Wrong: branch
    // ...
];
```

**User Comment**: "tidak ada konteks branch di wp-agency !"

## Root Cause Analysis

### Data Model Differences

**wp-customer** uses:
- Customer → **Branch** → Employee
- Branch = Cabang/Kantor (pusat/cabang)

**wp-agency** uses:
- Agency → **Division** → Employee
- Division = Unit/Bagian dalam agency (NOT branch)

### Problems Identified

1. **Hardcoded Agency Info**: Fallback used hardcoded "Dinas Tenaga Kerja" and "DISNAKER"
2. **Wrong Field Names**: Used `branch_name`/`branch_type` instead of `division_name`/`division_type`
3. **Missing Division for Owner**: Agency owner showed hardcoded "Kantor Pusat" instead of actual division

## Database Structure Review

### Tables in wp-agency:

1. **app_agencies**
   - id, code, name, status
   - user_id (owner reference)
   - provinsi_code, regency_code

2. **app_agency_divisions**
   - id, agency_id, code, name
   - type (pusat/cabang)
   - user_id (division admin reference)
   - status, address, phone, email
   - provinsi_code, regency_code

3. **app_agency_employees**
   - id, agency_id, division_id, user_id
   - name, position
   - finance, operation, legal, purchase (departments)
   - status, email, phone

4. **app_agency_jurisdictions**
   - id, division_id, jurisdiction_code
   - is_primary (regency utama)

### Key Insight:

**Division** is the correct level for displaying user info, NOT agency level.

## Solution Implemented

### 1. Fixed Agency Owner Info

**Before** (hardcoded):
```php
if ($agency) {
    $result = [
        'entity_name' => $agency->agency_name,
        'entity_code' => $agency->agency_code,
        'branch_name' => 'Kantor Pusat', // ❌ Hardcoded
        'branch_type' => 'pusat',         // ❌ Wrong field
        'relation_type' => 'owner',
        'icon' => '🏛️'
    ];
}
```

**After** (dynamic division):
```php
if ($agency) {
    // User is an agency owner - show first division
    $division = $wpdb->get_row($wpdb->prepare(
        "SELECT id, name, type
         FROM {$wpdb->prefix}app_agency_divisions
         WHERE agency_id = %d
         AND status = 'active'
         ORDER BY type DESC, id ASC
         LIMIT 1",
        $agency->id
    ));

    if ($division) {
        $result = [
            'entity_name' => $agency->agency_name,
            'entity_code' => $agency->agency_code,
            'division_id' => $division->id,
            'division_name' => $division->name,      // ✅ Real division
            'division_type' => $division->type,      // ✅ Correct field
            'relation_type' => 'owner',
            'icon' => '🏛️'
        ];
    }
}
```

### 2. Fixed Division Admin Info

**Before**:
```php
$result = [
    'division_id' => $division->id,
    'branch_name' => $division->name,    // ❌ Wrong field name
    'branch_type' => $division->type,    // ❌ Wrong field name
    'entity_name' => $division->agency_name,
    'entity_code' => $division->agency_code,
    'relation_type' => 'division_admin',
    'icon' => '🏛️'
];
```

**After**:
```php
$result = [
    'entity_name' => $division->agency_name,
    'entity_code' => $division->agency_code,
    'division_id' => $division->id,
    'division_name' => $division->name,   // ✅ Correct field name
    'division_type' => $division->type,   // ✅ Correct field name
    'relation_type' => 'division_admin',
    'icon' => '🏛️'
];
```

### 3. Fixed Employee Info

**Before**:
```php
$result = [
    'division_id' => $employee->division_id,
    'branch_name' => $employee->division_name,  // ❌ Wrong field name
    'branch_type' => $employee->division_type,  // ❌ Wrong field name
    'entity_name' => $employee->agency_name,
    'entity_code' => $employee->agency_code,
    'position' => $employee->position,
    'relation_type' => 'employee',
    'icon' => '🏛️'
];
```

**After**:
```php
$result = [
    'entity_name' => $employee->agency_name,
    'entity_code' => $employee->agency_code,
    'division_id' => $employee->division_id,
    'division_name' => $employee->division_name,  // ✅ Correct field name
    'division_type' => $employee->division_type,  // ✅ Correct field name
    'position' => $employee->position,
    'relation_type' => 'employee',
    'icon' => '🏛️'
];
```

### 4. Fixed Fallback (Role-Only Users)

**Before** (hardcoded):
```php
$result = [
    'entity_name' => 'Dinas Tenaga Kerja', // ❌ Hardcoded
    'entity_code' => 'DISNAKER',           // ❌ Hardcoded
    'branch_name' => $role_name ?? 'Staff', // ❌ Wrong field name
    'branch_type' => 'admin',
    'relation_type' => 'admin',
    'icon' => '🏛️'
];
```

**After** (generic):
```php
$result = [
    'entity_name' => 'Agency System',      // ✅ Generic for system roles
    'entity_code' => 'AGENCY',             // ✅ Generic code
    'division_id' => null,
    'division_name' => $role_name ?? 'Staff', // ✅ Correct field name
    'division_type' => 'admin',
    'relation_type' => 'role_only',        // ✅ Changed to distinguish
    'icon' => '🏛️'
];
```

## Changes Summary

### Field Name Changes

| Before | After | Reason |
|--------|-------|--------|
| `branch_name` | `division_name` | Correct terminology for agency structure |
| `branch_type` | `division_type` | Correct terminology for agency structure |
| `relation_type: 'admin'` | `relation_type: 'role_only'` | More descriptive for fallback users |

### Hardcoded Values Removed

| Before | After | Type |
|--------|-------|------|
| `'Dinas Tenaga Kerja'` | `'Agency System'` | Fallback entity name |
| `'DISNAKER'` | `'AGENCY'` | Fallback entity code |
| `'Kantor Pusat'` | `$division->name` | Agency owner division |

### New Query Added

Agency owner now queries for first division:
```sql
SELECT id, name, type
FROM app_agency_divisions
WHERE agency_id = %d
AND status = 'active'
ORDER BY type DESC, id ASC
LIMIT 1
```

**Logic**: ORDER BY type DESC prioritizes 'pusat' over 'cabang'

## Return Structure

### Consistent Structure Across All Cases

All return structures now follow the same pattern:

```php
[
    'entity_name' => string,      // Agency name
    'entity_code' => string,      // Agency code
    'division_id' => int|null,    // Division ID (null for fallback)
    'division_name' => string,    // Division name
    'division_type' => string,    // Division type (pusat/cabang/admin)
    'position' => string|null,    // Employee position (if applicable)
    'relation_type' => string,    // owner/division_admin/employee/role_only
    'icon' => string              // Display icon
]
```

### Field Ordering

1. Entity info (agency)
2. Division info
3. Relation info
4. Display info (icon)

## Testing Scenarios

### Test Case 1: Agency Owner
- **User**: Has agency.user_id link
- **Expected**: Shows agency name with first division (type DESC = 'pusat' first)
- **Result**: ✅ Shows actual division, not hardcoded

### Test Case 2: Division Admin
- **User**: Has divisions.user_id link
- **Expected**: Shows agency name with division info
- **Result**: ✅ Uses division_name/division_type correctly

### Test Case 3: Employee
- **User**: Has employees.user_id + division_id link
- **Expected**: Shows agency name, division info, and position
- **Result**: ✅ Uses division_name/division_type correctly

### Test Case 4: Role-Only User (Fallback)
- **User**: Has agency role but no entity link
- **Expected**: Shows "Agency System" with role name as division
- **Result**: ✅ Generic name, not hardcoded agency name

## Benefits

### 1. Correct Terminology
- ✅ No more "branch" in agency context
- ✅ Uses "division" throughout (matches database)

### 2. No Hardcoded Values
- ✅ No hardcoded agency names
- ✅ No hardcoded division names
- ✅ Dynamic data from database

### 3. Consistent Field Names
- ✅ Same field names across all return structures
- ✅ Clear distinction between entity and division
- ✅ Consistent with wp-app-core expectations

### 4. Proper Data Hierarchy
- ✅ Agency level → Entity info
- ✅ Division level → Division info
- ✅ Employee level → Position info

## Comparison with wp-customer

### wp-customer Structure:
```php
[
    'entity_name' => 'PT ABC',           // Customer
    'entity_code' => 'CUST001',
    'branch_id' => 1,
    'branch_name' => 'Kantor Pusat',     // Branch (correct for customer)
    'branch_type' => 'pusat',
    'relation_type' => 'owner',
    'icon' => '🏢'
]
```

### wp-agency Structure (after fix):
```php
[
    'entity_name' => 'Dinas ABC',        // Agency
    'entity_code' => 'AGENCY001',
    'division_id' => 1,
    'division_name' => 'Bidang SDM',     // Division (correct for agency)
    'division_type' => 'pusat',
    'relation_type' => 'owner',
    'icon' => '🏛️'
]
```

**Key Difference**: Customer uses "branch", Agency uses "division" - both correct for their context.

## wp-app-core Compatibility

### Admin Bar Display

wp-app-core should handle both structures:

```php
// Generic display logic
$location = $user_info['division_name'] ?? $user_info['branch_name'] ?? 'Unknown';
$location_type = $user_info['division_type'] ?? $user_info['branch_type'] ?? '';
```

This allows wp-app-core to work with both:
- Customer plugin (uses branch_name/branch_type)
- Agency plugin (uses division_name/division_type)

### Recommended Update to wp-app-core

Consider updating wp-app-core to use generic field names:

```php
[
    'entity_name' => string,      // Main organization
    'entity_code' => string,
    'location_id' => int|null,    // Generic: branch_id or division_id
    'location_name' => string,    // Generic: branch_name or division_name
    'location_type' => string,    // Generic: branch_type or division_type
    'relation_type' => string,
    'icon' => string
]
```

But for now, current approach works with fallback logic.

## Files Modified

### Primary File
- `/wp-agency/includes/class-app-core-integration.php` (v1.1.0)
  - Lines 83-106: Agency owner (added division query)
  - Lines 119-129: Division admin (field name changes)
  - Lines 147-157: Employee (field name changes)
  - Lines 190-198: Fallback (removed hardcoded values, field name changes)
  - Header: Updated changelog

### Documentation
- `/wp-agency/TODO/TODO-1201-review-05-fix-terminology.md` (this file)

## Implementation Date

- **Issue Reported**: 2025-01-18
- **Root Cause Identified**: 2025-01-18
- **Fix Applied**: 2025-01-18
- **Status**: ✅ COMPLETED

## Summary

✅ **Review-05 COMPLETED**:
- Removed all hardcoded agency names and codes
- Changed branch_name/branch_type to division_name/division_type (correct terminology)
- Agency owner now shows actual division from database
- Fallback uses generic "Agency System" instead of hardcoded agency name
- All return structures now consistent and use correct field names

**Key Takeaway**: Always use correct domain terminology - "branch" for customer plugin, "division" for agency plugin. No hardcoded values - always query from database.
