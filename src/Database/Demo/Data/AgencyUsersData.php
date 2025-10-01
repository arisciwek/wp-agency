<?php
/**
 * Agency Users Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
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
    const USER_ID_START = 102;
    const USER_ID_END = 111;

    // gunakan ID user mulai 102 sampai 111 untuk menghindari bentrok dengan user demo lain
    public static $data = [
        ['id' => 102, 'username' => 'admin_aceh', 'display_name' => 'Admin Aceh', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 103, 'username' => 'admin_sumut', 'display_name' => 'Admin Sumatera Utara', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 104, 'username' => 'admin_sumbar', 'display_name' => 'Admin Sumatera Barat', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 105, 'username' => 'admin_banten', 'display_name' => 'Admin Banten', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 106, 'username' => 'admin_jabar', 'display_name' => 'Admin Jawa Barat', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 107, 'username' => 'admin_jateng', 'display_name' => 'Admin Jawa Tengah', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 108, 'username' => 'admin_jakarta', 'display_name' => 'Admin DKI Jakarta', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 109, 'username' => 'admin_maluku', 'display_name' => 'Admin Maluku', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 110, 'username' => 'admin_papua', 'display_name' => 'Admin Papua', 'roles' => ['agency', 'admin_dinas']],
        ['id' => 111, 'username' => 'admin_sulsel', 'display_name' => 'Admin Sulawesi Selatan', 'roles' => ['agency', 'admin_dinas']]
    ];
}
