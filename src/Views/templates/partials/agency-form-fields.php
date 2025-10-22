<?php
/**
 * Agency Form Fields - Shared Component
 *
 * @package     WP_Agency
 * @subpackage  Views/Templates/Partials
 * @version     1.0.0
 * @author      arisciwek
 *
 * Path: /wp-agency/src/Views/templates/partials/agency-form-fields.php
 *
 * Description: Shared form component untuk agency registration.
 *              Digunakan oleh self-register, admin-create, dan edit forms.
 *              Memastikan field structure dan validation konsisten.
 *              Single source of truth untuk agency form fields.
 *
 * Parameters:
 * @param string $mode              Form mode: 'self-register', 'admin-create', or 'edit'
 * @param string $layout            Layout: 'single-column' or 'two-column'
 * @param array  $field_classes     CSS classes untuk fields (optional)
 * @param array  $wrapper_classes   CSS classes untuk wrapper (optional)
 * @param object $agency            Agency data for edit mode (optional)
 *
 * Usage:
 * include locate_template('partials/agency-form-fields.php', false, false, [
 *     'mode' => 'self-register',
 *     'layout' => 'single-column'
 * ]);
 *
 * Changelog:
 * 1.0.0 - 2025-01-22 (Task-2065 Form Sync)
 * - Initial version
 * - Shared component untuk register.php, create-agency-form.php, dan edit-agency-form.php
 * - Conditional rendering berdasarkan mode
 * - Integrated dengan wilayah-indonesia plugin
 */

defined('ABSPATH') || exit;

// Default parameters
$mode = $args['mode'] ?? 'self-register';
$layout = $args['layout'] ?? 'single-column';
$field_classes = $args['field_classes'] ?? 'regular-text';
$wrapper_classes = $args['wrapper_classes'] ?? 'form-group';
$agency = $args['agency'] ?? null;

$is_self_register = ($mode === 'self-register');
$is_admin_create = ($mode === 'admin-create');
$is_edit = ($mode === 'edit');
?>

<?php if ($is_self_register): ?>
<!-- Informasi Login (Self-Register Only) -->
<div class="wp-agency-card">
    <div class="wp-agency-card-header">
        <h3><?php _e('Informasi Login', 'wp-agency'); ?></h3>
    </div>
    <div class="wp-agency-card-body">
        <!-- Username -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="username">
                <?php _e('Username', 'wp-agency'); ?>
                <span class="required">*</span>
            </label>
            <input type="text"
                   id="username"
                   name="username"
                   class="<?php echo esc_attr($field_classes); ?>"
                   required>
            <p class="description"><?php _e('Username untuk login', 'wp-agency'); ?></p>
        </div>

        <!-- Email -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="email">
                <?php _e('Email', 'wp-agency'); ?>
                <span class="required">*</span>
            </label>
            <input type="email"
                   id="email"
                   name="email"
                   class="<?php echo esc_attr($field_classes); ?>"
                   required>
        </div>

        <!-- Password -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="password">
                <?php _e('Password', 'wp-agency'); ?>
                <span class="required">*</span>
            </label>
            <input type="password"
                   id="password"
                   name="password"
                   class="<?php echo esc_attr($field_classes); ?>"
                   required>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Informasi Dasar/Perusahaan -->
<?php if ($layout === 'two-column'): ?>
<div class="row left-side">
<?php endif; ?>
    <div class="<?php echo $is_self_register ? 'wp-agency-card' : 'agency-form-section'; ?>">
        <?php if ($is_self_register): ?>
        <div class="wp-agency-card-header">
            <h3><?php _e('Informasi Perusahaan', 'wp-agency'); ?></h3>
        </div>
        <div class="wp-agency-card-body">
        <?php else: ?>
            <h4><?php _e('Informasi Dasar', 'wp-agency'); ?></h4>
        <?php endif; ?>

            <!-- Nama Disnaker/Agency -->
            <div class="<?php echo esc_attr($wrapper_classes); ?>">
                <label for="<?php echo $is_edit ? 'edit-name' : ($is_admin_create ? 'agency-name' : 'name'); ?>">
                    <?php echo $is_self_register ? __('Nama Lengkap/Perusahaan', 'wp-agency') : __('Nama Disnaker', 'wp-agency'); ?>
                    <span class="<?php echo $is_self_register ? 'required' : 'required-field'; ?>">*</span>
                </label>
                <input type="text"
                       id="<?php echo $is_edit ? 'edit-name' : ($is_admin_create ? 'agency-name' : 'name'); ?>"
                       name="name"
                       class="<?php echo esc_attr($field_classes); ?>"
                       maxlength="100"
                       value="<?php echo $is_edit && $agency ? esc_attr($agency->name) : ''; ?>"
                       required>
                <?php if ($is_self_register): ?>
                <p class="description"><?php _e('Nama ini akan digunakan sebagai identitas agency', 'wp-agency'); ?></p>
                <?php elseif ($is_admin_create): ?>
                <span class="field-hint"><?php _e('Masukkan nama lengkap disnaker', 'wp-agency'); ?></span>
                <?php endif; ?>
            </div>

            <?php if (!$is_self_register): ?>
            <!-- Status (Admin Create & Edit Only) -->
            <div class="<?php echo esc_attr($wrapper_classes); ?>">
                <label for="<?php echo $is_edit ? 'edit-status' : 'agency-status'; ?>">
                    <?php _e('Status', 'wp-agency'); ?>
                    <span class="required-field">*</span>
                </label>
                <select id="<?php echo $is_edit ? 'edit-status' : 'agency-status'; ?>" name="status" required>
                    <option value="active" <?php echo ($is_edit && $agency && $agency->status === 'active') ? 'selected' : ''; ?>>
                        <?php _e('Aktif', 'wp-agency'); ?>
                    </option>
                    <option value="inactive" <?php echo ($is_edit && $agency && $agency->status === 'inactive') ? 'selected' : ''; ?>>
                        <?php _e('Tidak Aktif', 'wp-agency'); ?>
                    </option>
                </select>
                <?php if ($is_admin_create): ?>
                <span class="field-hint"><?php _e('Status aktif disnaker', 'wp-agency'); ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

        <?php if ($is_self_register): ?>
        </div><!-- .wp-agency-card-body -->
    </div><!-- .wp-agency-card -->
        <?php endif; ?>

