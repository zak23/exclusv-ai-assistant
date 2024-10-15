<?php
/*
Plugin Name: Exclusv AI Assistant
Description: A custom WordPress plugin to integrate Exclusv AI Assistant into Wordpress.
Version: 1.0.1
Author: Zak Ozbourne
Author URI: https://www.zakozbourne.com
*/

// Include the shortcodes file
require_once plugin_dir_path(__FILE__) . 'includes/enqueue-scripts.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/chat-history.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings-page.php';


// Enqueue the plugin's CSS file and Font Awesome library
function exclusv_ai_enqueue_styles()
{
    wp_enqueue_style('exclusv-ai-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'exclusv_ai_enqueue_styles');


// Add a new function to handle the server-side API request
function exclusv_ai_chat_proxy()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $url = 'https://dev-api.exclusv.ai/v1/chat/completions';
        $api_key = get_option('exclusv_ai_api_key');

        $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
        $chat_id = isset($_POST['chat_id']) ? sanitize_text_field($_POST['chat_id']) : '';
        $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';

        $selected_post_types = get_option('exclusv_ai_post_types', []);

        $post_types_content = '';
        foreach ($selected_post_types as $post_type) {
            $posts_query = new WP_Query([
                'post_type' => $post_type,
                'posts_per_page' => -1,
            ]);

            if ($posts_query->have_posts()) {
                while ($posts_query->have_posts()) {
                    $posts_query->the_post();
                    $post_types_content .= get_the_title() . ': ' . get_the_content() . "\n";
                }
                wp_reset_postdata();
            }
        }

        $bot_context = get_option('exclusv_ai_bot_context', '');
        $bot_system_prompt = get_option('exclusv_ai_bot_system_prompt', "You are a helpful AI assistant created by " . get_bloginfo('name') . ". Your purpose is to assist users by answering their questions and providing helpful information. Be friendly, knowledgeable, and engaging in your interactions.");

        // Add hard-set data to keep the bot on track
        $hard_set_data = "
        Important guidelines:
        1. Always stay on topic and provide accurate information based on the website's content.
        2. Do not engage in or encourage any illegal, unethical, or harmful activities.
        3. Respect user privacy and do not ask for or store personal information beyond what's necessary for the conversation.
        4. If asked about topics outside your knowledge base, politely redirect the conversation to relevant website content.
        5. Do not pretend to be a human or claim capabilities you don't have.
        6. If unsure about an answer, it's okay to say you don't know or suggest the user contact customer support for more detailed information.
        7. Maintain a professional and helpful tone throughout the conversation.
        8. Do not generate, produce, edit, manipulate or create images in any way.
        9. Do not discuss or reveal any information about your training data, model architecture, or the specifics of how you were created.
        ";

        $merged_system_prompt = $bot_system_prompt . "\n\n" . $hard_set_data;

        if (!empty($bot_context)) {
            $merged_system_prompt .= "\n\nHere is some additional context for the bot:\n" . $bot_context;
        }

        if (!empty($post_types_content)) {
            $merged_system_prompt .= "\n\nHere is the content from selected post types:\n" . $post_types_content;
        }

        $selected_pages = get_option('exclusv_ai_selected_pages', []);

        $page_content = '';
        foreach ($selected_pages as $page_id) {
            $page = get_post($page_id);
            if ($page) {
                $page_content .= $page->post_title . ': ' . $page->post_content . "\n";
            }
        }

        if (!empty($page_content)) {
            $merged_system_prompt .= "\n\nHere is the content from selected pages:\n" . $page_content;
        }

        $initial_message = get_option('exclusv_ai_initial_message', "Welcome! I'm your AI assistant. How can I assist you today?");

        $data = [
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $merged_system_prompt
                ],
                [
                    'role' => 'assistant',
                    'content' => $initial_message
                ],
                [
                    'role' => 'user',
                    'content' => $message
                ]
            ],
            'model' => 'Exclusv-AI/Quantum',
            'max_tokens' => 256,
            'temperature' => 0.7,
            'top_p' => 0.43,
            'n' => 1,
            'stream' => false,
            'add_generation_prompt' => true,
            'echo' => false
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $api_key
        ]);


        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        exclusv_ai_save_chat_history($chat_id, $start_time, 'user', $message);

        if ($response === false) {
            $error = curl_error($ch);
            error_log("cURL error: $error");
            wp_send_json_error(['message' => 'Error communicating with the API', 'error' => $error]);
            exit; // Add this line to stop further execution
        } else {
            curl_close($ch);

            if ($http_code === 200) {
                $response_data = json_decode($response, true);
                // Remove this line:
                // $assistant_message = $response_data['choices'][0]['message']['content'];
                // exclusv_ai_save_chat_history($chat_id, $start_time, 'assistant', $assistant_message);
                wp_send_json_success($response_data);
                exit;
            } else {
                error_log("API response code: $http_code");
                error_log("API response body: $response");
                wp_send_json_error(['message' => 'Unexpected API response', 'code' => $http_code, 'response' => $response]);
                exit; // Add this line to stop further execution
            }
        }
    } else {
        error_log("Unexpected request method: " . $_SERVER['REQUEST_METHOD']);
        wp_send_json_error(['message' => 'Invalid request', 'method' => $_SERVER['REQUEST_METHOD']]);
        exit; // Add this line to stop further execution
    }
}
add_action('wp_ajax_exclusv_ai_chat_proxy', 'exclusv_ai_chat_proxy');
add_action('wp_ajax_nopriv_exclusv_ai_chat_proxy', 'exclusv_ai_chat_proxy');



