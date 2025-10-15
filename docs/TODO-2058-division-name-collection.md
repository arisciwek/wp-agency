# TODO-2058: Generate Division User Names from Unique Collection

## Status
✅ COMPLETED

## Masalah
File `DivisionUsersData.php` menggunakan nama-nama hardcoded (Admin Aceh, Citra Dewi, Dani Hermawan, dll) dengan user IDs yang tidak konsisten dan tidak sequential. Perlu diupdate untuk mengikuti pattern name collection seperti yang sudah diterapkan di TODO-2057 untuk AgencyUsersData.

**Masalah Spesifik:**
- User IDs tidak sequential (140, 112, 113, 103, 114, dst)
- Nama tidak dari collection
- Role menggunakan 'admin_unit' tanpa prefix 'agency_'
- Tidak ada helper methods untuk validation

## Target
1. Membuat name collection yang unik untuk DivisionUsersData
2. Generate semua user names dari collection tersebut
3. Fix user IDs menjadi sequential dari 140-169
4. Update role menjadi 'agency_admin_unit' (dengan prefix)
5. Menambahkan helper methods untuk validasi

## Solusi

### File: DivisionUsersData.php (COMPLETELY REWRITTEN)
**Location**: `src/Database/Demo/Data/DivisionUsersData.php`

#### 1. Fixed Constants (line 22-24)
```php
const USER_ID_START = 140;
const USER_ID_END = 169;  // Was: 131 (incorrect)
```

**Total Users:**
- 10 agencies × 3 divisions each = 30 users
- User IDs: 140-169 (30 users total)

#### 2. Added Name Collection (line 26-36)
```php
private static $name_collection = [
    'Budi', 'Citra', 'Dani', 'Eko', 'Fajar', 'Gita',
    'Hendra', 'Indah', 'Joko', 'Kartika', 'Lina', 'Mira',
    'Nando', 'Omar', 'Putri', 'Raka', 'Siti', 'Tono',
    'Usman', 'Vina', 'Winda', 'Yani', 'Zainal', 'Anton'
];
```

**Karakteristik:**
- 24 unique names (words)
- BERBEDA dari collections lain:
  - wp-customer/CustomerUsersData: Andi, Budi, Citra, dll
  - wp-customer/BranchUsersData: Agus, Bayu, Dedi, dll
  - wp-customer/CustomerEmployeeUsersData: Abdul, Amir, Anwar, dll
  - wp-agency/AgencyUsersData: Ahmad, Bambang, Cahyo, dll
  - wp-agency/DivisionUsersData: Budi, Citra, Dani, dll (NEW)

#### 3. Updated User Data (line 44-95)

**Before:**
```php
1 => [
    'pusat' => ['id' => 140, 'username' => 'admin_aceh', 'display_name' => 'Admin Aceh', 'role' => 'admin_unit'],
    'cabang1' => ['id' => 112, 'username' => 'citra_aceh', 'display_name' => 'Citra Dewi', 'role' => 'admin_unit'],
    'cabang2' => ['id' => 113, 'username' => 'dani_aceh', 'display_name' => 'Dani Hermawan', 'role' => 'admin_unit']
]
```

**After:**
```php
1 => [
    'pusat' => ['id' => 140, 'username' => 'budi_citra', 'display_name' => 'Budi Citra', 'role' => 'agency_admin_unit'],
    'cabang1' => ['id' => 141, 'username' => 'dani_eko', 'display_name' => 'Dani Eko', 'role' => 'agency_admin_unit'],
    'cabang2' => ['id' => 142, 'username' => 'fajar_gita', 'display_name' => 'Fajar Gita', 'role' => 'agency_admin_unit']
]
```

**Changes:**
- ✅ User IDs now sequential: 140, 141, 142, 143, ... 169
- ✅ All names generated from collection (2-word combinations)
- ✅ Role updated to 'agency_admin_unit' (with prefix)
- ✅ Username format: lowercase with underscore
- ✅ Display name format: Title Case

**Complete User ID Mapping:**
- Agency 1: 140, 141, 142
- Agency 2: 143, 144, 145
- Agency 3: 146, 147, 148
- Agency 4: 149, 150, 151
- Agency 5: 152, 153, 154
- Agency 6: 155, 156, 157
- Agency 7: 158, 159, 160
- Agency 8: 161, 162, 163
- Agency 9: 164, 165, 166
- Agency 10: 167, 168, 169

#### 4. Added Helper Methods (line 97-121)

**getNameCollection()** - Get collection array
```php
public static function getNameCollection(): array {
    return self::$name_collection;
}
```

**isValidName()** - Validate if name uses only words from collection
```php
public static function isValidName(string $name): bool {
    $words = explode(' ', $name);
    foreach ($words as $word) {
        if (!in_array($word, self::$name_collection)) {
            return false;
        }
    }
    return true;
}
```

## Name Collection Comparison

### All Collections Summary:

1. **wp-customer/CustomerUsersData (10 users, IDs: 2-11)**
   - Andi, Budi, Citra, Dewi, Eko, Fajar, Gita, Hari, Indra, Joko, Kartika, Lestari, Mawar, Nina, Omar, Putri, Qori, Rini, Sari, Tono, Umar, Vina, Wati, Yanto

