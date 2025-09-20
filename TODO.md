# TODO: Add Jurisdiction Field to Division Forms with Complex Constraints

## Completed Tasks
- [x] Analyze current forms and code structure
- [x] Plan implementation with constraints (exclusivity per agency, is_primary protection)
- [x] Add jurisdiction field to create-division-form.php (Select2 multiple select)
- [x] Add jurisdiction field to edit-division-form.php (Select2 with current + available options)
- [x] Add getAvailableJurisdictions AJAX endpoint to DivisionController.php
- [x] Add saveJurisdictions, getJurisdictionsByDivision, getAvailableRegenciesForAgency methods to DivisionModel.php
- [x] Update create-division-form.js to load and handle jurisdiction selection
- [x] Update edit-division-form.js to load current jurisdictions, available options, and enforce is_primary validation
- [x] Update store/update methods in DivisionController.php to save jurisdiction data
- [x] Add cache invalidation for jurisdiction changes

## Pending Tasks
- [ ] Test complete flow with exclusivity and is_primary constraints
- [x] Ensure Select2 library is loaded
- [x] Verify AJAX endpoints return correct data (added table existence check)
- [x] Fix demo data jurisdiction assignments (removed Bogor from DKI Jakarta, ensured no duplicates)
- [x] Add Admin column to division datatable

## Notes
- Jurisdictions are regencies assigned to divisions within an agency
- Constraints: regencies can only be in one division per agency (exclusivity)
- is_primary jurisdictions cannot be removed during edit
- Available options = all regencies in agency minus those already assigned to other divisions
- Need dynamic option loading based on agency and current assignments
- Cache management required for jurisdiction availability
- Primary jurisdiction must match division's regency_id
- All jurisdictions must be within agency's province
- No duplicate regencies across divisions in same agency
