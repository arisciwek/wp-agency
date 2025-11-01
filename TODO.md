# TODO List for WP Agency Plugin


## TODO-3098: Add User Static ID Hook ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-11-01)
**Priority**: HIGH

Add filter hooks sebelum wp_insert_user() di production code untuk allow static WordPress user ID injection. Enables demo data generation dengan predictable IDs, consistent dengan wp-customer TODO-2185. Implemented 10 hooks total (6 entity + 4 user) across wp-customer dan wp-agency.

**Hooks Implemented**:
- WordPress User: `wp_agency_agency_user_before_insert`, `wp_agency_employee_user_before_insert`
- Entity: `wp_agency_before_insert`, `wp_agency_division_before_insert`, `wp_agency_employee_before_insert`

**Changes**:
- AgencyController v1.0.8 - Added user static ID hook (lines 708-795)
- AgencyEmployeeController v1.0.8 - Added user static ID hook (lines 416-502)
- AgencyModel v1.0.11 - Added entity static ID hook
- DivisionModel v1.1.1 - Added entity static ID hook
- AgencyEmployeeModel v1.4.1 - Added entity static ID hook

**Testing**: test-user-static-id-hook.php, test-entity-static-id-hook.php, test-agency-static-id.php

**Related**: wp-customer TODO-2185, wp-app-core TODO-1190

---

## TODO-3091: Fix Race Condition Permission Matrix ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-10-29)
**Priority**: HIGH (CRITICAL BUG FIX)

Critical race condition vulnerability antara tombol "Reset to Default" dan "Save Permission Changes". User bisa trigger kedua operasi bersamaan → data corruption. Solution: Page-level locking dengan cross-disable buttons, disable checkboxes, immediate reload (no 1.5s delay window).

**Changes**: agency-permissions-tab-script.js v1.0.2 - Added lockPage/unlockPage methods, cross-disable protection

**Related**: TODO-2182 (same fix for wp-customer)

---

## TODO-3090: Improve Permission Matrix Display ✅ COMPLETED

**Status**: ✅ COMPLETED (Phase 1 & Review-01) (2025-10-29)
**Priority**: MEDIUM

Merubah pola matriks permission agar sama dengan wp-app-core: hanya tampilkan agency roles (bukan semua WordPress roles), tambah visual indicator (dashicons-building), improve section styling dengan header/reset/matrix sections. Review-01: Refactor PermissionModel.php untuk clean pattern, remove scope violation (wp_customer tab).

**Changes**:
- tab-permissions.php v1.1.0 - Filter only agency roles, add sections, skip base role 'agency'
- PermissionModel v1.1.0 - Added getDefaultCapabilitiesForRole(), refactored addCapabilities/resetToDefault

---

## TODO-3089: Simplify Architecture - Remove TabViewTemplate ✅ COMPLETED

**Status**: ✅ COMPLETED (All Phases: 1, 2, Hotfix, CSS Fix, StatsBox Fix, Phase 3) (2025-10-29)
**Priority**: HIGH

Architectural decision untuk remove TabViewTemplate class dari wp-app-core karena over-engineering. User insight: "no active users + still development" = bisa breaking changes untuk better architecture. Removed 1,158+ lines total.

**Phases**:
- Phase 1: Cleanup wp-agency (removed 'template' key)
- Phase 2: Delete TabViewTemplate + NavigationTemplate
- Hotfix: TabSystemTemplate support 2 patterns
- CSS Fix: Add wpapp- prefix to statistics classes
- StatsBox Fix: Remove filter-based rendering (dual mechanism)
- Phase 3: Update documentation (TODO-1188, TODO-1186 marked OBSOLETE)

**Philosophy**: "Simple > Abstraction when no active users exist"

---

## TODO-3088: Clarify Template Key Usage ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-10-29)
**Priority**: LOW (Documentation only)

Klarifikasi bahwa 'template' key di register_tabs() adalah untuk konvensi/backward compatibility saja, TIDAK digunakan oleh render_tab_contents(). User confused kenapa info.php "dipanggil 2 kali" - ternyata cuma metadata, actual inclusion hanya via hook.

**Changes**: AgencyDashboardController docblock - Added NOTE explaining template key not used

---

## TODO-3087: Eliminate Duplicate Hooks ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-10-29)
**Priority**: HIGH

Menghapus duplikasi hook `wpapp_tab_view_after_content` yang ada di TabViewTemplate (wp-app-core) dan AgencyDashboardController. User request: "Kembalikan sebelum revisi terakhir" - simple solution dengan hapus duplikasi dari controller karena TabViewTemplate sudah provide.

