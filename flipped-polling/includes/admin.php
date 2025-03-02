<?php
// Admin menu and pages
function flipped_polling_menu() {
    add_menu_page('Flipped Polling', 'Flipped Polling', 'manage_options', 'flipped-polling', 'flipped_polling_manage', 'dashicons-chart-bar', 80);
    add_submenu_page('flipped-polling', 'Manage Polls', 'Manage Polls', 'manage_options', 'flipped-polling', 'flipped_polling_manage');
    add_submenu_page('flipped-polling', 'Add New Poll', 'Add New', 'manage_options', 'flipped-polling-add', 'flipped_polling_add');
    add_submenu_page('flipped-polling', 'Poll Stats', 'Poll Stats', 'manage_options', 'flipped-polling-stats', 'flipped_polling_stats');
    add_submenu_page('flipped-polling', 'Settings', 'Settings', 'manage_options', 'flipped-polling-settings', 'flipped_polling_settings_page');
}
add_action('admin_menu', 'flipped_polling_menu');

function flipped_polling_manage() {
    $polls = get_option('flipped_polls', []);

    // Handle deletion
    if (isset($_GET['delete']) && check_admin_referer('delete_poll_' . $_GET['delete'])) {
        $id = intval($_GET['delete']);
        if (isset($polls[$id])) {
            unset($polls[$id]);
            update_option('flipped_polls', $polls);
            delete_option("flipped_poll_votes_$id");
            delete_option("flipped_poll_voters_$id");
            wp_redirect(admin_url('admin.php?page=flipped-polling'));
            exit;
        }
    }

    // Handle duplication
    if (isset($_GET['duplicate']) && check_admin_referer('duplicate_poll_' . $_GET['duplicate'])) {
        $id = intval($_GET['duplicate']);
        if (isset($polls[$id])) {
            $polls[] = $polls[$id];
            update_option('flipped_polls', $polls);
            wp_redirect(admin_url('admin.php?page=flipped-polling'));
            exit;
        }
    }

    ?>
    <div class="wrap">
        <h1>Flipped Polling - Manage Polls</h1>
        <?php if (empty($polls)) : ?>
            <p>No polls created yet. <a href="<?php echo admin_url('admin.php?page=flipped-polling-add'); ?>">Add a new poll</a>.</p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Question</th>
                        <th>Category</th>
                        <th>Template</th>
                        <th>Shortcode</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $templates = flipped_polling_get_templates();
                    foreach ($polls as $id => $poll) : ?>
                        <tr>
                            <td><?php echo esc_html($id); ?></td>
                            <td><?php echo esc_html($poll['question']); ?></td>
                            <td><?php echo esc_html($poll['category'] ?: 'None'); ?></td>
                            <td><?php echo esc_html($templates[$poll['template']]['name']); ?></td>
                            <td><code>[flipped_poll id="<?php echo esc_attr($id); ?>"]</code></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=flipped-polling-add&edit=' . $id); ?>">Edit</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=flipped-polling&delete=' . $id), 'delete_poll_' . $id); ?>" onclick="return confirm('Are you sure?');">Delete</a> |
                                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=flipped-polling&duplicate=' . $id), 'duplicate_poll_' . $id); ?>">Duplicate</a> |
                                <a href="<?php echo admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $id); ?>">Stats</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="flipped-polling-footer">
                    Developed by <a href="https://sethideclercq.com" target="_blank">Sethi De Clercq</a>
        </div>
    </div>
    <?php
}

