<?php
/**
 * Demo Data Generator Tab Template
 *
 * @package     WP_Agency
 * @subpackage  Views/Settings
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/settings/tab-demo-data.php
 */


/* 
1. Jika kedua opsi TIDAK dicentang:
   - Development Mode: ❌ 
   - Clear demo data: ❌
   - Hasil: Data TIDAK akan dihapus
   - Alasan: Development mode tidak aktif, jadi langsung ke fallback konstanta WP_AGENCY_DEVELOPMENT yang bernilai false

2. Jika HANYA Development Mode dicentang:
   - Development Mode: ✅
   - Clear demo data: ❌
   - Hasil: Data TIDAK akan dihapus
   - Alasan: Meskipun development mode aktif, clear_data_on_deactivate tidak dicentang

3. Jika HANYA Clear demo data dicentang:
   - Development Mode: ❌
   - Clear demo data: ✅
   - Hasil: Data TIDAK akan dihapus
   - Alasan: Development mode tidak aktif, jadi clear_data_on_deactivate tidak akan diperiksa dan langsung ke fallback konstanta

4. Jika KEDUA opsi dicentang:
   - Development Mode: ✅
   - Clear demo data: ✅
   - Hasil: Data AKAN dihapus
   - Alasan: Development mode aktif dan clear_data_on_deactivate juga aktif

Kesimpulannya:
- Data hanya akan dihapus jika KEDUA opsi dicentang
- Ini membuat sistem menjadi "double safety" - harus mengaktifkan development mode terlebih dahulu sebelum dapat menghapus data
- Jika salah satu saja tidak dicentang, data tidak akan dihapus
- Konstanta WP_AGENCY_DEVELOPMENT hanya digunakan sebagai fallback jika development mode tidak diaktifkan melalui UI

*/

if (!defined('ABSPATH')) {
    die;
}

// Verify nonce and capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}
?>

<div class="wrap">
    <div id="demo-data-messages"></div>
    <div class="demo-data-section">
        <h3><?php _e('Generate Demo Data', 'wp-agency'); ?></h3>
        <p class="description">
            <?php _e('Generate demo data for testing purposes. Each button will generate specific type of data.', 'wp-agency'); ?>
        </p>

        <div class="demo-data-grid">
            <!-- Feature Groups -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Feature Groups', 'wp-agency'); ?></h4>
                <p><?php _e('Generate feature group definitions for membership capabilities.', 'wp-agency'); ?></p>
                <button type="button" 
                        class="button button-primary agency-generate-demo-data" 
                        data-type="membership-groups"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_membership-groups'); ?>">
                    <?php _e('Generate Feature Groups', 'wp-agency'); ?>
                </button>
            </div>

            <!-- Membership Features -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Features', 'wp-agency'); ?></h4>
                <p><?php _e('Generate membership feature definitions for capabilities and limits.', 'wp-agency'); ?></p>
                <button type="button" 
                        class="button button-primary agency-generate-demo-data" 
                        data-type="membership-features"
                        data-requires="membership-groups"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_membership-groups'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_membership-features'); ?>">
                    <?php _e('Generate Membership Features', 'wp-agency'); ?>
                </button>
            </div>
            
            <!-- Membership Levels -->
            <div class="demo-data-card">
                <h4><?php _e('Membership Levels', 'wp-agency'); ?></h4>
                <p><?php _e('Generate default membership levels configuration.', 'wp-agency'); ?></p>
                <button type="button" 
                        class="button button-primary agency-generate-demo-data" 
                        data-type="membership-level"
                        data-requires="membership-features"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_membership-features'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_membership-level'); ?>">
                    <?php _e('Generate Membership Levels', 'wp-agency'); ?>
                </button>
            </div>

            <!-- Agencys -->
            <div class="demo-data-card">
                <h4><?php _e('Agencys', 'wp-agency'); ?></h4>
                <p><?php _e('Generate sample agency data with WordPress users.', 'wp-agency'); ?></p>
                <button type="button" 
                        class="button button-primary agency-generate-demo-data" 
                        data-type="agency"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_agency'); ?>">
                    <?php _e('Generate Agencys', 'wp-agency'); ?>
                </button>
            </div>

            <!-- Divisiones -->
            <div class="demo-data-card">
                <h4><?php _e('Divisions', 'wp-agency'); ?></h4>
                <p><?php _e('Generate division offices for existing agencies.', 'wp-agency'); ?></p>
                <button type="button" 
                        class="button button-primary agency-generate-demo-data" 
                        data-type="division"
                        data-requires="agency"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_agency'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_division'); ?>">
                    <?php _e('Generate Divisiones', 'wp-agency'); ?>
                </button>
            </div>

            <!-- Employees -->
            <div class="demo-data-card">
                <h4><?php _e('Employees', 'wp-agency'); ?></h4>
                <p><?php _e('Generate employee data for divisions.', 'wp-agency'); ?></p>
                <button type="button"
                        class="button button-primary agency-generate-demo-data"
                        data-type="employee"
                        data-requires="division"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_division'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_employee'); ?>">
                    <?php _e('Generate Employees', 'wp-agency'); ?>
                </button>
            </div>

            <!-- Jurisdictions -->
            <div class="demo-data-card">
                <h4><?php _e('Jurisdictions', 'wp-agency'); ?></h4>
                <p><?php _e('Generate jurisdiction data linking divisions to regencies.', 'wp-agency'); ?></p>
                <button type="button"
                        class="button button-primary agency-generate-demo-data"
                        data-type="jurisdiction"
                        data-requires="division"
                        data-check-nonce="<?php echo wp_create_nonce('check_demo_division'); ?>"
                        data-nonce="<?php echo wp_create_nonce('generate_demo_jurisdiction'); ?>">
                    <?php _e('Generate Jurisdictions', 'wp-agency'); ?>
                </button>
            </div>
        </div>
    </div>

    <div class="development-settings-section" style="margin-top: 30px;">
        <h3><?php _e('Development Settings', 'wp-agency'); ?></h3>
        <form method="post" action="options.php">
            <?php 
            settings_fields('wp_agency_development_settings');
            $dev_settings = get_option('wp_agency_development_settings', array(
                'enable_development' => 0,
                'clear_data_on_deactivate' => 0
            ));
            ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <?php _e('Development Mode', 'wp-agency'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="wp_agency_development_settings[enable_development]" 
                                   value="1" 
                                   <?php checked($dev_settings['enable_development'], 1); ?>>
                            <?php _e('Enable development mode', 'wp-agency'); ?>
                        </label>
                        <p class="description">
                            <?php _e('When enabled, this overrides WP_AGENCY_DEVELOPMENT constant.', 'wp-agency'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Data Cleanup', 'wp-agency'); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   name="wp_agency_development_settings[clear_data_on_deactivate]" 
                                   value="1" 
                                   <?php checked($dev_settings['clear_data_on_deactivate'], 1); ?>>
                            <?php _e('Clear demo data on plugin deactivation', 'wp-agency'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Warning: When enabled, all demo data will be permanently deleted when the plugin is deactivated.', 'wp-agency'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>

</div>
