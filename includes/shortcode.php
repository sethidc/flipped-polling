<?php
function flipped_poll_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'flipped_poll');
    $id = (int) $atts['id'];
    $polls = get_option('flipped_polls', []);
    if (!isset($polls[$id])) {
        return '<p>' . esc_html__('Poll not found.', 'flipped-polling') . '</p>';
    }

    $poll = $polls[$id];
    $settings = get_option('flipped_poll_settings', ['vote_restriction' => 'cookie']);
    $current_date = gmdate('Y-m-d');
    $user_id = get_current_user_id();

    if (($poll['open_date'] && $current_date < $poll['open_date']) || ($poll['close_date'] && $current_date > $poll['close_date'])) {
        return '<div class="flipped-poll-' . esc_attr($poll['template']) . '"><p>' . esc_html__('The poll is currently closed.', 'flipped-polling') . '</p></div>';
    }

    $votes = get_option("flipped_poll_votes_$id", []);
    $has_voted = flipped_polling_has_voted($id, $settings, $user_id);

    if (isset($_POST['flipped_poll_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['flipped_poll_nonce'])), 'flipped_poll_vote_' . $id)) {
        $vote_key = 'poll_vote_' . $id;
        if (isset($_POST[$vote_key]) && !$has_voted) {
            $vote = is_array($_POST[$vote_key]) ? array_map('sanitize_text_field', wp_unslash($_POST[$vote_key])) : sanitize_text_field(wp_unslash($_POST[$vote_key]));
            if (is_array($vote)) {
                foreach ($vote as $v) {
                    $votes[$v] = isset($votes[$v]) ? $votes[$v] + 1 : 1;
                }
            } else {
                $votes[$vote] = isset($votes[$vote]) ? $votes[$vote] + 1 : 1;
            }
            update_option("flipped_poll_votes_$id", $votes);
            flipped_polling_record_vote($id, $settings, $user_id);
            $has_voted = true;
        }
    }

    ob_start();
    ?>
    <div class="flipped-poll-<?php echo esc_attr($poll['template']); ?>" data-poll-id="<?php echo esc_attr($id); ?>">
        <h3><?php echo esc_html($poll['question']); ?></h3>
        <?php if ($poll['category']) : ?>
            <p class="poll-category"><?php echo esc_html__('Category:', 'flipped-polling') . ' ' . esc_html($poll['category']); ?></p>
        <?php endif; ?>
        <?php if (!$has_voted) : ?>
            <form method="post" class="flipped-poll-form" data-poll-id="<?php echo esc_attr($id); ?>">
                <?php wp_nonce_field('flipped_poll_vote_' . $id, 'flipped_poll_nonce'); ?>
                <?php if ($poll['template'] === 'button-grid') : ?>
                    <div class="options">
                        <?php foreach (explode("\n", trim($poll['options'])) as $option) : $option = trim($option); if (!empty($option)) : ?>
                            <button type="submit" name="poll_vote_<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($option); ?>" class="option-button"><?php echo esc_html($option); ?></button>
                        <?php endif; endforeach; ?>
                    </div>
                <?php elseif ($poll['template'] === 'checkbox') : ?>
                    <?php foreach (explode("\n", trim($poll['options'])) as $option) : $option = trim($option); if (!empty($option)) : ?>
                        <p><input type="checkbox" name="poll_vote_<?php echo esc_attr($id); ?>[]" value="<?php echo esc_attr($option); ?>"> <?php echo esc_html($option); ?></p>
                    <?php endif; endforeach; ?>
                    <p><button type="submit" class="button"><?php echo esc_html__('Vote', 'flipped-polling'); ?></button></p>
                <?php else : ?>
                    <?php foreach (explode("\n", trim($poll['options'])) as $option) : $option = trim($option); if (!empty($option)) : ?>
                        <p><input type="radio" name="poll_vote_<?php echo esc_attr($id); ?>" value="<?php echo esc_attr($option); ?>" required> <?php echo esc_html($option); ?></p>
                    <?php endif; endforeach; ?>
                    <p><button type="submit" class="button"><?php echo esc_html__('Vote', 'flipped-polling'); ?></button></p>
                <?php endif; ?>
            </form>
        <?php else : ?>
            <p><?php echo esc_html__('You have already voted.', 'flipped-polling'); ?></p>
        <?php endif; ?>

        <?php if ($poll['show_results'] === 'before' || ($poll['show_results'] === 'after' && $has_voted)) : ?>
            <?php /* translators: %d is the total number of votes */ ?>
            <h4><?php printf(esc_html__('Results (%d votes):', 'flipped-polling'), esc_html(array_sum($votes))); ?></h4>
            <?php foreach (explode("\n", trim($poll['options'])) as $option) : $option = trim($option); if (!empty($option)) : 
                $vote_count = isset($votes[$option]) ? $votes[$option] : 0;
                $total_votes = array_sum($votes);
                $percentage = $total_votes > 0 ? round(($vote_count / $total_votes) * 100, 2) : 0;
            ?>
                <div class="poll-result">
                    <?php /* translators: %1$s is the option name, %2$d is the vote count, %3$s is the percentage */ ?>
                    <p><?php printf(esc_html__('%1$s: %2$d votes (%3$s%%)', 'flipped-polling'), esc_html($option), esc_html($vote_count), esc_html($percentage)); ?></p>
                    <div class="poll-bar" style="width: <?php echo esc_attr($percentage); ?>%; background: #<?php echo esc_attr(substr(md5($option), 0, 6)); ?>;"></div>
                </div>
            <?php endif; endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('flipped_poll', 'flipped_poll_shortcode');

function flipped_polling_ajax_vote() {
    check_ajax_referer('flipped_polling_vote', 'nonce');
    $id = isset($_POST['poll_id']) ? (int) $_POST['poll_id'] : 0;
    $vote = isset($_POST['vote']) ? (is_array($_POST['vote']) ? array_map('sanitize_text_field', wp_unslash($_POST['vote'])) : sanitize_text_field(wp_unslash($_POST['vote']))) : '';
    $polls = get_option('flipped_polls', []);
    if (!isset($polls[$id]) || empty($vote)) {
        wp_send_json_error(esc_html__('Poll not found or invalid vote.', 'flipped-polling'));
    }

    $settings = get_option('flipped_poll_settings', ['vote_restriction' => 'cookie']);
    $user_id = get_current_user_id();
    if (!flipped_polling_has_voted($id, $settings, $user_id)) {
        $votes = get_option("flipped_poll_votes_$id", []);
        if (is_array($vote)) {
            foreach ($vote as $v) {
                $votes[$v] = isset($votes[$v]) ? $votes[$v] + 1 : 1;
            }
        } else {
            $votes[$vote] = isset($votes[$vote]) ? $votes[$vote] + 1 : 1;
        }
        update_option("flipped_poll_votes_$id", $votes);
        flipped_polling_record_vote($id, $settings, $user_id);
        wp_send_json_success(flipped_poll_shortcode(['id' => $id]));
    }
    wp_send_json_error(esc_html__('You have already voted.', 'flipped-polling'));
}
add_action('wp_ajax_flipped_polling_vote', 'flipped_polling_ajax_vote');
add_action('wp_ajax_nopriv_flipped_polling_vote', 'flipped_polling_ajax_vote');

function flipped_polling_has_voted($id, $settings, $user_id) {
    if ($settings['vote_restriction'] === 'cookie') {
        return isset($_COOKIE["flipped_poll_voted_$id"]);
    } elseif ($settings['vote_restriction'] === 'ip') {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        return in_array($ip, get_option("flipped_poll_voters_$id", []));
    } elseif ($settings['vote_restriction'] === 'user' && $user_id) {
        $user_votes = get_user_meta($user_id, 'flipped_poll_votes', true);
        return !empty($user_votes) && in_array($id, $user_votes);
    }
    return false;
}

function flipped_polling_record_vote($id, $settings, $user_id) {
    if ($settings['vote_restriction'] === 'cookie') {
        setcookie("flipped_poll_voted_$id", true, time() + 3600 * 24 * 30);
    } elseif ($settings['vote_restriction'] === 'ip') {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        $voters = get_option("flipped_poll_voters_$id", []);
        $voters[] = $ip;
        update_option("flipped_poll_voters_$id", $voters);
    } elseif ($settings['vote_restriction'] === 'user' && $user_id) {
        $user_votes = get_user_meta($user_id, 'flipped_poll_votes', true) ?: [];
        $user_votes[] = $id;
        update_user_meta($user_id, 'flipped_poll_votes', $user_votes);
    }
}
