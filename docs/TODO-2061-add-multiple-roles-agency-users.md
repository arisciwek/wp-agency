# TODO-2061: Add Multiple Roles to Agency Users

## Status
✅ COMPLETED

## Masalah
File `AgencyUsersData.php` sudah menggunakan struktur `'roles' => ['agency', 'agency_admin_dinas']`, tetapi saat generate demo data, role yang diberikan ke user hanya "agency" saja. Role tambahan 'agency_admin_dinas' tidak dibuat.

**Root Cause**:
`AgencyDemoData.php` hardcoded `'role' => 'agency'` saat generate user (line 162), tidak menggunakan roles array dari `AgencyUsersData`.

## Target
- Setiap agency user harus memiliki default role `'agency'` plus role spesifik
- Format: `'roles' => ['agency', 'agency_admin_dinas']`
- Menggunakan data dari AgencyUsersData, bukan hardcoded

## Solusi

### File: AgencyDemoData.php (UPDATED)
**Location**: `src/Database/Demo/AgencyDemoData.php`

**Issue**: Hardcoded single role instead of using roles array from data

**Before (WRONG)**:
```php
$user_id = $userGenerator->generateUser([
    'id' => $user_data['id'],
    'username' => $user_data['username'],
    'display_name' => $user_data['display_name'],
    'role' => 'agency'  // ❌ Hardcoded single role
]);
```

**After (CORRECT)**:
```php
$user_id = $userGenerator->generateUser([
    'id' => $user_data['id'],
    'username' => $user_data['username'],
    'display_name' => $user_data['display_name'],
    'roles' => $user_data['roles']  // ✅ Use roles array from AgencyUsersData
]);
```

**Changes Applied**:
- ✅ Changed from hardcoded 'role' => 'agency' to 'roles' => $user_data['roles']
- ✅ Now uses roles array from AgencyUsersData
- ✅ Fixed untuk SEMUA 10 agency users (ID 130-139)

## Structure Details

### Complete Data Structure (AgencyUsersData.php):
```php
public static $data = [
    ['id' => 130, 'username' => 'ahmad_bambang', 'display_name' => 'Ahmad Bambang',
     'roles' => ['agency', 'agency_admin_dinas']],
    ['id' => 131, 'username' => 'cahyo_darmawan', 'display_name' => 'Cahyo Darmawan',
     'roles' => ['agency', 'agency_admin_dinas']],
    // ... hingga ID 139
];
```

### Role Structure:
- **Default Role**: `'agency'` - Base role untuk semua agency users
- **Specific Role**: `'agency_admin_dinas'` - Agency admin role
- **Format**: Array `['agency', 'agency_admin_dinas']`

## Benefits
1. ✅ **Uses Data from AgencyUsersData**: Tidak lagi hardcoded role
2. ✅ **Multiple Roles Support**: Menggunakan WPUserGenerator yang sudah support multiple roles (dari TODO-2060)
3. ✅ **Consistent Structure**: Sama dengan DivisionUsersData dan AgencyEmployeeUsersData (roles sebagai array)
4. ✅ **Default Role Included**: Setiap user punya default role 'agency' plus specific role
5. ✅ **Complete Coverage**: Fixed untuk semua 10 agency users

## How It Works

**WordPress User Capabilities Structure**:
```php
// In wp_usermeta table:
meta_key: 'wp_capabilities'
meta_value: serialize([
    'agency' => true,
    'agency_admin_dinas' => true
])
```

**Data Flow**:
1. `AgencyUsersData::$data` contains `'roles' => ['agency', 'agency_admin_dinas']`
2. `AgencyDemoData` passes `'roles' => $user_data['roles']` to WPUserGenerator
3. `WPUserGenerator` builds capabilities array from roles (implemented in TODO-2060)
4. Capabilities saved to usermeta as serialized array

## Testing

### Test 1: Verify Syntax
```bash
php -l src/Database/Demo/AgencyDemoData.php
# Should return: No syntax errors detected
```

### Test 2: Verify User Roles After Generation
```php
// After generating agency demo data
$user_id = 130; // First agency user
$user = get_userdata($user_id);
$roles = $user->roles;

// Expected: ['agency', 'agency_admin_dinas']
var_dump($roles);
```

### Test 3: Check Capabilities in Database
```php
global $wpdb;
$capabilities = $wpdb->get_var($wpdb->prepare(
    "SELECT meta_value FROM {$wpdb->usermeta}
     WHERE user_id = %d AND meta_key = %s",
    130,
    $wpdb->prefix . 'capabilities'
));

// Expected: a:2:{s:6:"agency";b:1;s:19:"agency_admin_dinas";b:1;}
var_dump(unserialize($capabilities));
```

### Test 4: Verify All Agency Users
```php
// Check all 10 agency users (ID 130-139)
for ($id = 130; $id <= 139; $id++) {
    $user = get_userdata($id);
    if (!$user) {
        echo "User {$id} not found\n";
        continue;
    }

    $has_agency = in_array('agency', $user->roles);
    $has_admin_dinas = in_array('agency_admin_dinas', $user->roles);

    if (!$has_agency || !$has_admin_dinas) {
        echo "ERROR: User {$id} missing roles. Has: " . implode(', ', $user->roles) . "\n";
    } else {
        echo "User {$id} ({$user->display_name}): OK\n";
    }
}
```

### Test 5: Generate Demo Data
```php
// Run demo data generation
$agencyDemo = new AgencyDemoData();
$agencyDemo->run();
// Should complete without errors and all users have both roles
```

## Files Modified
- ✅ `src/Database/Demo/AgencyDemoData.php` (UPDATED)
  - Changed line 162 from hardcoded 'role' => 'agency' to 'roles' => $user_data['roles']
  - Now uses roles array from AgencyUsersData
  - Coverage: All 10 agency users (ID 130-139)

## Notes
- AgencyUsersData.php sudah memiliki struktur roles yang benar, tidak perlu diubah
- WPUserGenerator sudah support multiple roles (dari TODO-2060), hanya perlu menggunakan 'roles' parameter
- Total 10 agency users (ID 130-139)
- Semua users sekarang memiliki default role 'agency' + specific role 'agency_admin_dinas'
- Backward compatible dengan WPUserGenerator yang tetap support single 'role' string

## Related Tasks
- TODO-2060: Add Multiple Roles to Division Users (implementasi WPUserGenerator support multiple roles)
- TODO-2059: Agency Employee Users with roles array structure
- TODO-2058: Division Name Collection
- TODO-2057: Agency Name Collection
- TODO-2056: Role Management

## References
- Role management: `/wp-agency/includes/class-role-manager.php`
- WPUserGenerator: `/wp-agency/src/Database/Demo/WPUserGenerator.php`
- Pattern reference: `/wp-agency/src/Database/Demo/Data/AgencyUsersData.php`
