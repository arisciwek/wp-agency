# TODO List for WP Agency Plugin

## TODO-2053: Fix Jurisdictions Table Migration Error
- Issue: Migration fails with "Unknown column 'regency_id' in 'wp_app_agency_jurisdictions'" during activation.
- Root Cause: Migration assumes old 'regency_id' column exists, but table already has 'jurisdiction_code'.
- Target: Update migration to check for correct column and only migrate if old column exists.
- Files: src/Database/Migration.php
- Status: Completed

## TODO-2052: Fix Duplicate Foreign Key Constraint Error
- Issue: Plugin activation fails with "Duplicate foreign key constraint name 'wp_app_agency_employees_ibfk_1'" after table name replacement.
- Root Cause: Foreign key constraint already exists but add_foreign_keys() doesn't check before adding.
- Target: Modify AgencyEmployeesDB::add_foreign_keys() to check existing constraint before adding.
- Files: src/Database/Tables/AgencyEmployeesDB.php
- Status: Completed

## TODO-2051: Remove Membership from Plugin
- Issue: Remove all membership functionality from the wp-agency plugin, including tabs, controllers, models, database tables, demo data, and related assets. Fixed fatal error from missing controller reference.
- Root Cause: Membership functionality not needed in this plugin.
- Target: Delete all membership-related files, remove tabs from settings, clean up controllers and dependencies.
- Files: Multiple files (see docs/TODO-2051-remove-membership-from-plugin.md for complete list)
- Status: Completed
