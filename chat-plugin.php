<?php
/*
Plugin Name: Chat Plugin
Description: A simple chat plugin for WordPress
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/ChatPluginAjax.php';
require_once plugin_dir_path(__FILE__) . 'public/ChatPluginWidget.php';
require_once plugin_dir_path(__FILE__) . 'admin/ChatPluginAdmin.php';

register_activation_hook(__FILE__, 'chat_plugin_activate');

function chat_plugin_activate() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "
    CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chat_rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_name VARCHAR(255) NOT NULL
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chat_users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL
    ) $charset_collate;

    CREATE TABLE IF NOT EXISTS {$wpdb->prefix}chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        chat_room_id INT,
        chat_user_id INT,
        message TEXT,
        sender VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (chat_room_id) REFERENCES {$wpdb->prefix}chat_rooms(id),
        FOREIGN KEY (chat_user_id) REFERENCES {$wpdb->prefix}chat_users(id)
    ) $charset_collate;
    ";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

new ChatPluginAjax();
new ChatPluginWidget();
new ChatPluginAdmin();
?>
