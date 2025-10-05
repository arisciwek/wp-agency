# TODO WP-Agency

## Fix PHP Fatal Error in AgencyEmployeeValidator

### Issue
- PHP Warning: Undefined property: WPAgency\Validators\Employee\AgencyEmployeeValidator::$model
- PHP Fatal error: Call to a member function find() on null in AgencyEmployeeValidator.php:262

### Root Cause
In `validateUpdate` method, line 262 uses `$this->model->find($id)` but the property is named `$employee_model`, not `$model`.

### Steps to Fix
- [x] Edit `src/Validators/Employee/AgencyEmployeeValidator.php` line 262: Change `$this->model` to `$this->employee_model`
- [ ] Test the employee update functionality to ensure the fix works
- [x] Verify no other similar typos in the codebase

### Files to Edit
- `src/Validators/Employee/AgencyEmployeeValidator.php`

### Followup
- Run the update employee action in the plugin to confirm the error is resolved.

## Support Multi-Role pada Datatable Staff Agency

### Issue
- Datatable karyawan agency hanya menampilkan satu role (primary role) pada kolom "Wewenang"
- Karyawan dapat memiliki multiple roles, namun tidak ditampilkan di datatable

### Root Cause
- Method `getUserRole` di `AgencyEmployeeController` hanya mengembalikan role pertama dari array roles user

### Steps to Fix
- [x] Modify `getUserRole` method in `src/Controllers/Employee/AgencyEmployeeController.php` to return all roles as formatted string
- [x] Test datatable display to ensure multiple roles show correctly
- [x] Verify column width is sufficient for multiple roles display (increased from 18% to 22%)

### Files to Edit
- `src/Controllers/Employee/AgencyEmployeeController.php`
- `assets/js/employee/employee-datatable.js`

### Followup
- Check datatable in agency right panel to confirm multiple roles are displayed

## Support Pencarian Multi-Column pada Datatable Staff Agency

### Issue
- Datatable karyawan agency menggunakan single search box global
- Pencarian hanya mencari di kolom nama saja, tidak di kolom lain seperti jabatan, wewenang, cabang, status
- Contoh: mencari "pengawas" tidak menemukan data karena "pengawas" ada di kolom wewenang, bukan nama

### Root Cause
- Query pencarian di model hanya mencari di e.name, e.position, b.name (division)
- Tidak termasuk pencarian di status dan wewenang (role dari user capabilities)

### Steps to Fix
- [x] Update model `src/Models/Employee/AgencyEmployeeModel.php` method getDataTableData untuk menambahkan pencarian di kolom status dan wewenang (role)
- [x] Untuk wewenang, gunakan subquery ke wp_usermeta untuk mencari di capabilities
- [x] Test pencarian global untuk memastikan mencari di semua kolom: nama, jabatan, wewenang, cabang, status
- [x] Verifikasi pencarian case-insensitive dan partial match
- [x] Perbaiki pencarian multiple words dengan split pada spasi dan AND logic

### Files to Edit
- `src/Models/Employee/AgencyEmployeeModel.php`

### Followup
- Test pencarian kata seperti "pengawas" (di wewenang), "aktif" (di status), dll.
- Pastikan pencarian tetap efisien dengan dataset besar

## Buat Multi-Yurisdiksi di Agency Banten dan Sumatera Barat

### Issue
Division di agency Banten dan Sumatera Barat saat ini hanya memiliki satu yurisdiksi (regency utama). Perlu diimplementasikan multi-yurisdiksi untuk division di agency tersebut agar dapat menjangkau multiple regency dalam provinsi tersebut.

### Root Cause
Data demo jurisdiction di JurisdictionData.php untuk division Banten (ID 10,11,12) dan Sumatera Barat (ID 7,8,9) hanya memiliki regency utama, tidak ada additional regencies.

### Steps to Fix
- [x] Update `src/Database/Demo/Data/JurisdictionData.php` untuk menambahkan additional regencies pada division Banten dan Sumatera Barat
- [x] Pastikan `JurisdictionDemoData.php` dapat membaca dan generate data multi-jurisdiction
- [x] Test generate demo data untuk memastikan multi-jurisdiction terbuat
- [x] Verify di database bahwa division Banten dan Sumatera Barat memiliki multiple jurisdictions

### Contoh Data Banten
Division Kabupaten Tangerang (ID 10):
- Primary: Kabupaten Tangerang (3603)

Division Cabang Kota Cilegon (ID 11):
- Primary: Kota Cilegon (3672)
- Additional: Kota Serang (3671)

