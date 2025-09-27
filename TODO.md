# TODO: Fix Empty Select Lists in Edit Agency Form

## Current Issue
- Edit agency form select lists (province and regency) are empty
- Uses `do_action('wilayah_indonesia_province_select')` and `do_action('wilayah_indonesia_regency_select')` hooks that don't exist
- Even if hooks existed, they filter out assigned provinces which is wrong for editing

## Solution
Use hardcoded select elements + AJAX like create form, but show ALL provinces for editing (not just available ones)

## Tasks
- [x] Add `getAvailableProvincesForAgencyEditing()` method in AgencyController
- [x] Register AJAX action `wp_ajax_get_available_provinces_for_agency_editing`
- [x] Update `edit-agency-form.php`: replace do_action with hardcoded select elements
- [ ] Update `edit-agency-form.js`:
  - [x] Add `loadAllProvinces()` method
  - [x] Add `loadRegenciesByProvince()` method
  - [x] Call `loadAllProvinces()` in `showEditForm()`
  - [x] Handle province change to load regencies
- [x] Fix permission check issue (removed restrictive permission check)
- [x] Test: Open edit form, ensure province select shows all provinces, regency loads based on selected province

---

# TODO-1045: Fix DataTables ParserError in Agency Employee List

## Issue
- DataTables parsererror when switching to employee-list tab in agency dashboard
- Error: "DataTables Error: parsererror" in employee-datatable.js line 202

## Root Cause
- Invalid database query referencing non-existent `department` column
- Incorrect parameter count in prepared statement
- Permission check throwing exception instead of skipping unauthorized employees
- Corrupted cached responses from previous buggy implementation

## Solution
- Fixed query to search on existing columns (name, position, division_name)
- Corrected parameter count for prepared statements
- Changed permission handling to skip unauthorized employees instead of failing
- Temporarily disabled cache to clear corrupted data, then re-enabled

## Tasks
- [x] Fix search query in `AgencyEmployeeModel.php` to use valid columns
- [x] Correct parameter count in database query preparation
- [x] Update permission check in `AgencyEmployeeController.php` to skip instead of throw
- [x] Temporarily disable cache to clear corrupted data
- [x] Re-enable cache after confirming fix works
- [x] Test: Switch to employee-list tab, verify DataTable loads without parsererror
