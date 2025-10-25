# TODO List for WP Agency Plugin

## TODO-2071: Implement Agency Dashboard with Panel System 🔵 READY TO START

**Status**: 🔵 READY TO START
**Created**: 2025-10-23
**Dependencies**: TODO-2179 (Base Panel System Phase 1-7) ✅, TODO-2178 ✅, TODO-2174 ✅
**Priority**: HIGH (Critical for TODO-2179 Phase 8 completion)
**Complexity**: High (Full dashboard + cross-plugin integration)

**Summary**: Implement Agency Dashboard ("Disnaker") using base panel system from wp-app-core (TODO-2179). Serves as **Phase 8 integration testing** for base panel system. Features 3-tab layout with lazy loading, cross-plugin permission filtering, and hook-based access control.

**SQL Query**: ✅ VERIFIED (2025-10-23)
```sql
-- User → CustomerEmployee → Branch → Agency
SELECT a.* FROM wp_app_agencies a
INNER JOIN wp_app_customer_branches b ON a.id = b.agency_id
INNER JOIN wp_app_customer_employees ce ON b.id = ce.branch_id
WHERE ce.user_id = ? AND a.status = 'active'
GROUP BY a.id;
```

**Test Results**: user_id=2 can access 1 agency (Disnaker Provinsi Maluku)

**Current Status**:
- ✅ **Action Hooks**: 9/9 implemented (Agency, Division, Employee lifecycle)
- ⏳ **Filter Hooks**: 0/8 implemented (documented but not in code)

**Filter Hooks to Implement**:

**Permission Filters (3 hooks)**:
- [ ] `wp_agency_can_create_employee` - Override employee creation permission
  - Parameters: `($can_create, $agency_id, $division_id, $user_id)`
  - Return: `bool`
  - Location: `AgencyEmployeeController.php` or `AgencyEmployeeValidator.php`

- [ ] `wp_agency_can_create_division` - Override division creation permission
  - Parameters: `($can_create, $agency_id, $user_id)`
  - Return: `bool`
  - Location: `DivisionController.php` or `DivisionValidator.php`

- [ ] `wp_agency_max_inspector_assignments` - Maximum inspector assignments
  - Parameters: none
  - Return: `int`
  - Location: Inspector assignment logic (future feature)

**UI/UX Filters (2 hooks)**:
- [ ] `wp_agency_enable_export` - Enable/disable export button
  - Parameters: none
  - Return: `bool`
  - Location: DataTable templates (agency-list.php, division-list.php, employee-list.php)

- [ ] `wp_company_detail_tabs` - Add/remove company detail tabs
  - Parameters: `($tabs)`
  - Return: `array`
  - Location: Company detail view template

**System Filters (1 hook)**:
- [ ] `wp_agency_debug_mode` - Enable debug logging
  - Parameters: none
  - Return: `bool`
  - Location: Logger class or utility functions

**External Integration Filters (2 hooks)**:
- [ ] `wilayah_indonesia_get_province_options` - Get province dropdown options
  - Parameters: `($options)`
  - Return: `array`
  - Location: Form templates or AJAX handlers

- [ ] `wilayah_indonesia_get_regency_options` - Get regency dropdown options
  - Parameters: `($options, $province_id)`
  - Return: `array`
  - Location: Form templates or AJAX handlers

**Implementation Plan**:

**Phase 1: Permission Filters**
- [ ] Implement `wp_agency_can_create_employee` in AgencyEmployeeController
  - Add filter before validation in create() method
  - Default: check current capability, allow override
  - Return false to prevent creation

- [ ] Implement `wp_agency_can_create_division` in DivisionController
  - Add filter before validation in create() method
  - Default: check current capability, allow override

- [ ] Document `wp_agency_max_inspector_assignments` for future use
  - Skip implementation (feature not yet built)

**Phase 2: UI/UX Filters**
- [ ] Implement `wp_agency_enable_export` in DataTable templates
  - Add filter check before rendering export button
  - Default: true (enabled)
  - Hide button if filter returns false

- [ ] Implement `wp_company_detail_tabs` in company detail template
  - Add filter to tabs array before rendering
  - Allow adding/removing/reordering tabs
  - Default: existing tabs structure

**Phase 3: System Filters**
- [ ] Implement `wp_agency_debug_mode` globally
  - Add filter in error_log calls or Logger class
  - Default: false (production mode)
  - Enable verbose logging when true

**Phase 4: External Integration Filters**
- [ ] Implement wilayah filters in form rendering
  - Add filters when building province/regency dropdowns
  - Allow external plugins to modify options
  - Maintain compatibility with wilayah-indonesia plugin

