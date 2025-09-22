# WP Agency Plugin - Changelog

## [1.0.1] - 2024-12-XX

### Fixed
- **Jurisdiction Removal Issue**: Fixed bug where unchecking jurisdiction checkboxes in division edit form didn't remove them from database. HTML forms only send checked values, so backend now handles empty selections properly.
- **DataTable Caching**: Removed caching from division DataTable to ensure immediate display of updated data after edits.
- **Primary Jurisdiction Management**: Implemented automatic primary jurisdiction assignment based on division's regency_code. Jurisdiction matching division's location is now automatically marked as primary.

### Technical Changes
- Modified `DivisionController::update()` to always process jurisdictions with empty array default
- Disabled DataTable response caching in `handleDataTableRequest()`
- Updated primary jurisdiction logic to be determined by division's regency_code rather than preserved state

### Files Modified
- `src/Controllers/Division/DivisionController.php`

### Testing
- Verified jurisdiction addition/removal works correctly
- Confirmed DataTable shows fresh data immediately
- Validated primary jurisdiction flags update automatically

## [1.0.0] - 2024-12-XX
- Initial release
- Basic division and jurisdiction management
- DataTable integration
- Cache support
- User permission system