**Changes**:
- AgencyDashboardController v1.3.0 - Removed duplicate hook
- TabViewTemplate v1.2.0 - Removed (not used by implementations)

---

## TODO-3086: Hook Separation Pattern ✅ COMPLETED

**Status**: ✅ COMPLETED (2025-10-29)
**Priority**: HIGH

Added `wpapp_tab_view_after_content` hook untuk memisahkan core content rendering dari extension content injection. Mengatasi masalah duplikasi konten dari wp-customer statistics yang muncul dua kali karena single hook digunakan untuk dua tujuan berbeda.

**Changes**: AgencyDashboardController v1.1.0 - Added extension hook with per-tab hook registration pattern

---

## TODO-3084: TabViewTemplate Architecture - Hook-Based Extensibility ✅ COMPLETED

**Status**: ✅ COMPLETED (All 4 Phases Done)
**Created**: 2025-10-28
**Last Updated**: 2025-10-28
**Priority**: HIGH
**Category**: Architecture, Extensibility, Cross-Plugin Integration
**Related**: task-3084.md, wp-app-core TODO-1186

**Summary**: Evolution of tab system architecture to achieve extensibility while maintaining clean MVC View pattern. Final goal: Allow cross-plugin content injection (e.g., wp-customer can add stats to agency tabs).

**Problem Evolution**:
- **Initial**: Mixed OLD/NEW patterns
- **Review-01**: Empty tabs after removing OLD hooks
- **Review-02**: Too complex (2 files per tab, controller logic in views)
- **Review-03**: ❌ No extensibility - other plugins can't inject content!

**Solution (4 Phases)**:

**Phase 1: Migration (v2.0.0)** ✅ COMPLETED
- ✅ Migrated to TabViewTemplate pattern
- ✅ Removed OLD PATTERN hooks and methods
- ❌ Still complex (wrapper + partial files)

**Phase 2: Review-01 Fix** ✅ COMPLETED
- ❌ **Issue**: Tabs rendered empty
- ✅ **Fix**: Updated render_tab_contents() for direct template inclusion
- ✅ **Result**: Tabs render correctly

**Phase 3: Review-02 Simplification (v3.0.0 - Option A)** ✅ COMPLETED
- ✅ Merged 4 tab files with their partials (info+details → details.php)
- ✅ Removed hook registration (`wpapp_tab_view_content`)
- ✅ Removed 5 tab-related controller methods (~102 lines)
- ✅ Deleted /tabs/partials/ folder (4 files)
- ✅ Kept `render_partial()` for non-tab partials (headers, stats, ajax-datatables)
- ✅ Pure HTML templates (no controller logic)
- ✅ True MVC View pattern
- ❌ **Side Effect**: Lost extensibility - no hook points!

**Phase 4: Review-03 Extensibility Restoration (v4.0.0)** ✅ COMPLETED
- ✅ **Goal**: Restore hook-based extensibility WITHOUT losing pure view benefits
- ✅ **Strategy**: Hook-Based Content Injection Pattern
- ✅ Restored `render_tab_view_content()` method in controller (58 lines)
- ✅ Restored hook registration `wpapp_tab_view_content`
- ✅ Updated `render_tab_contents()` to trigger hook (67 lines)
- ✅ Kept view files as Pure HTML (NO changes to view files!)
- ✅ Enabled cross-plugin integration (wp-customer, etc.)

**Achieved Architecture (Review-03)**:
```
TabViewTemplate → do_action('wpapp_tab_view_content', $entity, $tab_id, $data)
  ├─> [Priority 10] wp-agency responds → include details.php (Pure HTML)
  ├─> [Priority 20] wp-customer responds → inject customer stats
  └─> [Priority 30+] Other plugins can inject content
```

**Files Modified (Review-03)** ✅ COMPLETED:
- ✅ AgencyDashboardController.php:
  - Added hook registration line (line 111)
  - Restored render_tab_view_content() method (58 lines, lines 346-404)
  - Updated render_tab_contents() to trigger hook (67 lines, lines 789-855)
  - Total: ~125 lines added/modified
- ✅ tabs/details.php → NO CHANGES (kept pure HTML)
- ✅ tabs/divisions.php → NO CHANGES (kept pure HTML)
- ✅ tabs/employees.php → NO CHANGES (kept pure HTML)

