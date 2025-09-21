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
