# TODO-2069: Division Generator Runtime Flow

## Status
- **Created**: 2025-01-22
- **Status**: 🔄 IN PROGRESS
- **Related**: TODO-2067 (Agency Runtime Flow), TODO-2068 (Division User Auto-Creation), wp-customer TODO-2167 (Branch Runtime Flow)

## Problem (Before)

**Demo Code in Production:**
- DivisionController has `createDemoDivision()` method ❌
- DivisionDemoData uses bulk insert instead of runtime flow ❌
- Direct database insert bypasses validation & hooks ❌
- Inconsistent with Agency runtime flow pattern ❌

**Current Flow:**
```
DivisionDemoData
  ↓
createDemoDivision() → Direct DB insert
  ↓
Manual employee creation (no hook)
```

## Solution (After)

**Clean Runtime Flow:**
- Remove ALL demo code from production files ✓
- Use Controller->create() via runtime flow ✓
- Use Model validation & hooks ✓
- Consistent with Agency & Branch patterns ✓

**New Flow (Same as wp-customer Branch):**
```
DivisionDemoData
  ↓
Step 1: WPUserGenerator->generateUser() (static ID)
  ↓
Step 2: createDivisionViaRuntimeFlow()
  ↓
Step 3: DivisionController->create()
  ↓
Step 4: DivisionModel->create()
  ↓
Step 5: Hook wp_agency_division_created
  ↓
Step 6: AutoEntityCreator->handleDivisionCreated()
  ↓
Step 7: Auto-create Employee
```

## Implementation Plan

### 1. Remove Demo Code from Production
**File: src/Controllers/Division/DivisionController.php**
- [x] Remove `createDemoDivision()` method (if exists)
- [x] Keep only production methods (create, update, delete, etc)

### 2. Create Runtime Flow Method
**File: src/Database/Demo/DivisionDemoData.php**
- [x] Add `createDivisionViaRuntimeFlow()` method
- [x] Replicate exact logic from DivisionController::create()
- [x] Support user_id passing (user already created by WPUserGenerator)
- [x] Skip AJAX/nonce validation (demo context)

### 3. Update Generation Methods
**File: src/Database/Demo/DivisionDemoData.php**

**generatePusatDivision():**
```php
// OLD (bulk insert):
$division_id = $this->divisionController->createDemoDivision($division_data);

// NEW (runtime flow):
$user_id = $userGenerator->generateUser([...]); // Step 1: Create user first
$division_id = $this->createDivisionViaRuntimeFlow(
    $agency_id,
    $division_data,
    ['user_id' => $user_id],  // Pass existing user_id
    $agency->user_id  // created_by
);
```

**generateCabangDivisions():**
- Same pattern as pusat
- Create user first → Pass to runtime flow

### 4. Update Cleanup Strategy
**File: src/Database/Demo/DivisionDemoData.php**

**generate() method:**
```php
// OLD (manual cleanup):
$this->wpdb->query("DELETE FROM {$this->wpdb->prefix}app_agency_divisions WHERE id > 0");

// NEW (hook cascade):
// 1. Enable hard delete temporarily
$original_settings = get_option('wp_agency_general_options', []);
update_option('wp_agency_general_options', ['enable_hard_delete' => true]);

// 2. Delete via Model (triggers cascade)
foreach ($cabang_divisions as $division_id) {
    $this->divisionModel->delete($division_id);
    // → Triggers wp_agency_division_deleted hook
    //   → Cascade deletes employees
}

// 3. Restore settings
update_option('wp_agency_general_options', $original_settings);

// 4. Clear cache
wp_cache_flush();
```

## Pattern Consistency

### Agency Pattern (TODO-2067)
```php
// 1. Create user via WPUserGenerator (static ID)
$user_id = $userGenerator->generateUser([...]);

// 2. Create agency via runtime flow
$agency_id = $this->createAgencyViaRuntimeFlow($agency_data, $user_id);

// 3. Hook auto-creates division + employee
```

### Branch Pattern (wp-customer TODO-2167)
```php
// 1. Create user via WPUserGenerator (static ID)
$user_id = $userGenerator->generateUser([...]);

// 2. Create branch via runtime flow
$branch_id = $this->createBranchViaRuntimeFlow(
    $customer_id,
    $branch_data,
    ['user_id' => $user_id],
    $current_user_id
);

// 3. Hook auto-creates employee
```

