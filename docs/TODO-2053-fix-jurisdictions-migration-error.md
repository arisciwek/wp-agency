# TODO-2053: Fix Jurisdictions Table Migration Error

## Issue
During plugin activation, the migration fails with errors:
```
Unknown column 'regency_id' in 'wp_app_agency_jurisdictions'
```

## Root Cause
The migration code in `Migration::migrateJurisdictionsTable()` assumes the jurisdictions table has a 'regency_id' column that needs to be migrated to 'jurisdiction_code', but the current table schema already has 'jurisdiction_code' column and no 'regency_id' column.

## Target
Update the migration logic to check for the correct column name ('jurisdiction_code') and only run migration if the old 'regency_id' column exists.

## Files
- `src/Database/Migration.php` - Fix `migrateJurisdictionsTable()` method

## Status
Completed
