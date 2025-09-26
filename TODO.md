## New Tasks: Select Available Provinces on Agency Creation
- [x] Create method getAvailableProvincesForAgencyCreation() in src/Controllers/AgencyController.php using raw query: SELECT p.id, p.name FROM wp_wi_provinces p LEFT JOIN wp_app_agencies a ON a.provinsi_code = p.code WHERE a.provinsi_code IS NULL
- [x] Add AJAX action hook 'wp_ajax_get_available_provinces_for_agency_creation' in AgencyController.php
- [x] Update assets/js/agency/create-agency-form.js to use the new AJAX call for loading available provinces
- [x] Update src/Validators/AgencyValidator.php if needed for validation
- [ ] Test the create agency form to ensure only available provinces (not assigned to any agency) are shown

## New Tasks: Select Available Regencies on Agency Creation
- [x] Create method getAvailableRegenciesForAgencyCreation() in src/Controllers/AgencyController.php using raw query: SELECT r.id,r.name FROM wp_wi_provinces p LEFT JOIN wp_app_agencies a ON a.provinsi_code = p.code LEFT JOIN wp_wi_regencies r on r.province_id=p.id WHERE a.provinsi_code IS NULL AND p.code=province_code
- [x] Add AJAX action hook 'wp_ajax_get_available_regencies_for_agency_creation' in AgencyController.php
- [x] Update assets/js/agency/create-agency-form.js to use the new AJAX call for loading available regencies
- [ ] Test the create agency form to ensure regencies load correctly when province is selected

