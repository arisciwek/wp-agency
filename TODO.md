# TODO List for WP Agency Plugin

## TODO-3080: Scope Separation Refactoring & UX Improvements ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: HIGH
**Category**: Architecture, Code Quality, UX Enhancement
**Dependencies**: wp-app-core TODO-1183, 1184, 1185, 004

**Summary**: Comprehensive refactoring mengatasi 4 masalah utama: scroll jump, visual flicker, inline CSS/JS dalam PHP, dan mixed scope (wpapp-* vs agency-*). Hasil akhir: Clean architecture dengan strict scope separation dan professional UX.

**Problems Solved**:

**Problem 1: Scroll Jump on Panel Open** ✅
- **Root Cause**: `window.location.hash` triggers browser scroll
- **Solution**: Changed to `history.pushState()` (line 664-670)
- **Result**: No scroll jump, hash updates correctly

**Problem 2: Visual Flicker** ✅
- **A. Right Panel Loading Flicker**:
  - Anti-flicker pattern: 300ms delay before showing loader
  - CSS transition for smooth fade-in
  - Request < 300ms: Loading NEVER shows
  - Request > 300ms: Smooth fade-in
- **B. Left Panel DataTable Flicker**:
  - Removed unnecessary `draw(false)` call
  - Only use `columns.adjust()` for width recalculation
  - No more row flash/redraw

**Problem 3: Inline CSS & JavaScript** ✅
- **Before**: divisions.php & employees.php with 34 lines inline script each
- **After**: Event-driven tab loading via data attributes
- Pattern: Configuration (data-*) → External JS (wpapp-tab-manager.js)
- Benefits: Cacheable, reusable, clean HTML, automatic caching

**Problem 4: Mixed Scope (wpapp-* vs agency-*)** ✅
- **Before**: `stats-card agency-stats-card` (mixed prefixes)
- **After**: `agency-stat-card` (pure local scope)
- Rule: wpapp-* (global structure) vs agency-* (local content)
- NO mixing scopes in same file

**Architecture Pattern**:
```
wp-app-core: Container + Hook (infrastructure)
  ↓
wp-agency: Full HTML + CSS (implementation)
```

**Files Modified**:

**wp-app-core (4 files)**:
1. `assets/js/datatable/wpapp-panel-manager.js` - Anti-flicker + scroll fix
2. `assets/js/datatable/panel-handler.js` - DELETED (deprecated)
3. `assets/css/datatable/wpapp-datatable.css` - Loading + tab states
4. `assets/js/datatable/wpapp-tab-manager.js` - Auto-load tab content

**wp-agency (5 files)**:
5. `src/Views/agency/tabs/divisions.php` - 81→57 lines (-30%)
6. `src/Views/agency/tabs/employees.php` - 81→57 lines (-30%)
7. `src/Controllers/Agency/AgencyDashboardController.php` - Refactored to agency-*
8. `assets/css/agency/agency-style.css` - Complete rewrite (pure agency-*)
9. `includes/class-dependencies.php` - Re-enabled agency-style.css

**Code Quality Metrics**:
- Mixed scopes: 100% → 0% ✅
- Inline scripts: 68 lines → 0 lines ✅
- Coupling: High → Loose ✅
- Scroll jump: Yes → No ✅
- Visual flicker: Yes → No ✅

**Benefits**:
- ✅ Professional UX (no flicker, no scroll jump)
- ✅ Clean architecture (strict scope separation)
- ✅ Maintainable (clear ownership)
- ✅ Testable (loose coupling)
- ✅ Reusable (hook-based pattern)
- ✅ Cacheable (external JS/CSS)

See: [TODO/TODO-3080-scope-separation-refactoring.md](TODO/TODO-3080-scope-separation-refactoring.md)

---

## TODO-3079: Test Anti-Flicker Panel Pattern ✅ READY TO TEST

**Status**: ✅ READY TO TEST
**Created**: 2025-10-26
**Related**: wp-app-core TODO-1182

**Summary**: Test implementasi anti-flicker panel pattern dari wp-app-core untuk Agency dashboard. Verifikasi smooth panel transitions, DataTable adjustment, dan tab system integration.

**What to Test**:

**1. Panel Opening Behavior**:
- ✅ Left panel shrinks 100% → 45% (smooth 300ms)
- ✅ Right panel muncul 55% width
- ✅ No flicker atau layout jump
- ✅ DataTable columns ter-adjust otomatis
- ✅ Agency detail data loads

**2. Panel Closing Behavior**:
- ✅ Right panel hilang dengan smooth transition
- ✅ Left panel expand 100%
- ✅ DataTable columns adjust back
- ✅ No flicker atau layout jump

**3. DataTable Integration**:
- ✅ Columns proportional di 45% width
- ✅ No horizontal scrollbar if not needed
- ✅ Sorting/filtering still works
- ✅ Pagination still works

**4. Tab System**:
- ✅ Tab "Data Disnaker" loads immediately
- ✅ Tab "Unit Kerja" lazy loads
- ✅ Tab "Staff" lazy loads
- ✅ Content fits in 55% panel
- ✅ No layout issues