**Implementation Example**:
```php
// In AgencyEmployeeController::create()
$can_create = current_user_can('add_agency_employee');
$can_create = apply_filters('wp_agency_can_create_employee', $can_create, $agency_id, $division_id, $user_id);

if (!$can_create) {
    wp_send_json_error(['message' => 'Permission denied by custom filter']);
    return;
}
```

**Files to Modify**:
- `/src/Controllers/Employee/AgencyEmployeeController.php` (permission filter)
- `/src/Controllers/Division/DivisionController.php` (permission filter)
- `/src/Views/templates/agency-list.php` (export filter)
- `/src/Views/templates/division-list.php` (export filter)
- `/src/Views/templates/employee-list.php` (export filter)
- `/src/Views/templates/company-detail.php` (tabs filter)
- Form templates with wilayah dropdowns (integration filters)
- Logger or debug utility class (debug mode filter)

**Success Criteria**:
- ✅ All 8 filter hooks implemented in code
- ✅ Filters applied at correct locations
- ✅ Default behavior preserved (backward compatible)
- ✅ Filter parameters match documentation
- ✅ Examples created for each filter
- ✅ Updated hooks documentation with implementation notes

**Testing Plan**:
```php
// Test permission filter
add_filter('wp_agency_can_create_employee', function($can, $agency_id, $division_id, $user_id) {
    // Block creation outside business hours
    return $can && (current_time('H') >= 8 && current_time('H') <= 17);
}, 10, 4);

// Test UI filter
add_filter('wp_agency_enable_export', '__return_false'); // Disable export

// Test debug mode
add_filter('wp_agency_debug_mode', '__return_true'); // Enable debug logs
```

**Benefits**:
- ✅ Complete hook system (9 actions + 8 filters = 17 hooks)
- ✅ External extensibility via filters
- ✅ Custom business logic without core modifications
- ✅ Consistent with WordPress hook standards
- ✅ Developer-friendly with comprehensive documentation

**Documentation Reference**:
- `/docs/hooks/README.md` - Main hooks documentation
- `/docs/hooks/filters/permission-filters.md` - Permission filters
- `/docs/hooks/filters/ui-filters.md` - UI/UX filters
- `/docs/hooks/filters/system-filters.md` - System filters
- `/docs/hooks/examples/` - Real-world examples

**Notes**:
- Action hooks (9) already implemented in TODO-2066 ✅
- Filter hooks (8) documented but need implementation ⏳
- Some filters (like inspector assignments) are for future features
- Maintain backward compatibility (filters should enhance, not break)

---

## TODO-2070: Employee Generator Runtime Flow Migration ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-01-22
**Completed**: 2025-01-22
**Dependencies**: TODO-2067 (Agency Runtime Flow) ✅, TODO-2069 (Division Runtime Flow) ✅, wp-customer TODO-2170 (Employee Runtime Flow) ✅
**Priority**: HIGH
**Complexity**: Medium (refactoring demo generator to use production code)

**Summary**: Migrated Employee demo data generation from bulk generation to runtime flow pattern following wp-customer Employee pattern. Removed demo code from production files and implemented full validation + hooks.

**Results**:
- **Total Employees**: 87 (target: 90, gap: 3 due to missing division in Agency 15)
  - 29 admin employees PRESERVED (from wp_agency_division_created hook)
  - 58 staff employees CREATED (from AgencyEmployeeUsersData, ID 170-229)
- ✅ Zero production pollution (removed `createDemoEmployee()` from AgencyEmployeeController)
- ✅ Full validation via AgencyEmployeeValidator (no bypasses)
- ✅ Hook `wp_agency_employee_created` registered and firing
- ✅ Dynamic division mapping handles varying IDs
- ✅ WordPress cache properly cleared after user ID changes

**Implementation Complete**:
- ✅ Remove ALL demo code from production files
- ✅ Create user via WPUserGenerator (static ID 170-229)
- ✅ Build dynamic division mapping (index → actual ID)
- ✅ Use AgencyEmployeeValidator for validation
- ✅ Trigger wp_agency_employee_created hook
- ✅ Preserve 29 admin employees from division hook

**Pattern Consistency**:
- ✅ Agency: User first → Validator → Model → Hook
- ✅ Division: User first → Validator → Model → Hook
- ✅ Customer (wp-customer): User first → Validator → Model → Hook
- ✅ **Employee**: User first → Validator → Model → Hook

