# DEBUG-3092: Divisions DataTable Tidak Tampil

**Created**: 2025-10-31
**Updated**: 2025-10-31 (Round 2 - Enhanced Logging)
**Issue**: DataTable tidak tampil saat tab "Unit Kerja" diklik
**Status**: Debugging with comprehensive logging

---

## 🎯 ROOT CAUSE ANALYSIS (Round 1)

Berdasarkan log files dari testing pertama:

### Finding 1: Tab Content Pre-Rendered
- Tab divisions content sudah di-render di server-side (655 bytes)
- Content di-inject saat panel dibuka, bukan lazy-load
- divisions.php template sudah di-include via `render_divisions_tab()`

### Finding 2: No AJAX to load_divisions_tab
- **TIDAK ADA** AJAX request ke `load_divisions_tab`
- **TIDAK ADA** log `[WPApp Tab] Content loaded successfully`
- Artinya: `autoLoadTabContent()` tidak dipanggil atau tidak bekerja

### Finding 3: MutationObserver Tidak Detect Table
- **TIDAK ADA** log `[AgencyDataTable] Lazy table detected`
- Table HTML tidak di-inject ke DOM (karena AJAX tidak jalan)
- MutationObserver menunggu, tapi tidak ada table baru

### Hypothesis
wpapp-tab-manager.js `autoLoadTabContent()` tidak bekerja karena:
1. Tab tidak memiliki class `wpapp-tab-autoload` (sudah di-strip?)
2. Tab sudah marked sebagai `loaded` (false positive?)
3. Data attributes missing atau tidak terbaca

---

## 🔍 Debug Logging Enabled (ENHANCED)

Debug logging sudah ditambahkan di:

### Backend (PHP) ✅
1. **AgencyDashboardController::handle_divisions_datatable()** (Line 850-893)
   - Log saat AJAX handler dipanggil
   - Log POST data
   - Log nonce verification
   - Log permission check
   - Log response data

2. **DivisionDataTableModel::get_columns()** (Line 63-76)
   - Log columns yang di-return

3. **DivisionDataTableModel::get_where()** (Line 104-121)
   - Log agency_id filter
   - Log WHERE conditions

### Frontend (JavaScript) ✅ ENHANCED
4. **agency-datatable.js**
   - Lazy table detection
   - Table initialization
   - Column configuration

5. **wpapp-tab-manager.js** ⭐ NEW - Line 200-280
   - `autoLoadTabContent()` entry point
   - Check wpapp-tab-autoload class
   - Check loaded status
   - Data attributes (agency-id, load-action, content-target)
   - AJAX request start
   - AJAX response handling
   - HTML injection
   - Success/error states

---

## 📋 Steps to Debug

### 1. Open WordPress Admin Panel
```
URL: http://wppm.local/wp-admin/admin.php?page=wp-agency-disnaker
```

### 2. Open Browser DevTools
- Press F12
- Go to Console tab
- Clear console (Ctrl+L)

### 3. Open Agency Detail Panel
- Click any agency row in the main DataTable
- Right panel should slide open with tabs

### 4. Click "Unit Kerja" Tab
- Click the second tab ("Unit Kerja")
- Watch console for JavaScript logs
- Watch Network tab for AJAX requests

### 5. Check PHP Error Log
```bash
# Real-time monitoring
sudo tail -f /var/log/apache2/error.log

# Or check WordPress debug log if enabled
tail -f /home/mkt01/Public/wppm/public_html/wp-content/debug.log
```

---

## 🔍 What to Look For (UPDATED)

### JavaScript Console - Expected Flow

**Step 1: Tab Switch**
```
[WPApp Tab] Switched to: divisions
```

**Step 2: Auto-Load Check** ⭐ NEW
```
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Tab element: [jQuery object]
[WPApp Tab] Has wpapp-tab-autoload: true
[WPApp Tab] Has loaded: false
[WPApp Tab] Data attributes: {agencyId: 11, loadAction: "load_divisions_tab", ...}
[WPApp Tab] Starting AJAX request for: load_divisions_tab
```

**Step 3: AJAX Response** ⭐ NEW
```
[WPApp Tab] AJAX Success Response: {success: true, data: {...}}
[WPApp Tab] Loading HTML into: .wpapp-divisions-content
[WPApp Tab] HTML length: 1234
[WPApp Tab] Target element found: 1
[WPApp Tab] Content loaded successfully for: load_divisions_tab
[WPApp Tab] HTML preview: <table id="divisions-datatable"...
```

**Step 4: MutationObserver Detects Table**
```
[AgencyDataTable] Lazy table detected in DOM
[AgencyDataTable] Initializing lazy table: {tableId, entity, agencyId, ajaxAction}
```

