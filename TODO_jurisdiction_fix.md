# Fix Available Jurisdictions Query for Edit Mode

## Issue
The `getAvailableRegenciesForAgency` method was returning ALL regencies in the province for edit mode, instead of only available ones (not assigned to other divisions in the same agency).

## Root Cause
Edit mode query was missing the logic to exclude regencies already assigned to other divisions, causing conflicts when multiple divisions try to claim the same jurisdiction.

## Plan
- [x] Modify edit mode query to exclude regencies assigned to other divisions (but allow current division's assignments)
- [x] Add LEFT JOIN with agency_jurisdictions and divisions tables with proper conditions
- [x] Add r.code to SELECT fields for jurisdiction matching
- [x] Update cache key to '_v5' to invalidate old cached results
- [x] Remove unnecessary fields (province_id) from SELECT

## Files to Edit
- `src/Models/Division/DivisionModel.php`

## Testing
- Test edit division form - should show regencies not assigned to other divisions in same agency
- Test create division form - should show only unassigned regencies
- Verify current assignments are properly pre-selected in edit mode
- Check that no duplicate jurisdiction assignments occur
