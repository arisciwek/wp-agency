## WP Agency Plugin v1.0.5

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
