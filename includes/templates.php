<?php
/**
 * Provides template definitions for Flipped Polling.
 *
 * @package Flipped_Polling
 */

/**
 * Returns an array of available poll templates with their names and CSS styles.
 *
 * @return array Associative array of templates with 'name' and 'css' keys.
 */
function flipped_polling_get_templates() {
    return [
        'classic' => [
            'name' => esc_html__('Classic', 'flipped-polling'),
            'css' => '
                .flipped-poll-classic { max-width: 500px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #fff; }
                .flipped-poll-classic h3 { color: #333; font-size: 24px; margin-bottom: 15px; }
                .flipped-poll-classic p { margin: 10px 0; }
                .flipped-poll-classic .button { background: #0073aa; color: #fff; border: none; padding: 8px 16px; border-radius: 3px; }
                .flipped-poll-classic .button:hover { background: #005177; }
            '
        ],
        'modern' => [
            'name' => esc_html__('Modern', 'flipped-polling'),
            'css' => '
                .flipped-poll-modern { max-width: 600px; margin: 20px auto; padding: 25px; border: none; border-radius: 10px; background: #f5f5f5; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .flipped-poll-modern h3 { color: #2c3e50; font-size: 26px; margin-bottom: 20px; }
                .flipped-poll-modern p { margin: 12px 0; font-size: 16px; }
                .flipped-poll-modern .button { background: #3498db; color: #fff; border: none; padding: 10px 20px; border-radius: 25px; }
                .flipped-poll-modern .button:hover { background: #2980b9; }
            '
        ],
        'bold' => [
            'name' => esc_html__('Bold', 'flipped-polling'),
            'css' => '
                .flipped-poll-bold { max-width: 550px; margin: 20px auto; padding: 20px; border: 3px solid #e74c3c; border-radius: 0; background: #fff; }
                .flipped-poll-bold h3 { color: #e74c3c; font-size: 28px; margin-bottom: 15px; text-transform: uppercase; }
                .flipped-poll-bold p { margin: 10px 0; font-weight: bold; }
                .flipped-poll-bold .button { background: #e74c3c; color: #fff; border: none; padding: 10px 20px; border-radius: 0; }
                .flipped-poll-bold .button:hover { background: #c0392b; }
            '
        ],
        'minimal' => [
            'name' => esc_html__('Minimal', 'flipped-polling'),
            'css' => '
                .flipped-poll-minimal { max-width: 450px; margin: 20px auto; padding: 15px; border: none; background: transparent; }
                .flipped-poll-minimal h3 { color: #555; font-size: 22px; margin-bottom: 10px; }
                .flipped-poll-minimal p { margin: 8px 0; }
                .flipped-poll-minimal .button { background: none; color: #555; border: 1px solid #555; padding: 6px 12px; border-radius: 0; }
                .flipped-poll-minimal .button:hover { background: #555; color: #fff; }
            '
        ],
        'dark' => [
            'name' => esc_html__('Dark', 'flipped-polling'),
            'css' => '
                .flipped-poll-dark { max-width: 500px; margin: 20px auto; padding: 20px; border: 1px solid #444; border-radius: 5px; background: #333; color: #fff; }
                .flipped-poll-dark h3 { color: #fff; font-size: 24px; margin-bottom: 15px; }
                .flipped-poll-dark p { margin: 10px 0; }
                .flipped-poll-dark .button { background: #555; color: #fff; border: none; padding: 8px 16px; border-radius: 3px; }
                .flipped-poll-dark .button:hover { background: #777; }
            '
        ]
    ];
}
