# TODO - WP Agency Changes

## Task: Replace "Departemen" with "Wewenang" in Employee Datatable

### Changes Made:

1. **Datatable Template** (`src/Views/templates/employee/partials/_employee_list.php`):
   - Changed "Departemen" to "Wewenang" in both thead and tfoot
   - Removed "Email" column from both thead and tfoot
   - Final columns: Nama, Jabatan, Wewenang, Cabang, Status, Aksi

2. **JavaScript DataTable** (`assets/js/employee/employee-datatable.js`):
   - Updated hardcoded thead to remove Email and change Departemen to Wewenang
   - Updated columns array to remove email column and change 'department' to 'role'
   - Adjusted column widths: name(18%), position(18%), role(18%), division_name(18%), status(13%), actions(15%)

3. **Controller** (`src/Controllers/Employee/AgencyEmployeeController.php`):
   - Removed 'email' from data array in handleDataTableRequest method
   - Changed 'department' to 'role' in data array
   - Replaced generateDepartmentsBadges method with getUserRole method
   - getUserRole method displays WordPress user roles with Indonesian translations:
     - 'agency' → 'Disnaker'
     - 'admin_dinas' → 'Admin Dinas'
     - 'admin_unit' → 'Admin Unit'
     - 'pengawas' → 'Pengawas'
     - etc.

4. **Data Source Change**:
   - Now displays actual WordPress user roles instead of department checkboxes (finance, operation, legal, purchase)
   - Uses get_userdata() to fetch user roles

### Files Modified:
- `src/Views/templates/employee/partials/_employee_list.php`
- `assets/js/employee/employee-datatable.js`
- `src/Controllers/Employee/AgencyEmployeeController.php`

### Testing Notes:
- Datatable should now show 6 columns instead of 7
- "Wewenang" column displays user role names in Indonesian
- Email column completely removed from display

## Task: Create Single Source of Truth for Roles in Staff Agency

### Changes Made:

1. **Class Activator** (`includes/class-activator.php`):
   - Added static method `getRoles()` to return the roles array with translations (single source of truth)
   - Modified `activate()` method to use `self::getRoles()` and exclude 'administrator' for role creation
   - Removed hardcoded `$roles_to_create` array in `activate()`

2. **Employee Controller** (`src/Controllers/Employee/AgencyEmployeeController.php`):
   - Modified `getUserRole()` method to use `\WP_Agency_Activator::getRoles()` instead of hardcoded $role_names array
   - Removed the hardcoded $role_names array

### Files Modified:
- `includes/class-activator.php`
- `src/Controllers/Employee/AgencyEmployeeController.php`

### Testing Notes:
- Datatable should continue to display roles correctly
- Role names should match the translations from the activator
- No duplication of role definitions
- Single source of truth established in WP_Agency_Activator::getRoles()

## Task: Adjust Agency Employee Form to Change Department to Wewenang (Roles)

### Changes Made:

1. **Form Templates Updated**:
   - `src/Views/templates/employee/forms/create-employee-form.php`: Changed "Wewenang" section from department checkboxes to multiple select for roles
   - `src/Views/templates/employee/forms/edit-employee-form.php`: Changed "Wewenang" section from department checkboxes to multiple select for roles
   - Both forms now load available roles from `WP_Agency_Activator::getRoles()`
   - Excludes 'administrator' role from selection

2. **JavaScript Updated**:
   - `assets/js/employee/create-employee-form.js`: Updated validation and form data handling for roles array instead of department checkboxes
   - `assets/js/employee/edit-employee-form.js`: Updated validation and form data handling for roles array

3. **Controller Updates Needed**:
   - `src/Controllers/Employee/AgencyEmployeeController.php`: Needs update to handle roles array in store() and update() methods
   - Should assign selected roles to WordPress user accounts using WP_User methods
   - Should retrieve current user roles for edit forms

4. **Validator Updates Needed**:
   - `src/Validators/Employee/AgencyEmployeeValidator.php`: Change `hasAtLeastOneDepartment()` to `hasAtLeastOneRole()`
   - Update validation calls in controller

5. **CSS Added**:
   - `assets/css/employee/employee-style.css`: Comprehensive styling for employee forms including multiple select styling

### Files Modified:
- `src/Views/templates/employee/forms/create-employee-form.php`
- `src/Views/templates/employee/forms/edit-employee-form.php`
- `assets/js/employee/create-employee-form.js`
- `assets/js/employee/edit-employee-form.js`
- `assets/css/employee/employee-style.css`

### Remaining Tasks:
1. Update `AgencyEmployeeController::store()` method to handle roles array and assign to user
2. Update `AgencyEmployeeController::update()` method to handle roles array and update user roles
3. Update `AgencyEmployeeValidator::hasAtLeastOneDepartment()` to `hasAtLeastOneRole()`
4. Test the complete flow from form submission to role assignment
5. Verify datatable displays updated roles correctly

### Status: Partially Completed - Forms Updated, Backend Logic Pending

## Additional Issue: Edit Form Not Showing Current User Roles

### Problem Description:
When editing an employee (e.g., "Hendro Wibowo;Staff;Pengawas Spesialis;Disnaker Provinsi Sumatera Utara Division Kabupaten Tapanuli Utara"), the datatable correctly shows the role "Pengawas Spesialis", but when opening the edit form, the role dropdown does not have the current roles pre-selected.

### Root Cause:
The edit form JavaScript is trying to load user roles via `loadCurrentUserRoles()` method, but the controller's `show()` method is not including user roles in the response data.

### Changes Made:
1. **Controller Update**: Modified `AgencyEmployeeController::show()` to include `user_roles` in the response data
2. **JavaScript Update**: Updated `edit-employee-form.js` to use `data.user_roles` for pre-selecting roles in the multiple select
3. **CSS Added**: Created `assets/css/employee/employee-style.css` with comprehensive styling for employee forms including multiple select styling

### Remaining Issues:
1. Backend controller methods (`store()` and `update()`) still need to handle roles array and assign to WordPress users
2. Validator needs to be updated to validate roles instead of departments
3. Test the complete flow from form submission to role assignment

### Files Modified:
- `src/Controllers/Employee/AgencyEmployeeController.php` (show method updated)
- `assets/js/employee/edit-employee-form.js` (showEditForm method updated)
- `assets/css/employee/employee-style.css` (new file created)

### Next Steps:
1. Update controller store() and update() methods to handle roles
2. Update validator to validate roles
3. Test the complete role assignment workflow
4. Verify datatable displays updated roles correctly

