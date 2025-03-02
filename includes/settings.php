<?php
function flipped_polling_settings_page() {
    if (isset($_POST['flipped_poll_settings_save'])) {
        update_option('flipped_poll_settings', [
            'vote_restriction' => sanitize_text_field($_POST['vote_restriction']),
            'primary_color' => sanitize_hex_color($_POST['primary_color']),
            'custom_css' => wp_strip_all_tags($_POST['custom_css'], false)
        ]);
        echo '<div class="updated"><p>Settings saved!</p></div>';
    }

    $settings = get_option('flipped_poll_settings', [
        'vote_restriction' => 'cookie',
        'primary_color' => '#0073aa',
        'custom_css' => ''
    ]);
    ?>
    <div class="wrap">
        <h1>Flipped Polling Settings</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="vote_restriction">Vote Restriction</label></th>
                    <td>
                        <select name="vote_restriction" id="vote_restriction">
                            <option value="cookie" <?php selected($settings['vote_restriction'], 'cookie'); ?>>Cookie (Basic)</option>
                            <option value="ip" <?php selected($settings['vote_restriction'], 'ip'); ?>>IP Address</option>
                            <option value="user" <?php selected($settings['vote_restriction'], 'user'); ?>>User (Logged-in Only)</option>
                            <option value="none" <?php selected($settings['vote_restriction'], 'none'); ?>>None (Allow Multiple Votes)</option>
                        </select>
                        <p class="description">Method to prevent multiple votes per user.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="primary_color">Primary Color</label></th>
                    <td><input type="color" name="primary_color" id="primary_color" value="<?php echo esc_attr($settings['primary_color']); ?>">
                        <p class="description">Used for headings across all templates.</p></td>
                </tr>
                <tr>
                    <th><label for="custom_css">Custom CSS</label></th>
                    <td><textarea name="custom_css" id="custom_css" rows="10" class="large-text code"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                        <p class="description">Override styles with custom CSS. Use <code>.flipped-poll-[template]</code> (e.g., .flipped-poll-classic).</p></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="flipped_poll_settings_save" class="button-primary" value="Save Settings">
            </p>
        </form>
    </div>
    <?php
}

function flipped_polling_styles() {
    $settings = get_option('flipped_poll_settings', ['primary_color' => '#0073aa', 'custom_css' => '']);
    $templates = flipped_polling_get_templates();
    $css = '';
    foreach ($templates as $key => $template) {
        $css .= $template['css'];
    }
    $css .= ".flipped-poll-classic h3, .flipped-poll-modern h3, .flipped-poll-bold h3, .flipped-poll-minimal h3, .flipped-poll-dark h3 { color: {$settings['primary_color']}; }";
    $css .= '.poll-result { margin: 10px 0; } .poll-bar { height: 10px; transition: width 0.3s ease; }';
    $css .= '.poll-category { font-style: italic; color: #666; }';
    $css .= $settings['custom_css'];
    wp_add_inline_style('wp-admin', $css);
}
add_action('wp_enqueue_scripts', 'flipped_polling_styles');