**5. Scroll Behavior**:
- ✅ Page scrolls to top automatically
- ✅ No flicker during scroll
- ✅ Panel opens smoothly after scroll

**Panel Width Comparison**:
- Old: 58.33% / 41.67%
- New: 45% / 55% (more space for details)

**Performance Metrics**:
- Panel open animation: ~300ms
- DataTable adjust: ~50ms (after 350ms wait)
- Total time: ~400ms
- AJAX data load: 200-500ms

**Common Issues to Check**:
- DataTable not adjusting (check instance found)
- Panel flickers (check timing 300ms)
- Layout jump (check scroll-to-top)
- Tabs not loading (check AJAX actions)

See: [TODO/TODO-3079-test-anti-flicker-panel-for-agency.md](TODO/TODO-3079-test-anti-flicker-panel-for-agency.md)

---

## TODO-3078: Implement Agency Header Action Buttons ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-26
**Completed**: 2025-10-26
**Related**: wp-app-core TODO-1181

**Summary**: Implementasi tombol action (Print, Export, Tambah Disnaker) di page header Agency dashboard menggunakan hook `wpapp_page_header_right` dari wp-app-core.

**Buttons Implemented**:

**1. Print Button**:
- Icon: `dashicons-printer`
- ID: `agency-print-btn`
- Permission: `view_agency_list`
- Ready for JS handler

**2. Export Button**:
- Icon: `dashicons-download`
- ID: `agency-export-btn`
- Permission: `view_agency_list`
- Ready for JS handler

**3. Tambah Disnaker Button**:
- Icon: `dashicons-plus-alt`
- Class: `button-primary agency-add-btn`
- Permission: `add_agency`
- Ready for modal/form handler

**Implementation**:
- File: `src/Controllers/Agency/AgencyDashboardController.php`
- Line 96: Registered hook `wpapp_page_header_right`
- Lines 190-226: Method `render_header_buttons()`

**Permission Matrix**:
| Role | Print | Export | Tambah |
|------|-------|--------|--------|
| Administrator | ✅ | ✅ | ✅ |
| Agency Admin Dinas | ✅ | ✅ | ⚙️ |
| Agency Employee | ✅ | ✅ | ⚙️ |

**Benefits**:
- ✅ No template changes (hook system)
- ✅ Permission-based UI
- ✅ Consistent WordPress styling
- ✅ Icon aligned properly
- ✅ Extensible
- ✅ JS ready (IDs available)

See: [TODO/TODO-3078-implement-agency-header-buttons.md](TODO/TODO-3078-implement-agency-header-buttons.md)

---

## TODO-3077: Move Inline JavaScript to Separate File ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-25
**Completed**: 2025-10-25
**Priority**: HIGH
**Category**: Code Quality, Separation of Concerns

**Summary**: Remove inline JavaScript dari datatable.php dan pindahkan ke external file `agency-datatable.js` dengan proper enqueue dan localization. Achieve 100% separation of concerns.

**Problem**:
- 100+ lines inline `<script>` di datatable.php
- Mixed concerns (PHP + JavaScript)
- Not cacheable, not minifiable
- Hard to maintain
- Translation issues

**Solution**:
- Created: `assets/js/agency/agency-datatable.js` v2.0.0
- Removed: All inline scripts from datatable.php
- Added: Enqueue + localize in class-dependencies.php (lines 359-382)
- Pattern: Module pattern with AgencyDataTable object

**Key Features**:
- Table ID: `#agency-list-table`
- AJAX action: `get_agencies_datatable`
- Columns: code, name, provinsi_name, regency_name, actions
- Integration: Base panel system via `wpapp:open-panel` event
- Localized: `wpAgencyDataTable` object with translations

**Module Structure**:
```javascript
const AgencyDataTable = {
    table: null,
    initialized: false,
    init() { ... },
    initDataTable() { ... },
    bindEvents() { ... },
    refresh() { ... },
    destroy() { ... }
};
```

**Translation Strategy**:
- Before: PHP mixed with JS (inline)
- After: `wp_localize_script()` with fallback values
- Benefits: Cacheable, proper i18n, clean separation

**Files Modified**:
1. `assets/js/agency/agency-datatable.js` - Rewritten v2.0.0
2. `assets/js/agency/agency-datatable.js.backup` - Backup old version
3. `src/Views/DataTable/Templates/datatable.php` - Removed inline script (v1.0.3)
4. `includes/class-dependencies.php` - Added enqueue + localize

**Benefits**:
- ✅ Separation of concerns (PHP = HTML, JS = JavaScript)
- ✅ Performance (cacheable, minifiable)
- ✅ Maintainability (single source of truth)
- ✅ Internationalization (proper WordPress i18n)
- ✅ Reusability (module pattern)
- ✅ Best practices (WordPress standards)

See: [TODO/TODO-3077-move-inline-js-to-separate-file.md](TODO/TODO-3077-move-inline-js-to-separate-file.md)

---

## TODO-3076: Move Partials to DataTable Templates ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-25
**Completed**: 2025-10-25

