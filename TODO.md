# TODO: Add JurisdictionDB Table

## Completed Tasks
- [x] Create JurisdictionDB.php with schema for app_agency_jurisdictions table
- [x] Update Installer.php to include app_agency_jurisdictions in table list and class mappings
- [x] Add foreign key constraints handling for JurisdictionDB in Installer.php
- [x] Create JurisdictionDemoData.php to generate demo data from CSV
- [x] Change table name to app_agency_jurisdictions to match existing pattern
- [x] Update activator/deactivator to handle the new table
- [x] Create TODO.md with task breakdown
- [x] Verify PHP syntax for all modified files

## Pending Tasks
- [ ] Test table creation by running plugin activation or installer
- [ ] Test demo data generation by running JurisdictionDemoData
- [ ] Verify foreign key constraints work properly
- [ ] Check CSV parsing logic handles all cases correctly
- [ ] Ensure is_primary flag is set correctly for division's main regency

## Notes
- Table name changed to app_agency_jurisdictions to match existing pattern
- JurisdictionDB stores many-to-many relations between divisions and regencies
- is_primary = 1 for the regency matching division.regency_id (cannot be moved)
- Data populated from docs/wp_app_juridictions.csv
- Foreign keys to app_divisions and wi_regencies with CASCADE delete
- Updated activator/deactivator to handle the new table
