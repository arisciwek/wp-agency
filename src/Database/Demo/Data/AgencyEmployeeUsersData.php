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
 *              User IDs: 170-229 (60 users total)
 *              10 agencies × 3 divisions × 2 users per division = 60 users
 *              Names generated from $name_collection (different from all other plugins)
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class AgencyEmployeeUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 170;
    const USER_ID_END = 229;

    /**
     * Name collection for generating unique agency employee names
     * All names must use words from this collection only
     * MUST BE DIFFERENT from wp-customer plugin and other AgencyUsersData/DivisionUsersData collections
     */
    private static $name_collection = [
        'Ade', 'Andra', 'Bintang', 'Bayu', 'Chandra', 'Dewi',
        'Doni', 'Endang', 'Fikri', 'Gandi', 'Haryo', 'Haris',
        'Ismail', 'Jaya', 'Krisna', 'Lestari', 'Maulana', 'Melati',
        'Naufal', 'Nurul', 'Prima', 'Permata', 'Rini', 'Rizal',
        'Santoso', 'Septian', 'Tari', 'Wulan', 'Yusuf', 'Zahra',
        'Alam', 'Bunga', 'Citra', 'Dini', 'Erlangga', 'Farah',
        'Gilang', 'Hani', 'Ilham', 'Jasmine', 'Khoirul', 'Liza',
        'Manda', 'Nova', 'Okta', 'Panca', 'Qori', 'Reza',
        'Sari', 'Tiara', 'Ulfa', 'Vicky', 'Wira', 'Yoga',
        'Zain', 'Ayu', 'Bagus', 'Cici', 'Dika', 'Eko'
    ];

    public static $data = [
        // Agency 1 (Disnaker Provinsi Aceh) - Division 1 (Pusat)
        170 => [
            'id' => 170,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'ade_andra',
            'display_name' => 'Ade Andra',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        171 => [
            'id' => 171,
            'agency_id' => 1,
            'division_id' => 1,
            'username' => 'bintang_bayu',
            'display_name' => 'Bintang Bayu',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 1 (Disnaker Provinsi Aceh) - Division 2 (Cabang 1)
        172 => [
            'id' => 172,
            'agency_id' => 1,
            'division_id' => 2,
            'username' => 'chandra_dewi',
            'display_name' => 'Chandra Dewi',
            'roles' => ['agency', 'agency_kepala_unit']
        ],
        173 => [
            'id' => 173,
            'agency_id' => 1,
            'division_id' => 2,
            'username' => 'doni_endang',
            'display_name' => 'Doni Endang',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 1 (Disnaker Provinsi Aceh) - Division 3 (Cabang 2)
        174 => [
            'id' => 174,
            'agency_id' => 1,
            'division_id' => 3,
            'username' => 'fikri_gandi',
            'display_name' => 'Fikri Gandi',
            'roles' => ['agency', 'agency_kepala_bidang']
        ],
        175 => [
            'id' => 175,
            'agency_id' => 1,
            'division_id' => 3,
            'username' => 'haryo_haris',
            'display_name' => 'Haryo Haris',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 2 (Disnaker Provinsi Sumatera Utara) - Division 4 (Pusat)
        176 => [
            'id' => 176,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'ismail_jaya',
            'display_name' => 'Ismail Jaya',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        177 => [
            'id' => 177,
            'agency_id' => 2,
            'division_id' => 4,
            'username' => 'krisna_lestari',
            'display_name' => 'Krisna Lestari',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 2 (Disnaker Provinsi Sumatera Utara) - Division 5 (Cabang 1)
        178 => [
            'id' => 178,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'maulana_melati',
            'display_name' => 'Maulana Melati',
            'roles' => ['agency', 'agency_kepala_seksi']
        ],
        179 => [
            'id' => 179,
            'agency_id' => 2,
            'division_id' => 5,
            'username' => 'naufal_nurul',
            'display_name' => 'Naufal Nurul',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 2 (Disnaker Provinsi Sumatera Utara) - Division 6 (Cabang 2)
        180 => [
            'id' => 180,
            'agency_id' => 2,
            'division_id' => 6,
            'username' => 'prima_permata',
            'display_name' => 'Prima Permata',
            'roles' => ['agency', 'agency_kepala_bidang']
        ],
        181 => [
            'id' => 181,
            'agency_id' => 2,
            'division_id' => 6,
            'username' => 'rini_rizal',
            'display_name' => 'Rini Rizal',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 3 (Disnaker Provinsi Sumatera Barat) - Division 7 (Pusat)
        182 => [
            'id' => 182,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'santoso_septian',
            'display_name' => 'Santoso Septian',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        183 => [
            'id' => 183,
            'agency_id' => 3,
            'division_id' => 7,
            'username' => 'tari_wulan',
            'display_name' => 'Tari Wulan',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 3 (Disnaker Provinsi Sumatera Barat) - Division 8 (Cabang 1)
        184 => [
            'id' => 184,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'yusuf_zahra',
            'display_name' => 'Yusuf Zahra',
            'roles' => ['agency', 'agency_kepala_unit']
        ],
        185 => [
            'id' => 185,
            'agency_id' => 3,
            'division_id' => 8,
            'username' => 'alam_bunga',
            'display_name' => 'Alam Bunga',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 3 (Disnaker Provinsi Sumatera Barat) - Division 9 (Cabang 2)
        186 => [
            'id' => 186,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'citra_dini',
            'display_name' => 'Citra Dini',
            'roles' => ['agency', 'agency_kepala_seksi']
        ],
        187 => [
            'id' => 187,
            'agency_id' => 3,
            'division_id' => 9,
            'username' => 'erlangga_farah',
            'display_name' => 'Erlangga Farah',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 4 (Disnaker Provinsi Banten) - Division 10 (Pusat)
        188 => [
            'id' => 188,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'gilang_hani',
            'display_name' => 'Gilang Hani',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        189 => [
            'id' => 189,
            'agency_id' => 4,
            'division_id' => 10,
            'username' => 'ilham_jasmine',
            'display_name' => 'Ilham Jasmine',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 4 (Disnaker Provinsi Banten) - Division 11 (Cabang 1)
        190 => [
            'id' => 190,
            'agency_id' => 4,
            'division_id' => 11,
            'username' => 'khoirul_liza',
            'display_name' => 'Khoirul Liza',
            'roles' => ['agency', 'agency_kepala_unit']
        ],
        191 => [
            'id' => 191,
            'agency_id' => 4,
            'division_id' => 11,
            'username' => 'manda_nova',
            'display_name' => 'Manda Nova',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 4 (Disnaker Provinsi Banten) - Division 12 (Cabang 2)
        192 => [
            'id' => 192,
            'agency_id' => 4,
            'division_id' => 12,
            'username' => 'okta_panca',
            'display_name' => 'Okta Panca',
            'roles' => ['agency', 'agency_kepala_bidang']
        ],
        193 => [
            'id' => 193,
            'agency_id' => 4,
            'division_id' => 12,
            'username' => 'qori_reza',
            'display_name' => 'Qori Reza',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 5 (Disnaker Provinsi Jawa Barat) - Division 13 (Pusat)
        194 => [
            'id' => 194,
            'agency_id' => 5,
            'division_id' => 13,
            'username' => 'sari_tiara',
            'display_name' => 'Sari Tiara',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        195 => [
            'id' => 195,
            'agency_id' => 5,
            'division_id' => 13,
            'username' => 'ulfa_vicky',
            'display_name' => 'Ulfa Vicky',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 5 (Disnaker Provinsi Jawa Barat) - Division 14 (Cabang 1)
        196 => [
            'id' => 196,
            'agency_id' => 5,
            'division_id' => 14,
            'username' => 'wira_yoga',
            'display_name' => 'Wira Yoga',
            'roles' => ['agency', 'agency_kepala_seksi']
        ],
        197 => [
            'id' => 197,
            'agency_id' => 5,
            'division_id' => 14,
            'username' => 'zain_ayu',
            'display_name' => 'Zain Ayu',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 5 (Disnaker Provinsi Jawa Barat) - Division 15 (Cabang 2)
        198 => [
            'id' => 198,
            'agency_id' => 5,
            'division_id' => 15,
            'username' => 'bagus_cici',
            'display_name' => 'Bagus Cici',
            'roles' => ['agency', 'agency_kepala_bidang']
        ],
        199 => [
            'id' => 199,
            'agency_id' => 5,
            'division_id' => 15,
            'username' => 'dika_eko',
            'display_name' => 'Dika Eko',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 6 (Disnaker Provinsi Jawa Tengah) - Division 16 (Pusat)
        200 => [
            'id' => 200,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'ade_bintang',
            'display_name' => 'Ade Bintang',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        201 => [
            'id' => 201,
            'agency_id' => 6,
            'division_id' => 16,
            'username' => 'andra_chandra',
            'display_name' => 'Andra Chandra',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 6 (Disnaker Provinsi Jawa Tengah) - Division 17 (Cabang 1)
        202 => [
            'id' => 202,
            'agency_id' => 6,
            'division_id' => 17,
            'username' => 'bayu_dewi',
            'display_name' => 'Bayu Dewi',
            'roles' => ['agency', 'agency_kepala_unit']
        ],
        203 => [
            'id' => 203,
            'agency_id' => 6,
            'division_id' => 17,
            'username' => 'doni_fikri',
            'display_name' => 'Doni Fikri',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 6 (Disnaker Provinsi Jawa Tengah) - Division 18 (Cabang 2)
        204 => [
            'id' => 204,
            'agency_id' => 6,
            'division_id' => 18,
            'username' => 'endang_gandi',
            'display_name' => 'Endang Gandi',
            'roles' => ['agency', 'agency_kepala_seksi']
        ],
        205 => [
            'id' => 205,
            'agency_id' => 6,
            'division_id' => 18,
            'username' => 'haryo_ismail',
            'display_name' => 'Haryo Ismail',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 7 (Disnaker Provinsi DKI Jakarta) - Division 19 (Pusat)
        206 => [
            'id' => 206,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'haris_krisna',
            'display_name' => 'Haris Krisna',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        207 => [
            'id' => 207,
            'agency_id' => 7,
            'division_id' => 19,
            'username' => 'jaya_maulana',
            'display_name' => 'Jaya Maulana',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 7 (Disnaker Provinsi DKI Jakarta) - Division 20 (Cabang 1)
        208 => [
            'id' => 208,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'naufal_lestari',
            'display_name' => 'Naufal Lestari',
            'roles' => ['agency', 'agency_kepala_unit']
        ],
        209 => [
            'id' => 209,
            'agency_id' => 7,
            'division_id' => 20,
            'username' => 'prima_melati',
            'display_name' => 'Prima Melati',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 7 (Disnaker Provinsi DKI Jakarta) - Division 21 (Cabang 2)
        210 => [
            'id' => 210,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'rini_nurul',
            'display_name' => 'Rini Nurul',
            'roles' => ['agency', 'agency_kepala_bidang']
        ],
        211 => [
            'id' => 211,
            'agency_id' => 7,
            'division_id' => 21,
            'username' => 'santoso_permata',
            'display_name' => 'Santoso Permata',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 8 (Disnaker Provinsi Maluku) - Division 22 (Pusat)
        212 => [
            'id' => 212,
            'agency_id' => 8,
            'division_id' => 22,
            'username' => 'septian_rizal',
            'display_name' => 'Septian Rizal',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        213 => [
            'id' => 213,
            'agency_id' => 8,
            'division_id' => 22,
            'username' => 'yusuf_tari',
            'display_name' => 'Yusuf Tari',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 8 (Disnaker Provinsi Maluku) - Division 23 (Cabang 1)
        214 => [
            'id' => 214,
            'agency_id' => 8,
            'division_id' => 23,
            'username' => 'alam_wulan',
            'display_name' => 'Alam Wulan',
            'roles' => ['agency', 'agency_kepala_seksi']
        ],
        215 => [
            'id' => 215,
            'agency_id' => 8,
            'division_id' => 23,
            'username' => 'citra_zahra',
            'display_name' => 'Citra Zahra',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 8 (Disnaker Provinsi Maluku) - Division 24 (Cabang 2)
        216 => [
            'id' => 216,
            'agency_id' => 8,
            'division_id' => 24,
            'username' => 'erlangga_bunga',
            'display_name' => 'Erlangga Bunga',
            'roles' => ['agency', 'agency_kepala_bidang']
        ],
        217 => [
            'id' => 217,
            'agency_id' => 8,
            'division_id' => 24,
            'username' => 'gilang_dini',
            'display_name' => 'Gilang Dini',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 9 (Disnaker Provinsi Papua) - Division 25 (Pusat)
        218 => [
            'id' => 218,
            'agency_id' => 9,
            'division_id' => 25,
            'username' => 'ilham_farah',
            'display_name' => 'Ilham Farah',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        219 => [
            'id' => 219,
            'agency_id' => 9,
            'division_id' => 25,
            'username' => 'khoirul_hani',
            'display_name' => 'Khoirul Hani',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 9 (Disnaker Provinsi Papua) - Division 26 (Cabang 1)
        220 => [
            'id' => 220,
            'agency_id' => 9,
            'division_id' => 26,
            'username' => 'manda_jasmine',
            'display_name' => 'Manda Jasmine',
            'roles' => ['agency', 'agency_kepala_unit']
        ],
        221 => [
            'id' => 221,
            'agency_id' => 9,
            'division_id' => 26,
            'username' => 'okta_liza',
            'display_name' => 'Okta Liza',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 9 (Disnaker Provinsi Papua) - Division 27 (Cabang 2)
        222 => [
            'id' => 222,
            'agency_id' => 9,
            'division_id' => 27,
            'username' => 'qori_nova',
            'display_name' => 'Qori Nova',
            'roles' => ['agency', 'agency_kepala_seksi']
        ],
        223 => [
            'id' => 223,
            'agency_id' => 9,
            'division_id' => 27,
            'username' => 'sari_panca',
            'display_name' => 'Sari Panca',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ],

        // Agency 10 (Disnaker Provinsi Sulawesi Selatan) - Division 28 (Pusat)
        224 => [
            'id' => 224,
            'agency_id' => 10,
            'division_id' => 28,
            'username' => 'ulfa_reza',
            'display_name' => 'Ulfa Reza',
            'roles' => ['agency', 'agency_kepala_dinas']
        ],
        225 => [
            'id' => 225,
            'agency_id' => 10,
            'division_id' => 28,
            'username' => 'wira_tiara',
            'display_name' => 'Wira Tiara',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 10 (Disnaker Provinsi Sulawesi Selatan) - Division 29 (Cabang 1)
        226 => [
            'id' => 226,
            'agency_id' => 10,
            'division_id' => 29,
            'username' => 'zain_vicky',
            'display_name' => 'Zain Vicky',
            'roles' => ['agency', 'agency_kepala_unit']
        ],
        227 => [
            'id' => 227,
            'agency_id' => 10,
            'division_id' => 29,
            'username' => 'bagus_yoga',
            'display_name' => 'Bagus Yoga',
            'roles' => ['agency', 'agency_pengawas']
        ],
        // Agency 10 (Disnaker Provinsi Sulawesi Selatan) - Division 30 (Cabang 2)
        228 => [
            'id' => 228,
            'agency_id' => 10,
            'division_id' => 30,
            'username' => 'ayu_dika',
            'display_name' => 'Ayu Dika',
            'roles' => ['agency', 'agency_kepala_bidang']
        ],
        229 => [
            'id' => 229,
            'agency_id' => 10,
            'division_id' => 30,
            'username' => 'cici_eko',
            'display_name' => 'Cici Eko',
            'roles' => ['agency', 'agency_pengawas_spesialis']
        ]
    ];

    /**
     * Get name collection
     *
     * @return array Collection of name words
     */
    public static function getNameCollection(): array {
        return self::$name_collection;
    }

    /**
     * Validate if a name uses only words from collection
     *
     * @param string $name Full name to validate (e.g., "Ade Andra")
     * @return bool True if all words are from collection
     */
    public static function isValidName(string $name): bool {
        $words = explode(' ', $name);
        foreach ($words as $word) {
            if (!in_array($word, self::$name_collection)) {
                return false;
            }
        }
        return true;
    }
}