**Files Modified**:
- ✅ `/src/Controllers/Employee/AgencyEmployeeController.php` (removed createDemoEmployee)
- ✅ `/src/Database/Demo/AgencyEmployeeDemoData.php` (runtime flow + mapping)
- ✅ `/src/Models/Employee/AgencyEmployeeModel.php` (hook trigger)
- ✅ `/src/Validators/Employee/AgencyEmployeeValidator.php` (enhanced email validation)
- ✅ `/src/Database/Demo/WPUserGenerator.php` (cache clearing)
- ✅ `/src/Database/Demo/Data/AgencyEmployeeUsersData.php` (fixed duplicates)
- ✅ `/wp-agency.php` (registered wp_agency_employee_created hook)

**Issues Fixed**:
1. Duplicate usernames - renamed 20 users by swapping name order
2. Validation rejection - enhanced validator to allow existing WP users
3. WordPress cache stale data - added comprehensive cache clearing

**Reference**: `/TODO/TODO-2070-employee-runtime-flow.md` (detailed completion summary)

---

## TODO-2069: Division Generator Runtime Flow Migration 🔄 IN PROGRESS

**Status**: 🔄 IN PROGRESS
**Created**: 2025-01-22
**Dependencies**: TODO-2067 (Agency Runtime Flow), TODO-2068 (Division User Auto-Creation), wp-customer TODO-2167 (Branch Runtime Flow)
**Priority**: HIGH
**Complexity**: Medium (refactoring demo generator to use production code)

**Summary**: Migrate Division demo data generation from bulk generation to runtime flow pattern following wp-customer Branch pattern. Remove demo code from production files and use full validation + hooks.

**Problem**:
- Production code pollution (`createDemoDivision()` in DivisionController) ❌
- Bulk insert bypasses validation & hooks ❌
- Inconsistent with Agency & Branch patterns ❌
- Manual employee creation (no hook) ❌

**Solution (Runtime Flow)**:
- ✅ Remove ALL demo code from production files
- ⏳ Create user via WPUserGenerator (static ID)
- ⏳ Use DivisionController->create() via runtime flow
- ⏳ Hook auto-creates employee (wp_agency_division_created)
- ⏳ Cleanup via Model delete (cascade)

**Implementation Plan**:
```
DivisionDemoData::generate()
  → Step 1: WPUserGenerator->generateUser() (static ID)
  → Step 2: createDivisionViaRuntimeFlow()
    → Step 3: Validate via DivisionValidator
    → Step 4: Create via DivisionModel->create()
      → Step 5: Hook wp_agency_division_created fires
        → Step 6: AutoEntityCreator->handleDivisionCreated()
          → Step 7: Employee auto-created
```

**Files to Modify**:
- `/src/Controllers/Division/DivisionController.php` (remove createDemoDivision)
- `/src/Database/Demo/DivisionDemoData.php` (add runtime flow method)

**Pattern Consistency**:
- ✅ Agency: User first → Controller → Hook creates division+employee
- ⏳ **Division**: User first → Controller → Hook creates employee
- ✅ Branch (wp-customer): User first → Controller → Hook creates employee

**Progress**: Step 1/9 - Created TODO file

**Reference**: `/TODO/TODO-2069-division-runtime-flow.md`

---

## TODO-2067: Agency Generator Runtime Flow Migration 🚧 IN PROGRESS

**Status**: 🚧 IN PROGRESS
**Created**: 2025-01-22
**Dependencies**: Task-2066 (HOOK system), wp-customer TODO-2168, TODO-2167
**Priority**: HIGH
**Complexity**: Medium-High (refactoring demo generator to use production code)

**Summary**: Migrate demo data generation from bulk generation approach to runtime flow pattern following wp-customer. Transform demo generator from simple data creation tool into automated testing tool for production code.

**Problem**:
- Production code pollution (demo methods in Controller/Model)
- Validation bypass (no AgencyValidator usage)
- HOOK system untested (auto-create not triggered)
- Manual user creation (direct DB INSERT vs wp_insert_user)

**Solution (Phase 1: Agency Only)**:
- ✅ Delete demo methods from production code
- ⏳ Update WPUserGenerator to use wp_insert_user()
- ⏳ Create runtime flow method in AgencyDemoData
- ⏳ Test full HOOK chain (agency → division → employee)
- ⏳ Implement HOOK-based cleanup

**Implementation Plan**:
```
AgencyDemoData::generate()
  → 1. Create user via wp_insert_user()
  → 2. Update ID to static value (FOREIGN_KEY_CHECKS=0)
  → 3. Validate via AgencyValidator::validateForm()
  → 4. Create via AgencyModel::create()
    → HOOK: wp_agency_agency_created
      → Division pusat auto-created
        → HOOK: wp_agency_division_created
          → Employee auto-created
```

