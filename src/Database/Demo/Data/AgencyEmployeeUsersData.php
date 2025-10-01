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
            'role' => 'kepala_unit',
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
            'role' => 'kepala_unit',
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
            'role' => 'kepala_seksi',
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
            'role' => 'kepala_seksi',
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
            'role' => 'kepala_bidang',
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
            'role' => 'kepala_bidang',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Additional users for Agency 1
        188 => [
            'id' => 188,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'kepala_dinas_aceh',
            'display_name' => 'Ahmad Santoso',
            'role' => 'kepala_dinas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        189 => [
            'id' => 189,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'pengawas_aceh1',
            'display_name' => 'Budi Hartono',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        190 => [
            'id' => 190,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'pengawas_aceh2',
            'display_name' => 'Cici Lestari',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        191 => [
            'id' => 191,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'pengawas_aceh3',
            'display_name' => 'Dedi Kurniawan',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        192 => [
            'id' => 192,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'pengawas_aceh4',
            'display_name' => 'Elsa Putri',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        193 => [
            'id' => 193,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'pengawas_spesialis_aceh1',
            'display_name' => 'Fajar Nugroho',
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        194 => [
            'id' => 194,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'pengawas_spesialis_aceh2',
            'display_name' => 'Gina Sari',
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Agency 2 (Disnaker Provinsi Sumatera Utara) - Division 1 (Pusat)
        138 => [
            'id' => 138,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'legal_sumut1',
            'display_name' => 'Gunawan Santoso',
            'role' => 'kepala_dinas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Additional users for Agency 2
        195 => [
            'id' => 195,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'pengawas_spesialis_sumut',
            'display_name' => 'Hendro Wibowo',
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        196 => [
            'id' => 196,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'kepala_unit_sumut1',
            'display_name' => 'Ika Sari',
            'role' => 'kepala_unit',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        197 => [
            'id' => 197,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'kepala_unit_sumut2',
            'display_name' => 'Joko Santoso',
            'role' => 'kepala_unit',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        198 => [
            'id' => 198,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'kepala_seksi_sumut1',
            'display_name' => 'Kartika Dewi',
            'role' => 'kepala_seksi',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        199 => [
            'id' => 199,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'kepala_seksi_sumut2',
            'display_name' => 'Lutfi Rahman',
            'role' => 'kepala_seksi',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        200 => [
            'id' => 200,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'kepala_bidang_sumut1',
            'display_name' => 'Maya Putri',
            'role' => 'kepala_bidang',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        201 => [
            'id' => 201,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'kepala_bidang_sumut2',
            'display_name' => 'Nanda Wijaya',
            'role' => 'kepala_bidang',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
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
            'role' => 'kepala_dinas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Additional users for Agency 3
        202 => [
            'id' => 202,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'pengawas_spesialis_sumbar2',
            'display_name' => 'Sari Dewi',
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => true,
                'purchase' => false
            ]
        ],
        203 => [
            'id' => 203,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'kepala_unit_sumbar1',
            'display_name' => 'Taufik Rahman',
            'role' => 'kepala_unit',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        204 => [
            'id' => 204,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'kepala_unit_sumbar2',
            'display_name' => 'Umi Kalsum',
            'role' => 'kepala_unit',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        205 => [
            'id' => 205,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'kepala_seksi_sumbar1',
            'display_name' => 'Vera Sari',
            'role' => 'kepala_seksi',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        206 => [
            'id' => 206,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'kepala_seksi_sumbar2',
            'display_name' => 'Wahyu Nugroho',
            'role' => 'kepala_seksi',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        207 => [
            'id' => 207,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'kepala_bidang_sumbar1',
            'display_name' => 'Xenia Putri',
            'role' => 'kepala_bidang',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        208 => [
            'id' => 208,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'kepala_bidang_sumbar2',
            'display_name' => 'Yusuf Santoso',
            'role' => 'kepala_bidang',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 4 (Disnaker Provinsi Banten) - Division 1 (Pusat)
        150 => [
            'id' => 150,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'operation_banten1',
            'display_name' => 'Surya Pratama',
            'role' => 'kepala_dinas',
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
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Additional users for Agency 4
        209 => [
            'id' => 209,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'pengawas_banten2',
            'display_name' => 'Zaki Rahman',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        210 => [
            'id' => 210,
            'agency_id' => 4,
            'division_id' => 11,
            'username' => 'pengawas_banten3',
            'display_name' => 'Amira Sari',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        211 => [
            'id' => 211,
            'agency_id' => 4,
            'division_id' => 11,
            'username' => 'pengawas_banten4',
            'display_name' => 'Budi Santoso',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        212 => [
            'id' => 212,
            'agency_id' => 4,
            'division_id' => 12,
            'username' => 'pengawas_spesialis_banten1',
            'display_name' => 'Cici Lestari',
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        213 => [
            'id' => 213,
            'agency_id' => 4,
            'division_id' => 12,
            'username' => 'pengawas_spesialis_banten2',
            'display_name' => 'Dedi Kurniawan',
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        214 => [
            'id' => 214,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'kepala_unit_banten1',
            'display_name' => 'Elsa Putri',
            'role' => 'kepala_unit',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        215 => [
            'id' => 215,
            'agency_id' => 4,
            'division_id' => 11,
            'username' => 'kepala_unit_banten2',
            'display_name' => 'Fajar Nugroho',
            'role' => 'kepala_unit',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        216 => [
            'id' => 216,
            'agency_id' => 4,
            'division_id' => 11,
            'username' => 'kepala_seksi_banten1',
            'display_name' => 'Gina Sari',
            'role' => 'kepala_seksi',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        217 => [
            'id' => 217,
            'agency_id' => 4,
            'division_id' => 12,
            'username' => 'kepala_seksi_banten2',
            'display_name' => 'Hendro Wibowo',
            'role' => 'kepala_seksi',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        218 => [
            'id' => 218,
            'agency_id' => 4,
            'division_id' => 12,
            'username' => 'kepala_bidang_banten1',
            'display_name' => 'Ika Sari',
            'role' => 'kepala_bidang',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        219 => [
            'id' => 219,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'kepala_bidang_banten2',
            'display_name' => 'Joko Santoso',
            'role' => 'kepala_bidang',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
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
            'role' => 'kepala_dinas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas_spesialis',
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
            'role' => 'kepala_dinas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas_spesialis',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 7 (Disnaker Provinsi DKI Jakarta) - Division 1 (Pusat)
        164 => [
            'id' => 164,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'finance_jakarta1',
            'display_name' => 'Kevin Sutanto',
            'role' => 'kepala_dinas',
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
            'username' => 'legal_jakarta1',
            'display_name' => 'Lina Permata',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 7 (Disnaker Provinsi DKI Jakarta) - Division 2 (Cabang 1)
        166 => [
            'id' => 166,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'operation_jakarta2',
            'display_name' => 'Michael Wirawan',
            'role' => 'pengawas',
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
            'username' => 'finance_jakarta2',
            'display_name' => 'Nadira Sari',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 7 (Disnaker Provinsi DKI Jakarta) - Division 3 (Cabang 2)
        168 => [
            'id' => 168,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'legal_jakarta3',
            'display_name' => 'Oscar Putra',
            'role' => 'pengawas',
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
            'username' => 'operation_jakarta3',
            'display_name' => 'Patricia Dewi',
            'role' => 'pengawas_spesialis',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
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
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Additional users for Agency 1 - Division 2 & 3 (Aceh)
        220 => [
            'id' => 220,
            'agency_id' => 1,
            'division_id' => 2,
            'username' => 'pengawas_aceh_div2',
            'display_name' => 'Irfan Maulana',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        221 => [
            'id' => 221,
            'agency_id' => 1,
            'division_id' => 3,
            'username' => 'pengawas_aceh_div3',
            'display_name' => 'Siti Nurhaliza',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Additional user for Agency 4 - Division 12 (Banten)
        222 => [
            'id' => 222,
            'agency_id' => 4,
            'division_id' => 12,
            'username' => 'pengawas_banten_div12',
            'display_name' => 'Rudi Hermawan',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Additional pengawas for Agency 5 (Jawa Barat) - Divisions 13, 14, 15
        223 => [
            'id' => 223,
            'agency_id' => 5,
            'division_id' => 13,
            'username' => 'pengawas_jabar_div13_2',
            'display_name' => 'Ahmad Fauzi',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => true,
                'purchase' => false
            ]
        ],
        224 => [
            'id' => 224,
            'agency_id' => 5,
            'division_id' => 14,
            'username' => 'pengawas_jabar_div14_2',
            'display_name' => 'Bambang Sutrisno',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => false,
                'purchase' => true
            ]
        ],
        225 => [
            'id' => 225,
            'agency_id' => 5,
            'division_id' => 15,
            'username' => 'pengawas_jabar_div15_2',
            'display_name' => 'Citra Dewi',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 6 (Jawa Tengah) - Divisions 16, 17, 18
        226 => [
            'id' => 226,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'pengawas_jateng_div16_2',
            'display_name' => 'Dian Pramesti',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        227 => [
            'id' => 227,
            'agency_id' => 6,
            'division_id' => 17,
            'username' => 'pengawas_jateng_div17_2',
            'display_name' => 'Edi Susanto',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        228 => [
            'id' => 228,
            'agency_id' => 6,
            'division_id' => 18,
            'username' => 'pengawas_jateng_div18_2',
            'display_name' => 'Fitri Handayani',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 7 (DKI Jakarta) - Divisions 19, 20, 21
        229 => [
            'id' => 229,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'pengawas_jakarta_div19_2',
            'display_name' => 'Guntur Wibowo',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        230 => [
            'id' => 230,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'pengawas_jakarta_div20_2',
            'display_name' => 'Hendra Kusuma',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        231 => [
            'id' => 231,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'pengawas_jakarta_div21_2',
            'display_name' => 'Indah Permata',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 8 (Kalimantan Barat) - Divisions 22, 23, 24
        232 => [
            'id' => 232,
            'agency_id' => 8,
            'division_id' => 22,
            'username' => 'pengawas_kalbar_div22_2',
            'display_name' => 'Joko Prasetyo',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        233 => [
            'id' => 233,
            'agency_id' => 8,
            'division_id' => 23,
            'username' => 'pengawas_kalbar_div23_2',
            'display_name' => 'Kartika Sari',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        234 => [
            'id' => 234,
            'agency_id' => 8,
            'division_id' => 24,
            'username' => 'pengawas_kalbar_div24_2',
            'display_name' => 'Lukman Hakim',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 9 (Kalimantan Timur) - Divisions 25, 26, 27
        235 => [
            'id' => 235,
            'agency_id' => 9,
            'division_id' => 25,
            'username' => 'pengawas_kaltim_div25_2',
            'display_name' => 'Maya Anggraini',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        236 => [
            'id' => 236,
            'agency_id' => 9,
            'division_id' => 26,
            'username' => 'pengawas_kaltim_div26_2',
            'display_name' => 'Nugroho Santoso',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        237 => [
            'id' => 237,
            'agency_id' => 9,
            'division_id' => 27,
            'username' => 'pengawas_kaltim_div27_2',
            'display_name' => 'Olivia Putri',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 10 (Sulawesi Selatan) - Divisions 28, 29, 30
        238 => [
            'id' => 238,
            'agency_id' => 10,
            'division_id' => 28,
            'username' => 'pengawas_sulsel_div28_2',
            'display_name' => 'Pandu Wijaya',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        239 => [
            'id' => 239,
            'agency_id' => 10,
            'division_id' => 29,
            'username' => 'pengawas_sulsel_div29_2',
            'display_name' => 'Qori Maharani',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        240 => [
            'id' => 240,
            'agency_id' => 10,
            'division_id' => 30,
            'username' => 'pengawas_sulsel_div30_2',
            'display_name' => 'Rizki Pratama',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 2 (Sumatera Utara) - Need 4 more for divisions 4, 5
        241 => [
            'id' => 241,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'pengawas_sumut_div4_3',
            'display_name' => 'Sandi Kurniawan',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => true,
                'purchase' => false
            ]
        ],
        242 => [
            'id' => 242,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'pengawas_sumut_div4_4',
            'display_name' => 'Tari Wulandari',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => false,
                'purchase' => true
            ]
        ],
        243 => [
            'id' => 243,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'pengawas_sumut_div5_2',
            'display_name' => 'Usman Hakim',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        244 => [
            'id' => 244,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'pengawas_sumut_div5_3',
            'display_name' => 'Vivi Anggraini',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Additional pengawas for Agency 3 (Sumatera Barat) - Division 7
        245 => [
            'id' => 245,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'pengawas_sumbar_div7_2',
            'display_name' => 'Wawan Setiawan',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 5 (Jawa Barat) - Need 3 more for divisions 13, 14
        246 => [
            'id' => 246,
            'agency_id' => 5,
            'division_id' => 13,
            'username' => 'pengawas_jabar_div13_3',
            'display_name' => 'Xander Pratama',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        247 => [
            'id' => 247,
            'agency_id' => 5,
            'division_id' => 13,
            'username' => 'pengawas_jabar_div13_4',
            'display_name' => 'Yanti Kusuma',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        248 => [
            'id' => 248,
            'agency_id' => 5,
            'division_id' => 14,
            'username' => 'pengawas_jabar_div14_3',
            'display_name' => 'Zulfikar Rahman',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Additional pengawas for Agency 6 (Jawa Tengah) - Division 16
        249 => [
            'id' => 249,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'pengawas_jateng_div16_3',
            'display_name' => 'Agus Santoso',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Additional pengawas for Agency 7 (DKI Jakarta) - Division 19
        250 => [
            'id' => 250,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'pengawas_jakarta_div19_3',
            'display_name' => 'Bella Puspita',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Additional pengawas for remaining NULL branches
        251 => [
            'id' => 251,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'pengawas_sumbar_div7_3',
            'display_name' => 'Cahya Permata',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        252 => [
            'id' => 252,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'pengawas_banten_div10_2',
            'display_name' => 'Dimas Prasetyo',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        253 => [
            'id' => 253,
            'agency_id' => 4,
            'division_id' => 11,
            'username' => 'pengawas_banten_div11_3',
            'display_name' => 'Erna Kusuma',
            'role' => 'pengawas',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        254 => [
            'id' => 254,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'pengawas_jateng_div16_4',
            'display_name' => 'Faisal Rahman',
            'role' => 'pengawas',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
    ];
}