**Step 5: DataTable Initialized**
```
[AgencyDataTable] Lazy table initialized: divisions-datatable
```

**Step 6: DataTable AJAX Request**
```
=== DIVISIONS DATATABLE AJAX HANDLER CALLED === (in PHP error log)
```

### Possible Issues & Logs

**Issue A: autoLoadTabContent NOT Called**
```
[WPApp Tab] Switched to: divisions
// NO autoLoadTabContent logs
```
**→ Cause**: switchTab() tidak call autoLoadTabContent

**Issue B: Missing wpapp-tab-autoload Class**
```
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Has wpapp-tab-autoload: false
[WPApp Tab] Tab does NOT have wpapp-tab-autoload class - skipping
```
**→ Cause**: Class di-strip saat render atau inject

**Issue C: Already Loaded**
```
[WPApp Tab] autoLoadTabContent called
[WPApp Tab] Has loaded: true
[WPApp Tab] Tab already loaded - skipping
```
**→ Cause**: False positive, tab marked loaded tapi tidak ada content

**Issue D: Missing Data Attributes**
```
[WPApp Tab] Data attributes: {agencyId: undefined, loadAction: undefined, ...}
[WPApp Tab] Missing required data attributes for auto-load
```
**→ Cause**: Data attributes tidak terbaca atau tidak ada

### Network Tab

**Look for AJAX request**:
- URL: `/wp-admin/admin-ajax.php`
- Action: `get_divisions_datatable`
- Method: POST
- Status: Should be 200 OK

**Request payload should include**:
```
action: get_divisions_datatable
agency_id: [number]
nonce: [string]
draw: 1
start: 0
length: 10
```

**Response should be JSON**:
```json
{
  "draw": 1,
  "recordsTotal": 40,
  "recordsFiltered": [number],
  "data": [...]
}
```

### PHP Error Log

**Expected logs**:
```
=== DIVISIONS DATATABLE AJAX HANDLER CALLED ===
POST data: Array(...)
Nonce verified OK
User has view_agency_list: YES
After filter, can_view: YES
Creating DivisionDataTableModel...
Calling get_datatable_data...
[DivisionDataTableModel] get_columns() called
[DivisionDataTableModel] get_where() called
[DivisionDataTableModel] Filtering by agency_id: 1
Total records: 40
Filtered records: [number]
Data rows: [number]
```

**Possible errors**:
- Nonce verification failed
- Permission denied
- No agency_id in POST data
- SQL errors
- Exception with stack trace

---

## 🐛 Common Issues & Solutions

### Issue 1: AJAX Handler Not Called

**Symptom**: No PHP logs appear when tab clicked

**Possible causes**:
1. AJAX action name mismatch
2. Hook not registered
3. JavaScript not sending AJAX request

**Check**:
```bash
# Verify hook registration
wp action list 'wp_ajax_get_divisions_datatable' --path=/home/mkt01/Public/wppm/public_html --allow-root
```

### Issue 2: No agency_id in POST Data

**Symptom**: Log shows "WARNING: No agency_id in POST data"

**Possible causes**:
1. data-agency-id attribute missing from table
2. JavaScript not reading attribute
3. JavaScript not sending agency_id in AJAX data

**Check**:
- View page source when panel is open
- Find `<table id="divisions-datatable"`
- Verify `data-agency-id="[number]"` attribute exists

### Issue 3: Table Exists But Not Initializing

**Symptom**: Table HTML visible but no DataTable features (no pagination, search, etc.)

**Possible causes**:
1. MutationObserver not detecting table
2. Table missing class `agency-lazy-datatable`
3. DataTables library not loaded

**Check**:
```javascript
// In browser console
$.fn.DataTable.isDataTable('#divisions-datatable')
// Should return true if initialized

$('#divisions-datatable').hasClass('agency-lazy-datatable')
// Should return true
```

### Issue 4: AJAX Request Fails

**Symptom**: Network tab shows failed request (4xx/5xx status)

**Possible causes**:
1. Nonce verification failed (expired/invalid)
2. Permission denied
3. PHP fatal error

**Check**:
- Response tab in Network devtools
- Look for error message in response
- Check PHP error log for fatal errors

---

## 📝 Report Template

When reporting the issue, please provide:

```
**Browser Console Logs:**
[Paste console.log output here]

**Network Request Details:**
URL:
Method:
Status:
Request Payload:
Response:

**PHP Error Log:**
[Paste relevant error_log entries here]

**Screenshots:**
- Tab before click
- Tab after click
- Console tab
- Network tab
```

---

## ✅ Next Steps After Debugging

Once we identify the issue:

1. Fix the root cause
2. Remove debug logging (or comment out)
3. Add jurisdictions column (Review-01 requirement)
4. Test with real data
5. Update TODO-3092 documentation