<?php if (!$is_self_register): ?>
    </div><!-- .agency-form-section -->
<?php endif; ?>

<?php if ($layout === 'two-column'): ?>
</div><!-- .row .left-side -->

<div class="row right-side">
<?php endif; ?>

<!-- Lokasi -->
<?php if ($is_self_register): ?>
<div class="wp-agency-card">
    <div class="wp-agency-card-header">
        <h3><?php _e('Lokasi', 'wp-agency'); ?></h3>
    </div>
    <div class="wp-agency-card-body">
<?php else: ?>
    <div class="agency-form-section">
        <h4><?php _e('Lokasi', 'wp-agency'); ?></h4>
<?php endif; ?>

        <!-- Provinsi -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="<?php echo $is_edit ? 'edit-provinsi' : ($is_admin_create ? 'agency-provinsi' : 'provinsi_code'); ?>">
                <?php _e('Provinsi', 'wp-agency'); ?>
                <span class="<?php echo $is_self_register ? 'required' : 'required-field'; ?>">*</span>
            </label>
            <?php
            do_action('wilayah_indonesia_province_select', [
                'name' => 'provinsi_code',
                'id' => $is_edit ? 'edit-provinsi' : ($is_admin_create ? 'agency-provinsi' : 'provinsi_code'),
                'class' => $field_classes . ' wilayah-province-select',
                'required' => 'required',
                'data-placeholder' => __('Pilih Provinsi', 'wp-agency')
            ]);
            ?>
            <!-- Hidden field for provinsi_id (will be populated by JavaScript) -->
            <input type="hidden"
                   name="provinsi_id"
                   id="<?php echo $is_edit ? 'edit-provinsi-id' : ($is_admin_create ? 'agency-provinsi-id' : 'provinsi_id'); ?>"
                   value="">
            <?php if ($is_self_register): ?>
            <p class="description"><?php _e('Provinsi tempat agency berada', 'wp-agency'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Kabupaten/Kota -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="<?php echo $is_edit ? 'edit-regency' : ($is_admin_create ? 'agency-regency' : 'regency_code'); ?>">
                <?php _e('Kabupaten/Kota', 'wp-agency'); ?>
                <span class="<?php echo $is_self_register ? 'required' : 'required-field'; ?>">*</span>
            </label>
            <?php
            do_action('wilayah_indonesia_regency_select', [
                'name' => 'regency_code',
                'id' => $is_edit ? 'edit-regency' : ($is_admin_create ? 'agency-regency' : 'regency_code'),
                'class' => $field_classes . ' wilayah-regency-select',
                'required' => 'required',
                'data-loading-text' => __('Memuat...', 'wp-agency'),
                'data-dependent' => $is_edit ? 'edit-provinsi' : ($is_admin_create ? 'agency-provinsi' : 'provinsi_code')
            ]);
            ?>
            <!-- Hidden field for regency_id (will be populated by JavaScript) -->
            <input type="hidden"
                   name="regency_id"
                   id="<?php echo $is_edit ? 'edit-regency-id' : ($is_admin_create ? 'agency-regency-id' : 'regency_id'); ?>"
                   value="">
            <?php if ($is_self_register): ?>
            <p class="description"><?php _e('Kabupaten/Kota tempat agency berada', 'wp-agency'); ?></p>
            <?php endif; ?>
        </div>

        <?php if ($is_edit && current_user_can('edit_all_agencies')): ?>
        <!-- Admin User (Edit Only, with permission) -->
        <div class="<?php echo esc_attr($wrapper_classes); ?>">
            <label for="edit-user">
                <?php _e('Admin', 'wp-agency'); ?>
            </label>
            <select id="edit-user" name="user_id" class="<?php echo esc_attr($field_classes); ?>">
                <option value=""><?php _e('Pilih Admin', 'wp-agency'); ?></option>
                <?php
                $users = get_users(['role__in' => ['Disnaker']]);
                foreach ($users as $user) {
                    $selected = ($agency && $agency->user_id == $user->ID) ? 'selected' : '';
                    printf(
                        '<option value="%d" %s>%s</option>',
                        $user->ID,
                        $selected,
                        esc_html($user->display_name)
                    );
                }
                ?>
            </select>
        </div>
        <?php endif; ?>

    </div><!-- .agency-form-section or .wp-agency-card-body -->
<?php if ($is_self_register): ?>
</div><!-- .wp-agency-card -->
<?php endif; ?>

<?php if ($layout === 'two-column'): ?>
</div><!-- .row .right-side -->
<?php endif; ?>
