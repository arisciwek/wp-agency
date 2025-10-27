# TODO-3075: Restructure DataTable Templates Directory

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement
File-file DataTable seharusnya berada di struktur yang konsisten dengan wp-app-core.

**Instruksi:**
1. Pindahkan file dari `/Views/agency/` ke `/Views/DataTable/Templates/`
2. Update semua referensi ke file tersebut
3. Update path header di file yang dipindahkan

## Problem

**Before (Inconsistent Structure):**
```
wp-agency/
├── src/
    └── Views/
        └── agency/
            ├── dashboard.php    ❌ Wrong location
            └── datatable.php    ❌ Wrong location
```

**Issues:**
1. ❌ Tidak konsisten dengan struktur wp-app-core
2. ❌ Path `/Views/agency/` tidak mencerminkan bahwa ini adalah DataTable templates
3. ❌ Menyulitkan untuk memahami struktur kode
4. ❌ Path header tidak sesuai dengan lokasi file

## Solution

Pindahkan ke struktur yang konsisten dengan wp-app-core:

**After (Consistent Structure):**
```
wp-agency/
├── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php    ✅ Correct location
                └── datatable.php    ✅ Correct location
```

**Benefits:**
1. ✅ Konsisten dengan wp-app-core structure
2. ✅ Jelas bahwa ini adalah DataTable templates
3. ✅ Mudah dipahami dan dimaintain
4. ✅ Path header sesuai dengan lokasi file

## Changes Implemented

### 1. Create Directory Structure ✅
**Command:**
```bash
mkdir -p /wp-agency/src/Views/DataTable/Templates
```

**Result:**
- ✅ Created `/wp-agency/src/Views/DataTable/Templates/` directory

### 2. Move Files ✅
**Commands:**
```bash
mv /wp-agency/src/Views/agency/dashboard.php → /wp-agency/src/Views/DataTable/Templates/dashboard.php
mv /wp-agency/src/Views/agency/datatable.php → /wp-agency/src/Views/DataTable/Templates/datatable.php
```

**Files Moved:**
- ✅ `dashboard.php` moved to new location
- ✅ `datatable.php` moved to new location

### 3. Update References - MenuManager.php ✅
**File**: `/wp-agency/src/Controllers/MenuManager.php`

**Line 54:** Updated include path
```php
// Before:
include \WP_AGENCY_PATH . 'src/Views/agency/dashboard.php';

// After:
include \WP_AGENCY_PATH . 'src/Views/DataTable/Templates/dashboard.php';
```

**Changes:**
- ✅ Updated path in add_menu_page callback
- ✅ File now includes from correct location

### 4. Update References - AgencyDashboardController.php ✅
**File**: `/wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

**Line 156:** Updated include path
```php
// Before:
$datatable_file = WP_AGENCY_PATH . 'src/Views/agency/datatable.php';

// After:
$datatable_file = WP_AGENCY_PATH . 'src/Views/DataTable/Templates/datatable.php';
```

**Changes:**
- ✅ Updated path in render_left_panel method
- ✅ File now includes from correct location

### 5. Update File Headers - dashboard.php ✅
**File**: `/wp-agency/src/Views/DataTable/Templates/dashboard.php`

**Updated:**
1. **Path header** (line 10):
   ```php
   // Before:
   * Path: /wp-agency/src/Views/agency/dashboard.php

   // After:
   * Path: /wp-agency/src/Views/DataTable/Templates/dashboard.php
   ```

2. **Subpackage** (line 6):
   ```php
   // Before:
   * @subpackage  Views/Agency

   // After:
   * @subpackage  Views/DataTable/Templates
   ```

3. **Version** (line 7):
   ```php
   // Before:
   * @version     1.0.0

   // After:
   * @version     1.0.1
   ```

4. **Changelog** (lines 22-25):
   ```php
   * 1.0.1 - 2025-10-25
   * - Moved from /Views/agency/ to /Views/DataTable/Templates/
   * - Updated path to match DataTable structure
   * - Updated subpackage to Views/DataTable/Templates
   ```

### 6. Update File Headers - datatable.php ✅
**File**: `/wp-agency/src/Views/DataTable/Templates/datatable.php`

**Updated:**
1. **Path header** (line 10):
   ```php
   // Before:
   * Path: /wp-agency/src/Views/agency/datatable.php

   // After:
   * Path: /wp-agency/src/Views/DataTable/Templates/datatable.php
   ```

2. **Subpackage** (line 6):
   ```php
   // Before:
   * @subpackage  Views/Agency

   // After:
   * @subpackage  Views/DataTable/Templates
   ```

3. **Version** (line 7):
   ```php
   // Before:
   * @version     1.0.0

   // After:
   * @version     1.0.1
   ```

4. **Changelog** (lines 17-20):
   ```php
   * 1.0.1 - 2025-10-25
   * - Moved from /Views/agency/ to /Views/DataTable/Templates/
   * - Updated path to match DataTable structure
   * - Updated subpackage to Views/DataTable/Templates
   ```

## Structure Comparison

### Before (Inconsistent)
```
wp-agency/
├── src/
    ├── Controllers/
    │   └── MenuManager.php (includes Views/agency/dashboard.php)
    │   └── Agency/
    │       └── AgencyDashboardController.php (includes Views/agency/datatable.php)
    └── Views/
        └── agency/
            ├── dashboard.php    ❌ Not in DataTable structure
            └── datatable.php    ❌ Not in DataTable structure
