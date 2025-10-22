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
 *              Names generated from $name_collection (different from other plugins).
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class DivisionUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 140;
    const USER_ID_END = 169;

    /**
     * Name collection for generating unique division user names
     * All names must use words from this collection only
     * MUST BE DIFFERENT from wp-customer and AgencyUsersData collections
     */
    private static $name_collection = [
        'Budi', 'Citra', 'Dani', 'Eko', 'Fajar', 'Gita',
        'Hendra', 'Indah', 'Joko', 'Kartika', 'Lina', 'Mira',
        'Nando', 'Omar', 'Putri', 'Raka', 'Siti', 'Tono',
        'Usman', 'Vina', 'Winda', 'Yani', 'Zainal', 'Anton'
    ];

    /**
     * Static division user data
     * Names generated from $name_collection (2 words combination)
     * Each name is unique and uses only words from the collection
     *
     * NOTE: Division PUSAT data removed - division pusat inherits user from agency
     * Only division CABANG users are defined here (ID 141-169, 20 users total)
     * 10 agencies × 2 cabang each = 20 users
     */
    public static $data = [
        1 => [  // Disnaker Provinsi Aceh
            'cabang1' => ['id' => 141, 'username' => 'dani_eko', 'display_name' => 'Dani Eko', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 142, 'username' => 'fajar_gita', 'display_name' => 'Fajar Gita', 'role' => ['agency', 'agency_admin_unit']]
        ],
        2 => [  // Disnaker Provinsi Sumatera Utara
            'cabang1' => ['id' => 144, 'username' => 'joko_kartika', 'display_name' => 'Joko Kartika', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 145, 'username' => 'lina_mira', 'display_name' => 'Lina Mira', 'role' => ['agency', 'agency_admin_unit']]
        ],
        3 => [  // Disnaker Provinsi Sumatera Barat
            'cabang1' => ['id' => 147, 'username' => 'putri_raka', 'display_name' => 'Putri Raka', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 148, 'username' => 'siti_tono', 'display_name' => 'Siti Tono', 'role' => ['agency', 'agency_admin_unit']]
        ],
        4 => [  // Disnaker Provinsi Banten
            'cabang1' => ['id' => 150, 'username' => 'winda_yani', 'display_name' => 'Winda Yani', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 151, 'username' => 'zainal_anton', 'display_name' => 'Zainal Anton', 'role' => ['agency', 'agency_admin_unit']]
        ],
        5 => [  // Disnaker Provinsi Jawa Barat
            'cabang1' => ['id' => 153, 'username' => 'citra_eko', 'display_name' => 'Citra Eko', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 154, 'username' => 'fajar_hendra', 'display_name' => 'Fajar Hendra', 'role' => ['agency', 'agency_admin_unit']]
        ],
        6 => [  // Disnaker Provinsi Jawa Tengah
            'cabang1' => ['id' => 156, 'username' => 'joko_lina', 'display_name' => 'Joko Lina', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 157, 'username' => 'kartika_mira', 'display_name' => 'Kartika Mira', 'role' => ['agency', 'agency_admin_unit']]
        ],
        7 => [  // Disnaker Provinsi Jawa Timur
            'cabang1' => ['id' => 159, 'username' => 'omar_raka', 'display_name' => 'Omar Raka', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 160, 'username' => 'siti_usman', 'display_name' => 'Siti Usman', 'role' => ['agency', 'agency_admin_unit']]
        ],
        8 => [  // Disnaker Provinsi Kalimantan Barat
            'cabang1' => ['id' => 162, 'username' => 'winda_zainal', 'display_name' => 'Winda Zainal', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 163, 'username' => 'yani_anton', 'display_name' => 'Yani Anton', 'role' => ['agency', 'agency_admin_unit']]
        ],
        9 => [  // Disnaker Provinsi Kalimantan Timur
            'cabang1' => ['id' => 165, 'username' => 'citra_hendra', 'display_name' => 'Citra Hendra', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 166, 'username' => 'dani_indah', 'display_name' => 'Dani Indah', 'role' => ['agency', 'agency_admin_unit']]
        ],
        10 => [  // Disnaker Provinsi Sulawesi Selatan
            'cabang1' => ['id' => 168, 'username' => 'kartika_lina', 'display_name' => 'Kartika Lina', 'role' => ['agency', 'agency_admin_unit']],
            'cabang2' => ['id' => 169, 'username' => 'mira_nando', 'display_name' => 'Mira Nando', 'role' => ['agency', 'agency_admin_unit']]
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
     * @param string $name Full name to validate (e.g., "Budi Citra")
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
