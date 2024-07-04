document.addEventListener('DOMContentLoaded', function () {
    const chatToggle = document.getElementById('chat-toggle');
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const chatRoomInput = document.getElementById('chat-room');
    const chatMessages = document.getElementById('listmessage');
    const userInfo = document.getElementById('user-info');
    const chatPhone = document.getElementById('chat-phone');
    const chatEmail = document.getElementById('chat-email');

    function getAjaxUrl(action) {
        const url = new URL(window.location.href);
        return `${url.origin}/wpchat/wp-admin/admin-ajax.php?action=${action}`;
    }

    function generateRoom() {
        return 'room-' + Math.random().toString(36).substr(2, 9);
    }

    let chatRoom = localStorage.getItem('chatRoom');
    if (!chatRoom) {
        chatRoom = generateRoom();
        localStorage.setItem('chatRoom', chatRoom);
    }

    chatRoomInput.value = chatRoom;
    console.log(`Chat room: ${chatRoom}`);

    const storedPhone = localStorage.getItem('chatPhone');
    const storedEmail = localStorage.getItem('chatEmail');

    if (storedPhone && storedEmail) {
        chatPhone.value = storedPhone;
        chatEmail.value = storedEmail;
        userInfo.style.display = 'none';
    }

    let socket;

    chatToggle.addEventListener('click', function () {
        chatBox.style.display = chatBox.style.display === 'none' ? 'block' : 'none';
        loadMessages();
        if (socket) {
            socket.close();
        }
        socket = io(`http://localhost:3000`, { query: `room=${chatRoom}` });
        socket.on('message', function (data) {
            if (data.room === chatRoom) {
                appendMessage(data.sender, data.message);
                alert('Nova mensagem recebida!');
            }
        });
    });

    chatForm.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(chatForm);
        const message = formData.get('message');
        const phone = formData.get('phone');
        const email = formData.get('email');

        if (!storedPhone || !storedEmail) {
            localStorage.setItem('chatPhone', phone);
            localStorage.setItem('chatEmail', email);

            fetch(getAjaxUrl('chat_plugin_create_room'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `room=${chatRoom}&phone=${phone}&email=${email}`
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    sendMessage(chatRoom, message, 'User', email);
                } else {
                    console.error('Erro ao criar a sala.');
                    alert('Erro ao criar a sala.');
                }
            }).catch(err => {
                console.error('Erro ao criar a sala.', err);
                alert('Erro ao criar a sala.');
            });
        } else {
            sendMessage(chatRoom, message, 'User', email);
        }
    });

    function appendMessage(sender, message) {
        const messageElement = document.createElement('div');
        messageElement.classList.add('chat-message');
        messageElement.innerHTML = `<strong>${sender}</strong>: ${message}`;
        chatMessages.appendChild(messageElement);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Scroll to bottom
    }

    function sendMessage(room, message, sender, email) {
        fetch(getAjaxUrl('chat_plugin_send_message'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `room=${room}&message=${message}&sender=${sender}&email=${email}`
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                appendMessage(sender, message);
                chatForm.reset();
                userInfo.style.display = 'none';
                socket.emit('join-room', { room });
                socket.emit('message', { room, message, sender });
            } else {
                console.error('Erro ao enviar a mensagem.');
                alert('Erro ao enviar a mensagem.');
            }
        }).catch(err => {
            console.error('Erro ao enviar a mensagem.', err);
            alert('Erro ao enviar a mensagem.');
        });
    }

    function loadMessages() {
        fetch(getAjaxUrl('chat_plugin_load_messages'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `room=${chatRoom}`
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                chatMessages.innerHTML = '';
                data.data.forEach(msg => {
                    appendMessage(msg.sender, msg.message);
                });
            } else {
                console.error('Sala não encontrada.');
                alert('Sala não encontrada.');
            }
        }).catch(err => {
            console.error('Erro ao carregar as mensagens.', err);
            alert('Erro ao carregar as mensagens.');
        });
    }

    socket = io(`http://localhost:3000`, { query: `room=${chatRoom}` });
    socket.on('message', function (data) {
        if (data.room === chatRoom) {
            appendMessage(data.sender, data.message);
            alert('Nova mensagem recebida!');
        }
    });

    // Logic to check for new messages
    setInterval(checkNewMessages, 5000);

    function checkNewMessages() {
        fetch(getAjaxUrl('chat_plugin_load_messages'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `room=${chatRoom}`
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                const currentMessages = chatMessages.children.length;
                if (data.data.length > currentMessages) {
                    alert('Nova mensagem recebida!');
                    chatMessages.innerHTML = '';
                    data.data.forEach(msg => {
                        appendMessage(msg.sender, msg.message);
                    });
                }
            }
        }).catch(err => {
            console.error('Erro ao verificar novas mensagens.', err);
            alert('Erro ao verificar novas mensagens.');
        });
    }
});
