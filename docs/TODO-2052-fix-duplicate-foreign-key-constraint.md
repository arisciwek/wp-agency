# TODO-2052: Fix Duplicate Foreign Key Constraint Error

## Issue
After running the replacement command `grep -rl "app_divisions" . | xargs sed -i 's/app_divisions/app_agency_divisions/g'`, plugin activation fails with database error:
```
[08-Oct-2025 07:26:33 UTC] WordPress database error Duplicate foreign key constraint name 'wp_app_agency_employees_ibfk_1' for query ALTER TABLE wp_app_agency_employees
```

## Root Cause
The foreign key constraint `wp_app_agency_employees_ibfk_1` already exists on the `wp_app_agency_employees` table, but the `add_foreign_keys()` method in `AgencyEmployeesDB` attempts to add it again without checking if it already exists.

## Target
Modify `src/Database/Tables/AgencyEmployeesDB.php` to check if the foreign key constraint exists before attempting to add it. If it exists, either skip adding or drop and re-add.

## Files
- `src/Database/Tables/AgencyEmployeesDB.php` - Modify `add_foreign_keys()` method to check for existing constraint

## Status
Pending
