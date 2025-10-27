# TODO-3077: Move Inline JavaScript to Separate File

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement
Ada inline JavaScript di template PHP `/wp-agency/src/Views/DataTable/Templates/datatable.php` yang tidak sesuai best practice.

**User Request:**
> "ada script JS di datatable.php saya tidak suka, apakah cocok jika di tempatkan di /wp-agency/assets/js/agency/agency-datatable.js?"

**Answer:** Sangat cocok! Best practice adalah separation of concerns.

## Problem

### Before (Bad Practice):
```php
<!-- datatable.php -->
<table id="agency-list-table">...</table>
</div>

<script>
jQuery(document).ready(function($) {
    // 100+ lines of inline JavaScript
    var agencyTable = $('#agency-list-table').DataTable({...});
});
</script>
```

**Issues:**
1. ❌ **Mixed concerns** - PHP template contains JavaScript
2. ❌ **Not cacheable** - Inline script generated on every page load
3. ❌ **Not minifiable** - Can't optimize for production
4. ❌ **Hard to maintain** - JavaScript scattered in PHP files
5. ❌ **Translation issues** - PHP translations in JavaScript strings
6. ❌ **No reusability** - Can't reuse script for multiple tables

## Solution: Separate JavaScript File

### After (Best Practice):
```php
<!-- datatable.php - HTML ONLY -->
<table id="agency-list-table">...</table>
</div>
<!-- JavaScript handled by agency-datatable.js -->
```

```javascript
// agency-datatable.js - JAVASCRIPT ONLY
(function($) {
    const AgencyDataTable = {
        init() { ... },
        initDataTable() { ... }
    };

    $(document).ready(function() {
        AgencyDataTable.init();
    });
})(jQuery);
```

**Benefits:**
1. ✅ **Separation of concerns** - PHP template = HTML, JS file = JavaScript
2. ✅ **Cacheable** - Browser can cache .js file
3. ✅ **Minifiable** - Can be minified for production
4. ✅ **Maintainable** - All JavaScript in one place
5. ✅ **Localizable** - Translations via wp_localize_script
6. ✅ **Reusable** - Module pattern allows external access

## Changes Implemented

### 1. Backup Old File ✅
**Command:**
```bash
cp agency-datatable.js agency-datatable.js.backup
```

**Reason:** Old file had different implementation (different table ID, columns, action)

### 2. Create New agency-datatable.js ✅
**File**: `/wp-agency/assets/js/agency/agency-datatable.js`
**Version**: 2.0.0

**Key Features:**
- Module pattern with `AgencyDataTable` object
- Table ID: `#agency-list-table` (matches template)
- AJAX action: `get_agencies_datatable` (matches server-side)
- Columns: code, name, provinsi_name, regency_name, actions
- Integration: Base panel system via `wpapp:open-panel` event
- Localized: Expects `wpAgencyDataTable` object

**Structure:**
```javascript
const AgencyDataTable = {
    table: null,
    initialized: false,

    init() {
        // Initialize DataTable
    },

    initDataTable() {
        // Setup DataTable with server-side processing
    },

    bindEvents() {
        // Row click → open panel
        // Edit button
        // Delete button
    },

    refresh() {
        // Reload table data
    },

    destroy() {
        // Cleanup
    }
};
```

### 3. Remove Inline Script from datatable.php ✅
**File**: `/wp-agency/src/Views/DataTable/Templates/datatable.php`
**Version**: 1.0.2 → 1.0.3

**Before:** 100+ lines of inline `<script>` tag
**After:** Single HTML comment

```php
</table>
</div>
<!-- JavaScript handled by agency-datatable.js -->
```

**Changelog Added:**
```
* 1.0.3 - 2025-10-25
* - Removed inline JavaScript (moved to agency-datatable.js)
* - Template now contains HTML only (best practice)
* - Separation of concerns: PHP template vs JavaScript
```

### 4. Enqueue Script ✅ COMPLETED
**File**: `/wp-agency/includes/class-dependencies.php`
**Location**: Lines 359-382