**Final Metrics (Review-03)**:
- Files: 4 tabs (pure HTML) + 1 controller
- Lines added: ~125
- Controller methods: 1 method restored (render_tab_view_content)
- Hooks registered: 1 (wpapp_tab_view_content) ✅ WORKING
- Extensibility: ✅ Full cross-plugin support ACHIEVED
- View file changes: 0 (pure view pattern maintained!)

**Benefits Achieved (Review-03)**:
- ✅ Keep pure view pattern (no changes to view files)
- ✅ Enable cross-plugin content injection
- ✅ wp-customer can add customer stats to agency tabs
- ✅ Priority-based content ordering
- ✅ WordPress standard hook pattern
- ✅ No breaking changes to existing functionality

**See**:
- Main doc: `TODO/TODO-3084-tabview-template-migration.md`
- Review-03: `TODO/TODO-3084-Review-03-restore-extensibility.md` (FULL IMPLEMENTATION PLAN)

---

## TODO-3083: Remove Inline JavaScript from PHP Templates ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: HIGH
**Category**: Code Quality, Separation of Concerns, Best Practices
**Dependencies**: TODO-3082, wp-app-core TODO-1185
**Related**: task-1185.md Review-01

**Summary**: Remove ALL inline `<script>` tags and CSS from PHP template files. Implement event-driven DataTable initialization pattern using data-* attributes and MutationObserver for automatic detection of lazy-loaded tables.

**User Requirement (Review-01)**: "saya tidak mau ada CSS, JS, di kode php. kita pindahkan ke agency-datatable.js"

**Problem**: Inline JavaScript in templates
- ajax-divisions-datatable.php: 26 lines inline `<script>`
- ajax-employees-datatable.php: 27 lines inline `<script>`
- Violates separation of concerns
- Not CSP-compliant
- Difficult to maintain

**Solution**: Event-Driven DataTable Pattern

**Architecture**:
```
PHP Template (Pure HTML):
  <table class="agency-lazy-datatable"
         data-entity="division"
         data-agency-id="1"
         data-ajax-action="get_divisions_datatable">
  </table>
         ↓ (MutationObserver detects)
agency-datatable.js:
  - watchForLazyTables() → Automatic detection
  - initLazyDataTables() → Reads data-*, initializes
  - getLazyTableColumns() → Entity-specific config
```

**Files Modified**:

1. **ajax-divisions-datatable.php** (v1.0.0 → v1.1.0)
   - REMOVED: 26 lines inline `<script>` tag
   - ADDED: data-* attributes (entity, agency-id, ajax-action)
   - Result: Pure HTML template (67 lines, -15%)

2. **ajax-employees-datatable.php** (v1.0.0 → v1.1.0)
   - REMOVED: 27 lines inline `<script>` tag
   - ADDED: data-* attributes (entity, agency-id, ajax-action)
   - Result: Pure HTML template (67 lines, -17%)

3. **agency-datatable.js** (v2.0.0 → v2.1.0)
   - ADDED: watchForLazyTables() method (43 lines)
     - MutationObserver for automatic table detection
   - ADDED: initLazyDataTables($container) method (62 lines)
     - Reads data-* attributes for configuration
     - Initializes DataTable with proper AJAX settings
   - ADDED: getLazyTableColumns(entity) method (23 lines)
     - Entity-specific column configuration
   - Total: +128 lines centralized logic

**Benefits Achieved**:
- ✅ 100% Separation: PHP=HTML, JS=External files
- ✅ CSP Compliant: No inline scripts
- ✅ Maintainable: Single source of truth (agency-datatable.js)
- ✅ Reusable: Pattern works for any lazy-load table
- ✅ Automatic: MutationObserver detects tables in DOM
- ✅ Testable: JavaScript methods can be unit tested
- ✅ Clean Templates: View source shows pure HTML

**Code Quality Metrics**:
- Inline JS in templates: 53 lines → 0 lines (-100%) ✅
- Template size: -26 lines total
- JavaScript centralized: +128 lines in single file
- Separation of concerns: 100% achieved ✅

**Testing Checklist**:
- ✅ Divisions tab loads DataTable automatically
- ✅ Employees tab loads DataTable automatically
- ✅ No inline scripts in view source
- ✅ Console shows proper initialization logs
- ✅ No JavaScript errors
- ✅ Cache cleared and tested

**Pattern Established**: Configuration via Data Attributes + MutationObserver
- Can be applied to: forms, modals, widgets, any lazy-loaded component

See: [TODO/TODO-3083-remove-inline-js-from-templates.md](TODO/TODO-3083-remove-inline-js-from-templates.md)

