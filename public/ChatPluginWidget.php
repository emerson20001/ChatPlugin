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
        <div id="chat-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 1000;">
            <button id="chat-toggle" style="background: #0073aa; color: #fff; border: none; padding: 10px 20px; border-radius: 5px;">Chat</button>
            <div id="chat-box" style="display: none; background: #fff; border: 1px solid #ccc; padding: 10px; width: 300px; height: 400px;">
                <div id="listmessage" style="border: 1px solid #ccc; height: 200px; overflow-y: auto; margin-bottom: 10px;"></div>
                <form id="chat-form">
                    <input type="hidden" id="chat-room" name="room">
                    <div id="user-info">
                        <input type="text" id="chat-phone" name="phone" placeholder="Telefone" required style="width: 100%; margin-bottom: 10px;">
                        <input type="email" id="chat-email" name="email" placeholder="Email" required style="width: 100%; margin-bottom: 10px;">
                    </div>
                    <textarea id="chat-message" name="message" placeholder="Sua mensagem" required style="width: 100%; height: 50px; margin-bottom: 10px;"></textarea>
                    <button type="submit" style="background: #0073aa; color: #fff; border: none; padding: 10px 20px; border-radius: 5px;">Enviar</button>
                </form>
                <iframe id="hidden-iframe" style="display:none;"></iframe>
            </div>
        </div>
        <?php
    }
}
