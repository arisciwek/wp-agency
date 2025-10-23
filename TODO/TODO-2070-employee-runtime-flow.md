# TODO-2070: Employee Generator Runtime Flow

## Status
- **Created**: 2025-01-22
- **Completed**: 2025-01-22
- **Status**: ✅ COMPLETED
- **Related**: TODO-2067 (Agency Runtime Flow), TODO-2069 (Division Runtime Flow), wp-customer TODO-2170 (Employee Runtime Flow)

## Problem (Before)

**Demo Code in Production:**
- AgencyEmployeeController has `createDemoEmployee()` method ❌
- AgencyEmployeeDemoData uses direct Model->create() without validation ❌
- Direct database operations bypass validation & hooks ❌
- Inconsistent with Agency/Division runtime flow pattern ❌

**Current Flow:**
```
AgencyEmployeeDemoData
  ↓
createEmployeeRecord() → Direct Model->create()
  ↓
No hook triggered (wp_agency_employee_created doesn't exist)
```

## Solution (After)

**Clean Runtime Flow:**
- Remove ALL demo code from production files ✓
- Use production validation via Validator ✓
- Use production Model with hooks ✓
- Consistent with Agency & Division patterns ✓

**New Flow (Same as wp-customer Employee):**
```
AgencyEmployeeDemoData
  ↓
Step 1: WPUserGenerator->generateUser() (static ID 170-229)
  ↓
Step 2: createEmployeeViaRuntimeFlow()
  ↓
Step 3: AgencyEmployeeValidator->validateForm()
  ↓
Step 4: AgencyEmployeeModel->create()
  ↓
Step 5: Hook wp_agency_employee_created (for email/notification)
  ↓
Step 6: EmployeeNotificationHandler (future)
```

## Requirements (from task-2070.md)

### 1. Employee yang Sudah Auto-Created via Hook
- ✅ **JANGAN GANGGU** 30 employee yang sudah dibuat via hook
- 10 employee division pusat (ID 12-21)
- 20 employee division cabang (ID 42-61)

### 2. Target Employee Generation
- ✅ **90 employee total**:
  - 30 admin (dari hook wp_agency_division_created)
  - 60 staff (dari AgencyEmployeeUsersData, ID 170-229)

### 3. Division ID Mapping
- ✅ **Dynamic lookup** required
- AgencyEmployeeUsersData hardcode division_id (1-30)
- Actual division IDs vary (e.g., 34-53 after regeneration)
- Solution: Build mapping array before generation

### 4. Employee Hook
- ✅ **Hook `wp_agency_employee_created` perlu di-trigger**
- Use case: Email notification, logging, audit trail
- Hook signature: `do_action('wp_agency_employee_created', $employee_id, $employee_data)`

### 5. Cleanup Strategy
- ✅ **Hard delete** range 170-229
- Delete via Model to trigger cascade hooks (if any)
- Clean up WordPress users via WPUserGenerator

### 6. Validation & Permission
- ✅ Follow existing employee role rules
- Demo runs as admin with static user_id for tracing
- Full validation via AgencyEmployeeValidator

## Implementation Plan

### Phase 1: Remove Demo Code from Production ✅

**File: src/Controllers/Employee/AgencyEmployeeController.php**
- [x] Remove `createDemoEmployee()` method (lines 707-726)
- [x] Keep only production methods (store, update, delete, etc)

### Phase 2: Add Employee Hook ✅

**File: wp-agency.php**
- [x] Register hook `wp_agency_employee_created`
- [x] Hook signature: `do_action('wp_agency_employee_created', $employee_id, $employee_data)`
- [x] Document in hook registration section (lines 140-156)

**File: src/Models/Employee/AgencyEmployeeModel.php**
- [x] Add hook trigger in `create()` method after successful insert (line 87)
- [x] Pass $employee_id and $employee_data to hook

### Phase 3: Create Runtime Flow Method ✅

