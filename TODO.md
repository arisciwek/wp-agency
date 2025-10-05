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