**Files to Modify**:
- `/src/Controllers/AgencyController.php` (DELETE createDemoAgency method)
- `/src/Database/Demo/WPUserGenerator.php` (use wp_insert_user)
- `/src/Database/Demo/AgencyDemoData.php` (runtime flow methods)

**Success Criteria**:
- ✅ Zero demo code in production namespace
- ✅ Full validation via AgencyValidator
- ✅ User creation via wp_insert_user() with static ID
- ✅ HOOK cascade fully tested
- ✅ Cleanup via Model with cascade delete

**Reference**: `/TODO/TODO-2067-agency-generator-runtime-flow.md`

---

## TODO-2066: Auto Entity Creation & Lifecycle Hooks ✅ COMPLETED

**Status**: ✅ COMPLETED
**Created**: 2025-01-22
**Completed**: 2025-01-22
**Dependencies**: wp-customer (reference pattern), wp-customer TODO-2169 (naming convention)
**Priority**: High
**Complexity**: Medium (hook implementation + handler + delete hooks)

**Summary**: Implementasi complete hook system untuk entity lifecycle di wp-agency mengikuti pattern wp-customer dengan naming convention yang benar (`wp_{plugin}_{entity}_{action}`). Includes creation hooks for auto entity creation AND deletion hooks for cascade cleanup.

**Problem**:
- Manual entity creation required after agency/division creation
- No lifecycle hooks for deletion (cascade cleanup, external sync)
- Inconsistent data structure across agencies
- No soft delete support

**Solution:**

**Creation Hooks:**
- ✅ Added `wp_agency_agency_created` hook in AgencyModel (fixed naming)
- ✅ Added `wp_agency_division_created` hook in DivisionModel
- ✅ Created AutoEntityCreator handler class
- ✅ Registered creation hooks in main plugin file
- ✅ Added findByUserAndDivision() method in AgencyEmployeeModel

**Deletion Hooks:**
- ✅ Added `wp_agency_agency_before_delete` hook in AgencyModel
- ✅ Added `wp_agency_agency_deleted` hook in AgencyModel
- ✅ Added `wp_agency_division_before_delete` hook in DivisionModel
- ✅ Added `wp_agency_division_deleted` hook in DivisionModel
- ✅ Implemented soft delete support (status='inactive')
- ✅ Implemented hard delete option (via settings)

**Hook Flow**:
```
Creation:
Agency Created → wp_agency_agency_created hook fires
               → AutoEntityCreator::handleAgencyCreated()
               → Division Pusat auto-created
               → wp_agency_division_created hook fires
               → AutoEntityCreator::handleDivisionCreated()
               → Employee auto-created

Deletion:
Agency Delete → wp_agency_agency_before_delete (validation)
              → Soft/Hard delete based on settings
              → wp_agency_agency_deleted (cascade cleanup)
```

**Files Created**:
- `/src/Handlers/AutoEntityCreator.php` - Main handler class

**Files Modified**:
- `/src/Models/Agency/AgencyModel.php` (v2.0.0 → v2.1.0)
- `/src/Models/Division/DivisionModel.php` (v1.0.0 → v1.1.0)
- `/src/Models/Employee/AgencyEmployeeModel.php` (v1.0.0 → v1.1.0)
- `/wp-agency.php` (v1.0.0 → v1.1.0)
- `/TODO/TODO-2066-auto-entity-creation.md` - Complete documentation

**Hooks Implemented:**
- **2 Creation hooks** (agency_created, division_created)
- **4 Deletion hooks** (2x before_delete, 2x deleted)
- **Total: 6 lifecycle hooks**

**Features**:
- ✅ Automatic division pusat creation when agency created
- ✅ Automatic employee creation when division created
- ✅ Soft delete support (status='inactive', data recoverable)
- ✅ Hard delete option (actual DELETE from database)
- ✅ Before delete hooks for validation/prevention
- ✅ After delete hooks for cascade cleanup
- ✅ Duplicate prevention (checks before creating)
- ✅ Comprehensive error handling and logging
- ✅ Cache-aware implementation
- ✅ Follows wp-customer pattern with correct naming convention

**Naming Convention**: `wp_{plugin}_{entity}_{action}`
- Entity name ALWAYS explicit (wp_agency_**agency**_created)
- Consistent with wp-customer TODO-2169 standard
- Scalable and predictable

**Benefits**:
- ✅ Automation reduces manual work
- ✅ Consistent data structure across agencies
- ✅ Extensible via WordPress hook system
- ✅ Cascade cleanup for data integrity
- ✅ Soft delete for data recovery
- ✅ Easy to debug with detailed logging

**Reference**: `/TODO/TODO-2066-auto-entity-creation.md`

---