function flipped_polling_add() {
    $polls = get_option('flipped_polls', []);
    $edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : null;
    $poll = $edit_id !== null && isset($polls[$edit_id]) ? $polls[$edit_id] : [
        'question' => '',
        'options' => '',
        'open_date' => '',
        'close_date' => '',
        'show_results' => 'after',
        'template' => 'classic',
        'category' => ''
    ];

    if (isset($_POST['flipped_poll_save'])) {
        $new_poll = [
            'question' => sanitize_text_field($_POST['poll_question']),
            'options' => sanitize_textarea_field($_POST['poll_options']),
            'open_date' => sanitize_text_field($_POST['poll_open_date']),
            'close_date' => sanitize_text_field($_POST['poll_close_date']),
            'show_results' => sanitize_text_field($_POST['poll_show_results']),
            'template' => sanitize_text_field($_POST['poll_template']),
            'category' => sanitize_text_field($_POST['poll_category'])
        ];
        if ($edit_id !== null) {
            $polls[$edit_id] = $new_poll;
        } else {
            $polls[] = $new_poll;
        }
        update_option('flipped_polls', $polls);
        $new_id = $edit_id !== null ? $edit_id : (count($polls) - 1);
        echo '<div class="updated"><p>Poll saved! Use shortcode: <code>[flipped_poll id="' . $new_id . '"]</code></p></div>';
    }

    $templates = flipped_polling_get_templates();
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id !== null ? 'Edit Poll' : 'Add New Poll'; ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="poll_question">Poll Question</label></th>
                    <td><input type="text" name="poll_question" id="poll_question" value="<?php echo esc_attr($poll['question']); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="poll_options">Poll Options (one per line)</label></th>
                    <td><textarea name="poll_options" id="poll_options" rows="5" class="large-text" required><?php echo esc_textarea($poll['options']); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="poll_open_date">Open Date (YYYY-MM-DD)</label></th>
                    <td><input type="date" name="poll_open_date" id="poll_open_date" value="<?php echo esc_attr($poll['open_date']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="poll_close_date">Close Date (YYYY-MM-DD)</label></th>
                    <td><input type="date" name="poll_close_date" id="poll_close_date" value="<?php echo esc_attr($poll['close_date']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="poll_show_results">Show Results</label></th>
                    <td>
                        <select name="poll_show_results" id="poll_show_results">
                            <option value="before" <?php selected($poll['show_results'], 'before'); ?>>Before Voting</option>
                            <option value="after" <?php selected($poll['show_results'], 'after'); ?>>After Voting</option>
                            <option value="never" <?php selected($poll['show_results'], 'never'); ?>>Never</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="poll_template">Poll Template</label></th>
                    <td>
                        <select name="poll_template" id="poll_template">
                            <?php foreach ($templates as $key => $template) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($poll['template'], $key); ?>><?php echo esc_html($template['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Choose a design template for this poll.</p>
                    </td>
                </tr>
                <tr>
                    <th><label for="poll_category">Poll Category</label></th>
                    <td><input type="text" name="poll_category" id="poll_category" value="<?php echo esc_attr($poll['category']); ?>" class="regular-text">
                        <p class="description">Optional category for organization.</p></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="flipped_poll_save" class="button-primary" value="Save Poll">
            </p>
        </form>
    </div>
    <?php
}

function flipped_polling_stats() {
    $polls = get_option('flipped_polls', []);
    $poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : null;

    if ($poll_id === null || !isset($polls[$poll_id])) {
        ?>
        <div class="wrap">
            <h1>Poll Stats</h1>
            <p>Please select a poll from the <a href="<?php echo admin_url('admin.php?page=flipped-polling'); ?>">Manage Polls</a> page.</p>
        </div>
        <?php
        return;
    }

    // Handle CSV export
    if (isset($_GET['export']) && check_admin_referer('export_stats_' . $poll_id)) {
        $votes = get_option("flipped_poll_votes_$poll_id", []);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="poll_' . $poll_id . '_stats.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Option', 'Votes']);
        foreach (explode("\n", trim($polls[$poll_id]['options'])) as $option) {
            $option = trim($option);
            if (!empty($option)) {
                fputcsv($output, [$option, isset($votes[$option]) ? $votes[$option] : 0]);
            }
        }
        fclose($output);
        exit;
    }

    // Handle vote reset
    if (isset($_GET['reset_votes']) && check_admin_referer('reset_votes_' . $poll_id)) {
        delete_option("flipped_poll_votes_$poll_id");
        delete_option("flipped_poll_voters_$poll_id");
        wp_redirect(admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $poll_id));
        exit;
    }

    $poll = $polls[$poll_id];
    $votes = get_option("flipped_poll_votes_$poll_id", []);
    $total_votes = array_sum($votes);
    $options = explode("\n", trim($poll['options']));
    ?>
    <div class="wrap">
        <h1>Stats for Poll: <?php echo esc_html($poll['question']); ?></h1>
        <p>Total Votes: <?php echo $total_votes; ?></p>
        <?php if ($total_votes > 0) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Option</th>
                        <th>Votes</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options as $option) : $option = trim($option); if (!empty($option)) : ?>
                        <tr>
                            <td><?php echo esc_html($option); ?></td>
                            <td><?php echo isset($votes[$option]) ? $votes[$option] : 0; ?></td>
                            <td><?php echo $total_votes > 0 ? round((isset($votes[$option]) ? $votes[$option] : 0) / $total_votes * 100, 2) : 0; ?>%</td>
                        </tr>
                    <?php endif; endforeach; ?>
                </tbody>
            </table>
            <p>
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $poll_id . '&reset_votes=1'), 'reset_votes_' . $poll_id); ?>" onclick="return confirm('Reset all votes for this poll?');">Reset Votes</a> |
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $poll_id . '&export=1'), 'export_stats_' . $poll_id); ?>">Export CSV</a>
            </p>
        <?php else : ?>
            <p>No votes yet.</p>
        <?php endif; ?>
    </div>
    <?php
}