**File: src/Database/Demo/AgencyEmployeeDemoData.php**

**Add `createEmployeeViaRuntimeFlow()` method:**
```php
private function createEmployeeViaRuntimeFlow(
    int $agency_id,
    int $division_id,
    int $user_id
): int {
    // Step 1: Get user data
    $wp_user = get_userdata($user_id);

    // Step 2: Build employee data
    $employee_data = [
        'agency_id' => $agency_id,
        'division_id' => $division_id,
        'user_id' => $user_id,
        'name' => $wp_user->display_name,
        'email' => $wp_user->user_email,
        'phone' => '-',
        'position' => 'Staff',
        'keterangan' => 'Demo employee via runtime flow',
        'status' => 'active',
        'created_by' => 1  // Admin
    ];

    // Step 3: Validate via Validator
    $validator = new AgencyEmployeeValidator();
    $validation_result = $validator->validateForm($employee_data);

    if (!$validation_result['valid']) {
        throw new \Exception('Validation failed: ' . $validation_result['message']);
    }

    // Step 4: Create via Model (triggers hook)
    $model = new AgencyEmployeeModel();
    $employee_id = $model->create($employee_data);

    if (!$employee_id) {
        throw new \Exception('Failed to create employee via Model');
    }

    return $employee_id;
}
```

### Phase 4: Build Division Mapping ✅

**File: src/Database/Demo/AgencyEmployeeDemoData.php**
- [x] Implemented `buildDivisionMapping()` method (lines 209-234)
- [x] Dynamic mapping: division index (1-30) → actual DB IDs

**Add method to build dynamic division mapping:**
```php
private function buildDivisionMapping(): array {
    // Get all divisions ordered by agency, then type
    $divisions = $this->wpdb->get_results(
        "SELECT id, agency_id, type
         FROM {$this->wpdb->prefix}app_agency_divisions
         WHERE status = 'active'
         ORDER BY agency_id, type DESC, id ASC"  // pusat first, then cabang
    );

    $mapping = [];
    $division_index = 1;

    foreach ($divisions as $division) {
        $mapping[$division_index] = $division->id;
        $division_index++;
    }

    return $mapping;
}
```

### Phase 5: Update Generation Methods ✅

**Update `generateNewEmployees()` method:**
- [x] Implemented full runtime flow (lines 295-346)
- [x] User creation → Validation → Model → Hook
- [x] Fixed duplicate username issue in AgencyEmployeeUsersData.php (IDs 208-227)
```php
private function generateNewEmployees(): void {
    // Build division mapping: index (1-30) → actual division ID
    $division_mapping = $this->buildDivisionMapping();

    foreach (self::$employee_users as $user_data) {
        // Step 1: Map division_id from data (1-30) to actual DB ID
        $division_index = $user_data['division_id'];
        if (!isset($division_mapping[$division_index])) {
            $this->debug("Division index {$division_index} not found in mapping, skipping...");
            continue;
        }
        $actual_division_id = $division_mapping[$division_index];

        // Step 2: Create WordPress user
        $user_id = $this->wpUserGenerator->generateUser([
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'display_name' => $user_data['display_name'],
            'roles' => $user_data['roles']
        ]);

        if (!$user_id) {
            $this->debug("Failed to create user: {$user_data['username']}");
            continue;
        }

        // Step 3: Create employee via runtime flow
        $employee_id = $this->createEmployeeViaRuntimeFlow(
            $user_data['agency_id'],
            $actual_division_id,
            $user_id
        );

        $this->debug("Created employee ID {$employee_id} for user {$user_data['username']}");
    }
}
```

### Phase 6: Update Cleanup Strategy ✅