---

## TODO-3082: Template Separation Refactoring - Complete Architecture Cleanup ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: HIGH
**Category**: Architecture, Code Quality, Best Practices
**Dependencies**: TODO-3081, wp-app-core TODO-1186

**Summary**: Complete refactoring untuk memisahkan SEMUA HTML dari Controller ke template files terpisah. Menerapkan naming convention `{context}-{identifier}.php` untuk semua partial templates. **Controller = Logic Only, Templates = Presentation Only**.

**Problem**: Controller dengan 460+ lines HTML mixed dengan business logic (maintenance nightmare, not designer-friendly, violates SRP).

**Solution**: Template Separation dengan Naming Convention

**Naming Convention Pattern**:
```
Format: {context}-{identifier}[-{subtype}].php

Contexts:
- stat-       : Statistics/cards
- header-     : Header components
- tab-        : Tab content
- ajax-       : AJAX responses
- filter-     : Filters (future)
- form-       : Forms (future)
```

**Templates Created (9 Files)**:

**General Partials** (`partials/`):
1. `stat-cards.php` (62 lines) - Statistics cards
2. `header-title.php` (32 lines) - Page title & subtitle
3. `header-buttons.php` (47 lines) - Action buttons
4. `ajax-divisions-datatable.php` (77 lines) - Divisions DataTable
5. `ajax-employees-datatable.php` (79 lines) - Employees DataTable

**Tab Partials** (`tabs/partials/`):
6. `tab-info-content.php` (109 lines) - Info tab HTML
7. `tab-details-content.php` (153 lines) - Details tab HTML
8. `tab-divisions-content.php` (55 lines) - Divisions placeholder
9. `tab-employees-content.php` (55 lines) - Employees placeholder

**Helper Method Created**:
```php
private function render_partial($partial, $data = [], $context = 'tab'): void {
    // Extracts variables, determines path, includes template
}
```

**Controller Methods Refactored (9 Methods)**:

| Method | Before | After | Lines Removed |
|--------|--------|-------|---------------|
| render_header_title() | 9 lines | 1 line | -8 |
| render_header_buttons() | 33 lines | 1 line | -32 |
| render_header_cards() | 52 lines | 3 lines | -49 |
| render_info_content() | 75 lines | 1 line | -74 |
| render_details_content() | 125 lines | 1 line | -124 |
| render_divisions_content() | 35 lines | 2 lines | -33 |
| render_employees_content() | 35 lines | 2 lines | -33 |
| handle_load_divisions_tab() | 47 lines | 3 lines | -44 |
| handle_load_employees_tab() | 49 lines | 3 lines | -46 |
| **Total** | **460 lines** | **17 lines** | **-443 lines** |

**Code Quality Metrics**:
- Controller size: 1400 → 960 lines (**-31%**)
- HTML in Controller: 460 → **0 lines** (**-100%**)
- Separation of concerns: Mixed → **100% separated** ✅
- Maintainability: Difficult → **Easy** ✅
- Testability: Hard → **Simple** ✅

**Architecture Benefits**:
1. ✅ **Controller = Pure Logic**: No HTML, only business logic
2. ✅ **Templates = Pure Presentation**: Clean HTML, minimal PHP
3. ✅ **DRY Principle**: Reusable `render_partial()` helper
4. ✅ **Designer-Friendly**: Edit HTML without touching Controller
5. ✅ **Testable**: Clear separation, easy to mock

**Integration with wp-app-core**:
- Uses `TabViewTemplate` for tab system (TODO-1186)
- Tab files minimal (call `TabViewTemplate::render()`)
- Content rendered via `wpapp_tab_view_content` hook
- Controller handles hook, routes to template files

**Search-Friendly Organization**:
```bash
find . -name "stat-*.php"      # All statistics templates
find . -name "header-*.php"    # All header templates
find . -name "tab-*.php"       # All tab templates
find . -name "ajax-*.php"      # All AJAX templates
```

**Files Modified**:
- `src/Controllers/Agency/AgencyDashboardController.php` (9 methods refactored)
- `src/Views/agency/tabs/info.php` (v1.1.0 → v2.0.0)
- `src/Views/agency/tabs/details.php` (v1.1.0 → v2.0.0)

**Files Created**:
- 9 template files (669 total lines)
- `/TODO/TODO-3082-template-separation-refactoring.md` (785 lines)

