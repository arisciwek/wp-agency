# Penggunaan Select List WP Agency

## Setup Awal

### 1. Dependensi
Sebelum menggunakan select list, pastikan semua dependensi telah terpenuhi:

- jQuery
- WordPress Core
- AgencyToast untuk notifikasi (opsional)

### 2. Enqueue Scripts dan Styles

```php
// Di file plugin Anda
add_action('admin_enqueue_scripts', function($hook) {
    // Cek apakah sedang di halaman yang membutuhkan select
    if ($hook === 'your-page.php') {
        // Enqueue script
        wp_enqueue_script(
            'wp-agency-select-handler',
            WP_AGENCY_URL . 'assets/js/components/select-handler.js',
            ['jquery'],
            WP_AGENCY_VERSION,
            true
        );

        // Setup data untuk JavaScript
        wp_localize_script('wp-agency-select-handler', 'wpAgencyData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_agency_select_nonce'),
            'texts' => [
                'select_division' => __('Pilih Division', 'wp-agency'),
                'loading' => __('Memuat...', 'wp-agency'),
                'error' => __('Gagal memuat data', 'wp-agency')
            ]
        ]);

        // Enqueue AgencyToast jika digunakan
        wp_enqueue_script('agency-toast');
        wp_enqueue_style('agency-toast-style');
    }
});
```

### 3. Integrasi Cache System

```php
// Mengaktifkan cache
add_filter('wp_agency_enable_cache', '__return_true');

// Konfigurasi durasi cache (dalam detik)
add_filter('wp_agency_cache_duration', function() {
    return 3600; // 1 jam
});
```

## Penggunaan Hook

### 1. Filter untuk Data Options

```php
// Mendapatkan options agency dengan cache
$agency_options = apply_filters('wp_agency_get_agency_options', [
    '' => __('Pilih Agency', 'your-textdomain')
], true); // Parameter kedua untuk include_empty

// Mendapatkan options cabang dengan cache
$division_options = apply_filters(
    'wp_agency_get_division_options',
    [],
    $agency_id,
    true // Parameter ketiga untuk include_empty
);
```

### 2. Action untuk Render Select

```php
// Render agency select dengan atribut lengkap
do_action('wp_agency_agency_select', [
    'name' => 'my_agency',
    'id' => 'my_agency_field',
    'class' => 'my-select-class wp-agency-agency-select',
    'data-placeholder' => __('Pilih Agency', 'your-textdomain'),
    'required' => 'required',
    'aria-label' => __('Pilih Agency', 'your-textdomain')
], $selected_agency_id);

// Render division select dengan loading state
do_action('wp_agency_division_select', [
    'name' => 'my_division',
    'id' => 'my_division_field',
    'class' => 'my-select-class wp-agency-division-select',
    'data-loading-text' => __('Memuat...', 'your-textdomain'),
    'required' => 'required',
    'aria-label' => __('Pilih Division', 'your-textdomain')
], $agency_id, $selected_division_id);
```

## Implementasi JavaScript

### 1. Event Handling

```javascript
(function($) {
    'use strict';

    const WPSelect = {
        init() {
            this.bindEvents();
            this.setupLoadingState();
        },

        bindEvents() {
            $(document).on('change', '.wp-agency-agency-select', this.handleAgencyChange.bind(this));
            $(document).on('wilayah:loaded', '.wp-agency-division-select', this.handl.DivisionLoaded.bind(this));
        },

        setupLoadingState() {
            this.$loadingIndicator = $('<span>', {
                class: 'wp-agency-loading',
                text: wpAgencyData.texts.loading
            }).hide();
        },

        handleAgencyChange(e) {
            const $agency = $(e.target);
            const $division = $('.wp-agency-division-select');
            const agencyId = $agency.val();

            // Reset dan disable division select
            this.rese.DivisionSelect($division);

            if (!agencyId) return;

            // Show loading state
            this.showLoading($division);

            // Make AJAX call
            $.ajax({
                url: wpAgencyData.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'get_division_options',
                    agency_id: agencyId,
                    nonce: wpAgencyData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $division.html(response.data.html);
                        $division.trigger('wilayah:loaded');
                    } else {
                        this.handleError(response.data.message);
                    }
                },
                error: (jqXHR, textStatus, errorThrown) => {
                    this.handleError(errorThrown);
                },
                complete: () => {
                    this.hideLoading($division);
                }
            });
        },

        rese.DivisionSelect($division) {
            $division.prop('disabled', true)
                   .html(`<option value="">${wpAgencyData.texts.select_division}</option>`);
        },

        showLoading($element) {
            $element.prop('disabled', true);
            this.$loadingIndicator.insertAfter($element).show();
        },

        hideLoading($element) {
            $element.prop('disabled', false);
            this.$loadingIndicator.hide();
        },

        handleError(message) {
            console.error('WP Select Error:', message);
            if (typeof AgencyToast !== 'undefined') {
                AgencyToast.error(message || wpAgencyData.texts.error);
            }
        },

        handl.DivisionLoaded(e) {
            const $division = $(e.target);
            // Custom handling setelah data loaded
        }
    };

    $(document).ready(() => WPSelect.init());

})(jQuery);
```

