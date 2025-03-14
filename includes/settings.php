<?php
/**
 * Manages settings page and styles for Flipped Polling.
 *
 */

/**
 * Displays and processes the Flipped Polling settings page.
 */
function flipped_polling_settings_page() {
    if (isset($_POST['flipped_poll_settings_save']) && isset($_POST['flipped_poll_settings_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['flipped_poll_settings_nonce'])), 'flipped_poll_settings_save')) {
        update_option('flipped_poll_settings', [
            'vote_restriction' => isset($_POST['vote_restriction']) ? sanitize_text_field(wp_unslash($_POST['vote_restriction'])) : 'cookie',
            'primary_color' => isset($_POST['primary_color']) ? sanitize_hex_color(wp_unslash($_POST['primary_color'])) : '#0073aa',
            'custom_css' => isset($_POST['custom_css']) ? wp_strip_all_tags(wp_unslash($_POST['custom_css']), false) : ''
        ]);
        echo '<div class="updated"><p>' . esc_html__('Settings saved!', 'flipped-polling') . '</p></div>';
    }

    $settings = get_option('flipped_poll_settings', [
        'vote_restriction' => 'cookie',
        'primary_color' => '#0073aa',
        'custom_css' => ''
    ]);
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Flipped Polling Settings', 'flipped-polling'); ?></h1>
        <form method="post" action="">
            <?php wp_nonce_field('flipped_poll_settings_save', 'flipped_poll_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="vote_restriction"><?php esc_html_e('Vote Restriction', 'flipped-polling'); ?></label></th>
                    <td>
                        <select name="vote_restriction" id="vote_restriction">
                            <option value="cookie" <?php selected($settings['vote_restriction'], 'cookie'); ?>><?php esc_html_e('Cookie (Basic)', 'flipped-polling'); ?></option>
                            <option value="ip" <?php selected($settings['vote_restriction'], 'ip'); ?>><?php esc_html_e('IP Address', 'flipped-polling'); ?></option>
                            <option value="user" <?php selected($settings['vote_restriction'], 'user'); ?>><?php esc_html_e('User (Logged-in Only)', 'flipped-polling'); ?></option>
                            <option value="none" <?php selected($settings['vote_restriction'], 'none'); ?>><?php esc_html_e('None (Allow Multiple Votes)', 'flipped-polling'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Method to prevent multiple votes per user.', 'flipped-polling'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="primary_color"><?php esc_html_e('Primary Color', 'flipped-polling'); ?></label></th>
                    <td><input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>">
                        <p class="description"><?php esc_html_e('Used for headings across all templates.', 'flipped-polling'); ?></p></td>
                </tr>
                <tr>
                    <th><label for="custom_css"><?php esc_html_e('Custom CSS', 'flipped-polling'); ?></label></th>
                    <td><textarea name="custom_css" id="custom_css" rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                        <p class="description"><?php esc_html_e('Override styles with custom CSS. Use <code>.flipped-poll-[template]</code> (e.g., .flipped-poll-classic).', 'flipped-polling'); ?></p></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="flipped_poll_settings_save" class="button-primary" value="<?php esc_attr_e('Save Settings', 'flipped-polling'); ?>">
            </p>
        </form>
    </div>
    <?php
}

/**
 * Enqueues inline styles for Flipped Polling based on settings and templates.
 */
function flipped_polling_styles() {
    $settings = get_option('flipped_poll_settings', ['primary_color' => '#0073aa', 'custom_css' => '']);
    $templates = flipped_polling_get_templates();
    $css = '';
    foreach ($templates as $key => $template) {
        $css .= $template['css'];
    }
    $css .= ".flipped-poll-classic h3, .flipped-poll-modern h3, .flipped-poll-bold h3, .flipped-poll-minimal h3, .flipped-poll-dark h3 { color: " . esc_attr($settings['primary_color']) . "; }";
    $css .= '.poll-result { margin: 10px 0; } .poll-bar { height: 10px; transition: width 0.3s ease; }';
    $css .= '.poll-category { font-style: italic; color: #666; }';
    $css .= $settings['custom_css']; // Already sanitized via wp_strip_all_tags
    wp_add_inline_style('flipped-polling-admin', $css);
}
add_action('wp_enqueue_scripts', 'flipped_polling_styles');
add_action('admin_enqueue_scripts', 'flipped_polling_styles');
