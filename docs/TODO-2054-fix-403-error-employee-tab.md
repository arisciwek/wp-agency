# TODO-2054: Fix 403 Error When Opening Employee Tab

## Description
There is a 403 Forbidden error when opening the Employee tab in the customer detail panel, while the Branch tab opens without issues.

## Current Status
- Employee tab: Returns 403 error
- Branch tab: Works normally

## Investigation Findings
1. **Code Structure Comparison**:
   - Both tabs follow similar patterns: AJAX call for create button, then DataTable initialization
   - Employee uses `handle_customer_employee_datatable` action
   - Branch uses `handle_branch_datatable` action

2. **Controller Registration**:
   - Both `CustomerEmployeeController` and `BranchController` are instantiated in `wp-customer.php`
   - AJAX actions are registered in constructors

3. **Permission Logic**:
   - Employee validator has more complex permission checks including staff member validation
   - Branch validator uses simpler relation-based checks

4. **JavaScript Differences**:
   - Employee DataTable shows toast notifications on AJAX errors
   - Branch DataTable only logs to console

## Possible Causes
1. **Permission Issue**: User lacks permission to view employees but can view branches
2. **AJAX Action Not Registered**: Employee controller not properly loaded
3. **Validator Bug**: Permission check failing incorrectly
4. **Nonce/Authentication Issue**: Different nonce handling

## Steps to Fix
1. Verify AJAX action registration for employee datatable
2. Compare permission validation between employee and branch
3. Check autoloader functionality for Employee namespace
4. Test with different user roles (admin, customer owner, staff)
5. Add debug logging to identify exact failure point
6. Simplify permission checks if overly restrictive

## Files to Check
- `src/Controllers/Employee/CustomerEmployeeController.php`
- `src/Validators/Employee/CustomerEmployeeValidator.php`
- `assets/js/employee/employee-datatable.js`
- `wp-customer.php` (controller instantiation)
- `includes/class-autoloader.php`

## Testing
- Test with admin user
- Test with customer owner
- Test with branch admin
- Test with staff member
- Check browser network tab for exact error response
- Check WordPress debug logs

## Priority
High - Blocks employee management functionality

## Assigned
[To be assigned]

## Created
2025-01-15
