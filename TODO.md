# TODO: Add Jurisdiction Demo Data Generation Button

## Completed Tasks
- [x] Create JurisdictionDB.php with schema for app_agency_jurisdictions table
- [x] Update Installer.php to include app_agency_jurisdictions in table list and class mappings
- [x] Add foreign key constraints handling for JurisdictionDB in Installer.php
- [x] Create JurisdictionDemoData.php to generate demo data from CSV
- [x] Change table name to app_agency_jurisdictions to match existing pattern
- [x] Update activator/deactivator to handle the new table
- [x] Create TODO.md with task breakdown
- [x] Verify PHP syntax for all modified files
- [x] Create JurisdictionData.php with static array data (replacing CSV)
- [x] Modify JurisdictionDemoData.php to use static array instead of CSV
- [x] Fix primary regency validation to use division.regency_id instead of hardcoded values
- [x] Add Jurisdiction button in tab-demo-data.php
- [x] Add 'jurisdiction' case in SettingsController getGeneratorClass method
- [x] Add 'jurisdiction' case in SettingsController handle_check_demo_data method

## Pending Tasks
- [ ] Test table creation by running plugin activation or installer
- [ ] Test demo data generation by running JurisdictionDemoData
- [ ] Verify foreign key constraints work properly
- [ ] Test the new Jurisdiction button in settings page
- [ ] Ensure is_primary flag is set correctly for division's main regency

## Notes
- Table name changed to app_agency_jurisdictions to match existing pattern
- JurisdictionDB stores many-to-many relations between divisions and regencies
- is_primary = 1 for the regency matching division.regency_id (cannot be moved)
- Data now populated from static array JurisdictionData instead of CSV
- Foreign keys to app_divisions and wi_regencies with CASCADE delete
- Updated activator/deactivator to handle the new table
- Added Jurisdiction generation button with dependency on divisions
