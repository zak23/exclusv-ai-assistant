<?php
// Remove the duplicate function declaration
// function exclusv_ai_save_chat_history($chat_id, $start_time, $sender, $message, $email_submitted = false) {
//     // ... (remove this entire function)
// }

// Keep any other unique functions or code that might be in this file
// For example:

// Function to get chat history
function exclusv_ai_get_chat_history($chat_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'exclusv_ai_chat_history';
    
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE chat_id = %s ORDER BY start_time ASC", $chat_id);
    return $wpdb->get_results($query);
}

// Add any other unique functions here that are not already in the main plugin file