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
