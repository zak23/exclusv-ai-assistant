<?php

// Add shortcode for displaying the AI chat interface
function exclusv_ai_chat_shortcode()
{
    // Add this at the beginning of the function
    $header_color = get_option('exclusv_ai_header_color', '#33b89f');
    $send_button_color = get_option('exclusv_ai_send_button_color', '#33b89f');
    
    echo "<style>
        :root {
            --header-color: {$header_color};
            --send-button-color: {$send_button_color};
        }
    </style>";

    // Rest of the function remains the same
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
        <div id="chat-powered-by" class="card-footer text-muted">Powered by <a href="https://exclusv.ai" target="_blank" title="Adult Industry AI">Exclusv.ai</a></div>
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

        // Add this near the top of the JavaScript code
        console.log('Initial messageCount:', messageCount);
        console.log('Message limit:', exclusvAiSettings.messageLimit);

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

        // Update the sendMessage function
        function sendMessage() {
            var message = userInput.value.trim();
            if (message !== '') {
                displayMessage(message, 'user');
                sendMessageToServer(message);
                userInput.value = '';

                messageCount = parseInt(messageCount) + 1;
                localStorage.setItem('messageCount', messageCount);

                console.log('Updated messageCount:', messageCount);
                console.log('Message limit:', exclusvAiSettings.messageLimit);

                // Use the localized message limit
                var messageLimit = parseInt(exclusvAiSettings.messageLimit);

                if (messageCount >= messageLimit) {
                    console.log('Message limit reached. Showing email capture.');
                    showEmailCapture();
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

        // Add this new function
        function showEmailCapture() {
            chatInput.style.display = 'none';
            emailCapture.style.display = 'block';
            // Update the email prompt message
            document.querySelector('#email-capture h6').textContent = exclusvAiSettings.emailPromptMessage;
            console.log('Email capture displayed');
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
            // console.log('sendMessageToServer called with message:', message);
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
            // console.log('saveChatMessage called with:', {
            //     chatId,
            //     startTime,
            //     sender,
            //     message
            // });
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
            messageCount = parseInt(localStorage.getItem('messageCount')) || 0;
            console.log('Loaded messageCount:', messageCount);

            // Use the localized message limit
            var messageLimit = parseInt(exclusvAiSettings.messageLimit);
            console.log('Loaded message limit:', messageLimit);

            // Check if the email has been submitted
            var emailSubmitted = localStorage.getItem('emailSubmitted');

            if (messageCount >= messageLimit && emailSubmitted !== 'true') {
                console.log('Message limit reached on load. Showing email capture.');
                showEmailCapture();
            } else if (emailSubmitted === 'true') {
                console.log('Email already submitted. Showing thank you message.');
                showThankYouMessage();
            } else {
                console.log('Showing chat input.');
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

        // Apply custom colors
        var headerColor = '<?php echo esc_js(get_option('exclusv_ai_header_color', '#1f86be')); ?>';
        var sendButtonColor = '<?php echo esc_js(get_option('exclusv_ai_send_button_color', '#007bff')); ?>';

        document.getElementById('chat-header').style.backgroundColor = headerColor;
        document.getElementById('send-button').style.backgroundColor = sendButtonColor;
        document.getElementById('chat-button').style.backgroundColor = headerColor;
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('exclusv_ai_chat', 'exclusv_ai_chat_shortcode');