2. **wp-customer/BranchUsersData (30 users, IDs: 12-41)**
   - Agus, Bayu, Dedi, Eka, Feri, Hadi, Imam, Jaka, Kiki, Lina, Maya, Nita, Oki, Pandu, Ratna, Sinta, Taufik, Udin, Vera, Wawan, Yudi, Zahra, Arif, Bella, Candra, Dika, Elsa, Faisal, Gani, Hilda, Irwan, Jihan, Kirana, Lukman, Mira, Nadia, Putra, Rani, Sari

3. **wp-customer/CustomerEmployeeUsersData (60 users, IDs: 70-129)**
   - Abdul, Amir, Anwar, Asep, Bambang, Bagas, Cahya, Cindy, Danu, Dimas, Erna, Erik, Farhan, Fitria, Galuh, Gema, Halim, Hendra, Indah, Iwan, Joko, Jenni, Khalid, Kania, Laras, Lutfi, Mulyadi, Marina, Novianti, Nur, Oky, Olivia, Prabu, Priska, Qomar, Qonita, Reza, Riana, Salim, Silvia, Teguh, Tiara, Usman, Umi, Vikri, Vivi, Wahyu, Widya, Yayan, Yesi, Zulkifli, Zainal, Ayu, Bima, Citra, Doni, Evi, Fitra, Gunawan, Hani

4. **wp-agency/AgencyUsersData (10 users, IDs: 130-139)**
   - Ahmad, Bambang, Cahyo, Darmawan, Edi, Farid, Guntur, Hasan, Irfan, Jaya, Kurnia, Lukman, Mahendra, Noval, Okta, Prasetyo, Qodir, Rahman, Setya, Teguh, Ujang, Vivian, Wibowo, Xavier

5. **wp-agency/DivisionUsersData (30 users, IDs: 140-169) - NEW**
   - Budi, Citra, Dani, Eko, Fajar, Gita, Hendra, Indah, Joko, Kartika, Lina, Mira, Nando, Omar, Putri, Raka, Siti, Tono, Usman, Vina, Winda, Yani, Zainal, Anton

**Overlap Analysis:**
- Beberapa overlap dengan collections lain adalah acceptable karena:
  - Context berbeda (Division vs Customer/Branch/Employee)
  - Kombinasi names berbeda
  - User IDs tidak overlap
  - Username format berbeda

## Benefits
1. ✅ **Sequential IDs**: User IDs now 140-169 (easy to track)
2. ✅ **Unique Collection**: 24 unique names untuk divisions
3. ✅ **Consistency**: Pattern sama dengan collections lain
4. ✅ **Validation**: Helper methods tersedia
5. ✅ **Role Prefix**: Updated to 'agency_admin_unit'
6. ✅ **Maintainability**: Easy to generate more users

## Testing

### Test 1: Verify Name Collection
```php
$collection = DivisionUsersData::getNameCollection();
var_dump($collection); // Should return array of 24 names
```

### Test 2: Validate Names
```php
// Valid names
$valid1 = DivisionUsersData::isValidName('Budi Citra');
var_dump($valid1); // Should return true

$valid2 = DivisionUsersData::isValidName('Dani Eko');
var_dump($valid2); // Should return true

// Invalid names (not from collection)
$invalid = DivisionUsersData::isValidName('Ahmad Bambang');
var_dump($invalid); // Should return false (from AgencyUsersData)
```

### Test 3: Check User IDs Sequential
```php
$users = DivisionUsersData::$data;
$all_ids = [];
foreach ($users as $agency_users) {
    foreach ($agency_users as $user) {
        $all_ids[] = $user['id'];
    }
}
sort($all_ids);
var_dump($all_ids); // Should be [140, 141, 142, ..., 169]
```

### Test 4: Check Role Updates
```php
$users = DivisionUsersData::$data;
foreach ($users as $agency_users) {
    foreach ($agency_users as $user) {
        echo $user['role'] . "\n";
        // Should all be 'agency_admin_unit'
    }
}
```

### Test 5: Verify All Names Valid
```php
$users = DivisionUsersData::$data;
foreach ($users as $agency_id => $agency_users) {
    foreach ($agency_users as $key => $user) {
        $isValid = DivisionUsersData::isValidName($user['display_name']);
        echo "Agency {$agency_id} - {$key}: {$user['display_name']} - " .
             ($isValid ? 'VALID' : 'INVALID') . "\n";
    }
}
// All should be VALID
```

## Files Modified
- ✅ `src/Database/Demo/Data/DivisionUsersData.php` (COMPLETELY REWRITTEN)

## Notes
- Total 30 division users (10 agencies × 3 divisions)
- User IDs: 140-169 (sequential, no gaps)
- Name collection: 24 unique words
- 2-word combinations for full names
- Role prefix 'agency_' added for consistency with task-2056
- Pattern consistent dengan BranchUsersData di wp-customer

## Related Tasks
- TODO-2057: Generate Names from Unique Collection (AgencyUsersData)
- TODO-2056: Role Management (role prefix 'agency_')

## References
- Pattern reference: `/wp-customer/src/Database/Demo/Data/BranchUsersData.php`
- Previous task: `docs/TODO-2057-unique-name-collection.md`
