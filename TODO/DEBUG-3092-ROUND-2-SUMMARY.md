# DEBUG-3092: Round 2 - Enhanced Logging Summary

**Date**: 2025-10-31
**Status**: Ready for Testing

---

## ✅ Completed: Comprehensive Logging Added

### Changes Made

**1. Backend PHP Logging** ✅
- `AgencyDashboardController::handle_divisions_datatable()`
- `DivisionDataTableModel::get_columns()`
- `DivisionDataTableModel::get_where()`

**2. Frontend JavaScript Logging** ⭐ NEW
- `wpapp-tab-manager.js::autoLoadTabContent()` (Line 200-280)
- Detailed step-by-step logging:
  - Method entry
  - Class checks
  - Data attributes
  - AJAX request
  - Response handling
  - HTML injection
  - Success/error states

---

## 🎯 Root Cause Analysis (Round 1 Findings)

Berdasarkan log files testing pertama:

### Finding 1: Tab Content Pre-Rendered
- Tab divisions content (655 bytes) sudah di-render saat panel dibuka
- divisions.php sudah di-include via `render_divisions_tab()`

### Finding 2: No AJAX to load_divisions_tab
- **TIDAK ADA** AJAX request ke `load_divisions_tab` action
- **TIDAK ADA** log `[WPApp Tab] Content loaded successfully`
- Artinya: `autoLoadTabContent()` method tidak berjalan

### Finding 3: MutationObserver Waiting
- **TIDAK ADA** log `[AgencyDataTable] Lazy table detected`
- Table HTML tidak pernah di-inject (karena AJAX tidak jalan)

### Hypothesis
wpapp-tab-manager.js `autoLoadTabContent()` tidak bekerja karena salah satu dari:
1. Tab tidak memiliki class `wpapp-tab-autoload`
2. Tab sudah marked sebagai `loaded` (false positive)
3. Data attributes missing atau tidak terbaca

**Round 2 logging will reveal which one!**

---

## 📋 Testing Instructions

### Step 1: Clear Browser Cache
```
Ctrl+Shift+R (hard refresh)
atau
Ctrl+F5
```

**PENTING**: Pastikan wpapp-tab-manager.js versi baru ter-load!

### Step 2: Open Dashboard & DevTools
1. URL: `http://wppm.local/wp-admin/admin.php?page=wp-agency-disnaker`
2. Press F12 (Open DevTools)
3. Tab Console → Clear (Ctrl+L)
4. Tab Network → Clear

### Step 3: Open Agency Detail
- Click any agency row in main DataTable
- Right panel will slide open
- Wait for panel fully loaded

### Step 4: Click Tab "Unit Kerja"
- Click the second tab ("Unit Kerja")
- **Watch Console for NEW logs**

### Step 5: Capture Logs
Capture 3 outputs:

**A. Browser Console**
```
Right-click in Console → Save as...
Filename: console-tab-unit-kerja-round2.log
```

**B. Browser Network Tab**
```
Filter: XHR
Look for: load_divisions_tab, get_divisions_datatable
Right-click → Save all as HAR
Filename: network-tab-unit-kerja-round2.har
```

**C. PHP Error Log**
```bash
sudo tail -n 500 /var/log/apache2/error.log > ~/Downloads/php-error-round2.log
```

---

## 🔍 What to Look For

### Expected Logs (If Working Correctly)

**Browser Console**:
```
[WPApp Tab] Switched to: divisions
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Has wpapp-tab-autoload: true
[WPApp Tab] Has loaded: false
[WPApp Tab] Data attributes: {agencyId: 11, loadAction: "load_divisions_tab", ...}
[WPApp Tab] Starting AJAX request for: load_divisions_tab
[WPApp Tab] AJAX Success Response: {success: true, ...}
[WPApp Tab] Content loaded successfully for: load_divisions_tab
[AgencyDataTable] Lazy table detected in DOM
[AgencyDataTable] Initializing lazy table: divisions-datatable
```

**Network Tab**:
- Request to `admin-ajax.php?action=load_divisions_tab` (should exist)
- Request to `admin-ajax.php?action=get_divisions_datatable` (should exist)

**PHP Error Log**:
```
=== DIVISIONS DATATABLE AJAX HANDLER CALLED ===
[DivisionDataTableModel] get_columns() called
[DivisionDataTableModel] get_where() called
```

### Diagnostic Scenarios

**Scenario A: autoLoadTabContent NOT Called**
```
[WPApp Tab] Switched to: divisions
// NO other logs
```
→ Issue: switchTab() tidak call autoLoadTabContent (BUG in wpapp-tab-manager.js)

**Scenario B: Missing wpapp-tab-autoload Class**
```
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Has wpapp-tab-autoload: false
[WPApp Tab] Tab does NOT have wpapp-tab-autoload class - skipping
```
→ Issue: Class di-strip saat render

**Scenario C: Already Loaded (False Positive)**
```
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Has loaded: true
[WPApp Tab] Tab already loaded - skipping
```
→ Issue: Tab marked loaded tapi tidak ada content

**Scenario D: Missing Data Attributes**
```
[WPApp Tab] Data attributes: {agencyId: undefined, loadAction: undefined, ...}
[WPApp Tab] Missing required data attributes
```
→ Issue: Data attributes tidak terbaca

**Scenario E: AJAX Fails**
```
[WPApp Tab] Starting AJAX request for: load_divisions_tab
[WPApp Tab] AJAX error: ...
```
→ Issue: Server-side problem (nonce, permission, handler tidak terdaftar)

---

## 📝 Report Format

Please provide:

**1. Browser Console Logs**
- File: console-tab-unit-kerja-round2.log
- Or copy-paste console output

**2. Network Requests**
- Screenshot of Network tab (XHR filter)
- Or HAR file: network-tab-unit-kerja-round2.har

**3. PHP Error Log**
- File: php-error-round2.log
- Last 500 lines around the time of testing

**4. Which Scenario Matches?**
- Scenario A, B, C, D, or E?
- Or something else?

---

## 🎯 Next Steps After Testing

Once we have the logs, we can:
1. Identify exact point of failure
2. Implement targeted fix
3. Continue with Review-01 requirements (add Wilayah Kerja column)
4. Final testing

---

## 📄 Files Modified

1. `/wp-app-core/assets/js/datatable/wpapp-tab-manager.js` (Line 200-280)
2. `/wp-agency/src/Controllers/Agency/AgencyDashboardController.php` (Line 850-893)
3. `/wp-agency/src/Models/Division/DivisionDataTableModel.php` (Line 63-121)

**All changes are debug logging only - no functionality changes.**

---

Silakan lakukan testing dan share log files-nya! 🚀