**ADDED:**
```php
// Enqueue agency DataTable script (scope local)
wp_enqueue_script(
    'agency-datatable',
    \WP_AGENCY_URL . 'assets/js/agency/agency-datatable.js',
    ['jquery', 'jquery-datatables'],
    '2.0.0',
    true
);

// Localize script with translations and config
wp_localize_script('agency-datatable', 'wpAgencyDataTable', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('wpapp_panel_nonce'),
    'i18n' => [
        'processing' => __('Loading...', 'wp-agency'),
        'search' => __('Search:', 'wp-agency'),
        'lengthMenu' => __('Show _MENU_ entries', 'wp-agency'),
        'info' => __('Showing _START_ to _END_ of _TOTAL_ entries', 'wp-agency'),
        'infoEmpty' => __('Showing 0 to 0 of 0 entries', 'wp-agency'),
        'infoFiltered' => __('(filtered from _MAX_ total entries)', 'wp-agency'),
        'zeroRecords' => __('No matching records found', 'wp-agency'),
        'emptyTable' => __('No data available in table', 'wp-agency'),
        'confirmDelete' => __('Are you sure you want to delete this agency?', 'wp-agency'),
        'paginate' => [
            'first' => __('First', 'wp-agency'),
            'previous' => __('Previous', 'wp-agency'),
            'next' => __('Next', 'wp-agency'),
            'last' => __('Last', 'wp-agency')
        ]
    ]
]);
```

**Dependencies:**
- `jquery` - WordPress jQuery
- `jquery-datatables` - DataTables library (already enqueued)

**Load in footer:** `true` - Load after DOM ready

## File Comparison

### Old vs New Implementation

**Old (agency-datatable.js.backup):**
- Table ID: `#agencies-table` (plural, no -list)
- AJAX action: `handle_agency_datatable`
- Columns: code, name, owner_name, division_count, actions
- Pattern: Complex module with multiple features
- Status: Old implementation, different structure

**New (agency-datatable.js v2.0.0):**
- Table ID: `#agency-list-table` (singular + -list)
- AJAX action: `get_agencies_datatable`
- Columns: code, name, provinsi_name, regency_name, actions
- Pattern: Clean module integrated with base panel system
- Status: Current implementation, matches template

**Why Different?**
New implementation is from TODO-2071 base panel system integration.
Old file was from previous manual implementation.

## Translation Strategy

### Before (Inline Script):
```php
<script>
language: {
    processing: '<?php esc_html_e('Loading...', 'wp-agency'); ?>',
    search: '<?php esc_html_e('Search:', 'wp-agency'); ?>'
}
</script>
```

**Issues:**
- PHP mixed with JavaScript
- Generated on every page load
- Not cacheable

### After (Localized Script):
```php
// MenuManager.php
wp_localize_script('agency-datatable', 'wpAgencyDataTable', [
    'i18n' => [
        'processing' => __('Loading...', 'wp-agency'),
        'search' => __('Search:', 'wp-agency')
    ]
]);
```

```javascript
// agency-datatable.js
language: wpAgencyDataTable.i18n || {
    processing: 'Loading...',  // Fallback
    search: 'Search:'          // Fallback
}
```

**Benefits:**
- Clean separation
- Cacheable JavaScript
- Proper WordPress i18n
- Fallback values if localize fails

## Files Modified

### All Completed ✅
1. ✅ `/wp-agency/assets/js/agency/agency-datatable.js` - Rewritten (v2.0.0)
2. ✅ `/wp-agency/assets/js/agency/agency-datatable.js.backup` - Backup of old version
3. ✅ `/wp-agency/src/Views/DataTable/Templates/datatable.php` - Removed inline script (v1.0.3)
4. ✅ `/wp-agency/includes/class-dependencies.php` - Added enqueue + localize (lines 359-382)

## Testing Checklist

### Before Testing:
- [ ] Clear WordPress cache
- [ ] Clear browser cache
- [ ] Clear PHP opcache

