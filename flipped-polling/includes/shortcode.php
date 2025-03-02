<?php
function flipped_poll_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts, 'flipped_poll');
    $id = intval($atts['id']);
    $polls = get_option('flipped_polls', []);
    if (!isset($polls[$id])) {
        return '<p>Poll not found.</p>';
    }

    $poll = $polls[$id];
    $settings = get_option('flipped_poll_settings', ['vote_restriction' => 'cookie']);
    $current_date = date('Y-m-d');
    $user_id = get_current_user_id();

    if (($poll['open_date'] && $current_date < $poll['open_date']) || ($poll['close_date'] && $current_date > $poll['close_date'])) {
        return '<div class="flipped-poll-' . esc_attr($poll['template']) . '"><p>The poll is currently closed.</p></div>';
    }

    $votes = get_option("flipped_poll_votes_$id", []);
    $has_voted = flipped_polling_has_voted($id, $settings, $user_id);

    // Handle non-AJAX vote (fallback)
    if (isset($_POST['poll_vote_' . $id]) && !$has_voted) {
        $vote = sanitize_text_field($_POST['poll_vote_' . $id]);
        $votes[$vote] = isset($votes[$vote]) ? $votes[$vote] + 1 : 1;
        update_option("flipped_poll_votes_$id", $votes);
        flipped_polling_record_vote($id, $settings, $user_id);
        $has_voted = true;
    }

    ob_start();
    ?>
    <div class="flipped-poll-<?php echo esc_attr($poll['template']); ?>" data-poll-id="<?php echo $id; ?>">
        <h3><?php echo esc_html($poll['question']); ?></h3>
        <?php if ($poll['category']) : ?>
            <p class="poll-category">Category: <?php echo esc_html($poll['category']); ?></p>
        <?php endif; ?>
        <?php if (!$has_voted) : ?>
            <form method="post" class="flipped-poll-form" data-poll-id="<?php echo $id; ?>">
                <?php foreach (explode("\n", trim($poll['options'])) as $option) : $option = trim($option); if (!empty($option)) : ?>
                    <p><input type="radio" name="poll_vote_<?php echo $id; ?>" value="<?php echo esc_attr($option); ?>" required> <?php echo esc_html($option); ?></p>
                <?php endif; endforeach; ?>
                <p><button type="submit" class="button">Vote</button></p>
            </form>
        <?php else : ?>
            <p>You have already voted.</p>
        <?php endif; ?>

        <?php if ($poll['show_results'] === 'before' || ($poll['show_results'] === 'after' && $has_voted)) : ?>
            <h4>Results (<?php echo array_sum($votes); ?> votes):</h4>
            <?php foreach (explode("\n", trim($poll['options'])) as $option) : $option = trim($option); if (!empty($option)) :
                $vote_count = isset($votes[$option]) ? $votes[$option] : 0;
                $total_votes = array_sum($votes);
                $percentage = $total_votes > 0 ? round(($vote_count / $total_votes) * 100, 2) : 0;
            ?>
                <div class="poll-result">
                    <p><?php echo esc_html($option); ?>: <?php echo $vote_count; ?> votes (<?php echo $percentage; ?>%)</p>
                    <div class="poll-bar" style="width: <?php echo $percentage; ?>%; background: #<?php echo substr(md5($option), 0, 6); ?>;"></div>
                </div>
            <?php endif; endforeach; ?>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('flipped_poll', 'flipped_poll_shortcode');

// AJAX voting handler
function flipped_polling_ajax_vote() {
    check_ajax_referer('flipped_polling_vote', 'nonce');
    $id = intval($_POST['poll_id']);
    $vote = sanitize_text_field($_POST['vote']);
    $polls = get_option('flipped_polls', []);
    if (!isset($polls[$id])) {
        wp_send_json_error('Poll not found.');
    }

    $settings = get_option('flipped_poll_settings', ['vote_restriction' => 'cookie']);
    $user_id = get_current_user_id();
    if (!flipped_polling_has_voted($id, $settings, $user_id)) {
        $votes = get_option("flipped_poll_votes_$id", []);
        $votes[$vote] = isset($votes[$vote]) ? $votes[$vote] + 1 : 1;
        update_option("flipped_poll_votes_$id", $votes);
        flipped_polling_record_vote($id, $settings, $user_id);
        wp_send_json_success(flipped_poll_shortcode(['id' => $id]));
    }
    wp_send_json_error('You have already voted.');
}
add_action('wp_ajax_flipped_polling_vote', 'flipped_polling_ajax_vote');
add_action('wp_ajax_nopriv_flipped_polling_vote', 'flipped_polling_ajax_vote');

// Voting check and recording functions
function flipped_polling_has_voted($id, $settings, $user_id) {
    if ($settings['vote_restriction'] === 'cookie') {
        return isset($_COOKIE["flipped_poll_voted_$id"]);
    } elseif ($settings['vote_restriction'] === 'ip') {
        return in_array($_SERVER['REMOTE_ADDR'], get_option("flipped_poll_voters_$id", []));
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
        $voters = get_option("flipped_poll_voters_$id", []);
        $voters[] = $_SERVER['REMOTE_ADDR'];
        update_option("flipped_poll_voters_$id", $voters);
    } elseif ($settings['vote_restriction'] === 'user' && $user_id) {
        $user_votes = get_user_meta($user_id, 'flipped_poll_votes', true) ?: [];
        $user_votes[] = $id;
        update_user_meta($user_id, 'flipped_poll_votes', $user_votes);
    }
}