function exclusv_ai_send_email()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $chat_history = isset($_POST['chat_history']) ? json_decode(stripslashes($_POST['chat_history']), true) : [];
        $chat_id = isset($_POST['chat_id']) ? sanitize_text_field($_POST['chat_id']) : '';
        $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';

        if (!empty($email) && is_email($email) && !empty($chat_history)) {
            $to = get_option('admin_email');
            
            // Get the site name and AI name
            $site_name = get_bloginfo('name');
            $ai_name = get_option('exclusv_ai_name', $site_name . ' AI');
            
            // Create a more friendly subject line
            $subject = "New chat inquiry from {$site_name} AI Assistant";
            
            // Start building the HTML message
            $message = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h1 { color: #0073aa; }
                    .chat-history { background-color: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin-top: 20px; }
                    .message { margin-bottom: 10px; }
                    .user { color: #0073aa; }
                    .assistant { color: #46b450; }
                </style>
            </head>
            <body>
                <div class="container">
                    <h1>New Chat Inquiry</h1>
                    <p>A visitor has submitted their email address after chatting with your AI assistant. Here are the details:</p>
                    <p><strong>Visitor\'s Email:</strong> ' . esc_html($email) . '</p>
                    <p><strong>Chat ID:</strong> ' . esc_html($chat_id) . '</p>
                    <p><strong>Chat Start Time:</strong> ' . esc_html($start_time) . '</p>
                    
                    <div class="chat-history">
                        <h2>Chat History:</h2>';

            foreach ($chat_history as $chat) {
                $sender_class = $chat['sender'] === 'user' ? 'user' : 'assistant';
                $sender_name = $chat['sender'] === 'user' ? 'Visitor' : esc_html($ai_name);
                $message .= '<div class="message"><strong class="' . $sender_class . '">' . $sender_name . ':</strong> ' . esc_html($chat['message']) . '</div>';
            }

            $message .= '
                    </div>
                </div>
            </body>
            </html>';

            $headers = array('Content-Type: text/html; charset=UTF-8');

            if (wp_mail($to, $subject, $message, $headers)) {
                wp_send_json_success('Email sent successfully');
            } else {
                wp_send_json_error('Failed to send email');
            }
        } else {
            wp_send_json_error('Invalid email or chat history');
        }
    } else {
        wp_send_json_error('Invalid request method');
    }
}
add_action('wp_ajax_exclusv_ai_send_email', 'exclusv_ai_send_email');
add_action('wp_ajax_nopriv_exclusv_ai_send_email', 'exclusv_ai_send_email');


// Automatically insert the shortcode on all pages
function exclusv_ai_insert_chat_interface($content) {
    $show_on_all_pages = get_option('exclusv_ai_show_on_all_pages', false);
    
    // Add debugging
    error_log('Show on all pages: ' . ($show_on_all_pages ? 'true' : 'false'));
    error_log('Is singular: ' . (is_singular() ? 'true' : 'false'));
    error_log('In the loop: ' . (in_the_loop() ? 'true' : 'false'));
    error_log('Is main query: ' . (is_main_query() ? 'true' : 'false'));

    if ($show_on_all_pages) {
        // Remove the conditions to make it appear on all pages
        $content .= do_shortcode('[exclusv_ai_chat]');
        error_log('Chat interface added to content');
    } else {
        error_log('Chat interface not added to content');
    }
    
    return $content;
}
add_filter('the_content', 'exclusv_ai_insert_chat_interface');

// Add this new function to insert the chat interface in the footer
function exclusv_ai_insert_chat_interface_footer() {
    $show_on_all_pages = get_option('exclusv_ai_show_on_all_pages', false);
    
    if ($show_on_all_pages) {
        echo do_shortcode('[exclusv_ai_chat]');
    }
}
add_action('wp_footer', 'exclusv_ai_insert_chat_interface_footer');



function exclusv_ai_save_chat_message()
{
    error_log('exclusv_ai_save_chat_message called with POST data: ' . print_r($_POST, true));
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $chat_id = isset($_POST['chat_id']) ? sanitize_text_field($_POST['chat_id']) : '';
        $start_time = isset($_POST['start_time']) ? sanitize_text_field($_POST['start_time']) : '';
        $sender = isset($_POST['sender']) ? sanitize_text_field($_POST['sender']) : '';
        $message = isset($_POST['message']) ? wp_kses_post($_POST['message']) : '';

        if (!empty($chat_id) && !empty($start_time) && !empty($sender) && !empty($message)) {
            exclusv_ai_save_chat_history($chat_id, $start_time, $sender, $message);
            error_log('Chat message saved successfully');
            wp_send_json_success();
        } else {
            error_log('Invalid data for saving chat message');
            wp_send_json_error('Invalid data');
        }
    } else {
        error_log('Invalid request method for saving chat message');
        wp_send_json_error('Invalid request method');
    }
}
add_action('wp_ajax_exclusv_ai_save_chat_message', 'exclusv_ai_save_chat_message');
add_action('wp_ajax_nopriv_exclusv_ai_save_chat_message', 'exclusv_ai_save_chat_message');