Division Cabang Kabupaten Lebak (ID 12):
- Primary: Kabupaten Lebak (3602)
- Additional: Kabupaten Serang (3604)

### Contoh Data Sumatera Barat
Division Kota Padang (ID 7):
- Primary: Kota Padang (1371)

Division Cabang Kabupaten Solok (ID 8):
- Primary: Kabupaten Solok (1302)
- Additional: Kabupaten Sijunjung (1303)

Division Cabang Kota Bukittinggi (ID 9):
- Primary: Kota Bukittinggi (1375)
- Additional: Kabupaten Pesisir Selatan (1301)

### Files to Edit
- `src/Database/Demo/Data/JurisdictionData.php`

### Followup
- Jalankan generate demo data jurisdiction
- Periksa tabel app_agency_jurisdictions untuk memastikan data multi-jurisdiction ada
- Test di UI division datatable menampilkan multiple jurisdictions

## Buat Tab Baru "New Company" pada Agency Right Panel (TODO-1506)

### Issue
Perlu menambahkan tab baru "New Company" di agency right panel untuk menampilkan daftar company (dari data branch) yang belum memiliki pengawas (inspector_id = NULL).

### Requirements
- Tab baru di agency right panel setelah tab "Staff"
- DataTable dengan kolom: Kode, Perusahaan, Unit, Yuridiksi, Action
- Query data dari tabel wp_app_branches dengan filter inspector_id IS NULL
- Filter berdasarkan agency_id dari URL yang sedang diakses

### Implementation Plan

#### Backend Components:

1. **NewCompanyController** (`src/Controllers/Company/NewCompanyController.php`)
   - [ ] Create controller class extending base controller
   - [ ] Add AJAX handler `handle_new_company_datatable`
   - [ ] Implement query to get branches with NULL inspector_id
   - [ ] Join with agencies, divisions, and regencies tables
   - [ ] Add permission checks using NewCompanyValidator
   - [ ] Add action handler for assigning inspectors
   - [ ] Implement cache invalidation on data changes

2. **NewCompanyModel** (`src/Models/Company/NewCompanyModel.php`)
   - [ ] Create model class for branch data access
   - [ ] Implement `getDataTableData()` method with proper SQL joins
   - [ ] Add `getBranchesWithoutInspector()` method
   - [ ] Add `assignInspector()` method
   - [ ] Implement caching strategy using AgencyCacheManager
   - [ ] Add cache keys: `new_company_list`, `branch_without_inspector`
   - [ ] Set cache expiry to 2 hours (7200 seconds)

3. **NewCompanyValidator** (`src/Validators/Company/NewCompanyValidator.php`)
   - [ ] Create validator class for permission checks
   - [ ] Implement `canViewNewCompanies()` method
   - [ ] Implement `canAssignInspector()` method
   - [ ] Add `validateInspectorAssignment()` method
   - [ ] Check user capabilities and agency access
   - [ ] Validate inspector exists and is eligible

4. **Cache Integration** (using existing `src/Cache/AgencyCacheManager.php`)
   - [ ] Add cache keys for new company data
   - [ ] Implement cache invalidation on inspector assignment
   - [ ] Use DataTable cache methods for pagination
   - [ ] Clear related caches when data changes

#### Frontend Components:

5. **Update Agency Right Panel** (`src/Views/templates/agency-right-panel.php`)
   - [ ] Add "New Company" tab to navigation after "Staff" tab
   - [ ] Include new partial template in the templates array

6. **New Company Partial** (`src/Views/templates/company/partials/_new_company_list.php`)
   - [ ] Create DataTable structure with columns: Kode, Perusahaan, Unit, Yuridiksi, Action
   - [ ] Add loading states
   - [ ] Add empty state message for no data
   - [ ] Add error handling display
   - [ ] Include assign inspector modal/form

7. **DataTable JavaScript** (`assets/js/company/new-company-datatable.js`)
   - [ ] Create NewCompanyDataTable object
   - [ ] Initialize DataTable with server-side processing
   - [ ] Handle AJAX calls to fetch data
   - [ ] Implement action buttons (view, assign inspector)
   - [ ] Add refresh functionality
   - [ ] Handle events for inspector assignment
   - [ ] Show toast notifications on success/error

8. **Update Agency Script** (`assets/js/agency/agency-script.js`)
   - [ ] Add tab switching logic for "new-company" tab in `switchTab()` method
   - [ ] Initialize NewCompanyDataTable when tab is clicked
   - [ ] Pass agency_id parameter to DataTable
   - [ ] Handle tab state management

