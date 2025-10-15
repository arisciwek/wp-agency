# TODO-2062: Add Multiple Roles to Agency Employee Users

## Status
✅ COMPLETED

## Masalah
File `AgencyEmployeeUsersData.php` sudah menggunakan struktur `'roles' => ['agency', 'agency_xxx']` dengan berbagai role yang berbeda per user, tetapi saat generate demo data, role yang diberikan ke user hanya "agency" saja atau hanya satu role spesifik. Role tambahan tidak dibuat.

**Root Cause**:
`AgencyEmployeeDemoData.php` mengekstrak hanya satu "primary role" dari roles array (lines 168-169), kemudian passing `'role' => $primary_role` ke WPUserGenerator (line 175). Ini adalah kode lama sebelum WPUserGenerator support multiple roles.

**Old Code**:
```php
// Extract primary role only
$roles = $user_data['roles'] ?? ['agency'];
$primary_role = count($roles) > 1 ? $roles[1] : $roles[0];

$user_id = $this->wpUserGenerator->generateUser([
    'role' => $primary_role  // ❌ Only passing one role
]);
```

## Target
- Setiap agency employee user harus memiliki default role `'agency'` plus role spesifik
- Format: `'roles' => ['agency', 'agency_kepala_dinas']` (atau role lain sesuai definisi)
- Mendukung variasi role yang berbeda per user (kepala_dinas, pengawas, kepala_unit, dll)
- Menggunakan data dari AgencyEmployeeUsersData, bukan ekstrak sebagian

## Solusi

### File: AgencyEmployeeDemoData.php (UPDATED)
**Location**: `src/Database/Demo/AgencyEmployeeDemoData.php`

**Issue**: Extracting only primary role instead of using full roles array

**Before (WRONG)**:
```php
private function generateNewEmployees(): void {
    foreach (self::$employee_users as $user_data) {
        // Generate WordPress user first
        // Note: roles is now an array ['agency', 'agency_xxx']
        // But WPUserGenerator needs the primary role (first one after 'agency')
        $roles = $user_data['roles'] ?? ['agency'];
        $primary_role = count($roles) > 1 ? $roles[1] : $roles[0];

        $user_id = $this->wpUserGenerator->generateUser([
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'display_name' => $user_data['display_name'],
            'role' => $primary_role  // ❌ Only passing one role
        ]);
```

**After (CORRECT)**:
```php
private function generateNewEmployees(): void {
    foreach (self::$employee_users as $user_data) {
        // Generate WordPress user with multiple roles
        // WPUserGenerator now supports 'roles' array parameter (from TODO-2060)
        $user_id = $this->wpUserGenerator->generateUser([
            'id' => $user_data['id'],
            'username' => $user_data['username'],
            'display_name' => $user_data['display_name'],
            'roles' => $user_data['roles']  // ✅ Use full roles array from AgencyEmployeeUsersData
        ]);
```

**Changes Applied**:
- ✅ Removed primary role extraction logic (lines 168-169)
- ✅ Changed from 'role' => $primary_role to 'roles' => $user_data['roles']
- ✅ Updated comment to reflect WPUserGenerator multiple roles support
- ✅ Fixed untuk SEMUA 60 agency employee users (ID 170-229)

## Structure Details

### Complete Data Structure (AgencyEmployeeUsersData.php):
```php
public static $data = [
    170 => [
        'id' => 170,
        'agency_id' => 1,
        'division_id' => 1,
        'username' => 'ade_andra',
        'display_name' => 'Ade Andra',
        'roles' => ['agency', 'agency_kepala_dinas']
    ],
    171 => [
        'id' => 171,
        'agency_id' => 1,
        'division_id' => 1,
        'username' => 'bintang_bayu',
        'display_name' => 'Bintang Bayu',
        'roles' => ['agency', 'agency_pengawas']
    ],
    // ... up to 229 with various role combinations
];
```

### Role Variations:
Setiap user memiliki kombinasi roles yang berbeda sesuai posisinya:
- **Default Role**: `'agency'` - Base role untuk semua agency users
- **Specific Roles** (varies per user):
  - `'agency_kepala_dinas'` - Kepala Dinas
  - `'agency_pengawas'` - Pengawas
  - `'agency_kepala_unit'` - Kepala Unit
  - `'agency_kepala_seksi'` - Kepala Seksi
  - `'agency_kepala_bidang'` - Kepala Bidang
  - `'agency_pengawas_spesialis'` - Pengawas Spesialis

## Benefits
1. ✅ **Uses Full Roles Array**: Tidak lagi ekstrak sebagian, menggunakan semua roles
2. ✅ **Multiple Roles Support**: Menggunakan WPUserGenerator yang sudah support multiple roles (dari TODO-2060)
3. ✅ **Consistent Structure**: Sama dengan DivisionDemoData dan AgencyDemoData
4. ✅ **Default Role Included**: Setiap user punya default role 'agency' plus specific role
5. ✅ **Role Variations Supported**: Berbagai kombinasi role berbeda per user sesuai data
6. ✅ **Complete Coverage**: Fixed untuk semua 60 agency employee users

