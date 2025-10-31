# TODO-3093: Fix Jurisdiction Generation Error

**Status**: ✅ COMPLETED
**Date**: 2025-10-31
**Priority**: HIGH

## Deskripsi Masalah

Task ini bertujuan memperbaiki error saat generate jurisdiction. Minggu lalu generate agency dan division sudah diubah dari create bulk data menjadi generate real runtime, tetapi tidak sampai generate jurisdiction. Saat di-test, ternyata jurisdiction generation kena pengaruh dan ada error.

## Root Cause

Error terjadi karena **mismatch antara struktur data JurisdictionData dengan struktur database**:

1. **JurisdictionData.php** menggunakan key: `'pusat'`, `'cabang1'`, `'cabang2'`
2. **Database division.type** hanya ada 2 nilai: `'pusat'` dan `'cabang'`
3. **JurisdictionDemoData.php** query division dengan `WHERE type = 'cabang1'` → tidak menemukan apa-apa!

```php
// KODE LAMA (SALAH):
foreach (['pusat', 'cabang1', 'cabang2'] as $division_type) {
    // Query ini TIDAK menemukan cabang divisions!
    $division = $wpdb->get_row("... WHERE type = '$division_type'");
}
```

## Solusi yang Diterapkan

### 1. Refactor method `generate()` di JurisdictionDemoData.php:179-246

**Perubahan**:
- Query `pusat` division terpisah dengan `WHERE type = 'pusat'`
- Query ALL `cabang` divisions dengan `WHERE type = 'cabang' ORDER BY id ASC`
- Map cabang ke cabang1/cabang2 berdasarkan index: `cabang_key = 'cabang' . ($idx + 1)`

```php
// PROCESS PUSAT DIVISION
$pusat_division = $wpdb->get_row("... WHERE type = 'pusat'");
if ($pusat_division) {
    $jurisdiction_data = JurisdictionData::getForDivision($agency_index, 'pusat');
    $generated_count += $this->createJurisdictionsForDivision(...);
}

// PROCESS CABANG DIVISIONS
$cabang_divisions = $wpdb->get_results("... WHERE type = 'cabang' ORDER BY id");
foreach ($cabang_divisions as $idx => $cabang_division) {
    $cabang_key = 'cabang' . ($idx + 1); // Map ke cabang1, cabang2, etc
    $jurisdiction_data = JurisdictionData::getForDivision($agency_index, $cabang_key);
    $generated_count += $this->createJurisdictionsForDivision(...);
}
```

### 2. Refactor method `validate()` di JurisdictionDemoData.php:107-164

**Perubahan**: Sama dengan generate(), validasi juga disesuaikan

### 3. Add helper method `validateJurisdictionData()` di line 295-324

Extract validation logic ke method tersendiri untuk reusability

### 4. Add helper method `createJurisdictionsForDivision()` di line 326-365

Extract creation logic ke method tersendiri untuk reusability dan clean code

## File yang Diubah

### Modified
- `/wp-agency/src/Database/Demo/JurisdictionDemoData.php`
  - Line 107-164: Refactor validate() method
  - Line 179-246: Refactor generate() method
  - Line 295-324: Add validateJurisdictionData() helper
  - Line 326-365: Add createJurisdictionsForDivision() helper

### Data Source (Tidak Diubah)
- `/wp-agency/src/Database/Demo/Data/JurisdictionData.php` (v2.0.0 sudah benar)

## Testing Results

### Before Fix
```
Total jurisdictions: 23
- pusat: 23
- cabang: 0  ❌ TIDAK ADA
```

### After Fix
```
Total jurisdictions: 37 ✅
- pusat: 13
- cabang: 24 ✅ BERHASIL DIBUAT
```

### Sample Data Verification
```
Disnaker Provinsi Aceh
- pusat: 3 regencies (1172, 1103, 1102) ✅
- cabang: 2 divisions, masing-masing 1 regency ✅

Disnaker Provinsi DKI Jakarta
- pusat: 1 regency (3174) ✅
- cabang: 2 divisions (3173, 3175) ✅
```

## Pattern yang Digunakan

Pattern ini konsisten dengan:
- `DivisionDemoData.php` - yang juga query cabang divisions dengan `WHERE type = 'cabang'`
- `DivisionUsersData.php` - yang menggunakan key `cabang1`/`cabang2` untuk data mapping

## Changelog Notes

Update changelog di `JurisdictionDemoData.php`:

```php
/**
 * Changelog:
 * 2.0.0 - 2025-10-31 (TODO-3093)
 * - RESTRUCTURE: Changed to use agency index pattern (like DivisionDemoData)
 * - FIX: Works with runtime generated division IDs
 * - PATTERN: Loop agencies, query divisions by agency_id and type
 * - DATA: Uses JurisdictionData v2.0.0 with agency index structure
 * - FIX: Properly maps database type 'cabang' to JurisdictionData keys 'cabang1'/'cabang2'
 * - REFACTOR: Extract logic to validateJurisdictionData() and createJurisdictionsForDivision()
```

## Related Files for Reference

Database Tables:
- `/wp-agency/src/Database/Tables/AgencysDB.php`
- `/wp-agency/src/Database/Tables/DivisionsDB.php`
- `/wp-agency/src/Database/Tables/JurisdictionDB.php`

Demo Data:
- `/wp-agency/src/Database/Demo/AgencyDemoData.php`
- `/wp-agency/src/Database/Demo/DivisionDemoData.php`
- `/wp-agency/src/Database/Demo/Data/JurisdictionData.php`

## Kesimpulan

✅ **COMPLETED** - Error jurisdiction generation berhasil diperbaiki dengan menyesuaikan query pattern untuk cabang divisions. Sekarang jurisdiction dapat di-generate untuk semua division types (pusat dan cabang).
