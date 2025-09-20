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
        1 => [  // Disnaker Provinsi Aceh Division Kabupaten Aceh Selatan
            'regencies' => [53, 54, 55],  // Aceh Selatan, Aceh Tenggara, Aceh Timur
            'created_by' => 102
        ],
        2 => [  // Disnaker Provinsi Aceh Division Kota Sabang
            'regencies' => [57],  // Kota Sabang
            'created_by' => 102
        ],
        3 => [  // Disnaker Provinsi Aceh Division Kota Banda Aceh
            'regencies' => [56],  // Kota Banda Aceh
            'created_by' => 102
        ],
        4 => [  // Disnaker Provinsi Sumatera Utara Division Kota Pematang Siantar
            'regencies' => [62],  // Kota Pematang Siantar
            'created_by' => 103
        ],
        5 => [  // Disnaker Provinsi Sumatera Utara Division Kabupaten Tapanuli Tengah
            'regencies' => [58, 59, 60],  // Tapanuli Tengah, Tapanuli Utara, Tapanuli Selatan
            'created_by' => 103
        ],
        6 => [  // Disnaker Provinsi Sumatera Utara Division Kota Medan
            'regencies' => [61],  // Kota Medan
            'created_by' => 103
        ],
        7 => [  // Disnaker Provinsi Sumatera Barat Division Kota Padang
            'regencies' => [66, 67],  // Kota Padang, Kota Bukittinggi
            'created_by' => 104
        ],
        8 => [  // Disnaker Provinsi Sumatera Barat Division Kabupaten Sijunjung
            'regencies' => [65],  // Kabupaten Sijunjung
            'created_by' => 104
        ],
        9 => [  // Disnaker Provinsi Sumatera Barat Division Kabupaten Pesisir Selatan
            'regencies' => [63, 64],  // Pesisir Selatan, Solok
            'created_by' => 104
        ],
        10 => [  // Disnaker Provinsi Banten Division Kota Serang
            'regencies' => [89],  // Kota Serang
            'created_by' => 105
        ],
        11 => [  // Disnaker Provinsi Banten Division Kota Cilegon
            'regencies' => [88],  // Kota Cilegon
            'created_by' => 105
        ],
        12 => [  // Disnaker Provinsi Banten Division Kota Tangerang Selatan
            'regencies' => [90],  // Kota Tangerang Selatan
            'created_by' => 105
        ],
        13 => [  // Disnaker Provinsi Jawa Barat Division Kabupaten Sukabumi
            'regencies' => [74],  // Kabupaten Sukabumi
            'created_by' => 106
        ],
        14 => [  // Disnaker Provinsi Jawa Barat Division Kota Cimahi
            'regencies' => [77],  // Kota Cimahi
            'created_by' => 106
        ],
        15 => [  // Disnaker Provinsi Jawa Barat Division Kota Depok
            'regencies' => [76],  // Kota Depok
            'created_by' => 106
        ],
        16 => [  // Disnaker Provinsi Jawa Tengah Division Kota Semarang
            'regencies' => [82],  // Kota Semarang
            'created_by' => 107
        ],
        17 => [  // Disnaker Provinsi Jawa Tengah Division Kabupaten Cilacap
            'regencies' => [78, 79],  // Cilacap, Banyumas
            'created_by' => 107
        ],
        18 => [  // Disnaker Provinsi Jawa Tengah Division Kabupaten Purbalingga
            'regencies' => [80],  // Purbalingga
            'created_by' => 107
        ],
        19 => [  // Disnaker Provinsi DKI Jakarta Division Kota Jakarta Utara
            'regencies' => [69],  // Kota Jakarta Utara
            'created_by' => 108
        ],
        20 => [  // Disnaker Provinsi DKI Jakarta Division Kota Jakarta Pusat
            'regencies' => [68],  // Kota Jakarta Pusat
            'created_by' => 108
        ],
        21 => [  // Disnaker Provinsi DKI Jakarta Division Kota Jakarta Barat
            'regencies' => [70, 71, 72],  // Jakarta Barat, Jakarta Selatan, Jakarta Timur (Bogor is in Jawa Barat, not DKI Jakarta)
            'created_by' => 108
        ],
        22 => [  // Disnaker Provinsi Maluku Division Kabupaten Maluku Tenggara
            'regencies' => [97],  // Kabupaten Maluku Tenggara
            'created_by' => 109
        ],
        23 => [  // Disnaker Provinsi Maluku Division Kabupaten Maluku Tenggara Barat
            'regencies' => [98],  // Kabupaten Maluku Tenggara Barat
            'created_by' => 109
        ],
        24 => [  // Disnaker Provinsi Maluku Division Kota Ambon
            'regencies' => [99, 100],  // Kota Ambon, Kota Tual
            'created_by' => 109
        ],
        25 => [  // Disnaker Provinsi Papua Division Kabupaten Merauke
            'regencies' => [101],  // Kabupaten Merauke
            'created_by' => 110
        ],
        26 => [  // Disnaker Provinsi Papua Division Kabupaten Jayapura
            'regencies' => [103],  // Kabupaten Jayapura
            'created_by' => 110
        ],
        27 => [  // Disnaker Provinsi Papua Division Kota Jayapura
            'regencies' => [104],  // Kota Jayapura
            'created_by' => 110
        ],
        28 => [  // Disnaker Provinsi Sulawesi Selatan Division Kabupaten Bulukumba
            'regencies' => [92],  // Kabupaten Bulukumba
            'created_by' => 111
        ],
        29 => [  // Disnaker Provinsi Sulawesi Selatan Division Kabupaten Kepulauan Selayar
            'regencies' => [91],  // Kabupaten Kepulauan Selayar
            'created_by' => 111
        ],
        30 => [  // Disnaker Provinsi Sulawesi Selatan Division Kabupaten Bantaeng
            'regencies' => [93, 94, 95],  // Bantaeng, Makassar, Palopo
            'created_by' => 111
        ]
    ];
}
