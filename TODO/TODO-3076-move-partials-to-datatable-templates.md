# TODO-3076: Move Partials Folder to DataTable Templates

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement
Folder partials seharusnya berada di dalam struktur DataTable/Templates untuk konsistensi.

**Instruksi:**
1. Pindahkan `/Views/agency/partials/` ke `/Views/DataTable/Templates/partials/`
2. Update semua referensi ke file dalam folder partials
3. Update path header di semua file partials

## Problem

**Before (Inconsistent Structure):**
```
wp-agency/
├── src/
    └── Views/
        └── agency/
            └── partials/
                └── status-filter.php    ❌ Not in DataTable structure
```

**Issues:**
1. ❌ Tidak konsisten dengan struktur DataTable
2. ❌ Partials adalah bagian dari DataTable templates, tapi tidak di folder DataTable
3. ❌ Path tidak mencerminkan bahwa ini adalah DataTable template partials
4. ❌ Sulit untuk memahami hierarki kode

## Solution

Pindahkan partials ke dalam DataTable/Templates structure:

**After (Consistent Structure):**
```
wp-agency/
├── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php
                ├── datatable.php
                └── partials/
                    └── status-filter.php    ✅ Correct location
```

**Benefits:**
1. ✅ Konsisten dengan struktur DataTable
2. ✅ Jelas bahwa partials adalah bagian dari DataTable templates
3. ✅ Mudah dipahami dan dimaintain
4. ✅ Path header sesuai dengan lokasi file

## Changes Implemented

### 1. Move Partials Folder ✅
**Command:**
```bash
mv /wp-agency/src/Views/agency/partials → /wp-agency/src/Views/DataTable/Templates/partials
```

**Result:**
- ✅ Folder moved successfully
- ✅ All files inside partials moved with it

**Files in Partials:**
- `status-filter.php` (Agency status filter dropdown)

### 2. Update References - AgencyDashboardController.php ✅
**File**: `/wp-agency/src/Controllers/Agency/AgencyDashboardController.php`

**Line 268:** Updated include path
```php
// Before:
$filter_file = \WP_AGENCY_PATH . 'src/Views/agency/partials/status-filter.php';

// After:
$filter_file = \WP_AGENCY_PATH . 'src/Views/DataTable/Templates/partials/status-filter.php';
```

**Changes:**
- ✅ Updated path in render_filters method
- ✅ File now includes from correct location

### 3. Update File Headers - status-filter.php ✅
**File**: `/wp-agency/src/Views/DataTable/Templates/partials/status-filter.php`

**Updated:**
1. **Path header** (line 10):
   ```php
   // Before:
   * Path: /wp-agency/src/Views/agency/partials/status-filter.php

   // After:
   * Path: /wp-agency/src/Views/DataTable/Templates/partials/status-filter.php
   ```

2. **Subpackage** (line 6):
   ```php
   // Before:
   * @subpackage  Views/Agency/Partials

   // After:
   * @subpackage  Views/DataTable/Templates/Partials
   ```

3. **Version** (line 7):
   ```php
   // Before:
   * @version     1.0.0

   // After:
   * @version     1.0.3
   ```

4. **Changelog** (lines 17-20):
   ```php
   * 1.0.3 - 2025-10-25
   * - Moved from /Views/agency/partials/ to /Views/DataTable/Templates/partials/
   * - Updated path to match DataTable structure
   * - Updated subpackage to Views/DataTable/Templates/Partials
   ```

## Complete Structure After Changes

```
wp-agency/
└── src/
    ├── Controllers/
    │   └── Agency/
    │       └── AgencyDashboardController.php (includes Templates/partials/status-filter.php)
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php           ✅ Main dashboard template
                ├── datatable.php           ✅ DataTable HTML structure
                └── partials/               ✅ Template partials
                    └── status-filter.php   ✅ Status filter dropdown
```

## Alignment with Structure Pattern

### wp-app-core Pattern:
```
wp-app-core/
└── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── DashboardTemplate.php    (Base templates)
                ├── PanelLayoutTemplate.php
                └── ...
```

