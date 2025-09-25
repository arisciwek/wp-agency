# TODO-0941: Select Available Province on Agency Creation

## Tasks
- [x] Add new method `getAvailableProvincesForAgencyCreation()` in `src/Controllers/AgencyController.php`
- [x] Add action hook `wp_ajax_get_available_provinces_for_agency_creation` in constructor
- [x] Update `assets/js/agency/create-agency-form.js` to use new action
- [x] Test the functionality (syntax check passed)

## Details
- Query: SELECT p.code, p.name FROM wp_wi_provinces p LEFT JOIN wp_app_agencies a ON a.provinsi_code = p.code WHERE a.provinsi_code IS NULL
- Returns provinces not assigned to any agency
- Used in create agency form to populate province dropdown
