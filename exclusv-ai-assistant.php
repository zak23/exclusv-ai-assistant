<?php
/*
Plugin Name: Exclusv AI Assistant
Description: A custom WordPress plugin to integrate Exclusv AI Assistant into Wordpress.
Version: 1.0
Author: Zak Ozbourne
Author URI: https://www.zakozbourne.com
*/


// Add shortcode for displaying the AI chat interface
function exclusv_ai_chat_shortcode()
{
    ob_start();
    $site_name = get_bloginfo('name');
?>
    <div id="exclusv-ai-chat" class="card">
        <div id="chat-header" class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><?php echo esc_html(get_option('exclusv_ai_chat_title', $site_name . ' AI Chat')); ?></h5>
            <button id="close-button" class="btn btn-sm btn-outline-light"><i class="fas fa-times"></i></button>
        </div>
        <div id="chat-messages" class="card-body"></div>
        <div id="typing-indicator" class="card-footer text-muted" style="display: none;">
            <span><?php echo esc_html($site_name); ?> is Typing</span>
            <span class="typing-dots">
                <span class="dot dot-1">.</span>
                <span class="dot dot-2">.</span>
                <span class="dot dot-3">.</span>
            </span>
        </div>
        <div id="email-capture" class="card-footer" style="display: none;">
            <h6><?php echo esc_html(get_option('exclusv_ai_email_prompt_message', "Enter your email to continue talking to " . $site_name)); ?></h6>
            <div class="input-group">
                <input type="email" id="email-input" class="form-control" placeholder="Your email address" required>
                <button id="submit-email" class="btn btn-primary">Submit</button>
            </div>
            <div id="email-error" style="color: red; margin-top: 5px;"></div>
        </div>
        <div id="chat-input" class="card-footer">
            <div class="input-group">
                <input type="text" id="user-input" class="form-control" placeholder="Type your message...">
                <button id="send-button" class="btn btn-primary">Send</button>
            </div>
        </div>
    </div>
    <div id="chat-button" class="btn btn-primary rounded-circle">
        <i class="fas fa-comment-alt"></i>
    </div>

    <script>
        // JavaScript code for handling chat interactions
        var chatContainer = document.getElementById('exclusv-ai-chat');
        var chatButton = document.getElementById('chat-button');
        var userInput = document.getElementById('user-input');
        var sendButton = document.getElementById('send-button');
        var closeButton = document.getElementById('close-button');

        var emailCapture = document.getElementById('email-capture');
        var emailInput = document.getElementById('email-input');
        var submitEmailButton = document.getElementById('submit-email');
        var chatInput = document.getElementById('chat-input'); // Add this line

        var messageCount = localStorage.getItem('messageCount') || 0;

        chatButton.addEventListener('click', toggleChatVisibility);
        sendButton.addEventListener('click', sendMessage);
        userInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        });
        closeButton.addEventListener('click', toggleChatVisibility);
        submitEmailButton.addEventListener('click', function(event) {
            submitEmail(event);
        });

        // Find the existing event listeners section and add this new listener
        emailInput.addEventListener('keydown', function(event) {
            if (event.key === 'Enter') {
                submitEmail();
            }
        });

        function toggleChatVisibility() {
            if (chatContainer.classList.contains('open')) {
                chatContainer.classList.remove('open');
                localStorage.setItem('chatClosed', 'true');
            } else {
                chatContainer.classList.add('open');
                localStorage.setItem('chatClosed', 'false');

                // Clear the chat messages container before appending the chat history
                var chatMessages = document.getElementById('chat-messages');
                chatMessages.innerHTML = '';

                // Retrieve and display the chat history from local storage
                var chatHistory = localStorage.getItem('chatHistory');
                if (chatHistory) {
                    chatHistory = JSON.parse(chatHistory);
                    chatHistory.forEach(function(message) {
                        displayMessage(message.message, message.sender);
                    });
                }
            }
        }

        function sendMessage() {
            var message = userInput.value.trim();
            if (message !== '') {
                displayMessage(message, 'user');
                sendMessageToServer(message);
                userInput.value = '';

                messageCount++;
                localStorage.setItem('messageCount', messageCount);

                // Retrieve the message limit from the plugin settings
                var messageLimit = <?php echo esc_js(get_option('exclusv_ai_message_limit', 10)); ?>;

                if (messageCount >= messageLimit) {
                    chatInput.style.display = 'none';
                    emailCapture.style.display = 'block';
                }

                // Store the user's message in the chat history
                var chatHistory = localStorage.getItem('chatHistory');
                if (chatHistory) {
                    chatHistory = JSON.parse(chatHistory);
                } else {
                    chatHistory = [];
                }
                chatHistory.push({
                    sender: 'user',
                    message: message
                });
                localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
            }
        }

        // Update the submitEmail function to prevent form submission if it exists
        function submitEmail(event) {
            if (event) {
                event.preventDefault();
            }
            var email = emailInput.value.trim();
            if (email !== '' && isValidEmail(email)) {
                var chatHistory = localStorage.getItem('chatHistory');
                if (chatHistory) {
                    chatHistory = JSON.parse(chatHistory);
                    sendEmailToAdmin(email, chatHistory);

                    // Update the email submitted flag for all messages in the chat
                    var chatId = localStorage.getItem('chatId');
                    var startTime = localStorage.getItem('startTime');
                    updateEmailSubmitted(chatId, true);

                    // Store the email submission status in local storage
                    localStorage.setItem('emailSubmitted', 'true');
                }
                showThankYouMessage();
            } else {
                // Show an error message if the email is invalid
                showEmailError('Please enter a valid email address.');
            }
        }

        // Add this new function to show email error messages
        function showEmailError(message) {
            var errorElement = document.getElementById('email-error');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.id = 'email-error';
                errorElement.style.color = 'red';
                errorElement.style.marginTop = '5px';
                document.getElementById('email-capture').appendChild(errorElement);
            }
            errorElement.textContent = message;
        }

        function showThankYouMessage() {
            emailCapture.style.display = 'none';
            chatInput.style.display = 'none';

            // Display a message indicating that the chat is locked
            var chatMessages = document.getElementById('chat-messages');
            var messageElement = document.createElement('div');
            messageElement.classList.add('message', 'system');
            messageElement.innerHTML = 'Thank you for providing your email. A member of our team will be with you shortly.';
            chatMessages.appendChild(messageElement);

            // Disable the send button and user input
            sendButton.disabled = true;
            userInput.disabled = true;
        }

        function updateEmailSubmitted(chatId, emailSubmitted) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            var data = 'action=exclusv_ai_update_email_submitted' +
                '&chat_id=' + encodeURIComponent(chatId) +
                '&email_submitted=' + (emailSubmitted ? '1' : '0');
            xhr.send(data);
        }

        function sendMessageToServer(message) {
            console.log('sendMessageToServer called with message:', message);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        var assistantMessage = response.data.choices[0].message.content;
                        displayMessage(assistantMessage, 'assistant');

                        // Store the assistant's message in the chat history
                        var chatHistory = localStorage.getItem('chatHistory');
                        if (chatHistory) {
                            chatHistory = JSON.parse(chatHistory);
                        } else {
                            chatHistory = [];
                        }
                        chatHistory.push({
                            sender: 'assistant',
                            message: assistantMessage
                        });
                        localStorage.setItem('chatHistory', JSON.stringify(chatHistory));

                        // Save the assistant's message to the database
                        var chatId = localStorage.getItem('chatId');
                        var startTime = localStorage.getItem('startTime');
                        saveChatMessage(chatId, startTime, 'assistant', assistantMessage);

                        hideTypingIndicator();
                    } else {
                        console.error('Error:', response.data);
                        displayMessage('Error: ' + response.data, 'error');
                        hideTypingIndicator();
                    }
                } else {
                    var errorResponse = xhr.responseText;
                    console.error('Request failed. Status:', xhr.status);
                    console.error('Error details:', errorResponse);
                    displayMessage('Request failed. Please check the console for more details.', 'error');
                    hideTypingIndicator();
                }
            };
            var chatId = localStorage.getItem('chatId');
            var startTime = localStorage.getItem('startTime');
            var data = 'action=exclusv_ai_chat_proxy&message=' + encodeURIComponent(message) + '&chat_id=' + encodeURIComponent(chatId) + '&start_time=' + encodeURIComponent(startTime);
            xhr.send(data);
            showTypingIndicator();

            // We're removing this line to prevent double saving of user messages
            // saveChatMessage(chatId, startTime, 'user', message);
        }

        function saveChatMessage(chatId, startTime, sender, message) {
            console.log('saveChatMessage called with:', {
                chatId,
                startTime,
                sender,
                message
            });
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            var data = 'action=exclusv_ai_save_chat_message' +
                '&chat_id=' + encodeURIComponent(chatId) +
                '&start_time=' + encodeURIComponent(startTime) +
                '&sender=' + encodeURIComponent(sender) +
                '&message=' + encodeURIComponent(message);
            xhr.send(data);
        }

        function showTypingIndicator() {
            var typingIndicator = document.getElementById('typing-indicator');
            typingIndicator.style.display = 'block';
        }

        function hideTypingIndicator() {
            var typingIndicator = document.getElementById('typing-indicator');
            typingIndicator.style.display = 'none';
        }

        function displayMessage(message, sender) {
            var chatMessages = document.getElementById('chat-messages');
            var messageElement = document.createElement('div');
            messageElement.classList.add('message', sender);

            var senderName = sender === 'user' ? 'You' : (sender === 'assistant' ? '<?php echo esc_js(get_option('exclusv_ai_name', $site_name . ' AI')); ?>' : 'Error');

            // Wrap links in <a> tags
            message = message.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');

            var messageContent = document.createElement('span');
            messageContent.innerHTML = message;

            messageElement.innerHTML = '<strong>' + senderName + ':</strong> ';
            messageElement.appendChild(messageContent);

            chatMessages.appendChild(messageElement);

            // Scroll to the bottom of the chat window
            setTimeout(function() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }, 0);
        }

        function generateUUID() {
            return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
                var r = Math.random() * 16 | 0,
                    v = c == 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        }

        function startNewChat() {
            var chatId = generateUUID();
            var startTime = new Date().toISOString();
            localStorage.setItem('chatId', chatId);
            localStorage.setItem('startTime', startTime);
            localStorage.removeItem('chatHistory');
            localStorage.removeItem('messageCount');
        }

        // Send initial message from the AI
        window.addEventListener('load', function() {
            var initialMessage = <?php echo json_encode(get_option('exclusv_ai_initial_message', "Welcome to " . $site_name . "! I'm your AI assistant. How can I assist you today?")); ?>;

            // Check if the chat was previously closed
            var chatClosed = localStorage.getItem('chatClosed');

            if (chatClosed !== 'true') {
                // Open the chat after 5 seconds if it was not previously closed
                setTimeout(function() {
                    chatContainer.classList.add('open');

                    // Check if a chat ID and start time exist in local storage
                    var chatId = localStorage.getItem('chatId');
                    var startTime = localStorage.getItem('startTime');

                    if (!chatId || !startTime) {
                        // If no chat ID or start time exists, start a new chat
                        startNewChat();
                    }

                    // Retrieve the chat history from local storage
                    var chatHistory = localStorage.getItem('chatHistory');
                    if (chatHistory) {
                        chatHistory = JSON.parse(chatHistory);
                    } else {
                        // If no chat history exists, create a new chat and store the initial message
                        chatHistory = [{
                            sender: 'assistant',
                            message: initialMessage
                        }];
                        localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
                    }

                    // Display the chat history
                    chatHistory.forEach(function(message) {
                        displayMessage(message.message, message.sender);
                    });
                }, 1000);
            }

            // Retrieve the message count from local storage
            messageCount = localStorage.getItem('messageCount') || 0;

            // Retrieve the message limit from the plugin settings
            var messageLimit = <?php echo esc_js(get_option('exclusv_ai_message_limit', 10)); ?>;

            // Check if the email has been submitted
            var emailSubmitted = localStorage.getItem('emailSubmitted');

            if (messageCount >= messageLimit && emailSubmitted !== 'true') {
                chatInput.style.display = 'none';
                emailCapture.style.display = 'block';
            } else if (emailSubmitted === 'true') {
                showThankYouMessage();
            } else {
                chatInput.style.display = 'block';
                emailCapture.style.display = 'none';
            }
        });

        // Add this function near the other JavaScript-related functions

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function sendEmailToAdmin(email, chatHistory) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        console.log('Email sent successfully');
                    } else {
                        console.error('Failed to send email:', response.data);
                        showEmailError('Failed to submit email. Please try again.');
                    }
                } else {
                    console.error('Failed to send email');
                    showEmailError('Failed to submit email. Please try again.');
                }
            };
            var data = 'action=exclusv_ai_send_email' +
                '&email=' + encodeURIComponent(email) +
                '&chat_history=' + encodeURIComponent(JSON.stringify(chatHistory)) +
                '&chat_id=' + encodeURIComponent(localStorage.getItem('chatId')) +
                '&start_time=' + encodeURIComponent(localStorage.getItem('startTime'));
            xhr.send(data);
        }
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('exclusv_ai_chat', 'exclusv_ai_chat_shortcode');

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