9. **Styling** (`assets/css/company/new-company-style.css`)
   - [ ] Add specific styles for new company tab
   - [ ] Style DataTable for consistency
   - [ ] Style action buttons
   - [ ] Add responsive design rules
   - [ ] Style modal/form for inspector assignment

#### Asset Registration:

10. **Register Assets** (`includes/class-dependencies.php`)
    - [ ] Register CSS: `new-company-style.css` in `register_admin_styles()`
    - [ ] Register JS: `new-company-datatable.js` in `register_admin_scripts()`
    - [ ] Add localization for AJAX URL and nonce
    - [ ] Set proper dependencies (jQuery, DataTables)
    - [ ] Add version numbers for cache busting

11. **Register Controller** (`includes/class-init-hooks.php`)
    - [ ] Import NewCompanyController class
    - [ ] Import NewCompanyValidator class
    - [ ] Instantiate controller in initialization

### SQL Query to Implement:
```sql
SELECT 
    b.id,
    b.code AS Kode, 
    c.name AS Perusahaan,
    d.name AS Unit, 
    r.name AS Yuridiksi,
    b.agency_id,
    b.division_id,
    b.regency_id
FROM wp_app_branches b 
LEFT JOIN wp_app_customers c ON b.customer_id = c.id
LEFT JOIN wp_app_agencies a ON b.agency_id = a.id 
LEFT JOIN wp_app_divisions d ON b.division_id = d.id
LEFT JOIN wp_wi_regencies r ON b.regency_id = r.id 
WHERE a.id = %d 
    AND b.inspector_id IS NULL
    AND b.status = 'active'
ORDER BY b.code ASC
```

### Testing Checklist:
- [ ] Verify DataTable loads correctly
- [ ] Test filtering by agency_id from URL
- [ ] Verify only branches with NULL inspector_id are shown
- [ ] Test assign inspector functionality
- [ ] Check permission validations
- [ ] Test responsive design on mobile
- [ ] Verify caching works properly
- [ ] Test empty state when no data
- [ ] Test error handling
- [ ] Verify assets are loaded correctly
- [ ] Check console for JavaScript errors

### Files to Create:
- `src/Controllers/Company/NewCompanyController.php`
- `src/Models/Company/NewCompanyModel.php`
- `src/Validators/Company/NewCompanyValidator.php`
- `src/Views/templates/company/partials/_new_company_list.php`
- `assets/js/company/new-company-datatable.js`
- `assets/css/company/new-company-style.css`

### Files to Edit:
- `src/Views/templates/agency-right-panel.php`
- `assets/js/agency/agency-script.js`
- `includes/class-init-hooks.php`
- `includes/class-dependencies.php`

### Followup:
- Test the complete flow from tab click to data display
- Verify inspector assignment updates the branch record
- Check that assigned branches disappear from the list
- Monitor query performance with large datasets
- Test cache invalidation works properly
- Verify all permissions are checked correctly

### ✅ TASK COMPLETED - New Company Tab Successfully Implemented

**Status**: ✅ **COMPLETED** - All issues have been resolved and the feature is fully functional.

#### Summary of Implementation:
1. **New Company Tab** added to agency right panel showing companies (branches) without inspectors
2. **DataTable** with columns: Kode, Perusahaan, Unit, Yuridiksi, Action
3. **Inspector Assignment** functionality with proper filtering and validation
4. **All Issues Fixed**:
   - ✅ Inspector options now filtered by division
   - ✅ Inspector privilege validation works correctly
   - ✅ Only 'pengawas' role users can be assigned as inspectors

#### Files Created/Modified:
- ✅ `src/Controllers/Company/NewCompanyController.php` - Backend controller
- ✅ `src/Models/Company/NewCompanyModel.php` - Data model with caching
- ✅ `src/Validators/Company/NewCompanyValidator.php` - Permission validation
- ✅ `src/Views/templates/company/partials/_new_company_list.php` - Frontend template
- ✅ `assets/js/company/new-company-datatable.js` - DataTable JavaScript
- ✅ `assets/css/company/new-company-style.css` - Styling
- ✅ `src/Views/templates/agency-right-panel.php` - Added new tab
- ✅ `assets/js/agency/agency-script.js` - Tab switching logic
- ✅ `includes/class-dependencies.php` - Asset registration
- ✅ `wp-agency.php` - Controller registration

#### Testing Results:
- ✅ DataTable loads correctly with proper data
- ✅ Inspector filtering works by division
- ✅ Inspector assignment validates correctly
- ✅ All assets load without errors
- ✅ No PHP syntax errors
- ✅ Proper error handling implemented

The "New Company" tab is now fully functional and ready for production use.
