<?php
/**
 * Division Users Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/Data/DivisionUsersData.php
 *
 * Description: Static division user data for demo generation.
 *              Used by WPUserGenerator and DivisionDemoData.
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class DivisionUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 112;
    const USER_ID_END = 131;

    // gunakan ID user mulai 112 sampai 141 untuk menghindari bentrok dengan user demo lain
    // setiap agency memiliki 3 user division: pusat, cabang1, cabang2
    // contoh: agency ID 1 (Disnaker Provinsi Aceh) memiliki user division ID 102, 112, 113
    // untuk setiap division dengan key pusat, id usernya sama dengan id user agencynya
    // untuk cabang1 dan cabang2, id usernya bertambah 1 id user agency
    // sehingga untuk agency ID 1 (Disnaker Provinsi Aceh) dengan user agency ID 102
    
    public static $data = [
        1 => [  // Disnaker Provinsi Aceh
            'pusat' => ['id' => 102, 'username' => 'admin_aceh', 'display_name' => 'Admin Aceh', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 112, 'username' => 'citra_aceh', 'display_name' => 'Citra Dewi', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 113, 'username' => 'dani_aceh', 'display_name' => 'Dani Hermawan', 'role' => 'admin_unit']
        ],
        2 => [  // Disnaker Provinsi Sumatera Utara
            'pusat' => ['id' => 103, 'username' => 'admin_sumut', 'display_name' => 'Admin Sumatera Utara', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 114, 'username' => 'fajar_sumut', 'display_name' => 'Fajar Ramadhan', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 115, 'username' => 'gita_sumut', 'display_name' => 'Gita Lestari', 'role' => 'admin_unit']
        ],
        3 => [  // Disnaker Provinsi Sumatera Barat
            'pusat' => ['id' => 104, 'username' => 'admin_sumbar', 'display_name' => 'Admin Sumatera Barat', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 116, 'username' => 'indah_sumbar', 'display_name' => 'Indah Sari', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 117, 'username' => 'joko_sumbar', 'display_name' => 'Joko Santoso', 'role' => 'admin_unit']
        ],
        4 => [  // Disnaker Provinsi Banten
            'pusat' => ['id' => 105, 'username' => 'admin_banten', 'display_name' => 'Admin Banten', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 118, 'username' => 'lukman_banten', 'display_name' => 'Lukman Hakim', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 119, 'username' => 'mira_banten', 'display_name' => 'Mira Pratiwi', 'role' => 'admin_unit']
        ],
        5 => [  // Disnaker Provinsi Jawa Barat
            'pusat' => ['id' => 106, 'username' => 'admin_jabar', 'display_name' => 'Admin Jawa Barat', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 120, 'username' => 'oki_jabar', 'display_name' => 'Oki Wibowo', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 121, 'username' => 'putri_jabar', 'display_name' => 'Putri Maharani', 'role' => 'admin_unit']
        ],
        6 => [  // Disnaker Provinsi Jawa Tengah
            'pusat' => ['id' => 107, 'username' => 'admin_jateng', 'display_name' => 'Admin Jawa Tengah', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 122, 'username' => 'sinta_jateng', 'display_name' => 'Sinta Dewi', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 123, 'username' => 'tomi_jateng', 'display_name' => 'Tomi Gunawan', 'role' => 'admin_unit']
        ],
        7 => [  // Disnaker Provinsi Jawa Timur
            'pusat' => ['id' => 108, 'username' => 'admin_jatim', 'display_name' => 'Admin Jawa Timur', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 124, 'username' => 'vino_jatim', 'display_name' => 'Vino Pratama', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 125, 'username' => 'wulan_jatim', 'display_name' => 'Wulan Sari', 'role' => 'admin_unit']
        ],
        8 => [  // Disnaker Provinsi Kalimantan Barat
            'pusat' => ['id' => 109, 'username' => 'admin_kalbar', 'display_name' => 'Admin Kalimantan Barat', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 126, 'username' => 'zara_kalbar', 'display_name' => 'Zara Putri', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 127, 'username' => 'agung_kalbar', 'display_name' => 'Agung Nugroho', 'role' => 'admin_unit']
        ],
        9 => [  // Disnaker Provinsi Kalimantan Timur
            'pusat' => ['id' => 110, 'username' => 'admin_kaltim', 'display_name' => 'Admin Kalimantan Timur', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 128, 'username' => 'candra_kaltim', 'display_name' => 'Candra Wijaya', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 129, 'username' => 'dina_kaltim', 'display_name' => 'Dina Puspita', 'role' => 'admin_unit']
        ],
        10 => [  // Disnaker Provinsi Sulawesi Selatan
            'pusat' => ['id' => 111, 'username' => 'admin_sulsel', 'display_name' => 'Admin Sulawesi Selatan', 'role' => 'admin_unit'],
            'cabang1' => ['id' => 130, 'username' => 'fani_sulsel', 'display_name' => 'Fani Hartanti', 'role' => 'admin_unit'],
            'cabang2' => ['id' => 131, 'username' => 'guntur_sulsel', 'display_name' => 'Guntur Prasetyo', 'role' => 'admin_unit']
        ]
    ];
}