// Enqueue the plugin's CSS file and Font Awesome library
function exclusv_ai_enqueue_styles()
{
    wp_enqueue_style('exclusv-ai-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'exclusv_ai_enqueue_styles');

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
}

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

// Create the chat history table on plugin activation
function exclusv_ai_create_chat_history_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'exclusv_ai_chat_history';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        chat_id varchar(36) NOT NULL,
        start_time datetime NOT NULL,
        sender varchar(20) NOT NULL,
        message text NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Check if the email_submitted column exists
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'email_submitted'");

    if (!$column_exists) {
        // Add the email_submitted column if it doesn't exist
        $wpdb->query("ALTER TABLE $table_name ADD COLUMN email_submitted tinyint(1) NOT NULL DEFAULT 0");
    }

    // Check if the table was created successfully
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        error_log("Failed to create table: $table_name");
    } else {
        error_log("Successfully created table: $table_name");
    }
}
register_activation_hook(__FILE__, 'exclusv_ai_create_chat_history_table');

// Add this function to manually create the table
function exclusv_ai_manually_create_chat_history_table()
{
    exclusv_ai_create_chat_history_table();
    echo "Attempted to create the chat history table. Please check the error logs for any issues.";
    exit;
}

// Chat History Functions
function exclusv_ai_save_chat_history($chat_id, $start_time, $sender, $message, $email_submitted = false)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'exclusv_ai_chat_history';

    // Check if the table exists
    if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) != $table_name) {
        error_log("Chat history table does not exist: $table_name");
        return; // Exit the function if the table doesn't exist
    }

    $wpdb->insert(
        $table_name,
        [
            'chat_id' => sanitize_text_field($chat_id),
            'start_time' => sanitize_text_field($start_time),
            'sender' => sanitize_text_field($sender),
            'message' => sanitize_textarea_field($message),
            'email_submitted' => $email_submitted
        ],
        ['%s', '%s', '%s', '%s', '%d']
    );
}

