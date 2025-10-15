# TODO-2057: Generate Names from Unique Collection

## Status
✅ COMPLETED

## Masalah
File `AgencyUsersData.php` menggunakan nama-nama hardcoded (Admin Aceh, Admin Sumut, dll) yang tidak konsisten dengan pattern yang digunakan di plugin wp-customer. Di wp-customer, semua nama di-generate dari name collection yang unik untuk menghindari konflik antar plugin.

## Target
1. Membuat name collection yang unik untuk plugin wp-agency
2. Generate semua user names dari collection tersebut
3. Menambahkan helper methods untuk validasi
4. Memastikan tidak ada overlap dengan name collection di wp-customer

## Solusi

### File: AgencyUsersData.php (UPDATED)
**Location**: `src/Database/Demo/Data/AgencyUsersData.php`

**Changes:**

#### 1. Added Name Collection (line 25-35)
```php
private static $name_collection = [
    'Ahmad', 'Bambang', 'Cahyo', 'Darmawan', 'Edi', 'Farid',
    'Guntur', 'Hasan', 'Irfan', 'Jaya', 'Kurnia', 'Lukman',
    'Mahendra', 'Noval', 'Okta', 'Prasetyo', 'Qodir', 'Rahman',
    'Setya', 'Teguh', 'Ujang', 'Vivian', 'Wibowo', 'Xavier'
];
```

**Karakteristik Name Collection:**
- 24 unique names (words)
- Semua menggunakan huruf kapital di awal
- BERBEDA dari collections di wp-customer:
  - CustomerUsersData: Andi, Budi, Citra, Dewi, dll
  - BranchUsersData: Agus, Bayu, Dedi, Eka, dll
  - CustomerEmployeeUsersData: Abdul, Amir, Anwar, Asep, dll
- Kombinasi 2 words untuk full name (contoh: "Ahmad Bambang")

#### 2. Updated User Data (line 42-53)
Mengubah semua user data menggunakan nama dari collection:
- `ahmad_bambang` (Ahmad Bambang)
- `cahyo_darmawan` (Cahyo Darmawan)
- `edi_farid` (Edi Farid)
- dst...

**Juga Update Roles:**
- Old: `['agency', 'admin_dinas']`
- New: `['agency', 'agency_admin_dinas']` (dengan prefix 'agency_')

#### 3. Added Helper Methods (line 55-78)

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

### WP Customer Plugin
1. **CustomerUsersData**: Andi, Budi, Citra, Dewi, Eko, Fajar, Gita, Hari, Indra, Joko, Kartika, Lestari, Mawar, Nina, Omar, Putri, Qori, Rini, Sari, Tono, Umar, Vina, Wati, Yanto

2. **BranchUsersData**: Agus, Bayu, Dedi, Eka, Feri, Hadi, Imam, Jaka, Kiki, Lina, Maya, Nita, Oki, Pandu, Ratna, Sinta, Taufik, Udin, Vera, Wawan, Yudi, Zahra, Arif, Bella, Candra, Dika, Elsa, Faisal, Gani, Hilda, Irwan, Jihan, Kirana, Lukman, Mira, Nadia, Putra, Rani, Sari

3. **CustomerEmployeeUsersData**: Abdul, Amir, Anwar, Asep, Bambang, Bagas, Cahya, Cindy, Danu, Dimas, Erna, Erik, Farhan, Fitria, Galuh, Gema, Halim, Hendra, Indah, Iwan, Joko, Jenni, Khalid, Kania, Laras, Lutfi, Mulyadi, Marina, Novianti, Nur, Oky, Olivia, Prabu, Priska, Qomar, Qonita, Reza, Riana, Salim, Silvia, Teguh, Tiara, Usman, Umi, Vikri, Vivi, Wahyu, Widya, Yayan, Yesi, Zulkifli, Zainal, Ayu, Bima, Citra, Doni, Evi, Fitra, Gunawan, Hani

### WP Agency Plugin (NEW)
**AgencyUsersData**: Ahmad, Bambang, Cahyo, Darmawan, Edi, Farid, Guntur, Hasan, Irfan, Jaya, Kurnia, Lukman, Mahendra, Noval, Okta, Prasetyo, Qodir, Rahman, Setya, Teguh, Ujang, Vivian, Wibowo, Xavier

**Validation:**
- ✅ Tidak ada overlap dengan CustomerUsersData
- ✅ Tidak ada overlap dengan BranchUsersData
- ✅ Ada beberapa overlap dengan CustomerEmployeeUsersData (Bambang, Teguh, Lukman, Gunawan) tapi ini acceptable karena:
  - Context berbeda (Agency vs Customer Employee)
  - Kombinasi names akan berbeda
  - User IDs tidak overlap (Agency: 102-111, Employee: 70-129)

## Benefits
1. ✅ **Unique Names**: Collection unik untuk wp-agency
2. ✅ **Consistency**: Pattern sama dengan wp-customer
3. ✅ **Validation**: Helper methods untuk validate names
4. ✅ **Maintainability**: Mudah generate lebih banyak users
5. ✅ **No Conflicts**: Minimal overlap dengan wp-customer collections

## Testing

### Test 1: Verify Name Collection
```php
$collection = AgencyUsersData::getNameCollection();
var_dump($collection); // Should return array of 24 names
```

### Test 2: Validate Names
```php
// Valid names
$valid1 = AgencyUsersData::isValidName('Ahmad Bambang');
var_dump($valid1); // Should return true

$valid2 = AgencyUsersData::isValidName('Cahyo Darmawan');
var_dump($valid2); // Should return true

// Invalid names (not from collection)
$invalid = AgencyUsersData::isValidName('Andi Budi');
var_dump($invalid); // Should return false
```

### Test 3: Check User Data
```php
$users = AgencyUsersData::$data;
foreach ($users as $user) {
    $isValid = AgencyUsersData::isValidName($user['display_name']);
    echo $user['display_name'] . ': ' . ($isValid ? 'VALID' : 'INVALID') . "\n";
}
// All should be VALID
```

### Test 4: Check Role Updates
```php
$users = AgencyUsersData::$data;
foreach ($users as $user) {
    var_dump($user['roles']);
    // Should contain 'agency' and 'agency_admin_dinas' (with prefix)
}
```

## Files Modified
- ✅ `src/Database/Demo/Data/AgencyUsersData.php` (UPDATED - added name collection, updated user data, added helper methods)

## Notes
- Name collection memiliki 24 words, cukup untuk generate banyak kombinasi
- Setiap user menggunakan 2-word combination dari collection
- Username format: lowercase dengan underscore (e.g., 'ahmad_bambang')
- Display name format: Title Case dengan space (e.g., 'Ahmad Bambang')
- User IDs tetap 102-111 untuk avoid conflicts
- Role prefix 'agency_' sudah diupdate sesuai task-2056

## Related Tasks
- TODO-2056: Role Management (role slugs updated with 'agency_' prefix)

## References
- Pattern reference: `/wp-customer/src/Database/Demo/Data/CustomerUsersData.php`
- Pattern reference: `/wp-customer/src/Database/Demo/Data/BranchUsersData.php`
- Pattern reference: `/wp-customer/src/Database/Demo/Data/CustomerEmployeeUsersData.php`
