# docs/TODO-0941-select_available_province_on_agency_creation.md

## Target
mencari ID provinsi dan Nama provinsi yang ID Provinsinya belum di assign ke agency 

pada 

add_action('wp_ajax_get_available_provinces_for_agency_creation', [$this, 'getAvailableProvincesForAgencyCreation']);
        
raw querynya adalah
SELECT p.id, p.name FROM wp_wi_provinces p LEFT JOIN wp_app_agencies a ON a.provinsi_code = p.code WHERE a.provinsi_code IS NULL

buat debug raw query untuk menampilkan available province pada saat create division


buat method baru getAvailableProvincesForAgencyCreation()
Tambahkan action hook 'wp_ajax_get_available_provinces_for_agency_creation'.

file :
/wp-agency/assets/js/agency/create-agency-form.js
/wp-agency/docs/TODO-0941-select_available_province_on_agency_creation.md
/wp-agency/src/Controllers/AgencyController.php
/wp-agency/src/Validators/AgencyValidator.php

atau file lain yang terlibat
