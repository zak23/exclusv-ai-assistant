<?php


// Enqueue Select2 library
function exclusv_ai_enqueue_select2()
{
    wp_enqueue_style('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css');
    wp_enqueue_script('select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', ['jquery'], '4.1.0-rc.0', true);
}
add_action('admin_enqueue_scripts', 'exclusv_ai_enqueue_select2');