**Summary**: Pindahkan folder partials dari `/Views/agency/partials/` ke `/Views/DataTable/Templates/partials/` untuk konsistensi dengan struktur DataTable.

**Problem**:
- Partials berada di `/Views/agency/partials/` (wrong location)
- Tidak konsisten dengan struktur DataTable
- Path tidak mencerminkan bahwa ini adalah DataTable template partials

**Solution**:
```
MOVED:
/Views/agency/partials/
  └── status-filter.php

TO:
/Views/DataTable/Templates/partials/
  └── status-filter.php
```

**Files Modified**:
1. Moved partials folder to DataTable/Templates/partials/
2. Updated: `AgencyDashboardController.php` (line 268) - include path
3. Updated: `status-filter.php` - path header, subpackage, version (v1.0.3)

**Structure After Changes**:
```
wp-agency/
└── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php
                ├── datatable.php
                └── partials/
                    └── status-filter.php  ✅
```

**Alignment with Pattern**:
- wp-app-core: `/Views/DataTable/Templates/` (base templates)
- wp-agency: `/Views/DataTable/Templates/` (plugin-specific)
- Both use same structure ✅

**What is status-filter.php?**
- Status filter dropdown for agency DataTable
- Filters: All / Active / Inactive
- Permission: `edit_all_agencies` or `manage_options`
- CSS: `agency-status-filter-group`

See: [TODO/TODO-3076-move-partials-to-datatable-templates.md](TODO/TODO-3076-move-partials-to-datatable-templates.md)

---

## TODO-3075: Restructure DataTable Templates Directory ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-25
**Completed**: 2025-10-25

**Summary**: Pindahkan file DataTable dari `/Views/agency/` ke `/Views/DataTable/Templates/` untuk konsistensi dengan struktur wp-app-core.

**Problem**:
- Files berada di `/Views/agency/` (inconsistent)
- Path tidak mencerminkan bahwa ini adalah DataTable templates
- Tidak konsisten dengan wp-app-core structure

**Solution**:
```
MOVED:
/Views/agency/dashboard.php  →  /Views/DataTable/Templates/dashboard.php
/Views/agency/datatable.php  →  /Views/DataTable/Templates/datatable.php
```

**Files Modified**:
1. Created directory: `/Views/DataTable/Templates/`
2. Moved files: dashboard.php, datatable.php
3. Updated: `MenuManager.php` (line 54) - include path
4. Updated: `AgencyDashboardController.php` (line 156) - include path
5. Updated: `dashboard.php` - path header, subpackage, version (v1.0.1)
6. Updated: `datatable.php` - path header, subpackage, version (v1.0.1)

**Structure Comparison**:

**Before (Inconsistent)**:
```
wp-agency/
└── src/
    └── Views/
        └── agency/
            ├── dashboard.php  ❌
            └── datatable.php  ❌
```

**After (Consistent)**:
```
wp-agency/
└── src/
    └── Views/
        └── DataTable/
            └── Templates/
                ├── dashboard.php  ✅
                └── datatable.php  ✅
```

**Alignment with wp-app-core**:
- wp-app-core: `/Views/DataTable/Templates/` (base templates)
- wp-agency: `/Views/DataTable/Templates/` (plugin-specific)
- Clear separation between global and local scope ✅

**Benefits**:
- ✅ Consistency with wp-app-core structure
- ✅ Clear what type of templates (DataTable)
- ✅ Easier to find and understand files
- ✅ Path headers match actual locations
- ✅ Follows established plugin architecture

See: [TODO/TODO-3075-restructure-datatable-templates-directory.md](TODO/TODO-3075-restructure-datatable-templates-directory.md)

---

## TODO-3074: Rename Filter Group to Status Filter Group ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-25
**Completed**: 2025-10-25

**Summary**: Rename `agency-filter-group` menjadi `agency-status-filter-group` untuk menambahkan konteks "status" yang lebih deskriptif.

**Problem**:
- Class name `agency-filter-group` terlalu generic
- Tidak mencerminkan bahwa ini adalah filter untuk STATUS
- Kurang deskriptif untuk maintainability

**Solution**:
- Renamed: `agency-filter-group` → `agency-status-filter-group`
- Added "status" context to class name

**Changes**:

**1. status-filter.php (line 40)**:
```php
// Before:
<div class="agency-filter-group">

// After:
<div class="agency-status-filter-group">
```

**2. agency-filter.css (lines 20-25)**:
```css
/* Before: */
/* Filter Group */
.agency-filter-group { ... }

/* After: */
/* Status Filter Group */
.agency-status-filter-group { ... }
```

**Naming Convention Rationale**:
- `agency-` - Plugin scope prefix
- `status-` - Filter type context (filters by STATUS)
- `filter-` - Component type (this is a FILTER)
- `group` - Layout wrapper (groups label + select)

**Benefits**:
- ✅ More descriptive class name
- ✅ Better maintainability
- ✅ Consistent naming convention
- ✅ No breaking changes (only 2 files)
- ✅ No visual changes

See: [TODO/TODO-3074-rename-filter-group-to-status-filter-group.md](TODO/TODO-3074-rename-filter-group-to-status-filter-group.md)