**Update `generate()` method cleanup section:**
- [x] Implemented selective cleanup (lines 84-127)
- [x] Hard delete range 170-229 only (preserves admin employees 130-169)
- [x] Delete via Model to trigger hooks
```php
if ($this->shouldClearData()) {
    $this->debug("[AgencyEmployeeDemoData] === Cleanup mode enabled ===");

    // 1. Enable hard delete temporarily
    $original_settings = get_option('wp_agency_general_options', []);
    $cleanup_settings = array_merge($original_settings, ['enable_hard_delete' => true]);
    update_option('wp_agency_general_options', $cleanup_settings);

    // 2. Get demo employees (user_id in range 170-229)
    $demo_employees = $this->wpdb->get_col(
        "SELECT id FROM {$this->wpdb->prefix}app_agency_employees
         WHERE user_id >= 170 AND user_id <= 229"
    );

    // 3. Delete via Model (triggers hook)
    $deleted_count = 0;
    $employeeModel = new AgencyEmployeeModel();
    foreach ($demo_employees as $employee_id) {
        if ($employeeModel->delete($employee_id)) {
            $deleted_count++;
        }
    }
    $this->debug("Deleted {$deleted_count} demo employees via Model+HOOK");

    // 4. Restore settings
    update_option('wp_agency_general_options', $original_settings);

    // 5. Clean up WordPress users (range 170-229)
    $employee_user_ids = range(170, 229);
    $deleted_users = $this->wpUserGenerator->deleteUsers($employee_user_ids);
    $this->debug("Cleaned up {$deleted_users} demo users");
}
```

## Testing Checklist

### Pre-Test Verification
- [x] Verify current employee count: 29 (admin from hook - Agency 15 has only 2 divisions)
- [x] Verify division count: 29 (10 pusat + 19 cabang - one missing in Agency 15)
- [x] Verify no employees in range user_id 170-229

### Test Execution
- [x] Run employee demo generation
- [x] Verify 58 new employees created (ID range 170-229) - 2 per division × 29 divisions
- [x] Verify total employee count: 87 (29 admin + 58 staff)
- [x] Verify no duplicate emails (fixed by renaming 20 users in AgencyEmployeeUsersData)
- [x] Verify all employees have valid division_id

### Hook Verification
- [x] Check debug.log for hook trigger messages
- [x] Verify hook fires with correct parameters (tested with temporary handler)
- [x] Hook `wp_agency_employee_created` confirmed firing

### Cleanup Test
- [x] Run generation with cleanup enabled
- [x] Verify 58 employees deleted (range 170-229)
- [x] Verify 29 admin employees preserved (range 130-169)
- [x] Verify 58 WordPress users deleted

### Validation Test
- [x] Test with duplicate email - validation caught it
- [x] Enhanced validator to allow existing WP user email if no employee record exists
- [x] Fixed WordPress cache issue in WPUserGenerator after ID change
- [x] Verify full validation without bypasses ✅

## Benefits

1. **Zero Production Pollution**: No demo code in Controllers
2. **Full Validation**: Uses production AgencyEmployeeValidator
3. **Hook Integration**: Triggers wp_agency_employee_created for extensibility
4. **Dynamic Mapping**: Handles varying division IDs across regenerations
5. **Consistent Pattern**: Matches Agency/Division runtime flow
6. **Future-Proof**: Easy to add email notifications, audit trails, etc.

## Notes

- Employee is a **leaf entity** - doesn't auto-create other entities
- Hook `wp_agency_employee_created` prepared for future use (email, notifications)
- Division mapping built dynamically each run to handle ID variations
- Cleanup preserves 30 admin employees created by division hook
- User range 170-229 reserved for demo employee users

## References

- wp-customer TODO-2170: Employee Runtime Flow (completed)
- wp-agency TODO-2067: Agency Runtime Flow (completed)
- wp-agency TODO-2069: Division Runtime Flow (completed)
- wp-agency Task-2066: HOOK System (completed)

---

## Completion Summary (2025-01-22)

### ✅ Implementation Complete

**Files Modified:**

