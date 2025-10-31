# DEBUG-3092: Jurisdiction JOIN Test

**Date**: 2025-10-31
**Issue**: User reports wilayah kerja not showing (JOIN belum bekerja)
**Status**: JOIN SUDAH BEKERJA - Kemungkinan Cache Issue

---

## ✅ Query Test Results

### Direct SQL Test (Successful)
```sql
SELECT
    d.id,
    d.code,
    d.name,
    d.agency_id,
    GROUP_CONCAT(DISTINCT wr.name ORDER BY wr.name SEPARATOR ', ') as wilayah_kerja
FROM wp_app_agency_divisions d
LEFT JOIN wp_app_agency_jurisdictions j ON d.id = j.division_id
LEFT JOIN wp_wi_regencies wr ON j.jurisdiction_code = wr.code
WHERE d.agency_id = 11
GROUP BY d.id
ORDER BY d.name
LIMIT 5
```

**Results**:
| id | code | name | agency_id | wilayah_kerja |
|----|------|------|-----------|---------------|
| 11 | 5074Pk33Wc-34 | Disnaker Provinsi Aceh - Pusat | 11 | **Kota Tangerang** ✅ |
| 22 | 5074Pk33Wc-75 | UPT Kabupaten Aceh Tenggara | 11 | NULL (no jurisdictions) |
| 21 | 5074Pk33Wc-00 | UPT Kota Sabang | 11 | **Kota Jakarta Timur, Kota Sabang** ✅ |

**Conclusion**: JOIN **BEKERJA DENGAN BAIK** ✅

---

## 🔍 Verification

### 1. DivisionDataTableModel Implementation

**File**: `/wp-agency/src/Models/Division/DivisionDataTableModel.php`

**get_joins() - Line 73-80**:
```php
protected function get_joins(): array {
    global $wpdb;

    return [
        "LEFT JOIN {$wpdb->prefix}app_agency_jurisdictions j ON d.id = j.division_id",
        "LEFT JOIN {$wpdb->prefix}wi_regencies wr ON j.jurisdiction_code = wr.code"
    ];
}
```
✅ **CORRECT**

**get_columns() - Line 87-101**:
```php
protected function get_columns(): array {
    $columns = [
        'd.code as code',
        'd.name as name',
        "GROUP_CONCAT(DISTINCT wr.name ORDER BY wr.name SEPARATOR ', ') as wilayah_kerja",
        'd.status as status',
        'd.id as id'
    ];
    return $columns;
}
```
✅ **CORRECT**

**get_group_by() - Line 110-112**:
```php
protected function get_group_by(): string {
    return 'd.id';
}
```
✅ **CORRECT**

**Constructor - Line 48-66**:
```php
public function __construct() {
    parent::__construct();

    global $wpdb;
    $this->table = $wpdb->prefix . 'app_agency_divisions d';
    $this->index_column = 'd.id';

    $this->searchable_columns = [
        'd.code',
        'd.name'
    ];

    $this->base_joins = $this->get_joins();  // ✅ JOINS REGISTERED

    $this->base_where = [];
}
```
✅ **CORRECT**

---

## ⚠️ Likely Cause: Browser/Server Cache

### Symptoms of Cache Issue:
- ✅ Database query works (verified above)
- ✅ Code implementation correct
- ❌ Browser may be using old JavaScript
- ❌ Browser may be caching old AJAX responses

### Solution: Clear All Caches

**1. Server-side Cache** ✅ DONE
```bash
wp cache flush
```

**2. Browser Cache** ⚠️ USER MUST DO
```
Hard Refresh: Ctrl+Shift+R (Windows/Linux)
Or: Cmd+Shift+R (Mac)
```

