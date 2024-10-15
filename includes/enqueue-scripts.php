<?php


// Enqueue Select2 library
function exclusv_ai_enqueue_select2()
{
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
}
add_action('admin_enqueue_scripts', 'exclusv_ai_enqueue_select2');


// Enqueue the main chat script
function exclusv_ai_enqueue_scripts() {
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);

    // Enqueue the main chat script
    wp_enqueue_script('exclusv-ai-chat-js', plugin_dir_url(dirname(__FILE__)) . 'js/exclusv-ai-chat.js', ['jquery'], '1.0.0', true);

    // Localize the script
    wp_localize_script('exclusv-ai-chat-js', 'exclusvAiSettings', array(
        'messageLimit' => intval(get_option('exclusv_ai_message_limit', 10)),
        'emailPromptMessage' => get_option('exclusv_ai_email_prompt_message', "Enter your email to continue talking to " . get_bloginfo('name')),
    ));
}
add_action('wp_enqueue_scripts', 'exclusv_ai_enqueue_scripts');
