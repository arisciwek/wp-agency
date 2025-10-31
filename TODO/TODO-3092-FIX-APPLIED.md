# TODO-3092: FIX APPLIED - Missing wpapp-tab-autoload Class

**Date**: 2025-10-31
**Status**: ✅ FIXED - Ready for Testing
**Issue**: DataTable tidak tampil di tab Unit Kerja

---

## 🎯 Root Cause (Confirmed)

**Issue**: Class `wpapp-tab-autoload` hilang dari outer div saat tab content di-inject

### The Problem

**Browser Log Evidence**:
```javascript
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Has wpapp-tab-autoload: false  // ❌ MISSING!
[WPApp Tab] Tab does NOT have wpapp-tab-autoload class - skipping
```

### Why It Happened

**Step 1**: TabSystemTemplate creates empty container:
```html
<div id="divisions" class="wpapp-tab-content">
  <!-- empty -->
</div>
```

**Step 2**: Server sends divisions.php content WITH classes:
```html
<div class="wpapp-tab-content wpapp-divisions-tab wpapp-tab-autoload" ...>
  <div class="wpapp-tab-header">...</div>
  ...
</div>
```

**Step 3**: wpapp-panel-manager.js replaces INNER HTML only:
```javascript
$('#divisions').html(content);  // ❌ Only replaces innerHTML!
```

**Result**: Nested divs, classes on WRONG div:
```html
<div id="divisions" class="wpapp-tab-content">  ← NO wpapp-tab-autoload!
  <div class="wpapp-tab-content wpapp-divisions-tab wpapp-tab-autoload" ...>  ← Classes here
    ...
  </div>
</div>
```

---

## ✅ Solution Implemented

### Fix 1: Update wpapp-panel-manager.js (wp-app-core)

**File**: `/wp-app-core/assets/js/datatable/wpapp-panel-manager.js`
**Lines**: 562-603

**Changes**:
- Parse injected HTML content
- Extract classes from first child element
- Copy classes to outer tab div (excluding wpapp-tab-content)
- Copy all data-attributes to outer tab div
- Then inject content

**Code** (Line 570-595):
```javascript
// Create temporary element to parse content
const $temp = $('<div>').html(content);
const $firstChild = $temp.children().first();

// If content has a wrapper div, copy its classes and data-attributes to tab
if ($firstChild.length > 0) {
    // Copy classes (except wpapp-tab-content which tab already has)
    const classes = $firstChild.attr('class');
    if (classes) {
        const classArray = classes.split(/\s+/);
        classArray.forEach(function(cls) {
            if (cls && cls !== 'wpapp-tab-content' && !$tab.hasClass(cls)) {
                $tab.addClass(cls);
                console.log('[WPApp Panel] Added class to tab:', cls);
            }
        });
    }

    // Copy data-attributes
    $.each($firstChild[0].attributes, function(idx, attr) {
        if (attr.name.startsWith('data-')) {
            $tab.attr(attr.name, attr.value);
            console.log('[WPApp Panel] Added attribute:', attr.name, '=', attr.value);
        }
    });
}

// Inject content
$tab.html(content);
```

### Fix 2: Update divisions.php Template (wp-agency)

**File**: `/wp-agency/src/Views/agency/tabs/divisions.php`
**Version**: 3.0.0 → 3.1.0

**Changes**:
- Removed duplicate outer `wpapp-tab-content` class
- Keep only inner div with `wpapp-tab-autoload` and data-attributes
- Outer div provided by TabSystemTemplate, classes copied via JS

**Before** (Line 59):
```php
<div class="wpapp-tab-content wpapp-divisions-tab wpapp-tab-autoload" ...>
```

**After** (Line 69):
```php
<!-- Inner content only, outer wrapper from TabSystemTemplate -->
<div class="wpapp-divisions-tab wpapp-tab-autoload" ...>
```

### Fix 3: Update employees.php Template (wp-agency)

**File**: `/wp-agency/src/Views/agency/tabs/employees.php`
**Version**: 3.0.0 → 3.1.0

**Same fix as divisions.php**

---

## 📋 Expected Behavior After Fix

### Step 1: Panel Opens
```javascript
// TabSystemTemplate creates:
<div id="divisions" class="wpapp-tab-content"></div>
```

### Step 2: Content Injected
```javascript
// Server sends inner content with classes
// wpapp-panel-manager extracts and copies:
$('#divisions').addClass('wpapp-divisions-tab');
$('#divisions').addClass('wpapp-tab-autoload');
$('#divisions').attr('data-agency-id', '11');
$('#divisions').attr('data-load-action', 'load_divisions_tab');
// ... etc
```