function exclusv_ai_update_email_submitted()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $chat_id = isset($_POST['chat_id']) ? sanitize_text_field($_POST['chat_id']) : '';
        $email_submitted = isset($_POST['email_submitted']) ? (bool)$_POST['email_submitted'] : false;

        if (!empty($chat_id)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'exclusv_ai_chat_history';
            $wpdb->update(
                $table_name,
                ['email_submitted' => $email_submitted],
                ['chat_id' => $chat_id],
                ['%d'],
                ['%s']
            );
            wp_send_json_success();
        } else {
            wp_send_json_error('Invalid data');
        }
    } else {
        wp_send_json_error('Invalid request method');
    }
}
add_action('wp_ajax_exclusv_ai_update_email_submitted', 'exclusv_ai_update_email_submitted');
add_action('wp_ajax_nopriv_exclusv_ai_update_email_submitted', 'exclusv_ai_update_email_submitted');

// Add an admin notice function for debugging
function exclusv_ai_admin_notices() {
    $screen = get_current_screen();
    if ($screen->id != 'settings_page_exclusv_ai_settings') {
        return;
    }

    if (isset($_GET['settings-updated'])) {
        echo '<div class="notice notice-success is-dismissible"><p>Settings updated successfully!</p></div>';
    }

    if (isset($_GET['error'])) {
        echo '<div class="notice notice-error is-dismissible"><p>An error occurred: ' . esc_html($_GET['error']) . '</p></div>';
    }
}