---

## TODO-3073: Remove Double Wrapper in Filter ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-25
**Completed**: 2025-10-25

**Summary**: Hapus double wrapper yang tidak diperlukan (`agency-filter-wrapper` + `agency-filter-group`). Gunakan hanya `agency-filter-group` karena sudah ada `wpapp-datatable-filters` dari wp-app-core.

**Problem**:

**Before (Double Wrapper)**:
```html
<div class="wpapp-filters-container">        <!-- wp-app-core -->
  <div class="wpapp-datatable-filters">      <!-- wp-app-core (white box) -->
    <div class="agency-filter-wrapper">      <!-- ❌ Redundant 1 -->
      <div class="agency-filter-group">      <!-- ❌ Redundant 2 -->
        <label>...</label>
        <select>...</select>
      </div>
    </div>
  </div>
</div>
```

**Issues**:
- `wpapp-datatable-filters` already provides white box styling
- `agency-filter-wrapper` has no styling (padding: 0, margin: 0)
- Double wrapper adds no value, only complexity

**Solution**:

**After (Single Wrapper)**:
```html
<div class="wpapp-filters-container">        <!-- wp-app-core -->
  <div class="wpapp-datatable-filters">      <!-- wp-app-core (white box) -->
    <div class="agency-filter-group">        <!-- ✅ wp-agency (flex layout) -->
      <label>...</label>
      <select>...</select>
    </div>
  </div>
</div>
```

**Changes**:
1. Removed `agency-filter-wrapper` div from status-filter.php
2. Removed `.agency-filter-wrapper` CSS rules from agency-filter.css
3. Kept `agency-filter-group` (provides flex layout)

**Responsibility Separation**:
- **Global (wp-app-core)**: `wpapp-datatable-filters` - white box, padding, border
- **Local (wp-agency)**: `agency-filter-group` - flex layout for label + select

**Benefits**:
- ✅ Eliminated redundant wrapper (simpler HTML)
- ✅ Cleaner structure (less nesting)
- ✅ Clear separation (global vs local)
- ✅ Easier to maintain
- ✅ No visual changes

See: [TODO/TODO-3073-remove-double-wrapper-filter.md](TODO/TODO-3073-remove-double-wrapper-filter.md)

---

## TODO-3072: Rename agency-card to agency-stats-card ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-25
**Completed**: 2025-10-25

**Summary**: Ubah class `agency-card` menjadi `agency-stats-card` dan salin property CSS dari `customer-stats-card` (companies.css) untuk visual consistency dengan wp-customer.

**Changes Implemented**:

**1. AgencyDashboardController.php - HTML Class Names**:

| Before | After |
|--------|-------|
| `agency-card` | `agency-stats-card` |
| `agency-card-blue` | `agency-stats-card-blue` |
| `agency-card-green` | `agency-stats-card-green` |
| `agency-card-orange` | `agency-stats-card-orange` |
| `agency-card-icon` | `agency-stats-icon` |
| `agency-card-content` | `agency-stats-content` |
| `agency-card-value` | `agency-stats-value` |
| `agency-card-label` | `agency-stats-label` |

**2. agency-header-cards.css - CSS Properties** (copied from customer-stats-card):

**Key Property Changes**:
- `padding`: `10px 15px` → `20px` (more spacious)
- `background`: `#f8f9fa` → `#fff` (white bg)
- `border-radius`: `6px` → `8px` (larger radius)
- `box-shadow`: Added for depth
- Icon size: `40px` → `55px` (larger, more visible)
- Value font: `18px/600` → `32px/700` (bigger, bolder)
- Label font: `11px` → `13px` (more readable)

**Visual Changes**:
- Card size: Compact → Standard (more spacious)
- Icon: 40px → 55px (larger)
- Number: 18px → 32px (more prominent)
- Label: 11px → 13px (better readability)
- Background: Light gray → White
- Added hover effect (translateY + shadow)

**Benefits**:
- ✅ Consistent naming convention
- ✅ Visual consistency with wp-customer
- ✅ Better sizing and spacing
- ✅ Updated font hierarchy
- ✅ Professional appearance

**Files Modified**:
1. `src/Controllers/Agency/AgencyDashboardController.php` (lines 216-247)
2. `assets/css/agency/agency-header-cards.css` (entire file)

See: [TODO/TODO-3072-rename-agency-card-to-agency-stats-card.md](TODO/TODO-3072-rename-agency-card-to-agency-stats-card.md)

---

## TODO-3071: Fix Agency Stats Cards Container Position ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-25
**Completed**: 2025-10-25

**Summary**: Pindahkan `agency-header-cards` ke DALAM `wpapp-statistics-container` untuk konsistensi dengan wp-customer dan struktur global scope wp-app-core.

**Problem**:

**Before**:
```html
<div class="agency-header-cards">        <!-- ❌ Di luar container -->
  <div class="agency-card ...">...</div>
</div>
<div class="wpapp-statistics-container">  <!-- Empty or duplicate -->
</div>
```