### wp-agency Pattern (Now Aligned):
```
wp-agency/
└── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php            (Plugin-specific main templates)
                ├── datatable.php
                └── partials/                (Plugin-specific template partials)
                    └── status-filter.php
```

**Consistency:**
- ✅ Both use `/Views/DataTable/Templates/` structure
- ✅ Clear separation: main templates vs partials
- ✅ Plugin-specific partials in their own subfolder
- ✅ Follows standard MVC pattern (views with partials)

## Files Modified Summary
1. ✅ **Moved**: `/Views/agency/partials/` → `/Views/DataTable/Templates/partials/`
2. ✅ **Updated**: `AgencyDashboardController.php` (line 268) - include path
3. ✅ **Updated**: `status-filter.php` - path header, subpackage, version, changelog

## What is status-filter.php?

**Purpose:**
- Status filter dropdown for agency DataTable
- Allows filtering agencies by: All / Active / Inactive
- Requires `edit_all_agencies` or `manage_options` capability

**Usage:**
- Rendered via `wpapp_datatable_filters` hook
- Called by `AgencyDashboardController::render_filters()`
- Displays in filters container above DataTable

**Features:**
- Permission-based visibility (admin only)
- Default selection: "Active"
- GET parameter support: `?status_filter=active`
- CSS: `agency-status-filter-group` (local scope)

## Testing Checklist
- [ ] Clear WordPress cache
- [ ] Clear PHP opcache if enabled
- [ ] Visit wp-agency Disnaker menu:
  - [ ] Status filter displays above DataTable
  - [ ] Filter shows 3 options (All, Active, Inactive)
  - [ ] Default selection is "Active"
  - [ ] No PHP errors in error log
- [ ] Test filter functionality:
  - [ ] Select "All" - DataTable updates
  - [ ] Select "Active" - DataTable updates
  - [ ] Select "Inactive" - DataTable updates
- [ ] Check DevTools:
  - [ ] No JavaScript errors
  - [ ] CSS loads correctly
- [ ] Verify file paths:
  - [ ] Old path `/Views/agency/partials/` no longer exists
  - [ ] New path `/Views/DataTable/Templates/partials/` exists
  - [ ] status-filter.php exists in new location

## Related TODOs
- Related: `wp-agency/TODO/TODO-3075-restructure-datatable-templates-directory.md`
- Related: `wp-agency/TODO/TODO-3074-rename-filter-group-to-status-filter-group.md`
- Related: `wp-agency/TODO/TODO-3073-remove-double-wrapper-filter.md`
- See: `wp-app-core/TODO/TODO-1179-rename-container-to-datatable-container.md`

## References
- Dashboard controller: `AgencyDashboardController.php`
- Filter hook: `wpapp_datatable_filters`
- CSS: `agency-filter.css`

## Code Quality Notes
This restructuring improves:
1. **Consistency** - Partials now within DataTable/Templates structure
2. **Clarity** - Clear that partials are DataTable template components
3. **Organization** - Logical grouping of related templates
4. **Maintainability** - Easier to find and understand template hierarchy
5. **Best Practices** - Follows MVC pattern (views with partials subfolder)

## Old Directory Cleanup
The old directory `/wp-agency/src/Views/agency/partials/` no longer exists after move.

Check if parent directory `/wp-agency/src/Views/agency/` is now empty:

```bash
# Check if directory is empty
ls /wp-agency/src/Views/agency/

# If empty, remove it
rmdir /wp-agency/src/Views/agency/
```

**Note:** Only remove if the directory is completely empty and no other files depend on it.

## Future Partials
If more template partials are needed in the future, they should be created in:
- `/wp-agency/src/Views/DataTable/Templates/partials/`

**Examples of potential future partials:**
- `bulk-actions.php` - Bulk action dropdown
- `search-box.php` - Advanced search form
- `export-button.php` - Export functionality
- `column-visibility.php` - Column visibility toggle

All should follow the same pattern:
1. Located in `/Views/DataTable/Templates/partials/`
2. Use plugin-specific CSS classes (e.g., `agency-*`)
3. Include proper path headers
4. Document in TODO files
