<?php

// Add a settings page for the plugin
function exclusv_ai_settings_page()
{
    // Add error checking
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
?>
    <div class="wrap">
        <h1>Exclusv AI Assistant Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('exclusv_ai_settings');
            do_settings_sections('exclusv_ai_settings');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// Register the settings page
function exclusv_ai_register_settings()
{
    // Add error checking
    add_action('admin_notices', 'exclusv_ai_admin_notices');

    add_options_page(
        'Exclusv AI Assistant Settings',
        'Exclusv AI Assistant',
        'manage_options',
        'exclusv_ai_settings',
        'exclusv_ai_settings_page'
    );

    add_settings_section(
        'exclusv_ai_api_settings_section',
        'API Settings',
        function () {
            echo '<p>Configure the API settings for Exclusv AI Assistant.</p>';
        },
        'exclusv_ai_settings'
    );

    add_settings_field(
        'exclusv_ai_api_key',
        'API Key',
        'exclusv_ai_api_key_field',
        'exclusv_ai_settings',
        'exclusv_ai_api_settings_section',
        ['label_for' => 'exclusv_ai_api_key']
    );

    add_settings_section(
        'exclusv_ai_chat_settings_section',
        'Chat Settings',
        function () {
            echo '<p>Customize the chat interface and behavior.</p>';
        },
        'exclusv_ai_settings'
    );

    add_settings_field(
        'exclusv_ai_chat_title',
        'Chat Title',
        'exclusv_ai_chat_title_field',
        'exclusv_ai_settings',
        'exclusv_ai_chat_settings_section',
        ['label_for' => 'exclusv_ai_chat_title']
    );

    add_settings_field(
        'exclusv_ai_initial_message',
        'Initial Message',
        'exclusv_ai_initial_message_field',
        'exclusv_ai_settings',
        'exclusv_ai_chat_settings_section',
        ['label_for' => 'exclusv_ai_initial_message']
    );

    add_settings_field(
        'exclusv_ai_name',
        'AI Name',
        'exclusv_ai_name_field',
        'exclusv_ai_settings',
        'exclusv_ai_chat_settings_section',
        ['label_for' => 'exclusv_ai_name']
    );

    add_settings_field(
        'exclusv_ai_message_limit',
        'Message Limit',
        'exclusv_ai_message_limit_field',
        'exclusv_ai_settings',
        'exclusv_ai_chat_settings_section',
        ['label_for' => 'exclusv_ai_message_limit']
    );

    add_settings_field(
        'exclusv_ai_email_prompt_message',
        'Email Prompt Message',
        'exclusv_ai_email_prompt_message_field',
        'exclusv_ai_settings',
        'exclusv_ai_chat_settings_section',
        ['label_for' => 'exclusv_ai_email_prompt_message']
    );

    add_settings_field(
        'exclusv_ai_show_on_all_pages',
        'Show on All Pages',
        'exclusv_ai_show_on_all_pages_field',
        'exclusv_ai_settings',
        'exclusv_ai_chat_settings_section',
        ['label_for' => 'exclusv_ai_show_on_all_pages']
    );

    add_settings_section(
        'exclusv_ai_bot_settings_section',
        'Bot Settings',
        function () {
            echo '<p>Configure the bot\'s behavior and context.</p>';
        },
        'exclusv_ai_settings'
    );

    add_settings_field(
        'exclusv_ai_bot_system_prompt',
        'Bot System Prompt',
        'exclusv_ai_bot_system_prompt_field',
        'exclusv_ai_settings',
        'exclusv_ai_bot_settings_section',
        ['label_for' => 'exclusv_ai_bot_system_prompt']
    );

    add_settings_field(
        'exclusv_ai_bot_context',
        'Bot Context',
        'exclusv_ai_bot_context_field',
        'exclusv_ai_settings',
        'exclusv_ai_bot_settings_section',
        ['label_for' => 'exclusv_ai_bot_context']
    );

    add_settings_field(
        'exclusv_ai_post_types',
        'Post Types',
        'exclusv_ai_post_types_field',
        'exclusv_ai_settings',
        'exclusv_ai_bot_settings_section',
        ['label_for' => 'exclusv_ai_post_types']
    );

    add_settings_field(
        'exclusv_ai_selected_pages',
        'Selected Pages',
        'exclusv_ai_page_selection_field',
        'exclusv_ai_settings',
        'exclusv_ai_bot_settings_section',
        ['label_for' => 'exclusv_ai_selected_pages']
    );

    add_settings_section(
        'exclusv_ai_appearance_settings_section',
        'Appearance Settings',
        function () {
            echo '<p>Customize the appearance of the chat interface.</p>';
        },
        'exclusv_ai_settings'
    );

    add_settings_field(
        'exclusv_ai_header_color',
        'Chat Header Color',
        'exclusv_ai_header_color_field',
        'exclusv_ai_settings',
        'exclusv_ai_appearance_settings_section',
        ['label_for' => 'exclusv_ai_header_color']
    );

    add_settings_field(
        'exclusv_ai_send_button_color',
        'Send Button Color',
        'exclusv_ai_send_button_color_field',
        'exclusv_ai_settings',
        'exclusv_ai_appearance_settings_section',
        ['label_for' => 'exclusv_ai_send_button_color']
    );

    register_setting('exclusv_ai_settings', 'exclusv_ai_api_key');
    register_setting('exclusv_ai_settings', 'exclusv_ai_chat_title');
    register_setting('exclusv_ai_settings', 'exclusv_ai_initial_message');
    register_setting('exclusv_ai_settings', 'exclusv_ai_name');
    register_setting('exclusv_ai_settings', 'exclusv_ai_bot_system_prompt');
    register_setting('exclusv_ai_settings', 'exclusv_ai_bot_context');
    register_setting('exclusv_ai_settings', 'exclusv_ai_post_types');
    register_setting('exclusv_ai_settings', 'exclusv_ai_message_limit');
    register_setting('exclusv_ai_settings', 'exclusv_ai_email_prompt_message');
    register_setting('exclusv_ai_settings', 'exclusv_ai_selected_pages');
    register_setting('exclusv_ai_settings', 'exclusv_ai_show_on_all_pages');
    register_setting('exclusv_ai_settings', 'exclusv_ai_header_color');
    register_setting('exclusv_ai_settings', 'exclusv_ai_send_button_color');
}
add_action('admin_menu', 'exclusv_ai_register_settings');

// Render the API key field
function exclusv_ai_api_key_field()
{
    $api_key = get_option('exclusv_ai_api_key');
    echo '<input type="password" name="exclusv_ai_api_key" value="' . esc_attr($api_key) . '" size="40">';
    echo '<p class="description">Enter your Exclusv AI API key.</p>';
}


// Render the chat title field
function exclusv_ai_chat_title_field()
{
    $site_name = get_bloginfo('name');
    $chat_title = get_option('exclusv_ai_chat_title');

    if (empty($chat_title)) {
        $chat_title = $site_name . ' AI Chat';
        update_option('exclusv_ai_chat_title', sanitize_text_field($chat_title));
    }

    echo '<input type="text" name="exclusv_ai_chat_title" value="' . esc_attr($chat_title) . '" size="40">';
    echo '<p class="description">Set the title of the chat interface.</p>';
}


// Render the initial message field
function exclusv_ai_initial_message_field()
{
    $site_name = get_bloginfo('name');
    $default_initial_message = "Welcome to " . $site_name . "! I'm your AI assistant. How can I assist you today?";
    $initial_message = get_option('exclusv_ai_initial_message');

    if (empty($initial_message)) {
        $initial_message = $default_initial_message;
        update_option('exclusv_ai_initial_message', $initial_message);
    }

    echo '<textarea name="exclusv_ai_initial_message" rows="4" cols="50">' . esc_textarea($initial_message) . '</textarea>';
    echo '<p class="description">Set the initial message displayed by the AI assistant.</p>';
}


// Render the AI name field
function exclusv_ai_name_field()
{
    $site_name = get_bloginfo('name');
    $ai_name = get_option('exclusv_ai_name');

    if (empty($ai_name)) {
        $ai_name = $site_name . ' AI';
        update_option('exclusv_ai_name', $ai_name);
    }

    echo '<input type="text" name="exclusv_ai_name" value="' . esc_attr($ai_name) . '" size="40">';
    echo '<p class="description">Set the name of the AI assistant.</p>';
}


// Render the bot context field
function exclusv_ai_bot_context_field()
{
    $bot_context = get_option('exclusv_ai_bot_context', '');
    echo '<textarea name="exclusv_ai_bot_context" rows="10" cols="50">' . esc_textarea($bot_context) . '</textarea>';
    echo '<p class="description">Provide additional context for the bot to use during conversations.</p>';
}

// Render the bot system prompt field
function exclusv_ai_bot_system_prompt_field()
{
    $site_name = get_bloginfo('name');
    $default_prompt = "You are a helpful AI assistant created by " . $site_name . ". Your purpose is to assist users by answering their questions and providing helpful information. Be friendly, knowledgeable, and engaging in your interactions.";
    $bot_system_prompt = get_option('exclusv_ai_bot_system_prompt');

    if (empty($bot_system_prompt)) {
        $bot_system_prompt = $default_prompt;
        update_option('exclusv_ai_bot_system_prompt', $bot_system_prompt);
    }

    echo '<textarea name="exclusv_ai_bot_system_prompt" rows="10" cols="50">' . esc_textarea($bot_system_prompt) . '</textarea>';
    echo '<p class="description">Set the system prompt that defines the bot\'s behavior and personality.</p>';
}


// Render the post types field
function exclusv_ai_post_types_field()
{
    $selected_post_types = get_option('exclusv_ai_post_types', []);

    // Ensure $selected_post_types is an array
    if (!is_array($selected_post_types)) {
        $selected_post_types = [];
    }

    $post_types = get_post_types(['public' => true], 'objects');

    foreach ($post_types as $post_type) {
        $checked = in_array($post_type->name, $selected_post_types) ? 'checked' : '';
        echo '<label><input type="checkbox" name="exclusv_ai_post_types[]" value="' . esc_attr($post_type->name) . '" ' . $checked . '> ' . esc_html($post_type->label) . '</label><br>';
    }
    echo '<p class="description">Select the post types to include in the bot\'s knowledge base.</p>';
}

// Render the message limit field
function exclusv_ai_message_limit_field()
{
    $message_limit = get_option('exclusv_ai_message_limit', 10);
    echo '<input type="number" name="exclusv_ai_message_limit" value="' . esc_attr($message_limit) . '" min="1" size="40">';
    echo '<p class="description">Set the number of messages the bot can send before prompting for an email.</p>';
}

// Render the email prompt message field
function exclusv_ai_email_prompt_message_field()
{
    $site_name = get_bloginfo('name');
    $default_message = "Enter your email to have a representative from " . $site_name . " contact you";
    $email_prompt_message = get_option('exclusv_ai_email_prompt_message');

    if (empty($email_prompt_message)) {
        $email_prompt_message = $default_message;
        update_option('exclusv_ai_email_prompt_message', $email_prompt_message);
    }

    echo '<textarea name="exclusv_ai_email_prompt_message" rows="4" cols="50">' . esc_textarea($email_prompt_message) . '</textarea>';
    echo '<p class="description">Set the message displayed when prompting for an email.</p>';
}

// Render the page selection field
function exclusv_ai_page_selection_field()
{
    $selected_pages = get_option('exclusv_ai_selected_pages', []);

    // Ensure $selected_pages is an array
    if (!is_array($selected_pages)) {
        $selected_pages = [];
    }
?>
    <select id="exclusv-ai-page-selection" name="exclusv_ai_selected_pages[]" multiple="multiple" style="width: 100%; max-width: 400px;">
        <?php
        $pages = get_pages();
        foreach ($pages as $page) {
            $selected = in_array($page->ID, $selected_pages) ? 'selected' : '';
            echo '<option value="' . esc_attr($page->ID) . '" ' . $selected . '>' . esc_html($page->post_title) . '</option>';
        }
        ?>
    </select>
    <p class="description">Select the pages to include in the bot's knowledge base.</p>
    <script>
        jQuery(document).ready(function($) {
            $('#exclusv-ai-page-selection').select2({
                placeholder: 'Search and select pages...',
                allowClear: true
            });
        });
    </script>
<?php
}

// Render the checkbox
function exclusv_ai_show_on_all_pages_field() {
    $show_on_all_pages = get_option('exclusv_ai_show_on_all_pages', false);
    echo '<input type="checkbox" id="exclusv_ai_show_on_all_pages" name="exclusv_ai_show_on_all_pages" value="1" ' . checked(1, $show_on_all_pages, false) . '>';
    echo '<label for="exclusv_ai_show_on_all_pages">Automatically add the chat interface to all pages</label>';
    echo '<p class="description">When checked, the chat interface will be automatically added to the bottom of all pages.</p>';
    
    // Add the shortcode usage readme
    echo '<div class="exclusv-ai-shortcode-readme">';
    echo '<h4>Shortcode Usage</h4>';
    echo '<p>If you prefer to add the chat interface manually, you can use the following shortcode:</p>';
    echo '<code>[exclusv_ai_chat]</code>';
    echo '<p>Simply add this shortcode to any page or post where you want the chat interface to appear.</p>';
    echo '</div>';
}

function exclusv_ai_header_color_field() {
    $header_color = get_option('exclusv_ai_header_color', '#33b89f');
    echo '<input type="color" id="exclusv_ai_header_color" name="exclusv_ai_header_color" value="' . esc_attr($header_color) . '">';
    echo '<p class="description">Choose the color for the chat header bar.</p>';
}

function exclusv_ai_send_button_color_field() {
    $send_button_color = get_option('exclusv_ai_send_button_color', '#33b89f');
    echo '<input type="color" id="exclusv_ai_send_button_color" name="exclusv_ai_send_button_color" value="' . esc_attr($send_button_color) . '">';
    echo '<p class="description">Choose the color for the send button.</p>';
}
