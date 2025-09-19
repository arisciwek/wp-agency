<?php
/**
 * Employee Users Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/Data/AgencyEmployeeUsersData.php
 *
 * Description: Static employee user data for demo generation.
 *              Used by WPUserGenerator and AgencyEmployeeDemoData.
 *              60 users total (2 per division × 30 divisions)
 *              User IDs: 132-187
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class AgencyEmployeeUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 132;
    const USER_ID_END = 187;

    public static $data = [
        // Agency 1 (Disnaker Provinsi Aceh) - Division 1 (Pusat)
        132 => [
            'id' => 132,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'finance_aceh1',
            'display_name' => 'Aditya Pratama',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        133 => [
            'id' => 133,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'legal_aceh1',
            'display_name' => 'Sarah Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 1 (Disnaker Provinsi Aceh) - Division 2 (Cabang 1)
        134 => [
            'id' => 134,
            'agency_id' => 1,
            'division_id' => 2,
            'username' => 'finance_aceh2',
            'display_name' => 'Bima Setiawan',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        135 => [
            'id' => 135,
            'agency_id' => 1,
            'division_id' => 2,
            'username' => 'operation_aceh2',
            'display_name' => 'Diana Puspita',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Agency 1 (Disnaker Provinsi Aceh) - Division 3 (Cabang 2)
        136 => [
            'id' => 136,
            'agency_id' => 1,
            'division_id' => 3,
            'username' => 'operation_aceh3',
            'display_name' => 'Eko Wibowo',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        137 => [
            'id' => 137,
            'agency_id' => 1,
            'division_id' => 3,
            'username' => 'finance_aceh3',
            'display_name' => 'Fina Sari',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 2 (Disnaker Provinsi Sumatera Utara) - Division 1 (Pusat)
        138 => [
            'id' => 138,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'legal_sumut1',
            'display_name' => 'Gunawan Santoso',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        139 => [
            'id' => 139,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'finance_sumut1',
            'display_name' => 'Hana Permata',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 2 (Disnaker Provinsi Sumatera Utara) - Division 2 (Cabang 1)
        140 => [
            'id' => 140,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'operation_sumut2',
            'display_name' => 'Irfan Hakim',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        141 => [
            'id' => 141,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'purchase_sumut2',
            'display_name' => 'Julia Putri',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 2 (Disnaker Provinsi Sumatera Utara) - Division 3 (Cabang 2)
        142 => [
            'id' => 142,
            'agency_id' => 2,
            'division_id' => 6,
            'username' => 'finance_sumut3',
            'display_name' => 'Krisna Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        143 => [
            'id' => 143,
            'agency_id' => 2,
            'division_id' => 6,
            'username' => 'legal_sumut3',
            'display_name' => 'Luna Safitri',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 3 (Disnaker Provinsi Sumatera Barat) - Division 1 (Pusat)
        144 => [
            'id' => 144,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'operation_sumbar1',
            'display_name' => 'Mario Gunawan',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        145 => [
            'id' => 145,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'finance_sumbar1',
            'display_name' => 'Nadia Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 3 (Disnaker Provinsi Sumatera Barat) - Division 2 (Cabang 1)
        146 => [
            'id' => 146,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'legal_sumbar2',
            'display_name' => 'Oscar Pradana',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        147 => [
            'id' => 147,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'operation_sumbar2',
            'display_name' => 'Putri Handayani',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 3 (Disnaker Provinsi Sumatera Barat) - Division 3 (Cabang 2)
        148 => [
            'id' => 148,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'finance_sumbar3',
            'display_name' => 'Qori Rahman',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        149 => [
            'id' => 149,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'legal_sumbar3',
            'display_name' => 'Ratih Purnama',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Agency 4 (Disnaker Provinsi Banten) - Division 1 (Pusat)
        150 => [
            'id' => 150,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'operation_banten1',
            'display_name' => 'Surya Pratama',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        151 => [
            'id' => 151,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'finance_banten1',
            'display_name' => 'Tania Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 5 (Disnaker Provinsi Jawa Barat) - Division 1 (Pusat)
        152 => [
            'id' => 152,
            'agency_id' => 5,
            'division_id' => 13,
            'username' => 'finance_jabar1',
            'display_name' => 'Surya Pratama',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        153 => [
            'id' => 153,
            'agency_id' => 5,
            'division_id' => 13,
            'username' => 'legal_jabar1',
            'display_name' => 'Tania Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 5 (Disnaker Provinsi Jawa Barat) - Division 2 (Cabang 1)
        154 => [
            'id' => 154,
            'agency_id' => 5,
            'division_id' => 14,
            'username' => 'operation_jabar2',
            'display_name' => 'Umar Hakim',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        155 => [
            'id' => 155,
            'agency_id' => 5,
            'division_id' => 14,
            'username' => 'finance_jabar2',
            'display_name' => 'Vina Putri',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 5 (Disnaker Provinsi Jawa Barat) - Division 3 (Cabang 2)
        156 => [
            'id' => 156,
            'agency_id' => 5,
            'division_id' => 15,
            'username' => 'legal_jabar3',
            'display_name' => 'Wayan Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        157 => [
            'id' => 157,
            'agency_id' => 5,
            'division_id' => 15,
            'username' => 'operation_jabar3',
            'display_name' => 'Xena Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 6 (Disnaker Provinsi Jawa Tengah) - Division 1 (Pusat)
        158 => [
            'id' => 158,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'finance_jateng1a',
            'display_name' => 'Eko Santoso',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        159 => [
            'id' => 159,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'legal_jateng1',
            'display_name' => 'Fitri Wulandari',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 6 (Disnaker Provinsi Jawa Tengah) - Division 2 (Cabang 1)
        160 => [
            'id' => 160,
            'agency_id' => 6,
            'division_id' => 17,
            'username' => 'operation_jateng2',
            'display_name' => 'Galih Prasetyo',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        161 => [
            'id' => 161,
            'agency_id' => 6,
            'division_id' => 17,
            'username' => 'finance_jateng2',
            'display_name' => 'Hesti Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 6 (Disnaker Provinsi Jawa Tengah) - Division 3 (Cabang 2)
        162 => [
            'id' => 162,
            'agency_id' => 6,
            'division_id' => 18,
            'username' => 'legal_jateng3',
            'display_name' => 'Indra Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        163 => [
            'id' => 163,
            'agency_id' => 6,
            'division_id' => 18,
            'username' => 'operation_jateng3',
            'display_name' => 'Jasmine Putri',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 7 (Disnaker Provinsi Jawa Timur) - Division 1 (Pusat)
        164 => [
            'id' => 164,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'finance_jatim1',
            'display_name' => 'Kevin Sutanto',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        165 => [
            'id' => 165,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'legal_jatim1',
            'display_name' => 'Lina Permata',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 7 (Disnaker Provinsi Jawa Timur) - Division 2 (Cabang 1)
        166 => [
            'id' => 166,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'operation_jatim2',
            'display_name' => 'Michael Wirawan',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        167 => [
            'id' => 167,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'finance_jatim2',
            'display_name' => 'Nadira Sari',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 7 (Disnaker Provinsi Jawa Timur) - Division 3 (Cabang 2)
        168 => [
            'id' => 168,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'legal_jatim3',
            'display_name' => 'Oscar Putra',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        169 => [
            'id' => 169,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'operation_jatim3',
            'display_name' => 'Patricia Dewi',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 8 (Disnaker Provinsi Kalimantan Barat) - Division 1 (Pusat)
        170 => [
            'id' => 170,
            'agency_id' => 8,
            'division_id' => 22,
            'username' => 'finance_kalbar1',
            'display_name' => 'Qori Susanto',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        171 => [
            'id' => 171,
            'agency_id' => 8,
            'division_id' => 22,
            'username' => 'legal_kalbar1',
            'display_name' => 'Rahma Wati',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 8 (Disnaker Provinsi Kalimantan Barat) - Division 2 (Cabang 1)
        172 => [
            'id' => 172,
            'agency_id' => 8,
            'division_id' => 23,
            'username' => 'operation_kalbar2',
            'display_name' => 'Surya Darma',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        173 => [
            'id' => 173,
            'agency_id' => 8,
            'division_id' => 23,
            'username' => 'finance_kalbar2',
            'display_name' => 'Tania Putri',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 8 (Disnaker Provinsi Kalimantan Barat) - Division 3 (Cabang 2)
        174 => [
            'id' => 174,
            'agency_id' => 8,
            'division_id' => 24,
            'username' => 'legal_kalbar3',
            'display_name' => 'Umar Prasetyo',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        175 => [
            'id' => 175,
            'agency_id' => 8,
            'division_id' => 24,
            'username' => 'operation_kalbar3',
            'display_name' => 'Vina Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 9 (Disnaker Provinsi Kalimantan Timur) - Division 1 (Pusat)
        176 => [
            'id' => 176,
            'agency_id' => 9,
            'division_id' => 25,
            'username' => 'finance_kaltim1',
            'display_name' => 'Wayan Sudiarta',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        177 => [
            'id' => 177,
            'agency_id' => 9,
            'division_id' => 25,
            'username' => 'legal_kaltim1',
            'display_name' => 'Xena Maharani',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 9 (Disnaker Provinsi Kalimantan Timur) - Division 2 (Cabang 1)
        178 => [
            'id' => 178,
            'agency_id' => 9,
            'division_id' => 26,
            'username' => 'operation_kaltim2',
            'display_name' => 'Yoga Pratama',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        179 => [
            'id' => 179,
            'agency_id' => 9,
            'division_id' => 26,
            'username' => 'finance_kaltim2',
            'display_name' => 'Zahra Permata',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 9 (Disnaker Provinsi Kalimantan Timur) - Division 3 (Cabang 2)
        180 => [
            'id' => 180,
            'agency_id' => 9,
            'division_id' => 27,
            'username' => 'legal_kaltim3',
            'display_name' => 'Adi Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        181 => [
            'id' => 181,
            'agency_id' => 9,
            'division_id' => 27,
            'username' => 'operation_kaltim3',
            'display_name' => 'Bella Safina',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 10 (Disnaker Provinsi Sulawesi Selatan) - Division 1 (Pusat)
        182 => [
            'id' => 182,
            'agency_id' => 10,
            'division_id' => 28,
            'username' => 'finance_sulsel1',
            'display_name' => 'Candra Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        183 => [
            'id' => 183,
            'agency_id' => 10,
            'division_id' => 28,
            'username' => 'legal_sulsel1',
            'display_name' => 'Devi Puspita',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 10 (Disnaker Provinsi Sulawesi Selatan) - Division 2 (Cabang 1)
        184 => [
            'id' => 184,
            'agency_id' => 10,
            'division_id' => 29,
            'username' => 'operation_sulsel2',
            'display_name' => 'Eka Prasetya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        185 => [
            'id' => 185,
            'agency_id' => 10,
            'division_id' => 29,
            'username' => 'finance_sulsel2',
            'display_name' => 'Farah Sari',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 10 (Disnaker Provinsi Sulawesi Selatan) - Division 3 (Cabang 2)
        186 => [
            'id' => 186,
            'agency_id' => 10,
            'division_id' => 30,
            'username' => 'legal_sulsel3',
            'display_name' => 'Galang Wicaksono',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        187 => [
            'id' => 187,
            'agency_id' => 10,
            'division_id' => 30,
            'username' => 'operation_sulsel3',
            'display_name' => 'Hana Pertiwi',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
    ];
}
