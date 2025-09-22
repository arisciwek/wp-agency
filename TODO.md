# TODO: Update References from provinsi_id/regency_id to provinsi_code/regency_code

## Tasks
- [x] Update comments in src/Database/Demo/DivisionDemoData.php: Change provinsi_id to provinsi_code, regency_id to regency_code in database design comment.
- [x] Update comment in src/Database/Demo/Data/JurisdictionData.php: Change division.regency_id to division.regency_code.
- [x] Update comments in src/Database/Demo/JurisdictionDemoData.php: Change regency_id to regency_code.
- [x] Update src/Controllers/Division/DivisionController.php: Remove code that sets provinsi_id and regency_id from codes.
- [x] Update assets/js/division/edit-division-form.js: Change division.provinsi_id to division.provinsi_code, division.regency_id to division.regency_code.

## Followup
- [x] Verify changes by checking if code runs without errors, perhaps test division forms.
- [x] Fix migration constraint name from ibfk_2 to ibfk_1 in Migration.php
- [x] Add migration call to activator
- [x] Fix deactivator to drop foreign key constraints before dropping tables
- [x] Fix deactivator table drop order and add missing app_agency_membership_feature_groups table
- [x] Add missing foreign key constraint drops in deactivator
- [x] Handle old jurisdiction table name (app_jurisdictions) and its constraints in deactivator
- [x] Fix JurisdictionDemoData.php duplicate regency insertion bug by correcting check order
- [x] Fix edit division form not showing province/regency data by converting codes to ids in controller and updating JS to use ids
- [x] Change jurisdiction selection from Select2 to checkboxes for better UX and simpler implementation
- [x] Fix jurisdiction query to show only available regencies (not assigned to any division) plus current division's assignments using single query with LEFT JOINs
- [ ] Debug Kota Depok division - Kabupaten Bogor should be primary, Kota Depok should be primary, but currently Kota Depok is missing and Kabupaten Bogor is non-primary. Added comprehensive logging and flags to all jurisdiction objects for easier debugging.

## New Tasks: Implement Jurisdiction Checkboxes in Create Division Form
- [x] Update src/Views/templates/division/forms/create-division-form.php: Change jurisdiction field from multiple select to div with class jurisdiction-checkboxes like in edit form
- [x] Update assets/js/division/create-division-form.js: Remove initializeJurisdictionSelect method and replace with initializeJurisdictionCheckboxes similar to edit form
- [x] Update assets/js/division/create-division-form.js: Add event listeners for province and regency select changes to load jurisdictions via AJAX
- [x] Update assets/js/division/create-division-form.js: Handle case when no province/regency selected by disabling jurisdiction field or showing message
- [ ] Test create division form to ensure checkboxes load correctly when province/regency is selected and form submission works with jurisdictions array

## New Tasks: Simplify Jurisdiction Query
- [x] Simplify getAvailableRegenciesForAgency query in JurisdictionModel.php to use simpler LEFT JOIN structure like the example query
- [x] Ensure query correctly shows only unassigned regencies for create mode (WHERE division_regency_code IS NULL)
- [ ] Ensure query correctly shows unassigned + current division's assignments for edit mode
- [x] Test both create and edit forms to verify jurisdiction filtering works correctly (query simplified, ready for testing)

## New Tasks: Filter Provinces for Agency Creation
- [x] Move province filtering from static (page load) to dynamic AJAX loading when "Tambah agency" button is clicked
- [x] Add getAvailableProvinces AJAX action in AgencyController.php
- [x] Add getRegenciesByProvince AJAX action in AgencyController.php
- [x] Update create-agency-form.js to load provinces on showModal and regencies on province change
- [x] Fix JavaScript initialization issues to prevent undefined form errors
- [x] Fix "Tambah Agency" button not working - added modal structure and improved click handler
- [x] Fix regency select remaining disabled - corrected database query to use province_id instead of province_code
- [x] Test dynamic province/regency loading in create agency form
