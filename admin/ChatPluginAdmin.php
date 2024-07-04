<?php

class ChatPluginAdmin {
    public function __construct() {
        add_action('admin_menu', [$this, 'adminMenu']);
    }

    public function adminMenu() {
        add_menu_page('Chat Plugin', 'Chat Plugin', 'manage_options', 'chat-plugin', [$this, 'adminPage']);
    }

    public function adminPage() {
        ?>
        <div class="wrap">
            <h1>Chat Plugin</h1>
            <form id="select-room-form">
                <select id="chat-room-select" name="room">
                    <?php
                    global $wpdb;
                    $rooms = $wpdb->get_results("SELECT room_name FROM {$wpdb->prefix}chat_rooms");
                    foreach ($rooms as $room) {
                        echo "<option value='{$room->room_name}'>{$room->room_name}</option>";
                    }
                    ?>
                </select>
                <button type="submit">Selecionar Sala</button>
            </form>
            <div id="chat-container" style="width: 100%; height: 100%; display: flex; flex-direction: column;">
                <div id="listmessage" style="flex: 1; border: 1px solid #ccc; overflow-y: auto; margin-top: 10px;"></div>
                <div id="chat-reply" style="display: none; flex-direction: column; margin-top: 10px;">
                    <textarea id="reply-message" placeholder="Digite sua mensagem" style="width: 100%; height: 50px;"></textarea>
                    <button id="send-reply" style="background: #0073aa; color: #fff; border: none; padding: 10px 20px; border-radius: 5px;">Enviar</button>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const roomSelect = document.getElementById('chat-room-select');
                const chatMessages = document.getElementById('listmessage');
                const chatReply = document.getElementById('chat-reply');
                const replyMessage = document.getElementById('reply-message');
                const sendReply = document.getElementById('send-reply');

                function getAjaxUrl(action) {
                    const url = new URL(window.location.href);
                    return `${url.origin}/wpchat/wp-admin/admin-ajax.php?action=${action}`;
                }

                let selectedRoom = null;
                let socket = null;

                roomSelect.addEventListener('change', function () {
                    selectedRoom = roomSelect.value;
                    loadMessages(selectedRoom);
                    if (socket) {
                        socket.close();
                    }
                    socket = io(`http://localhost:3000`, { query: `room=${selectedRoom}` });
                    socket.on('message', function (data) {
                        if (data.room === selectedRoom) {
                            appendMessage(data.sender, data.message);
                            alert('Nova mensagem recebida!');
                        }
                    });
                });

                sendReply.addEventListener('click', function () {
                    const message = replyMessage.value;
                    appendMessage('Admin', message);
                    replyMessage.value = '';

                    fetch(getAjaxUrl('chat_plugin_send_message'), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `room=${selectedRoom}&message=${message}&sender=Admin`
                    }).then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            socket.emit('message', { room: selectedRoom, message, sender: 'Admin' });
                        } else {
                            console.error('Erro ao enviar a mensagem.');
                            alert('Erro ao enviar a mensagem.');
                        }
                    }).catch(err => {
                        console.error('Erro ao enviar a mensagem.', err);
                        alert('Erro ao enviar a mensagem.');
                    });
                });

                function appendMessage(sender, message) {
                    const messageElement = document.createElement('div');
                    messageElement.classList.add('chat-message');
                    messageElement.innerHTML = `<strong>${sender}</strong>: ${message}`;
                    chatMessages.appendChild(messageElement);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }

                function loadMessages(room) {
                    fetch(getAjaxUrl('chat_plugin_load_messages'), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `room=${room}`
                    }).then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            chatMessages.innerHTML = '';
                            data.data.forEach(msg => {
                                appendMessage(msg.sender, msg.message);
                            });
                            chatReply.style.display = 'flex';
                        } else {
                            console.error('Sala não encontrada.');
                            alert('Sala não encontrada.');
                        }
                    }).catch(err => {
                        console.error('Erro ao carregar as mensagens.', err);
                        alert('Erro ao carregar as mensagens.');
                    });
                }

                socket = io(`http://localhost:3000`, { query: `room=${selectedRoom}` });
                socket.on('message', function (data) {
                    if (data.room === selectedRoom) {
                        appendMessage(data.sender, data.message);
                        alert('Nova mensagem recebida!');
                    }
                });
            });
        </script>
        <?php
    }
}