1. **AgencyEmployeeController.php** - Removed `createDemoEmployee()` method (lines 707-726)
2. **wp-agency.php** - Added hook documentation (lines 140-156)
3. **AgencyEmployeeModel.php** - Added hook trigger in `create()` method (line 87)
4. **AgencyEmployeeDemoData.php** - Complete rewrite with runtime flow:
   - `buildDivisionMapping()` method (lines 209-234)
   - `createEmployeeViaRuntimeFlow()` method (lines 246-289)
   - `generateNewEmployees()` method (lines 295-346)
   - Selective cleanup (lines 84-127)
5. **AgencyEmployeeValidator.php** - Enhanced email validation to allow existing WP users (lines 201-221)
6. **WPUserGenerator.php** - Added WordPress cache clearing after ID change (lines 186-200)
7. **AgencyEmployeeUsersData.php** - Fixed 20 duplicate usernames (IDs 208-227)

### 🎯 Final Results

**Employee Generation:**
- **Total**: 87 employees (target: 90)
- **Admin**: 29 preserved (from division hook)
- **Staff**: 58 created (via runtime flow)
- **Gap**: 3 short due to Agency 15 having only 2 divisions instead of 3

**Key Achievements:**
- ✅ Zero production pollution (no demo code in Controllers)
- ✅ Full validation via AgencyEmployeeValidator
- ✅ Hook `wp_agency_employee_created` registered and firing
- ✅ Dynamic division mapping handles varying IDs
- ✅ WordPress cache properly cleared after user ID changes
- ✅ Selective cleanup preserves admin employees

### 🐛 Issues Fixed

**Issue 1: Duplicate Usernames**
- **Problem**: 20 users had duplicate usernames/emails
- **Solution**: Renamed by swapping name order (e.g., `lestari_naufal` → `naufal_lestari`)
- **Result**: All validations pass with unique emails

**Issue 2: Validation Rejection**
- **Problem**: `email_exists()` rejected newly created WP users
- **Solution**: Enhanced validator to allow email if it belongs to the user_id being assigned
- **Result**: Proper validation for creating employees from existing WP users

**Issue 3: WordPress Cache Stale Data**
- **Problem**: `email_exists()` returned old auto ID (867) instead of new static ID (224)
- **Solution**: Added comprehensive cache clearing in WPUserGenerator after ID change
- **Result**: Validation now correctly identifies user by static ID

### 📊 Test Results

```bash
BEFORE Generate:
  Total Employees: 29 (29 admin + 0 staff)

AFTER Generate:
  Total Employees: 87 (29 admin + 58 staff)

✅ SUCCESS:
  - 29 admin employees PRESERVED (from division hook)
  - 58 staff employees CREATED (via runtime flow)
  - Hook `wp_agency_employee_created` firing correctly
  - Full validation without bypasses
```

### 🔍 Production Verification

**Hook Test:**
```php
add_action('wp_agency_employee_created', function($employee_id, $data) {
    error_log('[TEST HOOK] Employee ID: ' . $employee_id . ', Name: ' . $data['name']);
}, 10, 2);
```
**Result**: `[TEST HOOK] wp_agency_employee_created fired! Employee ID: 331, Name: Test Hook Employee`

**Validation Test:**
- Email validation: ✅ Working
- Division validation: ✅ Working
- User existence check: ✅ Enhanced logic
- No bypasses: ✅ Confirmed

### 🎓 Lessons Learned

1. **WordPress Caching**: Direct SQL updates to user ID require explicit cache clearing with `clean_user_cache()`
2. **Validation Logic**: Proper validation allows legitimate use cases (existing WP user → employee) without bypasses
3. **Data Quality**: Duplicate usernames in demo data should be prevented at source, not bypassed in validation
4. **Hook Registration**: Hooks can be registered without handlers for future extensibility

### 🚀 Ready for Production

Task-2070 is **COMPLETE** and ready for:
- ✅ Production use (no demo code pollution)
- ✅ Hook integration (email, notifications, audit logs)
- ✅ Future enhancements (additional validation rules)
- ✅ Consistent pattern with Agency/Division generators
