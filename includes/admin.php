<?php
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
            setcookie("flipped_poll_voted_$id", '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN); // Clear cookie
            unset($_COOKIE["flipped_poll_voted_$id"]); // Clear for current request
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $user_votes = get_user_meta($user_id, 'flipped_poll_votes', true) ?: [];
                if (($key = array_search($id, $user_votes)) !== false) {
                    unset($user_votes[$key]);
                    update_user_meta($user_id, 'flipped_poll_votes', array_values($user_votes));
                }
            }
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
        <h1><?php echo esc_html__('Flipped Polling - Manage Polls', 'flipped-polling'); ?></h1>
        <?php if (empty($polls)) : ?>
            <p><?php echo esc_html__('No polls created yet.', 'flipped-polling'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=flipped-polling-add')); ?>"><?php echo esc_html__('Add a new poll', 'flipped-polling'); ?></a></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('ID', 'flipped-polling'); ?></th>
                        <th><?php echo esc_html__('Question', 'flipped-polling'); ?></th>
                        <th><?php echo esc_html__('Category', 'flipped-polling'); ?></th>
                        <th><?php echo esc_html__('Template', 'flipped-polling'); ?></th>
                        <th><?php echo esc_html__('Shortcode', 'flipped-polling'); ?></th>
                        <th><?php echo esc_html__('Actions', 'flipped-polling'); ?></th>
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
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flipped-polling-add&edit=' . $id)); ?>"><?php echo esc_html__('Edit', 'flipped-polling'); ?></a> |
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=flipped-polling&delete=' . $id), 'delete_poll_' . $id)); ?>" onclick="return confirm('<?php echo esc_js(__('Are you sure?', 'flipped-polling')); ?>');"><?php echo esc_html__('Delete', 'flipped-polling'); ?></a> |
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=flipped-polling&duplicate=' . $id), 'duplicate_poll_' . $id)); ?>"><?php echo esc_html__('Duplicate', 'flipped-polling'); ?></a> |
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $id)); ?>"><?php echo esc_html__('Stats', 'flipped-polling'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div class="flipped-polling-footer">
            <?php echo esc_html__('Developed by', 'flipped-polling'); ?> <a href="https://sethideclercq.com" target="_blank"><?php echo esc_html__('Sethi DeClercq', 'flipped-polling'); ?></a>
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
        echo '<div class="updated"><p>' . esc_html__('Poll saved! Use shortcode:', 'flipped-polling') . ' <code>[flipped_poll id="' . esc_attr($new_id) . '"]</code></p></div>';
    }

    $templates = flipped_polling_get_templates();
    ?>
    <div class="wrap">
        <h1><?php echo $edit_id !== null ? esc_html__('Edit Poll', 'flipped-polling') : esc_html__('Add New Poll', 'flipped-polling'); ?></h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="poll_question"><?php echo esc_html__('Poll Question', 'flipped-polling'); ?></label></th>
                    <td><input type="text" name="poll_question" id="poll_question" value="<?php echo esc_attr($poll['question']); ?>" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="poll_options"><?php echo esc_html__('Poll Options (one per line)', 'flipped-polling'); ?></label></th>
                    <td><textarea name="poll_options" id="poll_options" rows="5" class="large-text" required><?php echo esc_textarea($poll['options']); ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="poll_open_date"><?php echo esc_html__('Open Date (YYYY-MM-DD)', 'flipped-polling'); ?></label></th>
                    <td><input type="date" name="poll_open_date" id="poll_open_date" value="<?php echo esc_attr($poll['open_date']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="poll_close_date"><?php echo esc_html__('Close Date (YYYY-MM-DD)', 'flipped-polling'); ?></label></th>
                    <td><input type="date" name="poll_close_date" id="poll_close_date" value="<?php echo esc_attr($poll['close_date']); ?>"></td>
                </tr>
                <tr>
                    <th><label for="poll_show_results"><?php echo esc_html__('Show Results', 'flipped-polling'); ?></label></th>
                    <td>
                        <select name="poll_show_results" id="poll_show_results">
                            <option value="before" <?php selected($poll['show_results'], 'before'); ?>><?php echo esc_html__('Before Voting', 'flipped-polling'); ?></option>
                            <option value="after" <?php selected($poll['show_results'], 'after'); ?>><?php echo esc_html__('After Voting', 'flipped-polling'); ?></option>
                            <option value="never" <?php selected($poll['show_results'], 'never'); ?>><?php echo esc_html__('Never', 'flipped-polling'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="poll_template"><?php echo esc_html__('Poll Template', 'flipped-polling'); ?></label></th>
                    <td>
                        <select name="poll_template" id="poll_template">
                            <?php foreach ($templates as $key => $template) : ?>
                                <option value="<?php echo esc_attr($key); ?>" <?php selected($poll['template'], $key); ?>><?php echo esc_html($template['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php echo esc_html__('Choose a design template for this poll.', 'flipped-polling'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="poll_category"><?php echo esc_html__('Poll Category', 'flipped-polling'); ?></label></th>
                    <td><input type="text" name="poll_category" id="poll_category" value="<?php echo esc_attr($poll['category']); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('Optional category for organization.', 'flipped-polling'); ?></p></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="flipped_poll_save" class="button-primary" value="<?php echo esc_attr__('Save Poll', 'flipped-polling'); ?>">
            </p>
        </form>
    </div>
    <?php
}

function flipped_polling_stats() {
    $polls = get_option('flipped_polls', []);
    $poll_id = isset($_GET['poll_id']) ? intval($_GET['poll_id']) : null;

    // Handle resets before output
    if (isset($_GET['reset_votes']) && check_admin_referer('reset_votes_' . $poll_id)) {
        if (isset($polls[$poll_id])) {
            delete_option("flipped_poll_votes_$poll_id");
            delete_option("flipped_poll_voters_$poll_id");
            setcookie("flipped_poll_voted_$poll_id", '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN); // Clear cookie
            unset($_COOKIE["flipped_poll_voted_$poll_id"]); // Clear for current request
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                $user_votes = get_user_meta($user_id, 'flipped_poll_votes', true) ?: [];
                if (($key = array_search($poll_id, $user_votes)) !== false) {
                    unset($user_votes[$key]);
                    update_user_meta($user_id, 'flipped_poll_votes', array_values($user_votes));
                }
            }
            wp_redirect(admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $poll_id));
            exit;
        }
    }

    // Handle CSV export
    if (isset($_GET['export']) && check_admin_referer('export_stats_' . $poll_id)) {
        $votes = get_option("flipped_poll_votes_$poll_id", []);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="poll_' . esc_attr($poll_id) . '_stats.csv"');
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

    if ($poll_id === null || !isset($polls[$poll_id])) {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Poll Stats', 'flipped-polling'); ?></h1>
            <p><?php echo esc_html__('Please select a poll from the', 'flipped-polling'); ?> <a href="<?php echo esc_url(admin_url('admin.php?page=flipped-polling')); ?>"><?php echo esc_html__('Manage Polls', 'flipped-polling'); ?></a> <?php echo esc_html__('page.', 'flipped-polling'); ?></p>
        </div>
        <?php
        return;
    }

    $poll = $polls[$poll_id];
    $votes = get_option("flipped_poll_votes_$poll_id", []);
    $total_votes = array_sum($votes);
    $options = explode("\n", trim($poll['options']));
    ?>
    <div class="wrap">
        <h1><?php /* translators: %s is the poll question */ printf(esc_html__('Stats for Poll: %s', 'flipped-polling'), esc_html($poll['question'])); ?></h1>
        <p><?php /* translators: %d is the total number of votes */ printf(esc_html__('Total Votes: %d', 'flipped-polling'), esc_html($total_votes)); ?></p>
        <?php if ($total_votes > 0) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Option', 'flipped-polling'); ?></th>
                        <th><?php echo esc_html__('Votes', 'flipped-polling'); ?></th>
                        <th><?php echo esc_html__('Percentage', 'flipped-polling'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options as $option) : $option = trim($option); if (!empty($option)) : ?>
                        <tr>
                            <td><?php echo esc_html($option); ?></td>
                            <td><?php echo esc_html(isset($votes[$option]) ? $votes[$option] : 0); ?></td>
                            <td><?php echo esc_html($total_votes > 0 ? round((isset($votes[$option]) ? $votes[$option] : 0) / $total_votes * 100, 2) : 0); ?>%</td>
                        </tr>
                    <?php endif; endforeach; ?>
                </tbody>
            </table>
            <p>
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $poll_id . '&reset_votes=1'), 'reset_votes_' . $poll_id)); ?>" onclick="return confirm('<?php echo esc_js(__('Reset all votes for this poll?', 'flipped-polling')); ?>');"><?php echo esc_html__('Reset Votes', 'flipped-polling'); ?></a> |
                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=flipped-polling-stats&poll_id=' . $poll_id . '&export=1'), 'export_stats_' . $poll_id)); ?>"><?php echo esc_html__('Export CSV', 'flipped-polling'); ?></a>
            </p>
        <?php else : ?>
            <p><?php echo esc_html__('No votes yet.', 'flipped-polling'); ?></p>
        <?php endif; ?>
    </div>
    <?php
}
