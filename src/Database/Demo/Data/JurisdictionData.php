<?php
/**
 * Jurisdiction Data
 *
 * @package     WP_Agency
 * @subpackage  Database/Demo/Data
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Database/Demo/Data/JurisdictionData.php
 *
 * Description: Static jurisdiction data for demo generation.
 *              Defines which regencies each division covers.
 *              Used by JurisdictionDemoData.
 */

namespace WPAgency\Database\Demo\Data;

defined('ABSPATH') || exit;

class JurisdictionData {
    // Static jurisdiction data - defines regency coverage for each division
    // Key: division_id
    // Value: array with 'regencies' array (primary will be determined from division.regency_id)
    public static $data = [
        1 => [  // Disnaker Provinsi Aceh Division Kabupaten Aceh Tengah
            'regencies' => [54, 55],  // Additional: Aceh Tenggara, Aceh Timur
            'created_by' => 102
        ],
        2 => [  // Disnaker Provinsi Aceh Division Kabupaten Aceh  Selatan
            'regencies' => [],  // Primary only
            'created_by' => 102
        ],
        3 => [  // Disnaker Provinsi Aceh Division Kota Banda Aceh
            'regencies' => [57],  // Primary only
            'created_by' => 102
        ],
        4 => [  // Disnaker Provinsi Sumatera Utara Division Kota Pematang Siantar
            'regencies' => [],  // Primary only
            'created_by' => 103
        ],
        5 => [  // Disnaker Provinsi Sumatera Utara Division Kabupaten Tapanuli Tengah
            'regencies' => [],  // Additional: Tapanuli Utara, Tapanuli Selatan
            'created_by' => 103
        ],
        6 => [  // Disnaker Provinsi Sumatera Utara Division Kota Medan
            'regencies' => [58],  // Primary only
            'created_by' => 103
        ],
        7 => [  // Disnaker Provinsi Sumatera Barat Division Kota Padang
            'regencies' => [],  // Additional: Kota Bukittinggi
            'created_by' => 104
        ],
        8 => [  // Disnaker Provinsi Sumatera Barat Division Kabupaten Sijunjung
            'regencies' => [],  // Primary only
            'created_by' => 104
        ],
        9 => [  // Disnaker Provinsi Sumatera Barat Division Kabupaten Pesisir Selatan
            'regencies' => [],  // Additional: Solok
            'created_by' => 104
        ],
        10 => [  // Disnaker Provinsi Banten Division Kota Serang
            'regencies' => [],  // Primary only
            'created_by' => 105
        ],
        11 => [  // Disnaker Provinsi Banten Division Kota Cilegon
            'regencies' => [],  // Primary only
            'created_by' => 105
        ],
        12 => [  // Disnaker Provinsi Banten Division Kota Tangerang Selatan
            'regencies' => [],  // Primary only
            'created_by' => 105
        ],
        13 => [  // Disnaker Provinsi Jawa Barat Division Kabupaten Sukabumi
            'regencies' => [73],  // Additional: Kabupaten Bogor
            'created_by' => 106
        ],
        14 => [  // Disnaker Provinsi Jawa Barat Division Kota Cimahi
            'regencies' => [],  // Primary only
            'created_by' => 106
        ],
        15 => [  // Disnaker Provinsi Jawa Barat Division Kota Depok
            'regencies' => [],  // Primary only
            'created_by' => 106
        ],
        16 => [  // Disnaker Provinsi Jawa Tengah Division Kota Semarang
            'regencies' => [],  // Primary only
            'created_by' => 107
        ],
        17 => [  // Disnaker Provinsi Jawa Tengah Division Kabupaten Cilacap
            'regencies' => [79],  // Additional: Banyumas
            'created_by' => 107
        ],
        18 => [  // Disnaker Provinsi Jawa Tengah Division Kabupaten Purbalingga
            'regencies' => [],  // Primary only
            'created_by' => 107
        ],
        19 => [  // Disnaker Provinsi DKI Jakarta Division Kota Jakarta Utara
            'regencies' => [],  // Primary only
            'created_by' => 108
        ],
        20 => [  // Disnaker Provinsi DKI Jakarta Division Kota Jakarta Pusat
            'regencies' => [],  // Primary only
            'created_by' => 108
        ],
        21 => [  // Disnaker Provinsi DKI Jakarta Division Kota Jakarta Barat
            'regencies' => [71, 72],  // Additional: Jakarta Selatan, Jakarta Timur
            'created_by' => 108
        ],
        22 => [  // Disnaker Provinsi Maluku Division Kabupaten Maluku Tenggara
            'regencies' => [],  // Primary only
            'created_by' => 109
        ],
        23 => [  // Disnaker Provinsi Maluku Division Kabupaten Maluku Tenggara Barat
            'regencies' => [],  // Primary only
            'created_by' => 109
        ],
        24 => [  // Disnaker Provinsi Maluku Division Kota Ambon
            'regencies' => [],  // Additional: Kota Tual
            'created_by' => 109
        ],
        25 => [  // Disnaker Provinsi Papua Division Kabupaten Merauke
            'regencies' => [],  // Primary only
            'created_by' => 110
        ],
        26 => [  // Disnaker Provinsi Papua Division Kabupaten Jayapura
            'regencies' => [],  // Primary only
            'created_by' => 110
        ],
        27 => [  // Disnaker Provinsi Papua Division Kota Jayapura
            'regencies' => [],  // Primary only
            'created_by' => 110
        ],
        28 => [  // Disnaker Provinsi Sulawesi Selatan Division Kabupaten Bulukumba
            'regencies' => [],  // Primary only
            'created_by' => 111
        ],
        29 => [  // Disnaker Provinsi Sulawesi Selatan Division Kabupaten Kepulauan Selayar
            'regencies' => [],  // Primary only
            'created_by' => 111
        ],
        30 => [  // Disnaker Provinsi Sulawesi Selatan Division Kabupaten Bantaeng
            'regencies' => [95],  // Additional: Makassar, Palopo
            'created_by' => 111
        ]
    ];
}