**After**:
```html
<div class="wpapp-statistics-container">  <!-- ✅ Global container -->
  <div class="statistics-cards" id="agency-statistics">  <!-- ✅ Inside -->
    <div class="stats-card agency-card ...">...</div>
  </div>
</div>
```

**Changes Implemented**:

**1. AgencyDashboardController.php - Hook Registration (Line 98)**:
```php
// Before:
add_action('wpapp_dashboard_before_stats', [$this, 'render_header_cards'], 10, 2);

// After:
add_action('wpapp_statistics_cards_content', [$this, 'render_header_cards'], 10, 1);
```

**2. Method Signature**:
```php
// Before:
public function render_header_cards($config, $entity): void

// After:
public function render_header_cards($entity): void
```

**3. HTML Structure**:
- Wrapper: `agency-header-cards` → `statistics-cards` (global)
- Added global classes: `stats-card`, `stats-icon`, `stats-content`, `stats-number`, `stats-label`
- Kept local classes: `agency-card`, `agency-card-*` for custom styling

**Class Naming Convention**:

**Global Scope (wp-app-core)** - Prefix: `wpapp-` or `stats-`:
- `wpapp-statistics-container` - Container wrapper
- `statistics-cards` - Cards wrapper
- `stats-card` - Individual card
- `stats-icon`, `stats-content`, `stats-number`, `stats-label`

**Local Scope (wp-agency)** - Prefix: `agency-`:
- `agency-card` - Additional card styling
- `agency-card-blue/green/orange` - Color variants
- `agency-card-icon/content/value/label` - Custom styling

**Benefits**:
- ✅ Consistent structure with wp-customer
- ✅ Cards properly positioned inside container
- ✅ Maintains custom agency styling
- ✅ Follows global/local scope separation
- ✅ No breaking changes (both class sets preserved)

**Files Modified**:
- `src/Controllers/Agency/AgencyDashboardController.php`

See: [TODO/TODO-3071-fix-stats-cards-container-position.md](TODO/TODO-3071-fix-stats-cards-container-position.md)

---

## TODO-2071: Implement Agency Dashboard with Panel System 🔵 READY TO START

**Status**: 🔵 READY TO START
**Created**: 2025-10-23
**Dependencies**: TODO-2179 (Base Panel System Phase 1-7) ✅, TODO-2178 ✅, TODO-2174 ✅
**Priority**: HIGH (Critical for TODO-2179 Phase 8 completion)
**Complexity**: High (Full dashboard + cross-plugin integration)

**Summary**: Implement Agency Dashboard ("Disnaker") using base panel system from wp-app-core (TODO-2179). Serves as **Phase 8 integration testing** for base panel system. Features 3-tab layout with lazy loading, cross-plugin permission filtering, and hook-based access control.

**SQL Query**: ✅ VERIFIED (2025-10-23)
```sql
-- User → CustomerEmployee → Branch → Agency
SELECT a.* FROM wp_app_agencies a
INNER JOIN wp_app_customer_branches b ON a.id = b.agency_id
INNER JOIN wp_app_customer_employees ce ON b.id = ce.branch_id
WHERE ce.user_id = ? AND a.status = 'active'
GROUP BY a.id;
```

**Test Results**: user_id=2 can access 1 agency (Disnaker Provinsi Maluku)

**Current Status**:
- ✅ **Action Hooks**: 9/9 implemented (Agency, Division, Employee lifecycle)
- ⏳ **Filter Hooks**: 0/8 implemented (documented but not in code)

**Filter Hooks to Implement**:

**Permission Filters (3 hooks)**:
- [ ] `wp_agency_can_create_employee` - Override employee creation permission
  - Parameters: `($can_create, $agency_id, $division_id, $user_id)`
  - Return: `bool`
  - Location: `AgencyEmployeeController.php` or `AgencyEmployeeValidator.php`

- [ ] `wp_agency_can_create_division` - Override division creation permission
  - Parameters: `($can_create, $agency_id, $user_id)`
  - Return: `bool`
  - Location: `DivisionController.php` or `DivisionValidator.php`

- [ ] `wp_agency_max_inspector_assignments` - Maximum inspector assignments
  - Parameters: none
  - Return: `int`
  - Location: Inspector assignment logic (future feature)

**UI/UX Filters (2 hooks)**:
- [ ] `wp_agency_enable_export` - Enable/disable export button
  - Parameters: none
  - Return: `bool`
  - Location: DataTable templates (agency-list.php, division-list.php, employee-list.php)

- [ ] `wp_company_detail_tabs` - Add/remove company detail tabs
  - Parameters: `($tabs)`
  - Return: `array`
  - Location: Company detail view template

**System Filters (1 hook)**:
- [ ] `wp_agency_debug_mode` - Enable debug logging
  - Parameters: none
  - Return: `bool`
  - Location: Logger class or utility functions

**External Integration Filters (2 hooks)**:
- [ ] `wilayah_indonesia_get_province_options` - Get province dropdown options
  - Parameters: `($options)`
  - Return: `array`
  - Location: Form templates or AJAX handlers

