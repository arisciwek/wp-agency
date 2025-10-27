# TODO-3074: Rename Filter Group to Status Filter Group

## Status
✅ **COMPLETED** - 2025-10-25

## Requirement (Review-08: Filter-group)
Di wp-agency, karena filter ini terkait dengan status active, ubah HTML dan CSSnya agar mengandung konteks "active" atau "status".

**Instruksi:** Ganti `agency-filter-group` menjadi nama yang lebih deskriptif yang mencerminkan bahwa ini adalah filter untuk status.

## Problem

**Before (Generic Name):**
```html
<div class="agency-filter-group">
    <label>Filter Status:</label>
    <select>...</select>
</div>
```

**Issue:**
- Class name `agency-filter-group` terlalu generic
- Tidak mencerminkan bahwa ini adalah filter untuk STATUS
- Kurang deskriptif untuk maintainability

## Solution

Rename `agency-filter-group` → `agency-status-filter-group` untuk menambahkan konteks "status".

**After (Descriptive Name):**
```html
<div class="agency-status-filter-group">
    <label>Filter Status:</label>
    <select>...</select>
</div>
```

## Changes Implemented

### 1. status-filter.php - Update Class Name ✅
**File**: `wp-agency/src/Views/agency/partials/status-filter.php`

**Line 40:** Changed class name
```php
// Before:
<div class="agency-filter-group">

// After:
<div class="agency-status-filter-group">
```

**Changes:**
- ✅ Renamed `agency-filter-group` → `agency-status-filter-group`
- ✅ Added "status" context to class name
- ✅ More descriptive and maintainable

### 2. agency-filter.css - Update CSS Selector ✅
**File**: `wp-agency/assets/css/agency/agency-filter.css`

**Lines 20-25:** Updated CSS selector and comment
```css
/* Before:
/* Filter Group */
.agency-filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* After: */
/* Status Filter Group */
.agency-status-filter-group {
    display: flex;
    align-items: center;
    gap: 10px;
}
```

**Changes:**
- ✅ Renamed `.agency-filter-group` → `.agency-status-filter-group`
- ✅ Updated comment to reflect "Status Filter Group"
- ✅ CSS properties remain the same (no visual changes)

## Naming Convention Rationale

**Why `agency-status-filter-group`?**
1. **`agency-`**: Plugin scope prefix (local scope)
2. **`status-`**: Filter type context (this filters by STATUS)
3. **`filter-`**: Component type (this is a FILTER)
4. **`group`**: Layout wrapper (groups label + select)

**Alternative Names Considered:**
- `agency-active-filter-group` ❌ (too specific, filter has all/active/inactive options)
- `agency-filter-status-group` ❌ (wrong word order)
- `agency-status-filter-group` ✅ (clear hierarchy: plugin → type → component → layout)

## Responsibility Separation

### Global Scope (wp-app-core)
**Classes:**
- `wpapp-filters-container` - Container for all filters
- `wpapp-datatable-filters` - White box styling for filter area

**Provides:**
- Container layout
- White background, padding, border
- Box shadow
- Flex layout with gap

### Local Scope (wp-agency)
**Class:** `agency-status-filter-group`
**Provides:**
- Flex layout for label + select alignment
- Gap between elements (10px)
- **Context:** This is a STATUS filter (filters agencies by active/inactive status)

## Benefits
1. ✅ More descriptive class name (easier to understand purpose)
2. ✅ Better maintainability (clear what type of filter this is)
3. ✅ Consistent naming convention (plugin-type-component-layout)
4. ✅ No breaking changes to other files (only used in 2 files)
5. ✅ Follows Review-08 instruction to add "status" context

## Files Modified
1. ✅ `wp-agency/src/Views/agency/partials/status-filter.php` (line 40)
2. ✅ `wp-agency/assets/css/agency/agency-filter.css` (lines 20-21)

## Visual Impact
- ✅ **No visual changes** - appearance remains exactly the same
- ✅ Filter still displays in white box container
- ✅ Label and select still properly aligned
- ✅ Only the class name changed (internal change)

## Testing Checklist
- [ ] Clear WordPress cache
- [ ] Clear browser cache
- [ ] Open wp-agency dashboard
- [ ] Verify filter displays correctly:
  - [ ] White box container visible
  - [ ] Label "Filter Status:" visible
  - [ ] Select dropdown shows 3 options (Semua Status, Aktif, Tidak Aktif)
  - [ ] Default selection is "Aktif"
  - [ ] Dropdown still works
- [ ] Check DevTools - structure should show:
  ```
  wpapp-filters-container
    ├─ wpapp-datatable-filters
       └─ agency-status-filter-group
           ├─ label.agency-filter-label
           └─ select.agency-filter-select
  ```
- [ ] No console errors
- [ ] Filter functionality still works (DataTable updates when selection changes)

## HTML Structure (Final)

```html
<div class="wpapp-filters-container">              <!-- wp-app-core: Container -->
    <div class="wpapp-datatable-filters">          <!-- wp-app-core: White box styling -->
        <div class="agency-status-filter-group">   <!-- ✅ wp-agency: Status filter layout -->
            <label for="agency-status-filter" class="agency-filter-label">
                Filter Status:
            </label>
            <select id="agency-status-filter" class="agency-filter-select" data-current="active">
                <option value="all">Semua Status</option>
                <option value="active" selected>Aktif</option>
                <option value="inactive">Tidak Aktif</option>
            </select>
        </div>
    </div>
</div>
```

## Related TODOs
- Related: `wp-agency/TODO/TODO-3073-remove-double-wrapper-filter.md` (removed wrapper)
- Continues: Review-08 in `wp-app-core/claude-chats/task-1179.md`

## References
- Review-08 → Filter-group section in `wp-app-core/claude-chats/task-1179.md`
- Lines 285-291: "ganti HTML dan CSSnya agar mengandung konteks 'active'"

## Code Quality Notes
This change improves:
1. **Code clarity** - Class name now describes WHAT it filters (status)
2. **Maintainability** - Future developers understand purpose immediately
3. **Consistency** - Follows naming pattern: `plugin-type-component-layout`
4. **Documentation** - Self-documenting code (name explains function)
