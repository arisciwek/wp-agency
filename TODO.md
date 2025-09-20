# TODO: Add Jurisdiction Column to Division Datatable

## Completed Tasks
- [x] Modify DivisionModel::getDataTableData to include jurisdictions via GROUP_CONCAT with joins to wp_app_agency_jurisdictions and wi_regencies
- [x] Update DivisionController::handleDataTableRequest to include 'jurisdictions' in columns array and data response
- [x] Add 'Yuridiksi' header in _agency_division_list.php template (thead and tfoot)
- [x] Update division-datatable.js to include jurisdictions column in DataTable configuration

## Pending Tasks
- [ ] Test the datatable to ensure the new column displays correctly with comma-separated jurisdiction names
- [ ] Verify sorting and searching functionality on the jurisdictions column
- [ ] Check cache invalidation when jurisdiction data changes (may need additional cache clearing in jurisdiction CRUD operations)

## Notes
- Jurisdictions are displayed as comma-separated names of kabupaten/kota
- Search supports name, code, and jurisdiction names
- Sorting is enabled on all columns except actions
- Cache is handled via existing DataTable cache mechanism
