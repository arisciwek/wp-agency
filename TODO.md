# Fix Division Demo Data Generation Error

## Issue
Duplicate entry error when generating division demo data due to non-unique division names violating the unique key (agency_id, name) in wp_app_divisions table.

## Root Cause
1. In `generateCabangDivisions()`, the exclusion of agency's regency uses `$agency->regency_id` which is not set, causing cabang divisions to potentially select the same regency as the agency.
2. Name generation for pusat and cabang divisions uses the same format, leading to duplicate names if same regency is selected.

## Plan
- [x] Fix exclusion logic: Change `$excluded_regencies = [$agency->regency_id];` to `$excluded_regencies = [$this->getRegencyIdByCode($agency->regency_code)];`
- [x] Modify cabang division name format to include 'Cabang' for uniqueness: `sprintf('%s Division Cabang %s', $agency->name, $regency_name)`
- [x] Keep pusat division name format as is

## Files to Edit
- `src/Database/Demo/DivisionDemoData.php`

## Testing
- Run demo data generation and verify no duplicate name errors
- Check that pusat and cabang divisions have distinct names