- [ ] `wilayah_indonesia_get_regency_options` - Get regency dropdown options
  - Parameters: `($options, $province_id)`
  - Return: `array`
  - Location: Form templates or AJAX handlers

**Implementation Plan**:

**Phase 1: Permission Filters**
- [ ] Implement `wp_agency_can_create_employee` in AgencyEmployeeController
  - Add filter before validation in create() method
  - Default: check current capability, allow override
  - Return false to prevent creation

- [ ] Implement `wp_agency_can_create_division` in DivisionController
  - Add filter before validation in create() method
  - Default: check current capability, allow override

- [ ] Document `wp_agency_max_inspector_assignments` for future use
  - Skip implementation (feature not yet built)

**Phase 2: UI/UX Filters**
- [ ] Implement `wp_agency_enable_export` in DataTable templates
  - Add filter check before rendering export button
  - Default: true (enabled)
  - Hide button if filter returns false

- [ ] Implement `wp_company_detail_tabs` in company detail template
  - Add filter to tabs array before rendering
  - Allow adding/removing/reordering tabs
  - Default: existing tabs structure

**Phase 3: System Filters**
- [ ] Implement `wp_agency_debug_mode` globally
  - Add filter in error_log calls or Logger class
  - Default: false (production mode)
  - Enable verbose logging when true

**Phase 4: External Integration Filters**
- [ ] Implement wilayah filters in form rendering
  - Add filters when building province/regency dropdowns
  - Allow external plugins to modify options
  - Maintain compatibility with wilayah-indonesia plugin

**Implementation Example**:
```php
// In AgencyEmployeeController::create()
$can_create = current_user_can('add_agency_employee');
$can_create = apply_filters('wp_agency_can_create_employee', $can_create, $agency_id, $division_id, $user_id);

if (!$can_create) {
    wp_send_json_error(['message' => 'Permission denied by custom filter']);
    return;
}
```

**Files to Modify**:
- `/src/Controllers/Employee/AgencyEmployeeController.php` (permission filter)
- `/src/Controllers/Division/DivisionController.php` (permission filter)
- `/src/Views/templates/agency-list.php` (export filter)
- `/src/Views/templates/division-list.php` (export filter)
- `/src/Views/templates/employee-list.php` (export filter)
- `/src/Views/templates/company-detail.php` (tabs filter)
- Form templates with wilayah dropdowns (integration filters)
- Logger or debug utility class (debug mode filter)

**Success Criteria**:
- ✅ All 8 filter hooks implemented in code
- ✅ Filters applied at correct locations
- ✅ Default behavior preserved (backward compatible)
- ✅ Filter parameters match documentation
- ✅ Examples created for each filter
- ✅ Updated hooks documentation with implementation notes

**Testing Plan**:
```php
// Test permission filter
add_filter('wp_agency_can_create_employee', function($can, $agency_id, $division_id, $user_id) {
    // Block creation outside business hours
    return $can && (current_time('H') >= 8 && current_time('H') <= 17);
}, 10, 4);

// Test UI filter
add_filter('wp_agency_enable_export', '__return_false'); // Disable export

// Test debug mode
add_filter('wp_agency_debug_mode', '__return_true'); // Enable debug logs
```

**Benefits**:
- ✅ Complete hook system (9 actions + 8 filters = 17 hooks)
- ✅ External extensibility via filters
- ✅ Custom business logic without core modifications
- ✅ Consistent with WordPress hook standards
- ✅ Developer-friendly with comprehensive documentation

**Documentation Reference**:
- `/docs/hooks/README.md` - Main hooks documentation
- `/docs/hooks/filters/permission-filters.md` - Permission filters
- `/docs/hooks/filters/ui-filters.md` - UI/UX filters
- `/docs/hooks/filters/system-filters.md` - System filters
- `/docs/hooks/examples/` - Real-world examples

**Notes**:
- Action hooks (9) already implemented in TODO-2066 ✅
- Filter hooks (8) documented but need implementation ⏳
- Some filters (like inspector assignments) are for future features
- Maintain backward compatibility (filters should enhance, not break)

---

## TODO-2070: Employee Generator Runtime Flow Migration ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-01-22
**Completed**: 2025-01-22
**Dependencies**: TODO-2067 (Agency Runtime Flow) ✅, TODO-2069 (Division Runtime Flow) ✅, wp-customer TODO-2170 (Employee Runtime Flow) ✅
**Priority**: HIGH
**Complexity**: Medium (refactoring demo generator to use production code)

**Summary**: Migrated Employee demo data generation from bulk generation to runtime flow pattern following wp-customer Employee pattern. Removed demo code from production files and implemented full validation + hooks.

**Results**:
- **Total Employees**: 87 (target: 90, gap: 3 due to missing division in Agency 15)
  - 29 admin employees PRESERVED (from wp_agency_division_created hook)
  - 58 staff employees CREATED (from AgencyEmployeeUsersData, ID 170-229)
- ✅ Zero production pollution (removed `createDemoEmployee()` from AgencyEmployeeController)
- ✅ Full validation via AgencyEmployeeValidator (no bypasses)
- ✅ Hook `wp_agency_employee_created` registered and firing
- ✅ Dynamic division mapping handles varying IDs
- ✅ WordPress cache properly cleared after user ID changes