### Step 3: Tab Switched
```javascript
[WPApp Tab] Switched to: divisions
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Has wpapp-tab-autoload: true  // ✅ NOW TRUE!
[WPApp Tab] Starting AJAX request for: load_divisions_tab
```

### Step 4: Table Loaded
```javascript
[WPApp Tab] AJAX Success Response
[WPApp Tab] Content loaded successfully
[AgencyDataTable] Lazy table detected in DOM
[AgencyDataTable] Initializing lazy table: divisions-datatable
```

### Step 5: DataTable Initialized
```
=== DIVISIONS DATATABLE AJAX HANDLER CALLED ===
[DivisionDataTableModel] get_columns() called
[DivisionDataTableModel] get_where() called
```

---

## 📝 Files Modified

### Core (wp-app-core)
1. `/wp-app-core/assets/js/datatable/wpapp-panel-manager.js` (Line 562-603)
   - Added: Class and attribute copying from injected content

### Plugin (wp-agency)
2. `/wp-agency/src/Views/agency/tabs/divisions.php` (v3.1.0)
   - Changed: Removed outer div wrapper
   - Pattern: Inner content only

3. `/wp-agency/src/Views/agency/tabs/employees.php` (v3.1.0)
   - Changed: Removed outer div wrapper
   - Pattern: Inner content only

---

## ✅ Testing Instructions

### Step 1: Clear Browser Cache
```
Ctrl+Shift+R (hard refresh)
```

**IMPORTANT**: Ensure JS files reload with new changes!

### Step 2: Open Dashboard
```
URL: http://wppm.local/wp-admin/admin.php?page=wp-agency-disnaker
```

### Step 3: Open Agency Detail
- Click any agency row
- Wait for panel to open
- Panel should show 3 tabs: Data Disnaker, Unit Kerja, Staff

### Step 4: Click "Unit Kerja" Tab
- Click second tab
- **Watch console for NEW logs**:
  ```
  [WPApp Panel] Added class to tab: wpapp-divisions-tab
  [WPApp Panel] Added class to tab: wpapp-tab-autoload
  [WPApp Panel] Added attribute: data-agency-id = 11
  [WPApp Panel] Added attribute: data-load-action = load_divisions_tab
  [WPApp Tab] Switched to: divisions
  [WPApp Tab] autoLoadTabContent called
  [WPApp Tab] Has wpapp-tab-autoload: true  ← SHOULD BE TRUE NOW!
  [WPApp Tab] Starting AJAX request for: load_divisions_tab
  ```

### Step 5: Verify DataTable Appears
- Should see "Memuat data divisi..." briefly
- Then DataTable should appear with data
- Columns: Code, Name, Type, Status
- Pagination, search should work

### Expected Console Logs
```
[WPApp Panel] Added class to tab: wpapp-tab-autoload
[WPApp Tab] Has wpapp-tab-autoload: true
[WPApp Tab] Starting AJAX request: load_divisions_tab
[WPApp Tab] Content loaded successfully
[AgencyDataTable] Lazy table detected
[AgencyDataTable] Initializing lazy table
```

### Expected PHP Logs
```
=== DIVISIONS DATATABLE AJAX HANDLER CALLED ===
POST data: Array(action => get_divisions_datatable, agency_id => 11, ...)
Nonce verified OK
[DivisionDataTableModel] get_columns() called
[DivisionDataTableModel] get_where() called
Total records: 40
Filtered records: 3
Data rows: 3
```

---

## 🎯 Next Steps After Successful Test

1. ✅ Verify fix works (tab Unit Kerja shows DataTable)
2. ⏳ Remove debug logging from production code
3. ⏳ Implement Review-01 requirement: Add "Wilayah Kerja" column
4. ⏳ Final testing with real data
5. ⏳ Update main TODO-3092.md
6. ⏳ Git commit with proper message

---

## 📊 Impact Analysis

### Affects
- ✅ wp-agency: divisions tab (Unit Kerja)
- ✅ wp-agency: employees tab (Staff)
- ⚠️ wp-app-core: panel-manager.js (affects ALL plugins using tab system)

### Benefits
- ✅ Fixes lazy-load for agency tabs
- ✅ Prevents class/attribute loss during content injection
- ✅ Generic solution works for all tab-based panels
- ✅ Backward compatible (won't break existing tabs)

### Risks
- ⚠️ Minor: Extra class parsing overhead (negligible)
- ⚠️ Minor: Console logs visible (can be removed later)

---

## 🔍 Debugging Still Enabled

Console logging is still active. After successful test, we can:
1. Keep minimal logging for production
2. Or remove all debug logs
3. Or make it conditional on `wpAppConfig.debug`

Current logging helps verify the fix is working correctly.

---

Silakan test dengan hard refresh browser (Ctrl+Shift+R) dan klik tab Unit Kerja! 🚀