## How It Works

**WordPress User Capabilities Structure**:
```php
// In wp_usermeta table:
meta_key: 'wp_capabilities'
meta_value: serialize([
    'agency' => true,
    'agency_kepala_dinas' => true  // or other specific role
])
```

**Data Flow**:
1. `AgencyEmployeeUsersData::$data` contains `'roles' => ['agency', 'agency_xxx']` for each user
2. `AgencyEmployeeDemoData::generateNewEmployees()` passes `'roles' => $user_data['roles']` to WPUserGenerator
3. `WPUserGenerator` builds capabilities array from all roles (implemented in TODO-2060)
4. Capabilities saved to usermeta as serialized array

## Testing

### Test 1: Verify Syntax
```bash
php -l src/Database/Demo/AgencyEmployeeDemoData.php
# Should return: No syntax errors detected
```

### Test 2: Verify User Roles After Generation
```php
// After generating employee demo data
$user_id = 170; // First employee user (Kepala Dinas)
$user = get_userdata($user_id);
$roles = $user->roles;

// Expected: ['agency', 'agency_kepala_dinas']
var_dump($roles);

// Test another user with different role
$user_id = 171; // Second employee user (Pengawas)
$user = get_userdata($user_id);
// Expected: ['agency', 'agency_pengawas']
var_dump($user->roles);
```

### Test 3: Check Capabilities in Database
```php
global $wpdb;
$capabilities = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->usermeta}
     WHERE user_id = %d AND meta_key = %s",
    170,
    $wpdb->prefix . 'capabilities'
));

// Expected: a:2:{s:6:"agency";b:1;s:21:"agency_kepala_dinas";b:1;}
var_dump(unserialize($capabilities));
```

### Test 4: Verify All Employee Users with Different Roles
```php
// Check all 60 employee users (ID 170-229) have correct roles
for ($id = 170; $id <= 229; $id++) {
    $user = get_userdata($id);
    if (!$user) {
        echo "User {$id} not found\n";
        continue;
    }

    $has_agency = in_array('agency', $user->roles);
    $has_specific_role = count($user->roles) > 1;

    if (!$has_agency || !$has_specific_role) {
        echo "ERROR: User {$id} missing roles. Has: " . implode(', ', $user->roles) . "\n";
    } else {
        echo "User {$id} ({$user->display_name}): " . implode(', ', $user->roles) . " - OK\n";
    }
}
```

### Test 5: Verify Role Distribution
```php
// Count users by specific role to verify variations
$role_counts = [];
for ($id = 170; $id <= 229; $id++) {
    $user = get_userdata($id);
    if (!$user) continue;

    foreach ($user->roles as $role) {
        if ($role !== 'agency') {
            $role_counts[$role] = ($role_counts[$role] ?? 0) + 1;
        }
    }
}

// Expected: various roles distributed across 60 users
// kepala_dinas, pengawas, kepala_unit, kepala_seksi, kepala_bidang, pengawas_spesialis
print_r($role_counts);
```

### Test 6: Generate Demo Data
```php
// Run demo data generation
$employeeDemo = new AgencyEmployeeDemoData();
$employeeDemo->run();
// Should complete without errors and all users have their specific roles
```

## Files Modified
- ✅ `src/Database/Demo/AgencyEmployeeDemoData.php` (UPDATED)
  - Removed primary role extraction logic (lines 168-169)
  - Changed line 171 from 'role' => $primary_role to 'roles' => $user_data['roles']
  - Updated comment to reflect WPUserGenerator multiple roles support
  - Coverage: All 60 agency employee users (ID 170-229)

## Notes
- AgencyEmployeeUsersData.php sudah memiliki struktur roles yang benar, tidak perlu diubah
- WPUserGenerator sudah support multiple roles (dari TODO-2060), hanya perlu menggunakan 'roles' parameter
- Total 60 agency employee users (ID 170-229) dengan 10 agencies × 3 divisions × 2 users per division
- Setiap user memiliki kombinasi roles yang berbeda sesuai posisi/jabatannya
- Role variations: kepala_dinas, pengawas, kepala_unit, kepala_seksi, kepala_bidang, pengawas_spesialis
- Backward compatible dengan WPUserGenerator yang tetap support single 'role' string

## Related Tasks
- TODO-2060: Add Multiple Roles to Division Users (implementasi WPUserGenerator support multiple roles)
- TODO-2061: Add Multiple Roles to Agency Users
- TODO-2059: Agency Employee Users with roles array structure
- TODO-2058: Division Name Collection
- TODO-2057: Agency Name Collection
- TODO-2056: Role Management

## References
- Role management: `/wp-agency/includes/class-role-manager.php`
- WPUserGenerator: `/wp-agency/src/Database/Demo/WPUserGenerator.php`
- Pattern reference: `/wp-agency/src/Database/Demo/Data/AgencyEmployeeUsersData.php`