**Implementation Complete**:
- ✅ Remove ALL demo code from production files
- ✅ Create user via WPUserGenerator (static ID 170-229)
- ✅ Build dynamic division mapping (index → actual ID)
- ✅ Use AgencyEmployeeValidator for validation
- ✅ Trigger wp_agency_employee_created hook
- ✅ Preserve 29 admin employees from division hook

**Pattern Consistency**:
- ✅ Agency: User first → Validator → Model → Hook
- ✅ Division: User first → Validator → Model → Hook
- ✅ Customer (wp-customer): User first → Validator → Model → Hook
- ✅ **Employee**: User first → Validator → Model → Hook

**Files Modified**:
- ✅ `/src/Controllers/Employee/AgencyEmployeeController.php` (removed createDemoEmployee)
- ✅ `/src/Database/Demo/AgencyEmployeeDemoData.php` (runtime flow + mapping)
- ✅ `/src/Models/Employee/AgencyEmployeeModel.php` (hook trigger)
- ✅ `/src/Validators/Employee/AgencyEmployeeValidator.php` (enhanced email validation)
- ✅ `/src/Database/Demo/WPUserGenerator.php` (cache clearing)
- ✅ `/src/Database/Demo/Data/AgencyEmployeeUsersData.php` (fixed duplicates)
- ✅ `/wp-agency.php` (registered wp_agency_employee_created hook)

**Issues Fixed**:
1. Duplicate usernames - renamed 20 users by swapping name order
2. Validation rejection - enhanced validator to allow existing WP users
3. WordPress cache stale data - added comprehensive cache clearing

**Reference**: `/TODO/TODO-2070-employee-runtime-flow.md` (detailed completion summary)

---

## TODO-2069: Division Generator Runtime Flow Migration 🔄 IN PROGRESS

**Status**: 🔄 IN PROGRESS
**Created**: 2025-01-22
**Dependencies**: TODO-2067 (Agency Runtime Flow), TODO-2068 (Division User Auto-Creation), wp-customer TODO-2167 (Branch Runtime Flow)
**Priority**: HIGH
**Complexity**: Medium (refactoring demo generator to use production code)

**Summary**: Migrate Division demo data generation from bulk generation to runtime flow pattern following wp-customer Branch pattern. Remove demo code from production files and use full validation + hooks.

**Problem**:
- Production code pollution (`createDemoDivision()` in DivisionController) ❌
- Bulk insert bypasses validation & hooks ❌
- Inconsistent with Agency & Branch patterns ❌
- Manual employee creation (no hook) ❌

**Solution (Runtime Flow)**:
- ✅ Remove ALL demo code from production files
- ⏳ Create user via WPUserGenerator (static ID)
- ⏳ Use DivisionController->create() via runtime flow
- ⏳ Hook auto-creates employee (wp_agency_division_created)
- ⏳ Cleanup via Model delete (cascade)

**Implementation Plan**:
```
DivisionDemoData::generate()
  → Step 1: WPUserGenerator->generateUser() (static ID)
  → Step 2: createDivisionViaRuntimeFlow()
    → Step 3: Validate via DivisionValidator
    → Step 4: Create via DivisionModel->create()
      → Step 5: Hook wp_agency_division_created fires
        → Step 6: AutoEntityCreator->handleDivisionCreated()
          → Step 7: Employee auto-created
```

**Files to Modify**:
- `/src/Controllers/Division/DivisionController.php` (remove createDemoDivision)
- `/src/Database/Demo/DivisionDemoData.php` (add runtime flow method)

**Pattern Consistency**:
- ✅ Agency: User first → Controller → Hook creates division+employee
- ⏳ **Division**: User first → Controller → Hook creates employee
- ✅ Branch (wp-customer): User first → Controller → Hook creates employee

**Progress**: Step 1/9 - Created TODO file

**Reference**: `/TODO/TODO-2069-division-runtime-flow.md`

---

## TODO-2067: Agency Generator Runtime Flow Migration 🚧 IN PROGRESS

**Status**: 🚧 IN PROGRESS
**Created**: 2025-01-22
**Dependencies**: Task-2066 (HOOK system), wp-customer TODO-2168, TODO-2167
**Priority**: HIGH
**Complexity**: Medium-High (refactoring demo generator to use production code)

**Summary**: Migrate demo data generation from bulk generation approach to runtime flow pattern following wp-customer. Transform demo generator from simple data creation tool into automated testing tool for production code.

**Problem**:
- Production code pollution (demo methods in Controller/Model)
- Validation bypass (no AgencyValidator usage)
- HOOK system untested (auto-create not triggered)
- Manual user creation (direct DB INSERT vs wp_insert_user)

**Solution (Phase 1: Agency Only)**:
- ✅ Delete demo methods from production code
- ⏳ Update WPUserGenerator to use wp_insert_user()
- ⏳ Create runtime flow method in AgencyDemoData
- ⏳ Test full HOOK chain (agency → division → employee)
- ⏳ Implement HOOK-based cleanup

