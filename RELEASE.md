## WP Agency Plugin v1.0.10

### Removed
- **Agency Membership Tab**: Removed the agency membership tab from the agency dashboard as membership functionality is not applicable at agency level. Deleted related partial file, CSS, and JS files.

### Fixed
- **Company DataTable Cache Clearing After Inspector Assignment**: Fixed issue where company datatable in wp-customer plugin did not immediately update after inspector assignment. Cache now clears properly after successful assignment.
- **Inspector Assignment Count Display**: Fixed modal displaying incorrect assignment count ("0 penugasan") when re-assigning the same inspector. Now shows actual assignment count from database.

### Changed
- **Division Name Generation**: Changed division name format from '%s Division %s' to 'UPT %s' using regency name for consistency.
- **Agency and Company Terminology**: Updated UI text for consistency: "Nama Agency" to "Disnaker", "Cabang" to "Unit Kerja", "New Company" to "Perusahaan Baru".

### Technical Changes
- Removed agency membership tab from right panel and deleted related files
- Modified assignInspector method to clear wp-customer DataTable cache
- Updated getAvailableInspectors to include assignment count, modified JS to display actual count
- Changed generatePusatDivision, generateCabangDivisions, and static divisions array to use 'UPT %s' format
- Updated agency datatable and right panel text labels

### Files Modified
- src/Views/templates/agency/partials/_agency_membership.php (deleted)
- src/Views/templates/agency-right-panel.php
- assets/css/agency/agency-membership-tab-style.css (deleted)
- assets/js/agency/agency-membership.js (deleted)
- assets/js/agency/agency-script.js
- includes/class-dependencies.php
- src/Controllers/Company/NewCompanyController.php
- assets/js/company/new-company-datatable.js
- src/Models/Company/NewCompanyModel.php
- src/Validators/Company/NewCompanyValidator.php
- src/Database/Demo/DivisionDemoData.php
- assets/js/agency/agency-datatable.js

### Testing
- Verified agency membership tab is removed and no errors occur
- Confirmed company datatable updates immediately after inspector assignment
- Tested inspector assignment modal shows correct count
- Validated division names use new 'UPT %s' format
- Checked UI text changes are applied correctly