function exclusv_ai_get_chat_history($chat_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'exclusv_ai_chat_history';
    
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE chat_id = %s ORDER BY start_time ASC", $chat_id);
    return $wpdb->get_results($query);
}

function exclusv_ai_delete_chat_history($chat_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'exclusv_ai_chat_history';
    $wpdb->delete($table_name, ['chat_id' => $chat_id], ['%s']);
}

// Add a chat history page in the WordPress admin
function exclusv_ai_chat_history_page()
{
    if (isset($_GET['delete_chat']) && isset($_GET['chat_id'])) {
        $chat_id = sanitize_text_field($_GET['chat_id']);
        exclusv_ai_delete_chat_history($chat_id);
        echo '<div class="notice notice-success"><p>Chat history deleted successfully.</p></div>';
    }
?>
    <div class="wrap">
        <h1>Exclusv AI Chat History</h1>
        <div class="chat-history-accordion">
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'exclusv_ai_chat_history';
            $query = "SELECT * FROM {$table_name} ORDER BY start_time DESC";
            $chats = $wpdb->get_results($query);

            if ($chats === null) {
                echo '<div class="chat-history-error">Error: ' . esc_html($wpdb->last_error) . '</div>';
            } else if (empty($chats)) {
                echo '<div class="chat-history-empty">No chat history found.</div>';
            } else {
                $grouped_chats = [];
                foreach ($chats as $chat) {
                    $grouped_chats[$chat->chat_id][] = $chat;
                }

                foreach ($grouped_chats as $chat_id => $chat_messages) {
                    $start_time = $chat_messages[0]->start_time;
                    $email_submitted = isset($chat_messages[0]->email_submitted) ? $chat_messages[0]->email_submitted : false;
                    $email_emoji = $email_submitted ? '📧' : '❌';
            ?>
                    <div class="chat-history-item">
                        <div class="chat-history-header">
                            <span class="chat-history-id"><?php echo esc_html($chat_id); ?></span>
                            <span class="chat-history-time"><?php echo esc_html($start_time); ?></span>
                            <span class="chat-history-email"><?php echo $email_emoji; ?></span>
                            <span class="chat-history-delete">
                                <a href="<?php echo esc_url(add_query_arg(['delete_chat' => 1, 'chat_id' => $chat_id])); ?>" class="button button-small">Delete</a>
                            </span>
                            <span class="chat-history-toggle"></span>
                        </div>
                        <div class="chat-history-content">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Sender</th>
                                        <th>Message</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($chat_messages as $message) {
                                    ?>
                                        <tr>
                                            <td><?php echo esc_html($message->sender); ?></td>
                                            <td><?php echo esc_html($message->message); ?></td>
                                        </tr>
                                    <?php
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
            <?php
                }
            }
            ?>
        </div>
    </div>
    <style>
        .chat-history-accordion {
            margin-top: 20px;
        }

        .chat-history-item {
            border: 1px solid #ccc;
            margin-bottom: 10px;
        }

        .chat-history-header {
            background-color: #f1f1f1;
            padding: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
        }

        .chat-history-id {
            font-weight: bold;
            margin-right: 10px;
        }

        .chat-history-time {
            color: #666;
            margin-right: 10px;
        }

        .chat-history-toggle {
            width: 0;
            height: 0;
            border-top: 5px solid transparent;
            border-bottom: 5px solid transparent;
            border-left: 5px solid #666;
            margin-left: auto;
            transition: transform 0.3s;
        }

        .chat-history-content {
            display: none;
            padding: 10px;
        }

        .chat-history-item.open .chat-history-toggle {
            transform: rotate(90deg);
        }

        .chat-history-item.open .chat-history-content {
            display: block;
        }

        .chat-history-email {
            margin-right: 10px;
        }

        .chat-history-delete {
            margin-right: 10px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var chatHistoryItems = document.querySelectorAll('.chat-history-item');
            chatHistoryItems.forEach(function(item) {
                var header = item.querySelector('.chat-history-header');
                header.addEventListener('click', function() {
                    item.classList.toggle('open');
                });
            });
        });
    </script>
<?php
}


// Register the chat history page
function exclusv_ai_register_chat_history_page()
{
    add_submenu_page(
        'tools.php',
        'Exclusv AI Chat History',
        'Exclusv AI Chat History',
        'manage_options',
        'exclusv_ai_chat_history',
        'exclusv_ai_chat_history_page'
    );
}
add_action('admin_menu', 'exclusv_ai_register_chat_history_page');

add_action('admin_init', function () {
    if (isset($_GET['create_exclusv_ai_table'])) {
        exclusv_ai_create_chat_history_table();
        echo "Attempted to create the chat history table. Please check the error logs for any issues.";
        exit;
    }
});

// Enqueue Select2 library
function exclusv_ai_enqueue_select2()
{
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
}
add_action('admin_enqueue_scripts', 'exclusv_ai_enqueue_select2');

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