## Integrasi Cache System

Plugin ini menggunakan sistem cache WordPress untuk optimasi performa:

### 1. Cache Implementation

```php
class WPCache {
    private $cache_enabled;
    private $cache_duration;
    
    public function __construct() {
        $this->cache_enabled = apply_filters('wp_agency_enable_cache', true);
        $this->cache_duration = apply_filters('wp_agency_cache_duration', 3600);
    }
    
    public function get($key) {
        if (!$this->cache_enabled) return false;
        return wp_cache_get($key, 'wp_agency');
    }
    
    public function set($key, $data) {
        if (!$this->cache_enabled) return false;
        return wp_cache_set($key, $data, 'wp_agency', $this->cache_duration);
    }
    
    public function delete($key) {
        return wp_cache_delete($key, 'wp_agency');
    }
}
```

### 2. Penggunaan Cache

```php
// Di SelectListHooks.php
public function getAgencyOptions(array $default_options = [], bool $include_empty = true): array {
    $cache = new WPCache();
    $cache_key = 'agency_options_' . md5(serialize($default_options) . $include_empty);
    
    $options = $cache->get($cache_key);
    if (false !== $options) {
        return $options;
    }
    
    $options = $this->buildAgencyOptions($default_options, $include_empty);
    $cache->set($cache_key, $options);
    
    return $options;
}
```

## Error Handling & Debugging

### 1. PHP Error Handling

```php
try {
    // Operasi database atau file
} catch (\Exception $e) {
    error_log('WP Agency Plugin Error: ' . $e->getMessage());
    wp_send_json_error([
        'message' => __('Terjadi kesalahan saat memproses data', 'wp-agency')
    ]);
}
```

### 2. JavaScript Debugging

```javascript
// Aktifkan mode debug
add_filter('wp_agency_debug_mode', '__return_true');

// Di JavaScript
if (wpAgencyData.debug) {
    console.log('Agency changed:', agencyId);
    console.log('AJAX response:', response);
}
```

## Testing & Troubleshooting

### 1. Unit Testing

```php
class WPSelectTest extends WP_UnitTestCase {
    public function test_agency_options() {
        $hooks = new SelectListHooks();
        $options = $hooks->getAgencyOptions();
        
        $this->assertIsArray($options);
        $this->assertArrayHasKey('', $options);
    }
}
```

### 2. Common Issues & Solutions

1. **Select Kabupaten Tidak Update**
   - Periksa Console Browser
   - Validasi nonce
   - Pastikan hook AJAX terdaftar

2. **Cache Tidak Bekerja**
   - Periksa Object Cache aktif
   - Validasi cache key
   - Cek durasi cache

3. **Loading State Tidak Muncul**
   - Periksa CSS terload
   - Validasi selector JavaScript
   - Cek konflik jQuery

## Support & Maintenance

### 1. Reporting Issues
- Gunakan GitHub Issues
- Sertakan error log
- Berikan langkah reproduksi

### 2. Development Workflow
1. Fork repository
2. Buat division fitur
3. Submit pull request
4. Tunggu review

### 3. Kontribusi
- Ikuti coding standards
- Dokumentasikan perubahan
- Sertakan unit test

## Changelog

### Version 1.1.0 (2024-01-07)
- Implementasi loading state
- Perbaikan error handling
- Optimasi cache system
- Update dokumentasi

### Version 1.0.0 (2024-01-06)
- Initial release
- Basic select functionality
- Agency-division relation
