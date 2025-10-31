<?php
/**
 * Jurisdiction Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo/Data
 * @version     2.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/Data/JurisdictionData.php
 *
 * Description: Static jurisdiction data for demo generation.
 *              Defines which regencies each division covers.
 *              Used by JurisdictionDemoData.
 *
 * Structure: Uses agency index (like DivisionUsersData.php)
 * - Key: agency_index (1-10, maps to agency_id 21-30)
 * - Sub-keys: 'pusat', 'cabang1', 'cabang2'
 * - Each sub-key has 'regencies' array (additional regencies beyond primary)
 * - Primary regency is auto-determined from division.regency_code
 *
 * Changelog:
 * 2.0.0 - 2025-10-31 (TODO-3093)
 * - RESTRUCTURE: Changed from division_id based to agency_index based
 * - PATTERN: Now follows DivisionUsersData.php structure
 * - ADDED: Sub-keys pusat/cabang1/cabang2 for each agency
 * - FIX: Works with runtime generated division IDs
 * 1.0.7 - 2025-10-XX
 * - Used hardcoded division IDs (now deprecated)
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class JurisdictionData {
    /**
     * Static jurisdiction data
     * Key: agency_index (1-10)
     * Sub-keys: division type (pusat, cabang1, cabang2)
     * Value: array with 'regencies' (additional regencies) and 'created_by'
     *
     * Note: Primary regency determined from division.regency_code (auto-added)
     */
    public static $data = [
        1 => [  // Agency 21 - Disnaker Provinsi Aceh
            'pusat' => [
                'regencies' => ['1102', '1103'],  // Aceh Tenggara, Aceh Timur
                'created_by' => 102
            ],
            'cabang1' => [
                'regencies' => [],  // Primary only
                'created_by' => 102
            ],
            'cabang2' => [
                'regencies' => [],  // Primary only
                'created_by' => 102
            ]
        ],
        2 => [  // Agency 22 - Disnaker Provinsi Sumatera Utara
            'pusat' => [
                'regencies' => [],  // Primary only
                'created_by' => 103
            ],
            'cabang1' => [
                'regencies' => [],  // Primary only
                'created_by' => 103
            ],
            'cabang2' => [
                'regencies' => [],  // Primary only
                'created_by' => 103
            ]
        ],
        3 => [  // Agency 23 - Disnaker Provinsi Sumatera Barat
            'pusat' => [
                'regencies' => [],  // Primary only
                'created_by' => 104
            ],
            'cabang1' => [
                'regencies' => ['1303'],  // Sijunjung
                'created_by' => 104
            ],
            'cabang2' => [
                'regencies' => ['1301'],  // Pesisir Selatan
                'created_by' => 104
            ]
        ],
        4 => [  // Agency 24 - Disnaker Provinsi Banten
            'pusat' => [
                'regencies' => [],  // Primary only
                'created_by' => 105
            ],
            'cabang1' => [
                'regencies' => ['3671'],  // Kota Serang
                'created_by' => 105
            ],
            'cabang2' => [
                'regencies' => ['3604'],  // Kabupaten Serang
                'created_by' => 105
            ]
        ],
        5 => [  // Agency 25 - Disnaker Provinsi Jawa Barat
            'pusat' => [
                'regencies' => ['3201'],  // Kabupaten Bogor
                'created_by' => 106
            ],
            'cabang1' => [
                'regencies' => [],  // Primary only
                'created_by' => 106
            ],
            'cabang2' => [
                'regencies' => [],  // Primary only
                'created_by' => 106
            ]
        ],
        6 => [  // Agency 26 - Disnaker Provinsi Jawa Tengah
            'pusat' => [
                'regencies' => [],  // Primary only
                'created_by' => 107
            ],
            'cabang1' => [
                'regencies' => ['3302'],  // Banyumas
                'created_by' => 107
            ],
            'cabang2' => [
                'regencies' => [],  // Primary only
                'created_by' => 107
            ]
        ],
        7 => [  // Agency 27 - Disnaker Provinsi DKI Jakarta
            'pusat' => [
                'regencies' => ['3171', '3174'],  // Jakarta Pusat, Jakarta Selatan
                'created_by' => 108
            ],
            'cabang1' => [
                'regencies' => [],  // Primary only
                'created_by' => 108
            ],
            'cabang2' => [
                'regencies' => ['3175'],  // Jakarta Timur
                'created_by' => 108
            ]
        ],
        8 => [  // Agency 28 - Disnaker Provinsi Maluku
            'pusat' => [
                'regencies' => [],  // Primary only
                'created_by' => 109
            ],
            'cabang1' => [
                'regencies' => [],  // Primary only
                'created_by' => 109
            ],
            'cabang2' => [
                'regencies' => [],  // Primary only
                'created_by' => 109
            ]
        ],
        9 => [  // Agency 29 - Disnaker Provinsi Papua
            'pusat' => [
                'regencies' => [],  // Primary only
                'created_by' => 110
            ],
            'cabang1' => [
                'regencies' => [],  // Primary only
                'created_by' => 110
            ],
            'cabang2' => [
                'regencies' => [],  // Primary only
                'created_by' => 110
            ]
        ],
        10 => [  // Agency 30 - Disnaker Provinsi Sulawesi Selatan
            'pusat' => [
                'regencies' => [],  // Primary only
                'created_by' => 111
            ],
            'cabang1' => [
                'regencies' => [],  // Primary only
                'created_by' => 111
            ],
            'cabang2' => [
                'regencies' => ['7371', '7373'],  // Makassar, Palopo
                'created_by' => 111
            ]
        ]
    ];

    /**
     * Get jurisdiction data for a specific agency and division type
     *
     * @param int $agency_index Agency index (1-10)
     * @param string $division_type Division type ('pusat', 'cabang1', 'cabang2')
     * @return array|null Jurisdiction data or null if not found
     */
    public static function getForDivision(int $agency_index, string $division_type): ?array {
        if (!isset(self::$data[$agency_index])) {
            return null;
        }

        if (!isset(self::$data[$agency_index][$division_type])) {
            return null;
        }

        return self::$data[$agency_index][$division_type];
    }

    /**
     * Get regency codes for a specific agency and division type
     *
     * @param int $agency_index Agency index (1-10)
     * @param string $division_type Division type ('pusat', 'cabang1', 'cabang2')
     * @return array Array of regency codes (without primary regency)
     */
    public static function getRegencyCodes(int $agency_index, string $division_type): array {
        $data = self::getForDivision($agency_index, $division_type);
        return $data ? $data['regencies'] : [];
    }

    /**
     * Get created_by user ID for a specific agency and division type
     *
     * @param int $agency_index Agency index (1-10)
     * @param string $division_type Division type ('pusat', 'cabang1', 'cabang2')
     * @return int|null User ID or null if not found
     */
    public static function getCreatedBy(int $agency_index, string $division_type): ?int {
        $data = self::getForDivision($agency_index, $division_type);
        return $data ? $data['created_by'] : null;
    }
}