**Implementation Plan**:
```
AgencyDemoData::generate()
  → 1. Create user via wp_insert_user()
  → 2. Update ID to static value (FOREIGN_KEY_CHECKS=0)
  → 3. Validate via AgencyValidator::validateForm()
  → 4. Create via AgencyModel::create()
    → HOOK: wp_agency_agency_created
      → Division pusat auto-created
        → HOOK: wp_agency_division_created
          → Employee auto-created
```

**Files to Modify**:
- `/src/Controllers/AgencyController.php` (DELETE createDemoAgency method)
- `/src/Database/Demo/WPUserGenerator.php` (use wp_insert_user)
- `/src/Database/Demo/AgencyDemoData.php` (runtime flow methods)

**Success Criteria**:
- ✅ Zero demo code in production namespace
- ✅ Full validation via AgencyValidator
- ✅ User creation via wp_insert_user() with static ID
- ✅ HOOK cascade fully tested
- ✅ Cleanup via Model with cascade delete

**Reference**: `/TODO/TODO-2067-agency-generator-runtime-flow.md`

---

## TODO-2066: Auto Entity Creation & Lifecycle Hooks ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-01-22
**Completed**: 2025-01-22
**Dependencies**: wp-customer (reference pattern), wp-customer TODO-2169 (naming convention)
**Priority**: High
**Complexity**: Medium (hook implementation + handler + delete hooks)

**Summary**: Implementasi complete hook system untuk entity lifecycle di wp-agency mengikuti pattern wp-customer dengan naming convention yang benar (`wp_{plugin}_{entity}_{action}`). Includes creation hooks for auto entity creation AND deletion hooks for cascade cleanup.

**Problem**:
- Manual entity creation required after agency/division creation
- No lifecycle hooks for deletion (cascade cleanup, external sync)
- Inconsistent data structure across agencies
- No soft delete support

**Solution:**

**Creation Hooks:**
- ✅ Added `wp_agency_agency_created` hook in AgencyModel (fixed naming)
- ✅ Added `wp_agency_division_created` hook in DivisionModel
- ✅ Created AutoEntityCreator handler class
- ✅ Registered creation hooks in main plugin file
- ✅ Added findByUserAndDivision() method in AgencyEmployeeModel

**Deletion Hooks:**
- ✅ Added `wp_agency_agency_before_delete` hook in AgencyModel
- ✅ Added `wp_agency_agency_deleted` hook in AgencyModel
- ✅ Added `wp_agency_division_before_delete` hook in DivisionModel
- ✅ Added `wp_agency_division_deleted` hook in DivisionModel
- ✅ Implemented soft delete support (status='inactive')
- ✅ Implemented hard delete option (via settings)

**Hook Flow**:
```
Creation:
Agency Created → wp_agency_agency_created hook fires
               → AutoEntityCreator::handleAgencyCreated()
               → Division Pusat auto-created
               → wp_agency_division_created hook fires
               → AutoEntityCreator::handleDivisionCreated()
               → Employee auto-created

Deletion:
Agency Delete → wp_agency_agency_before_delete (validation)
              → Soft/Hard delete based on settings
              → wp_agency_agency_deleted (cascade cleanup)
```

**Files Created**:
- `/src/Handlers/AutoEntityCreator.php` - Main handler class

**Files Modified**:
- `/src/Models/Agency/AgencyModel.php` (v2.0.0 → v2.1.0)
- `/src/Models/Division/DivisionModel.php` (v1.0.0 → v1.1.0)
- `/src/Models/Employee/AgencyEmployeeModel.php` (v1.0.0 → v1.1.0)
- `/wp-agency.php` (v1.0.0 → v1.1.0)
- `/TODO/TODO-2066-auto-entity-creation.md` - Complete documentation

**Hooks Implemented:**
- **2 Creation hooks** (agency_created, division_created)
- **4 Deletion hooks** (2x before_delete, 2x deleted)
- **Total: 6 lifecycle hooks**

**Features**:
- ✅ Automatic division pusat creation when agency created
- ✅ Automatic employee creation when division created
- ✅ Soft delete support (status='inactive', data recoverable)
- ✅ Hard delete option (actual DELETE from database)
- ✅ Before delete hooks for validation/prevention
- ✅ After delete hooks for cascade cleanup
- ✅ Duplicate prevention (checks before creating)
- ✅ Comprehensive error handling and logging
- ✅ Cache-aware implementation
- ✅ Follows wp-customer pattern with correct naming convention

**Naming Convention**: `wp_{plugin}_{entity}_{action}`
- Entity name ALWAYS explicit (wp_agency_**agency**_created)
- Consistent with wp-customer TODO-2169 standard
- Scalable and predictable

**Benefits**:
- ✅ Automation reduces manual work
- ✅ Consistent data structure across agencies
- ✅ Extensible via WordPress hook system
- ✅ Cascade cleanup for data integrity
- ✅ Soft delete for data recovery
- ✅ Easy to debug with detailed logging

**Reference**: `/TODO/TODO-2066-auto-entity-creation.md`

---
