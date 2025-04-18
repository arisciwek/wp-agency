<?php
if (!defined('ABSPATH')) {
    die;
}
?>

<div class="wrap">
    <div class="membership-header">
        <h2><?php _e('Membership Levels Management', 'wp-agency'); ?></h2>
        <button type="button" class="button button-primary" id="add-membership-level">
            <?php _e('Add New Level', 'wp-agency'); ?>
        </button>
    </div>
        <div class="membership-grid">
            <?php foreach ($levels as $level): ?>

            <!-- Basic info tetap sama -->
            <div class="membership-card" data-level="<?php echo esc_attr($level['slug']); ?>">
                <div class="card-header">
                    <div class="level-name"><?php echo esc_html($level['name']); ?></div>
                    <div class="price">
                        Rp <?php echo number_format($level['price_per_month'], 0, ',', '.'); ?>/bulan
                    </div>
                    <div class="description"><?php echo esc_html($level['description']); ?></div>
                </div>

                <!-- Capabilities Section - Menampilkan fitur & batasan -->
                <?php 
                $capabilities = json_decode($level['capabilities'], true);
                if (!empty($capabilities)): ?>
                    <div class="features-section">
                        <!-- Staff Features -->
                        <?php if (!empty($capabilities['staff'])): ?>
                            <div class="feature-group">
                                <h4>Staff Features</h4>
                                <ul class="feature-list">
                                    <?php foreach ($capabilities['staff'] as $feature): ?>
                                        <li class="feature-item">
                                            <span class="feature-icon <?php echo $feature['value'] ? 'enabled' : 'disabled'; ?>">
                                                <?php echo $feature['value'] ? '✓' : '✗'; ?>
                                            </span>
                                            <?php echo esc_html($feature['label']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Data Management -->
                        <?php if (!empty($capabilities['data'])): ?>
                            <div class="feature-group">
                                <h4>Data Features</h4>
                                <ul class="feature-list">
                                    <?php foreach ($capabilities['data'] as $feature): ?>
                                        <li class="feature-item">
                                            <span class="feature-icon <?php echo $feature['value'] ? 'enabled' : 'disabled'; ?>">
                                                <?php echo $feature['value'] ? '✓' : '✗'; ?>
                                            </span>
                                            <?php echo esc_html($feature['label']); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Resource Limits -->
                        <?php if (!empty($capabilities['resources'])): ?>
                            <div class="feature-group">
                                <h4>Resource Limits</h4>
                                <ul class="feature-list">
                                    <?php foreach ($capabilities['resources'] as $feature): ?>
                                        <li class="feature-item">
                                            <span class="limit-label"><?php echo esc_html($feature['label']); ?>:</span>
                                            <span class="limit-value">
                                                <?php echo $feature['value'] === -1 ? '∞' : esc_html($feature['value']); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Communication Features -->
                        <?php if (!empty($capabilities['communication'])): ?>
                           <div class="feature-group">
                               <h4>Notifications</h4>
                               <ul class="feature-list">
                                   <?php foreach ($capabilities['communication'] as $feature): ?>
                                       <li class="feature-item">
                                           <span class="feature-icon <?php echo $feature['value'] ? 'enabled' : 'disabled'; ?>">
                                               <?php echo $feature['value'] ? '✓' : '✗'; ?>
                                           </span>
                                           <?php echo esc_html($feature['label']); ?>
                                       </li>
                                   <?php endforeach; ?>
                               </ul>
                           </div>
                        <?php endif; ?>

                    </div>
                <?php endif; ?>

                <!-- Trial & Grace Period seperti sebelumnya -->
                <div class="period-info">
                    <?php if ($level['is_trial_available']): ?>
                        <div class="trial-period">
                            Trial: <?php echo esc_html($level['trial_days']); ?> hari
                        </div>
                    <?php endif; ?>
                    <div class="grace-period">
                        Grace Period: <?php echo esc_html($level['grace_period_days']); ?> hari
                    </div>
                </div>

                <div class="card-footer card-actions">
                    <button type="button" class="button edit-level" data-id="<?php echo esc_attr($level['id']); ?>">
                        Edit
                    </button>
                    <button type="button" class="button delete-level" data-id="<?php echo esc_attr($level['id']); ?>">
                        Delete
                    </button>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

</div>

<?php
// Modal wrapper
?>
<div id="membership-level-modal" class="wp-agency-modal">
    <div class="modal-content">
        <div class="modal-header clearfix">
            <h3 class="modal-title"></h3>
            <button type="button" class="modal-close dashicons dashicons-no-alt"></button>
        </div>
        
        <form id="membership-level-form" class="wp-agency-form clearfix">
            <?php wp_nonce_field('wp_agency_membership_level', 'membership_level_nonce'); ?>
            <input type="hidden" name="id" id="level-id">

            <div class="form-fields-wrapper">
                <!-- Basic Info Section -->
                <div class="form-section">
                    <h4>
                        <span class="dashicons dashicons-admin-generic"></span> 
                        <?php _e('Basic Information', 'wp-agency'); ?>
                    </h4>
                    
                    <div class="form-row">
                        <label for="level-name">
                            <?php _e('Level Name', 'wp-agency'); ?> 
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="level-name" name="name" class="regular-text" required>
                        <p class="description"><?php _e('Enter a unique name for this membership level', 'wp-agency'); ?></p>
                    </div>

                    <div class="form-row">
                        <label for="level-description"><?php _e('Description', 'wp-agency'); ?></label>
                        <textarea id="level-description" name="description" rows="3" class="large-text"></textarea>
                        <p class="description"><?php _e('Brief description of what this level offers', 'wp-agency'); ?></p>
                    </div>

                    <div class="form-row">
                        <label for="price-per-month">
                            <?php _e('Price per Month (Rp)', 'wp-agency'); ?> 
                            <span class="required">*</span>
                        </label>
                        <input type="number" 
                               id="price-per-month" 
                               name="price_per_month" 
                               min="0" 
                               step="1000" 
                               class="regular-text" 
                               required>
                    </div>
                    
                    <div class="form-row">
                        <label for="sort-order"><?php _e('Sort Order', 'wp-agency'); ?></label>
                        <input type="number" 
                               id="sort-order" 
                               name="sort_order" 
                               min="0" 
                               class="small-text">
                        <p class="description">
                            <?php _e('Order in which this level appears (lower numbers first)', 'wp-agency'); ?>
                        </p>
                    </div>

                    <div class="form-row">
                        <label>
                            <input type="checkbox" name="is_trial_available" id="is-trial-available">
                            <?php _e('Enable Trial Period', 'wp-agency'); ?>
                        </label>
                    </div>

                    <div class="form-row trial-days-row" style="display: none;">
                        <label for="trial-days"><?php _e('Trial Days', 'wp-agency'); ?></label>
                        <input type="number" 
                               id="trial-days" 
                               name="trial_days" 
                               min="0" 
                               class="small-text">
                        <p class="description">
                            <?php _e('Number of days for trial period', 'wp-agency'); ?>
                        </p>
                    </div>

                    <div class="form-row">
                        <label for="grace-period-days"><?php _e('Grace Period (Days)', 'wp-agency'); ?></label>
                        <input type="number" 
                               id="grace-period-days" 
                               name="grace_period_days" 
                               min="0" 
                               class="small-text">
                        <p class="description">
                            <?php _e('Number of days after expiration before membership is suspended', 'wp-agency'); ?>
                        </p>
                    </div>
                </div>

                <!-- Features Sections - Dynamic from Model -->
                <?php 
                // Loop through each feature group
                foreach ($groups_and_features as $group_data): 
                    $group = $group_data['group'];
                    $features = $group_data['features'];

                    if (!empty($features)):
                ?>
                    <div class="form-section">
                        <h4>
                            <?php if (!empty($group['icon'])): ?>
                                <span class="dashicons <?php echo esc_attr($group['icon']); ?>"></span>
                            <?php endif; ?>
                            <?php echo esc_html($group['name']); ?>
                        </h4>
                        
                        <div class="features-grid">
                            <?php 
                            foreach ($features as $feature):
                                $metadata = json_decode($feature['metadata'], true);
                                $field_id = "feature-{$feature['field_name']}";
                                $field_name = "capabilities[{$metadata['group']}][{$feature['field_name']}]";
                            ?>
                                <div class="feature-item">
                                    <label class="input-label" for="<?php echo esc_attr($field_id); ?>">
                                        <?php if ($metadata['type'] === 'checkbox'): ?>
                                            <input type="checkbox"
                                                   name="<?php echo esc_attr($field_name); ?>"
                                                   id="<?php echo esc_attr($field_id); ?>"
                                                   class="<?php echo esc_attr($metadata['ui_settings']['css_class'] ?? ''); ?>"
                                                   data-group="<?php echo esc_attr($metadata['group']); ?>"
                                                   data-default="<?php echo esc_attr($metadata['default_value']); ?>"
                                                   <?php echo $metadata['is_required'] ? 'required' : ''; ?>>
                                            
                                        <?php elseif ($metadata['type'] === 'number'): ?>
                                            <input type="number"
                                                   name="<?php echo esc_attr($field_name); ?>"
                                                   id="<?php echo esc_attr($field_id); ?>"
                                                   class="<?php echo esc_attr($metadata['ui_settings']['css_class'] ?? ''); ?>"
                                                   data-group="<?php echo esc_attr($metadata['group']); ?>"
                                                   data-default="<?php echo esc_attr($metadata['default_value']); ?>"
                                                   min="<?php echo esc_attr($metadata['ui_settings']['min'] ?? ''); ?>"
                                                   max="<?php echo esc_attr($metadata['ui_settings']['max'] ?? ''); ?>"
                                                   step="<?php echo esc_attr($metadata['ui_settings']['step'] ?? '1'); ?>"
                                                   value="<?php echo esc_attr($metadata['default_value']); ?>"
                                                   <?php echo $metadata['is_required'] ? 'required' : ''; ?>>
                                        <?php endif; ?>
                                        
                                        <span class="label-text">
                                            <?php echo esc_html($metadata['label']); ?>
                                            <?php if ($metadata['is_required']): ?>
                                                <span class="required">*</span>
                                            <?php endif; ?>
                                        </span>
                                        
                                        <?php if (!empty($metadata['description'])): ?>
                                            <p class="description">
                                                <?php echo esc_html($metadata['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($metadata['ui_settings']['icon'])): ?>
                                            <span class="dashicons <?php echo esc_attr($metadata['ui_settings']['icon']); ?>"></span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>

            <div class="modal-footer clearfix">
                <div class="modal-buttons">
                    <button type="button" class="button modal-close">
                        <?php _e('Cancel', 'wp-agency'); ?>
                    </button>
                    <button type="submit" class="button button-primary">
                        <?php _e('Save Level', 'wp-agency'); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
