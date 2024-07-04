<?php

class ChatPluginWidget {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('wp_footer', [$this, 'displayWidget']);
    }

    public function enqueueScripts() {
        wp_enqueue_style('chat-widget', plugin_dir_url(__FILE__) . 'chat-widget.css');
        wp_enqueue_script('socket-io', 'https://cdn.socket.io/4.7.5/socket.io.min.js', [], null, true);
        wp_enqueue_script('chat-widget', plugin_dir_url(__FILE__) . 'chat-widget.js', ['socket-io'], false, true);
    }

    public function displayWidget() {
        ?>
        <div id="chat-widget" class="chat-widget">
            <button id="chat-toggle" class="chat-toggle">Chat</button>
            <div id="chat-box" class="chat-box">
                <div id="listmessage" class="chat-messages"></div>
                <form id="chat-form" class="chat-form">
                    <input type="hidden" id="chat-room" name="room">
                    <div id="user-info">
                        <input type="text" id="chat-phone" name="phone" placeholder="Telefone" required class="chat-input">
                        <input type="email" id="chat-email" name="email" placeholder="Email" required class="chat-input">
                    </div>
                    <textarea id="chat-message" name="message" placeholder="Sua mensagem" required class="chat-input"></textarea>
                    <button type="submit" class="chat-submit">Enviar</button>
                </form>
            </div>
        </div>
        <?php
    }
}
