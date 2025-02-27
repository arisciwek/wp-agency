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
    public static $data = [
        ['id' => 2, 'username' => 'budi_santoso', 'display_name' => 'Budi Santoso', 'role' => 'agency'],
        ['id' => 3, 'username' => 'dewi_kartika', 'display_name' => 'Dewi Kartika', 'role' => 'agency'],
        ['id' => 4, 'username' => 'ahmad_hidayat', 'display_name' => 'Ahmad Hidayat', 'role' => 'agency'],
        ['id' => 5, 'username' => 'siti_rahayu', 'display_name' => 'Siti Rahayu', 'role' => 'agency'],
        ['id' => 6, 'username' => 'rudi_hermawan', 'display_name' => 'Rudi Hermawan', 'role' => 'agency'],
        ['id' => 7, 'username' => 'nina_kusuma', 'display_name' => 'Nina Kusuma', 'role' => 'agency'],
        ['id' => 8, 'username' => 'eko_prasetyo', 'display_name' => 'Eko Prasetyo', 'role' => 'agency'],
        ['id' => 9, 'username' => 'maya_wijaya', 'display_name' => 'Maya Wijaya', 'role' => 'agency'],
        ['id' => 10, 'username' => 'dian_pertiwi', 'display_name' => 'Dian Pertiwi', 'role' => 'agency'],
        ['id' => 11, 'username' => 'agus_suryanto', 'display_name' => 'Agus Suryanto', 'role' => 'agency']
    ];
}
