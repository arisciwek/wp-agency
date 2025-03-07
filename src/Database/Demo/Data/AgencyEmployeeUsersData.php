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
 *              User IDs: 42-101
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class AgencyEmployeeUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 42;
    const USER_ID_END = 101;

    public static $data = [
        // Agency 1 (PT Maju Bersama) - Division 1 (Pusat)
        42 => [
            'id' => 42,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'finance_maju1',
            'display_name' => 'Aditya Pratama',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        43 => [
            'id' => 43,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'legal_maju1',
            'display_name' => 'Sarah Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 1 (PT Maju Bersama) - Division 2 (Cabang 1)
        44 => [
            'id' => 44,
            'agency_id' => 1,
            'division_id' => 2,
            'username' => 'finance_maju2',
            'display_name' => 'Bima Setiawan',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        45 => [
            'id' => 45,
            'agency_id' => 1,
            'division_id' => 2,
            'username' => 'operation_maju2',
            'display_name' => 'Diana Puspita',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Agency 1 (PT Maju Bersama) - Division 3 (Cabang 2)
        46 => [
            'id' => 46,
            'agency_id' => 1,
            'division_id' => 3,
            'username' => 'operation_maju3',
            'display_name' => 'Eko Wibowo',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        47 => [
            'id' => 47,
            'agency_id' => 1,
            'division_id' => 3,
            'username' => 'finance_maju3',
            'display_name' => 'Fina Sari',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 2 (CV Teknologi Nusantara) - Division 1 (Pusat)
        48 => [
            'id' => 48,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'legal_tekno1',
            'display_name' => 'Gunawan Santoso',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        49 => [
            'id' => 49,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'finance_tekno1',
            'display_name' => 'Hana Permata',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 2 (CV Teknologi Nusantara) - Division 2 (Cabang 1)
        50 => [
            'id' => 50,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'operation_tekno2',
            'display_name' => 'Irfan Hakim',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        51 => [
            'id' => 51,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'purchase_tekno2',
            'display_name' => 'Julia Putri',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 2 (CV Teknologi Nusantara) - Division 3 (Cabang 2)
        52 => [
            'id' => 52,
            'agency_id' => 2,
            'division_id' => 6,
            'username' => 'finance_tekno3',
            'display_name' => 'Krisna Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        53 => [
            'id' => 53,
            'agency_id' => 2,
            'division_id' => 6,
            'username' => 'legal_tekno3',
            'display_name' => 'Luna Safitri',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 3 (PT Sinar Abadi) - Division 1 (Pusat)
        54 => [
            'id' => 54,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'operation_sinar1',
            'display_name' => 'Mario Gunawan',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        55 => [
            'id' => 55,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'finance_sinar1',
            'display_name' => 'Nadia Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 3 (PT Sinar Abadi) - Division 2 (Cabang 1)
        56 => [
            'id' => 56,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'legal_sinar2',
            'display_name' => 'Oscar Pradana',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        57 => [
            'id' => 57,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'operation_sinar2',
            'display_name' => 'Putri Handayani',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 3 (PT Sinar Abadi) - Division 3 (Cabang 2)
        58 => [
            'id' => 58,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'finance_sinar3',
            'display_name' => 'Qori Rahman',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        59 => [
            'id' => 59,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'legal_sinar3',
            'display_name' => 'Ratih Purnama',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        // Agency 4 (PT Global Teknindo) - Division 1 (Pusat)
        60 => [
            'id' => 60,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'operation_global1',
            'display_name' => 'Surya Pratama',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        61 => [
            'id' => 61,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'finance_global1',
            'display_name' => 'Tania Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 6 (PT Karya Digital) - Division 1 (Pusat)
        72 => [
            'id' => 72,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'finance_karya1',
            'display_name' => 'Eko Santoso',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        73 => [
            'id' => 73,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'legal_karya1',
            'display_name' => 'Fitri Wulandari',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 6 (PT Karya Digital) - Division 2 (Cabang 1)
        74 => [
            'id' => 74,
            'agency_id' => 6,
            'division_id' => 17,
            'username' => 'operation_karya2',
            'display_name' => 'Galih Prasetyo',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        75 => [
            'id' => 75,
            'agency_id' => 6,
            'division_id' => 17,
            'username' => 'finance_karya2',
            'display_name' => 'Hesti Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 6 (PT Karya Digital) - Division 3 (Cabang 2)
        76 => [
            'id' => 76,
            'agency_id' => 6,
            'division_id' => 18,
            'username' => 'legal_karya3',
            'display_name' => 'Indra Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        77 => [
            'id' => 77,
            'agency_id' => 6,
            'division_id' => 18,
            'username' => 'operation_karya3',
            'display_name' => 'Jasmine Putri',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 7 (PT Bumi Perkasa) - Division 1 (Pusat)
        78 => [
            'id' => 78,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'finance_bumi1',
            'display_name' => 'Kevin Sutanto',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        79 => [
            'id' => 79,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'legal_bumi1',
            'display_name' => 'Lina Permata',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 7 (PT Bumi Perkasa) - Division 2 (Cabang 1)
        80 => [
            'id' => 80,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'operation_bumi2',
            'display_name' => 'Michael Wirawan',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        81 => [
            'id' => 81,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'finance_bumi2',
            'display_name' => 'Nadira Sari',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 7 (PT Bumi Perkasa) - Division 3 (Cabang 2)
        82 => [
            'id' => 82,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'legal_bumi3',
            'display_name' => 'Oscar Putra',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        83 => [
            'id' => 83,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'operation_bumi3',
            'display_name' => 'Patricia Dewi',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 8 (CV Cipta Kreasi) - Division 1 (Pusat)
        84 => [
            'id' => 84,
            'agency_id' => 8,
            'division_id' => 22,
            'username' => 'finance_cipta1',
            'display_name' => 'Qori Susanto',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        85 => [
            'id' => 85,
            'agency_id' => 8,
            'division_id' => 22,
            'username' => 'legal_cipta1',
            'display_name' => 'Rahma Wati',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 8 (CV Cipta Kreasi) - Division 2 (Cabang 1)
        86 => [
            'id' => 86,
            'agency_id' => 8,
            'division_id' => 23,
            'username' => 'operation_cipta2',
            'display_name' => 'Surya Darma',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        87 => [
            'id' => 87,
            'agency_id' => 8,
            'division_id' => 23,
            'username' => 'finance_cipta2',
            'display_name' => 'Tania Putri',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 8 (CV Cipta Kreasi) - Division 3 (Cabang 2)
        88 => [
            'id' => 88,
            'agency_id' => 8,
            'division_id' => 24,
            'username' => 'legal_cipta3',
            'display_name' => 'Umar Prasetyo',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        89 => [
            'id' => 89,
            'agency_id' => 8,
            'division_id' => 24,
            'username' => 'operation_cipta3',
            'display_name' => 'Vina Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 9 (PT Meta Inovasi) - Division 1 (Pusat)
        90 => [
            'id' => 90,
            'agency_id' => 9,
            'division_id' => 25,
            'username' => 'finance_meta1',
            'display_name' => 'Wayan Sudiarta',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        91 => [
            'id' => 91,
            'agency_id' => 9,
            'division_id' => 25,
            'username' => 'legal_meta1',
            'display_name' => 'Xena Maharani',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 9 (PT Meta Inovasi) - Division 2 (Cabang 1)
        92 => [
            'id' => 92,
            'agency_id' => 9,
            'division_id' => 26,
            'username' => 'operation_meta2',
            'display_name' => 'Yoga Pratama',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        93 => [
            'id' => 93,
            'agency_id' => 9,
            'division_id' => 26,
            'username' => 'finance_meta2',
            'display_name' => 'Zahra Permata',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 9 (PT Meta Inovasi) - Division 3 (Cabang 2)
        94 => [
            'id' => 94,
            'agency_id' => 9,
            'division_id' => 27,
            'username' => 'legal_meta3',
            'display_name' => 'Adi Wijaya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        95 => [
            'id' => 95,
            'agency_id' => 9,
            'division_id' => 27,
            'username' => 'operation_meta3',
            'display_name' => 'Bella Safina',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        // Agency 10 (PT Delta Sistem) - Division 1 (Pusat)
        96 => [
            'id' => 96,
            'agency_id' => 10,
            'division_id' => 28,
            'username' => 'finance_delta1',
            'display_name' => 'Candra Kusuma',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => true,
                'legal' => false,
                'purchase' => false
            ]
        ],
        97 => [
            'id' => 97,
            'agency_id' => 10,
            'division_id' => 28,
            'username' => 'legal_delta1',
            'display_name' => 'Devi Puspita',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        // Agency 10 (PT Delta Sistem) - Division 2 (Cabang 1)
        98 => [
            'id' => 98,
            'agency_id' => 10,
            'division_id' => 29,
            'username' => 'operation_delta2',
            'display_name' => 'Eka Prasetya',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => true,
                'legal' => true,
                'purchase' => false
            ]
        ],
        99 => [
            'id' => 99,
            'agency_id' => 10,
            'division_id' => 29,
            'username' => 'finance_delta2',
            'display_name' => 'Farah Sari',
            'role' => 'agency',
            'departments' => [
                'finance' => true,
                'operation' => false,
                'legal' => false,
                'purchase' => true
            ]
        ],
        // Agency 10 (PT Delta Sistem) - Division 3 (Cabang 2)
        100 => [
            'id' => 100,
            'agency_id' => 10,
            'division_id' => 30,
            'username' => 'legal_delta3',
            'display_name' => 'Galang Wicaksono',
            'role' => 'agency',
            'departments' => [
                'finance' => false,
                'operation' => false,
                'legal' => true,
                'purchase' => true
            ]
        ],
        101 => [
            'id' => 101,
            'agency_id' => 10,
            'division_id' => 30,
            'username' => 'operation_delta3',
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