### Test Enqueue (After adding to MenuManager):
- [ ] Visit wp-agency Disnaker menu
- [ ] Open DevTools → Network tab
- [ ] Verify `agency-datatable.js` loads (200 status)
- [ ] Check file size (should be ~8-10KB)
- [ ] Verify loaded from `/assets/js/agency/agency-datatable.js`

### Test Localization:
- [ ] Open DevTools → Console
- [ ] Type: `wpAgencyDataTable`
- [ ] Should see object with:
  - [ ] `ajaxurl` property
  - [ ] `nonce` property
  - [ ] `i18n` object with translations
- [ ] Verify no `undefined` errors

### Test Functionality:
- [ ] DataTable loads with data
- [ ] Search box works
- [ ] Pagination works
- [ ] Length menu works (10, 25, 50, 100)
- [ ] Sorting works (click column headers)
- [ ] Row click opens detail panel
- [ ] Edit button logs to console (not yet implemented)
- [ ] Delete button shows confirm dialog
- [ ] All text in correct language (Indonesian/English)

### Test Console:
- [ ] No JavaScript errors
- [ ] See log: `[AgencyDataTable] Initializing...`
- [ ] See log: `[AgencyDataTable] DataTable initialized`
- [ ] See log: `[AgencyDataTable] Events bound`
- [ ] See log: `[AgencyDataTable] Initialized successfully`

## Migration Path for Other Plugins

If other plugins (wp-company, wp-customer) have inline scripts:

**Step 1: Extract to separate file**
```javascript
// {plugin}-datatable.js
(function($) {
    const {Plugin}DataTable = {
        init() { ... }
    };

    $(document).ready(function() {
        {Plugin}DataTable.init();
    });
})(jQuery);
```

**Step 2: Enqueue with dependencies**
```php
wp_enqueue_script(
    '{plugin}-datatable',
    PLUGIN_URL . 'assets/js/{plugin}-datatable.js',
    ['jquery', 'jquery-datatables'],
    '1.0.0',
    true
);
```

**Step 3: Localize translations**
```php
wp_localize_script('{plugin}-datatable', 'wp{Plugin}DataTable', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('nonce_action'),
    'i18n' => [ /* translations */ ]
]);
```

**Step 4: Remove inline script from template**
```php
<!-- Before: -->
<script>...</script>

<!-- After: -->
<!-- JavaScript handled by {plugin}-datatable.js -->
```

## Related TODOs
- See: `wp-agency/TODO/TODO-3075-restructure-datatable-templates-directory.md`
- See: `wp-agency/TODO/TODO-3076-move-partials-to-datatable-templates.md`
- See: `wp-app-core/TODO/TODO-1179-implement-option-b-entity-specific-container.md`
- Related: `wp-agency/TODO/TODO-2071-implement-agency-dashboard-with-panel-system.md`

## References
- Base panel system: `wp-app-core/src/Views/DataTable/Templates/PanelLayoutTemplate.php`
- Old implementation: `agency-datatable.js.backup`
- WordPress Best Practices: https://developer.wordpress.org/apis/handbook/enqueuing/

## Code Quality Notes
This change improves:
1. **Separation of Concerns** - PHP = HTML, JS = JavaScript
2. **Performance** - Cacheable, minifiable JavaScript
3. **Maintainability** - Single source of truth for DataTable logic
4. **Internationalization** - Proper WordPress i18n integration
5. **Reusability** - Module pattern allows external access
6. **Best Practices** - Follows WordPress and modern JavaScript standards

## Implementation Complete ✅

**All steps completed:**
1. ✅ JavaScript extracted to separate file (agency-datatable.js v2.0.0)
2. ✅ Inline script removed from datatable.php (v1.0.3)
3. ✅ Script enqueued in class-dependencies.php (lines 359-382)
4. ✅ Translations localized via wpAgencyDataTable object

**Next Steps for Testing:**
1. Clear WordPress cache
2. Clear browser cache
3. Test DataTable functionality (see Testing Checklist above)
4. Verify translations display correctly
5. Check browser console for `[AgencyDataTable]` logs
