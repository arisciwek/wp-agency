## New Tasks: Select Available Provinces on Agency Creation
- [x] Create method getAvailableProvincesForAgencyCreation() in src/Controllers/AgencyController.php using raw query: SELECT p.id, p.name FROM wp_wi_provinces p LEFT JOIN wp_app_agencies a ON a.provinsi_code = p.code WHERE a.provinsi_code IS NULL
- [x] Add AJAX action hook 'wp_ajax_get_available_provinces_for_agency_creation' in AgencyController.php
- [x] Update assets/js/agency/create-agency-form.js to use the new AJAX call for loading available provinces
- [x] Update src/Validators/AgencyValidator.php if needed for validation
- [ ] Test the create agency form to ensure only available provinces (not assigned to any agency) are shown
