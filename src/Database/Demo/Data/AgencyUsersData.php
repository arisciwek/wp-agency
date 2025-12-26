<?php
/**
 * Agency Users Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo/Data
 * @version     1.0.7
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/Data/AgencyUsersData.php
 *
 * Description: Static agency user data for demo generation.
 *              Used by WPUserGenerator and AgencyDemoData.
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class AgencyUsersData {
    // Constants for user ID ranges
    const USER_ID_START = 130;
    const USER_ID_END = 139;

    /**
     * Name collection for generating unique agency user names
     * All names must use words from this collection only
     * MUST BE DIFFERENT from wp-customer plugin collections
     */
    private static $name_collection = [
        'Ahmad', 'Bambang', 'Cahyo', 'Darmawan', 'Edi', 'Farid',
        'Guntur', 'Hasan', 'Irfan', 'Jaya', 'Kurnia', 'Lukman',
        'Mahendra', 'Noval', 'Okta', 'Prasetyo', 'Qodir', 'Rahman',
        'Setya', 'Teguh', 'Ujang', 'Vivian', 'Wibowo', 'Xavier'
    ];

    /**
     * Static agency user data
     * Names generated from $name_collection (2 words combination)
     * Each name is unique and uses only words from the collection
     *
     * NOTE: 'agency_employee' role is NOT hardcoded here
     * It will be added by AgencyDemoData->generate() via add_role()
     */
    public static $data = [
        ['id' => 130, 'username' => 'ahmad_bambang', 'display_name' => 'Ahmad Bambang', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 131, 'username' => 'cahyo_darmawan', 'display_name' => 'Cahyo Darmawan', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 132, 'username' => 'edi_farid', 'display_name' => 'Edi Farid', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 133, 'username' => 'guntur_hasan', 'display_name' => 'Guntur Hasan', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 134, 'username' => 'irfan_jaya', 'display_name' => 'Irfan Jaya', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 135, 'username' => 'kurnia_lukman', 'display_name' => 'Kurnia Lukman', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 136, 'username' => 'mahendra_noval', 'display_name' => 'Mahendra Noval', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 137, 'username' => 'okta_prasetyo', 'display_name' => 'Okta Prasetyo', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 138, 'username' => 'qodir_rahman', 'display_name' => 'Qodir Rahman', 'roles' => ['agency', 'agency_admin_dinas']],
        ['id' => 139, 'username' => 'setya_teguh', 'display_name' => 'Setya Teguh', 'roles' => ['agency', 'agency_admin_dinas']]
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
     * @param string $name Full name to validate (e.g., "Ahmad Bambang")
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
