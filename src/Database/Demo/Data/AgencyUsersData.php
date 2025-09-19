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
    // gunakan ID user mulai 102 sampai 111 untuk menghindari bentrok dengan user demo lain
    public static $data = [
        ['id' => 102, 'username' => 'admin_aceh', 'display_name' => 'Admin Aceh', 'role' => 'agency'],
        ['id' => 103, 'username' => 'admin_sumut', 'display_name' => 'Admin Sumatera Utara', 'role' => 'agency'],
        ['id' => 104, 'username' => 'admin_sumbar', 'display_name' => 'Admin Sumatera Barat', 'role' => 'agency'],
        ['id' => 105, 'username' => 'admin_banten', 'display_name' => 'Admin Banten', 'role' => 'agency'],
        ['id' => 106, 'username' => 'admin_jabar', 'display_name' => 'Admin Jawa Barat', 'role' => 'agency'],
        ['id' => 107, 'username' => 'admin_jateng', 'display_name' => 'Admin Jawa Tengah', 'role' => 'agency'],
        ['id' => 108, 'username' => 'admin_jatim', 'display_name' => 'Admin Jawa Timur', 'role' => 'agency'],
        ['id' => 109, 'username' => 'admin_kalbar', 'display_name' => 'Admin Kalimantan Barat', 'role' => 'agency'],
        ['id' => 110, 'username' => 'admin_kaltim', 'display_name' => 'Admin Kalimantan Timur', 'role' => 'agency'],
        ['id' => 111, 'username' => 'admin_sulsel', 'display_name' => 'Admin Sulawesi Selatan', 'role' => 'agency']
    ];
}
