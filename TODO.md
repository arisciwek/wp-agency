# # TODO List for WP Agency Plugin

## TODO-2062: Add Multiple Roles to Agency Employee Users
- [x] Fix AgencyEmployeeDemoData to use full roles array from AgencyEmployeeUsersData
- [x] Remove primary role extraction logic
- [x] Update user generation to pass 'roles' parameter instead of single 'role'
- [x] Verify all 60 employee users receive their specific roles (various role combinations)
- [x] Create documentation file TODO-2062-add-multiple-roles-agency-employee-users.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2062-add-multiple-roles-agency-employee-users.md](docs/TODO-2062-add-multiple-roles-agency-employee-users.md)

---

## TODO-2061: Add Multiple Roles to Agency Users
- [x] Fix AgencyDemoData to use roles array from AgencyUsersData
- [x] Update user generation to pass 'roles' parameter instead of hardcoded 'role'
- [x] Verify all 10 agency users receive both 'agency' and 'agency_admin_dinas' roles
- [x] Create documentation file TODO-2061-add-multiple-roles-agency-users.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2061-add-multiple-roles-agency-users.md](docs/TODO-2061-add-multiple-roles-agency-users.md)

---

## TODO-2060: Add Default Role to Division Users
- [x] Fix syntax error in DivisionUsersData.php (missing closing brackets)
- [x] Update all 30 division users to use role array structure
- [x] Verify role format: ['agency', 'agency_admin_unit']
- [x] Create documentation file TODO-2060-add-default-role-division-users.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2060-add-default-role-division-users.md](docs/TODO-2060-add-default-role-division-users.md)

---

## TODO-2059: Generate Agency Employee User Names from Unique Collection
- [x] Create unique name_collection for AgencyEmployeeUsersData (60 unique names)
- [x] Update AgencyEmployeeUsersData with complete data for ALL 10 agencies
- [x] Update user IDs from 170-229 (60 users total)
- [x] Change structure: role → roles (array), remove departments field
- [x] Add getNameCollection() and isValidName() helper methods
- [x] Create documentation file TODO-2059-agency-employee-name-collection.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2059-agency-employee-name-collection.md](docs/TODO-2059-agency-employee-name-collection.md)

---

## TODO-2058: Generate Division User Names from Unique Collection
- [x] Create unique name_collection for DivisionUsersData (24 unique names)
- [x] Update DivisionUsersData with names generated from collection
- [x] Fix user IDs to be sequential from 140-169
- [x] Update role to 'agency_admin_unit' (with prefix)
- [x] Add getNameCollection() and isValidName() helper methods
- [x] Create documentation file TODO-2058-division-name-collection.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2058-division-name-collection.md](docs/TODO-2058-division-name-collection.md)

---

## TODO-2057: Generate Names from Unique Collection
- [x] Create unique name_collection for AgencyUsersData
- [x] Update AgencyUsersData with names generated from collection
- [x] Add getNameCollection() and isValidName() helper methods
- [x] Update roles with 'agency_' prefix
- [x] Create documentation file TODO-2057-unique-name-collection.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2057-unique-name-collection.md](docs/TODO-2057-unique-name-collection.md)

---

## TODO-2056: Role Management dan Delete Roles saat Plugin Deactivation
- [x] Create class-role-manager.php for centralized role management
- [x] Update class-activator.php to use RoleManager
- [x] Update class-deactivator.php to use RoleManager and remove roles on deactivate
- [x] Create documentation file TODO-2056-role-management.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2056-role-management.md](docs/TODO-2056-role-management.md)

---

## TODO-2055: Add Read Capability to Agency Role
- [x] Add 'read' capability to agency role in PermissionModel.php
- [x] Create documentation file TODO-2055-add-read-capability.md
- [x] Update TODO.md with task reference

See: [docs/TODO-2055-add-read-capability.md](docs/TODO-2055-add-read-capability.md)

---

# TODO-2021 Implementation Steps

## 1. Create Templates
- [x] Create company-invoice-dashboard.php
- [x] Create company-invoice-left-panel.php (DataTable with view payment button)
- [x] Create company-invoice-right-panel.php (tabs: detail, payment)
- [x] Create company-invoice-no-access.php

## 2. Create Controller
- [x] Create CompanyInvoiceController.php with renderMainPage, AJAX handlers, CRUD methods

## 3. Update Menu
- [ ] Add "WP Invoice Perusahaan" menu in MenuManager.php

## 4. Create Assets
- [ ] Create company-invoice-style.css
- [ ] Create company-invoice-script.js (panel navigation, AJAX)

## 5. Update Dependencies
- [ ] Register assets in class-dependencies.php for 'toplevel_page_invoice_perusahaan'

## 6. Update Main Plugin File
- [ ] Initialize CompanyInvoiceController in wp-customer.php

## 7. Testing
- [ ] Test page loads correctly
- [ ] Test DataTable functionality
- [ ] Test panel navigation
- [ ] Test AJAX calls
- [ ] Verify permissions