**Impact**:
- Code Quality: **Enterprise-grade** ✅
- Maintainability: **Significantly improved** ✅
- Developer Experience: **Easier to understand & modify** ✅
- Designer-Friendly: **Can edit templates directly** ✅

See: [TODO/TODO-3082-template-separation-refactoring.md](TODO/TODO-3082-template-separation-refactoring.md)

---

## TODO-3081: Scope Separation Phase 2 - Right Panel Tabs ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-10-27
**Completed**: 2025-10-27
**Priority**: HIGH
**Category**: Architecture, Code Quality
**Dependencies**: TODO-3080 (Phase 1)

**Summary**: Phase 2 scope separation untuk right panel tabs. Refactor info.php dan details.php untuk menggunakan LOCAL scope (agency-*) instead of GLOBAL scope (wpapp-*). Remove inline CSS dan move ke external file.

**Audit Results**:

| File | Lines | Status | wpapp-* Usage | Issues |
|------|-------|--------|---------------|--------|
| divisions.php | 57 | ✅ CLEAN | Structure only | Phase 1 clean |
| employees.php | 57 | ✅ CLEAN | Structure only | Phase 1 clean |
| **info.php** | 102 | ❌ MIXED | 8 classes | Content using wpapp-* |
| **details.php** | 215 | ❌❌ VIOLATIONS | 7 classes + 52 lines inline CSS | Double violation |

**Problems Solved**:

**Problem 1: info.php - Mixed Scope (8 classes)** ✅
- Changed: `wpapp-info-*` → `agency-info-*`
- Kept: `wpapp-badge` (global component)
- Result: 100% local scope for content

**Problem 2: details.php - Double Violation** ✅
- Changed: `wpapp-detail-*` → `agency-detail-*` (7 classes)
- Removed: 52 lines inline `<style>` tag
- Moved: All styles to agency-detail.css
- Result: Clean HTML, external CSS

**Changes Implemented**:

**1. Refactored info.php** (v1.0.0 → v1.1.0):
```
Classes Changed (6):
- wpapp-info-container → agency-info-container
- wpapp-info-section → agency-info-section
- wpapp-info-grid → agency-info-grid
- wpapp-info-item → agency-info-item
- wpapp-info-label → agency-info-label
- wpapp-info-value → agency-info-value
```

**2. Refactored details.php** (v1.0.0 → v1.1.0):
```
Classes Changed (5):
- wpapp-details-grid → agency-details-grid
- wpapp-detail-section → agency-detail-section
- wpapp-detail-row → agency-detail-row
- wpapp-no-data → agency-no-data

Inline CSS Removed:
- Before: 215 lines (52 inline CSS)
- After: 169 lines (pure HTML)
- Reduction: -21%
```

**3. Created agency-detail.css** (NEW):
- File: `assets/css/agency/agency-detail.css`
- Lines: 219 (organized, responsive)
- Scope: Strict agency-* prefix
- Features: Grid layouts, responsive (3 breakpoints), clean styling

**4. Enqueued agency-detail.css**:
- File: `includes/class-dependencies.php` (lines 237-240)
- Order: After agency-style.css, before division-style.css

**Code Quality Metrics**:
- Mixed scopes in right panel: 15 classes → 0 classes (100% clean) ✅
- Inline CSS: 52 lines → 0 lines (100% removed) ✅
- details.php file size: 215 lines → 169 lines (-21%) ✅
- Separation of concerns: Poor → Excellent ✅

**Files Modified**:
1. `src/Views/agency/tabs/info.php` (v1.1.0) - 6 classes changed
2. `src/Views/agency/tabs/details.php` (v1.1.0) - 5 classes changed, inline CSS removed
3. `assets/css/agency/agency-detail.css` (NEW) - 219 lines
4. `includes/class-dependencies.php` - Enqueued new CSS

**Architecture Principles**:
- ✅ Strict scope separation (wpapp-* vs agency-*)
- ✅ Separation of concerns (PHP = HTML, CSS = external)
- ✅ Maintainability (single source of truth)
- ✅ Performance (cacheable, minifiable)
- ✅ Testability (clear boundaries)

**Benefits**:
- ✅ 100% scope separation in right panel
- ✅ No inline CSS (all externalized)
- ✅ Cacheable assets (better performance)
- ✅ Maintainable (organized by entity)
- ✅ Responsive (mobile-friendly)
- ✅ Testable (clear class ownership)

See: [TODO/TODO-3081-scope-separation-phase2-right-panel.md](TODO/TODO-3081-scope-separation-phase2-right-panel.md)

---

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
