# TODO-2050: Remove Agency Membership Tab

### Description
Remove the agency membership tab from the agency dashboard. This includes deleting the partial template file, removing the tab from the right panel, and deleting related CSS and JavaScript files.

### Steps to Complete
- [x] Delete the partial file: `src/Views/templates/agency/partials/_agency_membership.php`
- [x] Remove the membership tab from `src/Views/templates/agency-right-panel.php`
- [x] Delete the related CSS file: `assets/css/agency/agency-membership-tab-style.css`
- [x] Delete the related JS file: `assets/js/agency/agency-membership.js`
- [x] Check `assets/js/agency/agency-script.js` for any membership-related code and remove if necessary
- [x] Check `includes/class-dependencies.php` for any membership dependencies and remove if necessary
- [ ] Test that the agency dashboard loads without errors after removal

### Files to Edit/Delete
- `src/Views/templates/agency/partials/_agency_membership.php` (delete)
- `src/Views/templates/agency-right-panel.php`
- `assets/css/agency/agency-membership-tab-style.css` (delete)
- `assets/js/agency/agency-membership.js` (delete)
- `assets/js/agency/agency-script.js`
- `includes/class-dependencies.php`

### Dependent Files
None

### Followup
- Verify agency dashboard functionality
- Ensure no broken links or missing assets
