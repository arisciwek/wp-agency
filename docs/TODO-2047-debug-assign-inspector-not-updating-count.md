# /wp-agency/docs/TODO-2047-debug-assign-inspector-not-updating-count.md

## masalah
Setelah assign inspector berhasil (data sudah masuk ke database), namun saat assign inspector lagi dengan pengawas yang sama, di modal pilih inspector muncul keterangan "Pengawas ini saat ini memiliki 0 penugasan" padahal seharusnya menunjukkan jumlah penugasan yang sebenarnya.

Contoh:
- Data sudah assigned: 1691Lu16Uc-TEST05 PT Global Teknindo Test Branch 5 - Kabupaten Aceh Tenggara cabang - Disnaker Provinsi Aceh Disnaker Provinsi Aceh Division Kota Sabang Cici Lestari
- Saat assign lagi dengan Cici Lestari, muncul "Pengawas ini saat ini memiliki 0 penugasan" (harusnya 1 atau lebih)

## target
Perbaiki tampilan counter penugasan inspector di modal assign inspector agar menunjukkan jumlah penugasan yang sebenarnya.

## baca
/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/assets/js/company/new-company-datatable.js
/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/src/Controllers/Company/NewCompanyController.php
/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/src/Models/Company/NewCompanyModel.php
/home/mkt01/Public/wppm/public_html/wp-content/plugins/wp-agency/src/Validators/Company/NewCompanyValidator.php

revisi /wp-agency/TODO.md, catat apa yang akan anda lakukan sebelum memperbaiki kode