```

### After (Consistent with wp-app-core)
```
wp-agency/
├── src/
    ├── Controllers/
    │   └── MenuManager.php (includes Views/DataTable/Templates/dashboard.php)
    │   └── Agency/
    │       └── AgencyDashboardController.php (includes Views/DataTable/Templates/datatable.php)
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php    ✅ Matches wp-app-core structure
                └── datatable.php    ✅ Matches wp-app-core structure
```

## Alignment with wp-app-core

### wp-app-core Structure:
```
wp-app-core/
└── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── DashboardTemplate.php
                ├── PanelLayoutTemplate.php
                ├── TabSystemTemplate.php
                ├── StatsBoxTemplate.php
                └── NavigationTemplate.php
```

### wp-agency Structure (Now Aligned):
```
wp-agency/
└── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php     ✅ Plugin-specific dashboard
                └── datatable.php     ✅ Plugin-specific datatable
```

**Consistency:**
- ✅ Both use `/Views/DataTable/Templates/` structure
- ✅ wp-app-core: Base templates (reusable)
- ✅ wp-agency: Plugin-specific templates (agency-specific)
- ✅ Clear separation between global and local scope

## Files Modified Summary
1. ✅ **Created**: `/wp-agency/src/Views/DataTable/Templates/` directory
2. ✅ **Moved**: `dashboard.php` to new location
3. ✅ **Moved**: `datatable.php` to new location
4. ✅ **Updated**: `MenuManager.php` (line 54) - include path
5. ✅ **Updated**: `AgencyDashboardController.php` (line 156) - include path
6. ✅ **Updated**: `dashboard.php` - path header, subpackage, version, changelog
7. ✅ **Updated**: `datatable.php` - path header, subpackage, version, changelog

## Testing Checklist
- [ ] Clear WordPress cache
- [ ] Clear PHP opcache if enabled
- [ ] Visit wp-agency Disnaker menu:
  - [ ] Dashboard loads correctly
  - [ ] DataTable displays
  - [ ] No PHP errors in error log
- [ ] Check DevTools console:
  - [ ] No JavaScript errors
  - [ ] All assets load correctly
- [ ] Test functionality:
  - [ ] DataTable loads data
  - [ ] Filtering works
  - [ ] Panel sliding works
  - [ ] Tab switching works (if tabs enabled)
- [ ] Verify file paths:
  - [ ] Old files no longer exist in `/Views/agency/`
  - [ ] New files exist in `/Views/DataTable/Templates/`

## Related TODOs
- See: `wp-app-core/TODO/TODO-1179-rename-container-to-datatable-container.md`
- See: `wp-agency/TODO/TODO-3073-remove-double-wrapper-filter.md`
- See: `wp-agency/TODO/TODO-3074-rename-filter-group-to-status-filter-group.md`
- Related: `wp-agency/TODO/TODO-2071-implement-agency-dashboard-with-panel-system.md`

## References
- wp-app-core structure: `/wp-app-core/src/Views/DataTable/Templates/`
- Menu registration: `MenuManager.php`
- Left panel hook: `AgencyDashboardController.php`

## Code Quality Notes
This restructuring improves:
1. **Consistency** - Matches wp-app-core structure
2. **Clarity** - Clear what type of templates these are (DataTable)
3. **Maintainability** - Easier to find and understand files
4. **Documentation** - Path headers match actual file locations
5. **Best Practices** - Follows established plugin architecture patterns

## Old Directory Cleanup
The old directory `/wp-agency/src/Views/agency/` may still exist but is now empty.
Consider removing it if no other files use it:

```bash
# Check if directory is empty
ls /wp-agency/src/Views/agency/

# If empty, remove it
rmdir /wp-agency/src/Views/agency/
```

**Note:** Only remove if the directory is completely empty and no other files depend on it.
