# WP Agency Plugin - Changelog

## [1.0.5] - 2024-12-XX

### Fixed
- **PHP Fatal Error in AgencyEmployeeValidator**: Fixed undefined property error where `$this->model` was used instead of `$this->employee_model` in the `validateUpdate` method.

### Added
- **Multi-Role Support in Staff DataTable**: Enhanced employee datatable to display all user roles instead of just the primary role. Increased column width from 18% to 22% to accommodate multiple roles.
- **Multi-Column Search in Staff DataTable**: Improved global search functionality to search across all columns including name, position, roles, division, and status. Added support for multiple word searches with AND logic.
- **Multi-Jurisdiction Support**: Implemented additional jurisdictions for divisions in Banten and Sumatera Barat agencies, allowing coverage of multiple regencies within provinces.
- **New Company Tab**: Added new "New Company" tab in agency right panel displaying branches without assigned inspectors. Includes DataTable with columns for code, company, unit, jurisdiction, and actions. Features inspector assignment functionality with proper permission validation.
- **Extra Branch Generation for Testing**: Added method to generate additional test branches with NULL inspector_id for comprehensive testing of assign inspector functionality.
- **Unique Index for Customer Regency Branches**: Added unique constraint on (customer_id, regency_id) to prevent duplicate branches in the same regency for the same customer.

### Technical Changes
- Modified `getUserRole` method in AgencyEmployeeController to return formatted string of all user roles
- Updated `getDataTableData` method in AgencyEmployeeModel to include search in status and roles columns using subquery to wp_usermeta
- Enhanced JurisdictionData.php with additional regency mappings for Banten and Sumatera Barat divisions
- Created NewCompanyController, NewCompanyModel, and NewCompanyValidator for handling new company tab functionality
- Implemented caching strategy for new company data with 2-hour expiry
- Added unique index migration in BranchesDB and Installer classes
- Integrated inspector assignment with division-based filtering and privilege validation

### Files Modified
- `src/Validators/Employee/AgencyEmployeeValidator.php`
- `src/Controllers/Employee/AgencyEmployeeController.php`
- `assets/js/employee/employee-datatable.js`
- `src/Models/Employee/AgencyEmployeeModel.php`
- `src/Database/Demo/Data/JurisdictionData.php`
- `src/Controllers/Company/NewCompanyController.php`
- `src/Models/Company/NewCompanyModel.php`
- `src/Validators/Company/NewCompanyValidator.php`
- `src/Views/templates/company/partials/_new_company_list.php`
- `assets/js/company/new-company-datatable.js`
- `assets/css/company/new-company-style.css`
- `src/Views/templates/agency-right-panel.php`
- `assets/js/agency/agency-script.js`
- `includes/class-dependencies.php`
- `includes/class-init-hooks.php`
- `../wp-customer/src/Database/Demo/BranchDemoData.php`
- `../wp-customer/src/Database/Tables/BranchesDB.php`
- `../wp-customer/src/Database/Installer.php`

### Testing
- Verified PHP fatal error is resolved in employee update functionality
- Confirmed multi-role display works correctly in staff datatable
- Tested multi-column search functionality across all relevant columns
- Validated multi-jurisdiction data generation for Banten and Sumatera Barat
- Ensured New Company tab loads correctly with proper data filtering
- Tested inspector assignment with division-based filtering and permissions
- Verified unique constraint prevents duplicate branches in same regency
- Confirmed all assets load without errors and DataTables function properly

## [1.0.4] - 2025-09-30

### Fixed
- **Employee Count Discrepancy**: Fixed issue where employee count showed agency-specific numbers on page reload but global totals on menu switch. Dashboard now always displays global statistics.
- **Empty Select Lists in Edit Agency Form**: Resolved empty province and regency dropdowns in agency edit form by implementing proper AJAX loading of all available options.
- **DataTables ParserError in Agency Employee List**: Fixed parser error caused by invalid database queries and incorrect parameter handling. Improved permission checks to skip unauthorized data instead of failing.
- **Persistent Spinning Icon on Employee Tab**: Resolved indefinite loading spinner when switching tabs by enhancing error handling in DataTable dataSrc, adding timeouts, and preventing overlapping operations.
- **DataTable Not Refreshing After Agency Edit**: Fixed UI not updating after successful agency edits by correcting cache invalidation and DataTable refresh logic.
- **Incorrect Regency Selection in Agency Editing**: Updated edit form to show only available regencies (those with existing divisions in the agency's province) instead of all regencies.
- **Agency Details Not Updating After Edit**: Fixed agency details template not refreshing after edits by implementing AJAX data reload instead of using cached response data.
- **Dashboard Data Mismatch**: Corrected dashboard statistics to show unrestricted global totals instead of user-permission-restricted counts, with proper cache invalidation on CRUD operations.

### Technical Changes
- Modified `loadStats()` in agency-script.js to always pass `id: 0` for global statistics
- Added `getAvailableProvincesForAgencyEditing()` method and AJAX action in AgencyController
- Fixed search query in AgencyEmployeeModel to use valid database columns
- Enhanced `dataSrc` in employee-datatable.js to handle error responses and added AJAX timeouts
- Implemented cache invalidation for DataTable and agency data in various controller update methods
- Added unrestricted count methods (`getTotalCountUnrestricted()`) in AgencyModel and DivisionModel
- Updated permission handling to skip unauthorized employees instead of throwing exceptions

### Files Modified
- `assets/js/agency/agency-script.js`
- `assets/js/agency/edit-agency-form.js`
- `assets/js/agency/agency-datatable.js`
- `assets/js/employee/employee-datatable.js`
- `src/Controllers/AgencyController.php`
- `src/Controllers/Division/DivisionController.php`
- `src/Controllers/Employee/AgencyEmployeeController.php`
- `src/Models/Agency/AgencyModel.php`
- `src/Models/Division/DivisionModel.php`
- `src/Models/Employee/AgencyEmployeeModel.php`
- `src/Views/templates/forms/edit-agency-form.php`
- `src/Cache/AgencyCacheManager.php`

### Testing
- Verified employee count consistency across page reloads and menu switches
- Confirmed province and regency selects populate correctly in edit forms
- Tested DataTable loading without parser errors in employee lists
- Ensured loading spinners hide properly after tab switches
- Validated DataTable refreshes immediately after agency edits
- Checked regency filtering works for agency editing
- Confirmed agency details update without page reload
- Verified dashboard shows accurate global statistics

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
