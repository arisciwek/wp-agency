# TODO-2051: Remove Membership from Plugin

### Description
Remove all membership functionality from the wp-agency plugin, including tabs, controllers, models, database tables, demo data, and related assets.

### Steps to Complete
- [x] Delete membership template files (tab-membership-features.php, tab-membership-levels.php)
- [x] Delete membership CSS files (agency-membership-levels-tab-style.css, membership-features-tab-style.css)
- [x] Delete membership JS files (agency-membership-features-tab-script.js, agency-membership-levels-tab-script.js)
- [x] Remove membership tabs from settings_page.php
- [x] Remove membership cases from class-dependencies.php enqueue methods
- [x] Remove membership handlers and logic from SettingsController.php
- [x] Remove membership data retrieval from AgencyController.php
- [x] Remove membership table creation from Installer.php
- [x] Delete membership controller directory (src/Controllers/Membership/)
- [x] Delete membership model directory (src/Models/Membership/)
- [x] Delete membership validator directory (src/Validators/Membership/)
- [x] Delete membership demo data files (MembershipDemoData.php, etc.)
- [x] Delete membership database table files (AgencyMembershipFeaturesDB.php, etc.)
- [x] Remove membership controller initialization from wp-agency.php
- [x] Test settings page loads without membership tabs
- [x] Verify agency dashboard functionality
- [x] Ensure no broken references or errors

### Files to Edit/Delete
- **Delete:**
  - src/Views/templates/settings/tab-membership-features.php
  - src/Views/templates/settings/tab-membership-levels.php
  - assets/css/settings/agency-membership-levels-tab-style.css
  - assets/css/settings/membership-features-tab-style.css
  - assets/js/settings/agency-membership-features-tab-script.js
  - assets/js/settings/agency-membership-levels-tab-script.js
  - src/Controllers/Membership/ (entire directory)
  - src/Models/Membership/ (entire directory)
  - src/Validators/Membership/ (entire directory)
  - src/Database/Demo/MembershipDemoData.php
  - src/Database/Demo/MembershipFeaturesDemoData.php
  - src/Database/Demo/MembershipGroupsDemoData.php
  - src/Database/Demo/MembershipLevelsDemoData.php
  - src/Database/Tables/AgencyMembershipFeaturesDB.php
  - src/Database/Tables/AgencyMembershipLevelsDB.php
  - src/Database/Tables/AgencyMembershipsDB.php

- **Edit:**
  - src/Views/templates/settings/settings_page.php (remove membership tabs)
  - includes/class-dependencies.php (remove membership enqueue cases)
  - src/Controllers/SettingsController.php (remove membership handlers and logic)
  - src/Controllers/AgencyController.php (remove membership data from show method)
  - src/Database/Installer.php (remove membership table creation)
  - TODO.md (add TODO-2051 entry)

### Dependent Files
None

### Followup
- Verify settings page functionality
- Test agency dashboard
- Check for any remaining membership references