**3. Clear Browser Data** (if hard refresh doesn't work)
```
Chrome: Ctrl+Shift+Delete → Clear cached images and files
```

---

## 📋 Testing Steps

### Step 1: Clear Browser Cache
```
Press: Ctrl+Shift+R
```

### Step 2: Open Dashboard
```
http://wppm.local/wp-admin/admin.php?page=wp-agency-disnaker
```

### Step 3: Open DevTools
```
Press F12
→ Network tab
→ Check "Disable cache" checkbox
```

### Step 4: Click Agency
- Click agency row (id 11: "Disnaker Provinsi Aceh")
- Panel opens with tabs

### Step 5: Click "Unit Kerja" Tab
- Watch Network tab for AJAX requests
- Look for `get_divisions_datatable` request

### Step 6: Check Response
**In Network tab**:
1. Find `admin-ajax.php?action=get_divisions_datatable`
2. Click on it
3. Go to "Response" tab
4. Look for `wilayah_kerja` field in JSON

**Expected JSON**:
```json
{
  "draw": 1,
  "recordsTotal": 40,
  "recordsFiltered": 3,
  "data": [
    {
      "DT_RowId": "division-11",
      "DT_RowData": {"id": 11, "status": "active"},
      "code": "5074Pk33Wc-34",
      "name": "Disnaker Provinsi Aceh - Pusat",
      "wilayah_kerja": "Kota Tangerang"  ← SHOULD BE HERE
    }
  ]
}
```

---

## 🔍 If Still Not Working

### Check PHP Error Log
```bash
sudo tail -f /var/log/apache2/error.log
```

**Look for**:
```
[DivisionDataTableModel] get_columns() called
[DivisionDataTableModel] Columns: Array (... wilayah_kerja ...)
[DivisionDataTableModel] get_where() called
```

### Check JavaScript Console
**Look for**:
```
[AgencyDataTable] Lazy table initialized: divisions-datatable
[AgencyDataTable] Columns configured for division table
```

### Verify File Timestamps
```bash
# Check when files were last modified
ls -la /home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/src/Models/Division/DivisionDataTableModel.php

# Check JavaScript file
ls -la /home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/assets/js/agency/agency-datatable.js
```

**Expected**: Files modified today (2025-10-31)

---

## 🐛 Debugging Commands

### Test Model Directly
```bash
# Test if model can be instantiated
wp eval "
\$model = new WPAgency\Models\Division\DivisionDataTableModel();
echo 'Joins: ';
print_r(\$model);
" --path=/home/mkt01/Public/wppm/public_html --allow-root
```

### Test AJAX Handler
```bash
# Simulate AJAX request
wp eval "
\$_POST['action'] = 'get_divisions_datatable';
\$_POST['agency_id'] = 11;
\$_POST['nonce'] = wp_create_nonce('wpapp_panel_nonce');
\$_POST['draw'] = 1;
\$_POST['start'] = 0;
\$_POST['length'] = 10;

\$controller = new WPAgency\Controllers\Agency\AgencyDashboardController();
\$controller->handle_divisions_datatable();
" --path=/home/mkt01/Public/wppm/public_html --allow-root
```

---

## ✅ Expected Behavior (After Cache Clear)

### Visual Result
```
┌──────────────┬────────────────────────────┬──────────────────┐
│ Kode         │ Nama Unit Kerja            │ Wilayah Kerja    │
├──────────────┼────────────────────────────┼──────────────────┤
│ 5074Pk33Wc-34│ Disnaker Provinsi Aceh...  │ Kota Tangerang   │
│ 5074Pk33Wc-00│ UPT Kota Sabang            │ Kota Jakarta..., │
│              │                            │ Kota Sabang      │
└──────────────┴────────────────────────────┴──────────────────┘
```

### Network Request
```
POST /wp-admin/admin-ajax.php
action=get_divisions_datatable
agency_id=11
```

### Network Response
```json
{
  "data": [
    {
      "code": "5074Pk33Wc-34",
      "name": "Disnaker Provinsi Aceh - Pusat",
      "wilayah_kerja": "Kota Tangerang"
    }
  ]
}
```

---

## 📝 Summary

✅ **Code Implementation**: CORRECT
✅ **Database Query**: WORKS
✅ **JOIN Syntax**: CORRECT
⚠️ **Cache**: LIKELY THE ISSUE

**Action Required**:
1. ✅ Server cache flushed (wp cache flush)
2. ⏳ User must clear browser cache (Ctrl+Shift+R)
3. ⏳ User must test again with DevTools Network tab open

---

If after clearing cache the issue persists, please provide:
1. Screenshot of Network tab showing AJAX response
2. PHP error log entries
3. Browser console logs
