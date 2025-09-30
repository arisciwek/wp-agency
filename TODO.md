# TODO-0511: Fix Employee Count Difference on Reload vs Menu Switch

## Issue
- Employee count shows 9 when reloading page with agency hash (#8), but 86 when switching menus
- 86 is correct (total employees), 9 is agency-specific count

## Root Cause
- loadStats() in agency-script.js passes agencyId from URL hash to get_agency_stats
- When hash present, stats show per-agency counts instead of global totals
- Dashboard should always show global statistics

## Solution
- Modify loadStats() to always pass id: 0 for global statistics
- Remove hash-based agencyId logic from stats loading

## Tasks
- [x] Update loadStats() in agency-script.js to always send id: 0
- [x] Test: Reload page with hash, verify employee count shows 86 (global total)

---

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

---

# TODO-2255: Fix Spinning Icon on Employee Tab When Switching from Division Tab

## Issue
- Spinning loading icon persists indefinitely when clicking "Unit" tab then "Staff" tab in agency right panel
- Loading state (.employee-loading-state with spinner) does not hide after DataTable initialization

## Root Cause
- DataTable AJAX success callback receives error JSON from server ({success: false, data: {message}}), but dataSrc tries to access response.data.length where data is object, causing TypeError
- No timeout on AJAX, potential for hanging requests
- Race conditions on rapid tab switches causing overlapping loading states
- Cached error responses not handled properly

## Solution
- Enhanced dataSrc in employee-datatable.js to detect and handle error responses (response.success === false)
- Added 10s timeout and cache: false to DataTable AJAX
- Added isLoading flag to prevent overlapping refresh calls
- Added small delay (100ms) in switchTab before DataTable init to ensure tab visibility
- Changed init to always reinitialize like division-datatable.js for consistency
- Disabled DataTable caching in backend to prevent stale responses

## Tasks
- [x] Modify dataSrc in employee-datatable.js to check for response.success === false and show error state
- [x] Add timeout: 10000 and cache: false to DataTable AJAX config
- [x] Add isLoading flag and prevent overlapping operations
- [x] Add setTimeout in agency-script.js switchTab for employee-list init
- [x] Change init to always reinitialize for fresh data
- [x] Disable DataTable caching in AgencyCacheManager to prevent stale responses
- [x] Test: Switch from Staff to other tab then back to Staff, verify spinner hides and data loads

---

# TODO: Fix DataTable Not Refreshing After Agency Edit

## Issue
- After successfully editing and saving agency data, the DataTable still shows the old agency name
- Spinning icon with "Memproses..." message persists indefinitely
- Data is correctly saved to database, but UI doesn't reflect changes

## Root Cause
- DataTable cache is not invalidated after agency update
- Cached DataTable response contains old data
- Possible AJAX timeout or error in refresh causing spinner to hang

## Solution
- Invalidate DataTable cache in AgencyController update method
- Ensure refresh() properly reloads data and hides spinner
- Add error handling for DataTable refresh

## Tasks
- [x] Add $this->cache->invalidateDataTableCache('agency_list'); in AgencyController update method (already present)
- [x] Check and fix DataTable refresh in agency-datatable.js (fixed event name mismatch)
- [x] Test: Edit agency name, submit, verify DataTable updates and spinner hides (fixed caching issue)

---

# TODO-1930: Select Available Regency on Agency Editing

## Issue
- Edit agency form loads all regencies by province, but should only show available regencies for the agency
- Available regencies are those in the agency's province that have existing divisions

## Solution
- Change AJAX action from 'get_regencies_by_province' to 'get_available_regencies_for_agency_editing'
- Update query to filter regencies based on agency's province and existing divisions
- Update JS to pass agency_id instead of province_code

## Tasks
- [x] Change add_action in AgencyController.php from 'wp_ajax_get_regencies_by_province' to 'wp_ajax_get_available_regencies_for_agency_editing'
- [x] Rename and update getRegenciesByProvince method to getAvailableRegenciesForAgencyEditing with new query
- [x] Update edit-agency-form.js loadRegenciesByProvince to call new action with agency_id
- [x] Add debug logging for raw query as requested
- [x] Test: Edit agency form should only show regencies with divisions in the agency's province

---

# TODO-1955: Update Lokasi Kantor Pusat After Agency Editing

## Issue
- After successfully editing agency data (changing regency), the _agency_details.php template doesn't refresh to show updated data
- Data is correctly saved to database, but UI doesn't reflect changes until clicking other menus or rows
- Need to refresh the template via AJAX after edit

## Root Cause
- handleUpdated in agency-script.js uses response data from update, but may not have complete updated location data
- Cache may not be properly invalidated or response may not include joined province/regency names

## Solution
- Modify handleUpdated to reload agency data via AJAX instead of using response data
- Ensure cache is properly invalidated after update
- Add DataTable cache invalidation after agency update

## Tasks
- [x] Modify handleUpdated in agency-script.js to call loadAgencyData instead of displayData
- [x] Ensure invalidateAgencyCache is called in AgencyController update method (already present)
- [x] Add DataTable cache invalidation in handleUpdated
- [x] Test: Edit agency regency, verify _agency_details.php updates immediately without page reload
