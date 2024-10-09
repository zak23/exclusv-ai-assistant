<?php

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