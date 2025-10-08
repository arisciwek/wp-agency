# TODO List for WP Agency Plugin

## TODO-2050: Remove Agency Membership Tab
- Issue: Remove the agency membership tab from the agency dashboard, including deleting the partial file, removing the tab from right panel, and deleting related CSS and JS files.
- Root Cause: Membership functionality not applicable at agency level.
- Target: Delete partial file, remove tab, delete CSS/JS, check for related code in script and dependencies.
- Files: src/Views/templates/agency/partials/_agency_membership.php (delete), src/Views/templates/agency-right-panel.php, assets/css/agency/agency-membership-tab-style.css (delete), assets/js/agency/agency-membership.js (delete), assets/js/agency/agency-script.js, includes/class-dependencies.php
- Status: Completed

## TODO-2049: Change Division Name
- Issue: Division name generation needs to be changed from '%s Division %s' to 'UPT %s' using regency name for consistency.
- Root Cause: Previous naming convention not aligned with requirements.
- Target: Update generatePusatDivision, generateCabangDivisions, and static divisions array to use 'UPT %s' format.
- Files: src/Database/Demo/DivisionDemoData.php, docs/TODO-2049-change-division-name.md
- Status: Completed

## TODO-2048: Fix Company DataTable Cache Clearing After Inspector Assignment
- Issue: After successful inspector assignment in wp-agency plugin, the company datatable in wp-customer plugin does not immediately update. The cached data persists for 2 minutes before refreshing.
- Root Cause: The DataTable cache in wp-customer ('company_list' context) is not invalidated when inspector assignments are made in wp-agency.
- Target: Modified the assignInspector method in NewCompanyController.php to clear the wp-customer DataTable cache after successful assignment.
- Files: src/Controllers/Company/NewCompanyController.php
- Status: Completed

## TODO-2047: Fix Inspector Assignment Count Display
- Issue: After successful inspector assignment, when assigning again with the same inspector, the modal displays "Pengawas ini saat ini memiliki 0 penugasan" instead of the actual assignment count.
- Root Cause: JavaScript onInspectorChange only sets placeholder text without loading actual data from database.
- Target: Modify getAvailableInspectors to include assignment count, update JS to display count in options, update onInspectorChange to show actual count.
- Files: assets/js/company/new-company-datatable.js, src/Controllers/Company/NewCompanyController.php, src/Models/Company/NewCompanyModel.php, src/Validators/Company/NewCompanyValidator.php
- Status: Completed

## TODO-2046: Ubah Teks Nama Agency dan New Company
- Issue: Teks "Nama Agency" dan "Cabang" di datatable agency perlu diubah untuk konsistensi terminologi. Juga teks "New Company" di tab agency right panel perlu diubah ke bahasa Indonesia.
- Root Cause: Inconsistent terminology in UI elements.
- Target: Change "Nama Agency" to "Disnaker", "Cabang" to "Unit Kerja", "New Company" to "Perusahaan Baru".
- Files: assets/js/agency/agency-datatable.js, src/Views/templates/agency-right-panel.php
- Status: Completed