### Division Pattern (TODO-2069) - NEW
```php
// 1. Create user via WPUserGenerator (static ID)
$user_id = $userGenerator->generateUser([...]);

// 2. Create division via runtime flow
$division_id = $this->createDivisionViaRuntimeFlow(
    $agency_id,
    $division_data,
    ['user_id' => $user_id],
    $current_user_id
);

// 3. Hook auto-creates employee
```

## Hooks Used (Already Exist)

✅ **wp_agency_division_created** (line 212 in DivisionModel.php)
- Fired after division creation
- Handled by AutoEntityCreator->handleDivisionCreated()
- Auto-creates employee with division.user_id

✅ **wp_agency_division_before_delete** (line 367 in DivisionModel.php)
- Fired before deletion
- Can prevent deletion via exception

✅ **wp_agency_division_deleted** (line 403 in DivisionModel.php)
- Fired after deletion (soft or hard)
- Handled by cascade delete for employees

**Note:** NO `before_create` hook needed (not used in wp-customer pattern)

## Files to Modify

1. **src/Controllers/Division/DivisionController.php**
   - Remove `createDemoDivision()` (if exists)

2. **src/Database/Demo/DivisionDemoData.php**
   - Add `createDivisionViaRuntimeFlow()` method
   - Update `generate()` for cleanup via Model
   - Update `generatePusatDivision()` to use runtime flow
   - Update `generateCabangDivisions()` to use runtime flow

## Benefits

### 1. No Production Pollution ✓
- Zero demo code in production files
- Controllers only handle production logic
- Clear separation: demo code in /Demo, production code in /Controllers

### 2. Pattern Consistency ✓
- Agency, Division, Employee use same runtime flow
- Easy to understand and maintain
- Consistent with wp-customer plugin

### 3. Full Validation ✓
- Uses production validators
- Catches errors like production would
- Demo data tests production code paths

### 4. Hook Integration ✓
- Tests hook functionality
- Validates cascade operations
- Ensures AutoEntityCreator works correctly

### 5. Clean Cleanup ✓
- Uses Model delete (not manual SQL)
- Hook cascade handles related data
- Cache properly invalidated

## Testing Checklist

- [ ] Remove existing divisions (cleanup)
- [ ] Generate divisions via new runtime flow
- [ ] Verify users created with static IDs
- [ ] Verify divisions created via Controller->create()
- [ ] Verify employees auto-created by hook
- [ ] Verify cache properly invalidated
- [ ] Test cleanup via Model delete
- [ ] Verify cascade deletion works

## Implementation Steps

1. ✅ Create TODO-2069-division-runtime-flow.md
2. ⏳ Update TODO.md with new task
3. ⏳ Remove `createDemoDivision()` from DivisionController
4. ⏳ Implement `createDivisionViaRuntimeFlow()` in DivisionDemoData
5. ⏳ Update `generate()` method for cleanup via Model
6. ⏳ Update `generatePusatDivision()` to use runtime flow
7. ⏳ Update `generateCabangDivisions()` to use runtime flow
8. ⏳ Test generation flow
9. ⏳ Update TODO.md status to completed

## Related Files Reference

**Agency Runtime Flow:**
- `/wp-agency/src/Database/Demo/AgencyDemoData.php`
- `/wp-agency/TODO/TODO-2067-agency-generator-runtime-flow.md`

**Branch Runtime Flow (wp-customer):**
- `/wp-customer/src/Database/Demo/BranchDemoData.php`
- `/wp-customer/TODO/TODO-2167-branch-generator-runtime-flow.md`

**Division User Auto-Creation:**
- `/wp-agency/src/Handlers/AutoEntityCreator.php`
- `/wp-agency/TODO/TODO-2068-COMPLETED.md`

## Expected Result

After implementation:
- Division demo generation uses real production flow
- No demo code in production files
- Pattern matches Agency & Branch generators
- Full validation & hook integration
- Clean cascade cleanup

---

**Status**: 🔄 IN PROGRESS (Step 1/9 completed)
