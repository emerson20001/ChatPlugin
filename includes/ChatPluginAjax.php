<?php

class ChatPluginAjax {
    public function __construct() {
        add_action('wp_ajax_chat_plugin_create_room', [$this, 'createRoom']);
        add_action('wp_ajax_nopriv_chat_plugin_create_room', [$this, 'createRoom']);
        add_action('wp_ajax_chat_plugin_send_message', [$this, 'sendMessage']);
        add_action('wp_ajax_nopriv_chat_plugin_send_message', [$this, 'sendMessage']);
        add_action('wp_ajax_chat_plugin_load_messages', [$this, 'loadMessages']);
        add_action('wp_ajax_nopriv_chat_plugin_load_messages', [$this, 'loadMessages']);
    }

    public function createRoom() {
        $room = sanitize_text_field($_POST['room']);
        $phone = sanitize_text_field($_POST['phone']);
        $email = sanitize_email($_POST['email']);

        $response = wp_remote_post('http://localhost:3000/create-room', [
            'body' => [
                'room' => $room,
                'phone' => $phone,
                'email' => $email
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to create room.');
        } else {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body);
            if ($result->success) {
                global $wpdb;

                // Ensure room exists in WordPress database
                $room_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}chat_rooms WHERE room_name = %s",
                    $room
                ));

                if (!$room_id) {
                    $wpdb->insert("{$wpdb->prefix}chat_rooms", [
                        'room_name' => $room
                    ]);
                }

                wp_send_json_success($result);
            } else {
                wp_send_json_error($result->message);
            }
        }
    }

    public function sendMessage() {
        global $wpdb;

        $room = sanitize_text_field($_POST['room']);
        $message = sanitize_textarea_field($_POST['message']);
        $sender = sanitize_text_field($_POST['sender']);
        $email = sanitize_email($_POST['email']);

        $room_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}chat_rooms WHERE room_name = %s",
            $room
        ));

        if (!$room_id) {
            wp_send_json_error('Room not found.');
            return;
        }

        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}chat_users WHERE email = %s",
            $email
        ));

        if (!$user_id) {
            $wpdb->insert("{$wpdb->prefix}chat_users", [
                'phone' => sanitize_text_field($_POST['phone']),
                'email' => $email
            ]);
            $user_id = $wpdb->insert_id;
        }

        $response = wp_remote_post('http://localhost:3000/send-message', [
            'body' => [
                'room' => $room,
                'message' => $message,
                'sender' => $sender
            ]
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error('Failed to send message.');
        } else {
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body);
            if ($result->success) {
                $wpdb->insert(
                    "{$wpdb->prefix}chat_messages",
                    [
                        'chat_room_id' => $room_id,
                        'chat_user_id' => $user_id,
                        'message' => $message,
                        'sender' => $sender
                    ]
                );
                wp_send_json_success();
            } else {
                wp_send_json_error($result->message);
            }
        }
    }

    public function loadMessages() {
        global $wpdb;

        $room = sanitize_text_field($_POST['room']);

        $room_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}chat_rooms WHERE room_name = %s",
            $room
        ));

        if ($room_id) {
            $messages = $wpdb->get_results($wpdb->prepare(
                "SELECT message, sender, created_at FROM {$wpdb->prefix}chat_messages WHERE chat_room_id = %d ORDER BY created_at ASC",
                $room_id
            ));

            wp_send_json_success($messages);
        } else {
            wp_send_json_error('Room not found.');
        }
    }
}
