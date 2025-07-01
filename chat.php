<?php
require_once 'functions.php';

$room_id = isset($_GET['room']) ? sanitizeInput($_GET['room']) : '';
$nickname = isset($_GET['nickname']) ? sanitizeInput($_GET['nickname']) : 'Anonymous';

if (empty($room_id) || !file_exists("rooms/$room_id.json")) {
    header("Location: index.php");
    exit();
}

$room_data = json_decode(file_get_contents("rooms/$room_id.json"), true);

// Add user to room if not already present
if (!in_array($nickname, $room_data['users'])) {
    $room_data['users'][] = $nickname;
    file_put_contents("rooms/$room_id.json", json_encode($room_data));
    
    // Add join message
    addMessageToRoom($room_id, 'system', "$nickname joined the chat");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Room <?php echo substr($room_id, 0, 6); ?> | Anonymous Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-picker-element@1.12.0/dist/emoji-picker.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-fluid vh-100 d-flex flex-column p-0">
        <!-- Chat Header -->
        <div class="bg-primary text-white p-3 shadow-sm">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Anonymous Chat Room</h5>
                    <small>Room ID: <?php echo $room_id; ?></small>
                </div>
                <div>
                    <button id="copyUrlBtn" class="btn btn-sm btn-light">
                        <i class="bi bi-link-45deg"></i> Copy Room URL
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <div class="row flex-grow-1 m-0 overflow-hidden">
            <!-- Online Users (hidden on small screens) -->
            <div class="col-md-3 d-none d-md-block bg-light p-3 border-end">
                <h6 class="text-muted mb-3">Online Users (<?php echo count($room_data['users']); ?>)</h6>
                <div id="onlineUsers" class="list-group">
                    <?php foreach ($room_data['users'] as $user): ?>
                        <div class="list-group-item list-group-item-action py-2">
                            <i class="bi bi-person-fill text-primary"></i> <?php echo htmlspecialchars($user); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Chat Messages -->
            <div class="col-md-9 p-0 d-flex flex-column">
                <div id="chatMessages" class="flex-grow-1 p-3 overflow-auto">
                    <?php foreach ($room_data['messages'] as $message): ?>
                        <div class="message mb-3 <?php echo $message['sender'] === 'system' ? 'system-message' : ''; ?>">
                            <div class="d-flex justify-content-between mb-1">
                                <strong class="<?php echo $message['sender'] === 'system' ? 'text-muted' : 'text-primary'; ?>">
                                    <?php echo htmlspecialchars($message['sender']); ?>
                                </strong>
                                <small class="text-muted"><?php echo date('H:i', $message['time']); ?></small>
                            </div>
                            <div class="message-content"><?php echo formatMessage($message['content']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Message Input -->
                <div class="p-3 border-top bg-light">
                    <form id="messageForm" class="d-flex">
                        <input type="hidden" id="room_id" value="<?php echo $room_id; ?>">
                        <input type="hidden" id="nickname" value="<?php echo htmlspecialchars($nickname); ?>">
                        
                        <div class="input-group">
                            <button type="button" id="emojiPickerBtn" class="btn btn-outline-secondary">
                                <i class="bi bi-emoji-smile"></i>
                            </button>
                            <input type="text" id="messageInput" class="form-control" placeholder="Type your message..." autocomplete="off" required>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send-fill"></i> Send
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Emoji Picker (hidden until triggered) -->
    <emoji-picker id="emojiPicker" class="hidden"></emoji-picker>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/emoji-picker-element@1.12.0/dist/emoji-picker.min.js"></script>
    <script>
        // Scroll to bottom of chat
        function scrollToBottom() {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Copy room URL to clipboard
        document.getElementById('copyUrlBtn').addEventListener('click', function() {
            const url = window.location.href.split('?')[0] + '?room=<?php echo $room_id; ?>&nickname=' + encodeURIComponent('<?php echo $nickname; ?>');
            navigator.clipboard.writeText(url).then(() => {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-check2"></i> Copied!';
                setTimeout(() => {
                    this.innerHTML = originalText;
                }, 2000);
            });
        });
        
        // Emoji picker
        const picker = document.getElementById('emojiPicker');
        const button = document.getElementById('emojiPickerBtn');
        const input = document.getElementById('messageInput');
        
        picker.addEventListener('emoji-click', event => {
            input.value += event.detail.unicode;
            input.focus();
        });
        
        button.addEventListener('click', () => {
            picker.classList.toggle('hidden');
        });
        
        // Close emoji picker when clicking outside
        document.addEventListener('click', (event) => {
            if (!picker.contains(event.target) && event.target !== button) {
                picker.classList.add('hidden');
            }
        });
        
        // AJAX message sending and receiving
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const chatMessages = document.getElementById('chatMessages');
        const onlineUsers = document.getElementById('onlineUsers');
        
        messageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const message = messageInput.value.trim();
            if (message === '') return;
            
            const room_id = document.getElementById('room_id').value;
            const nickname = document.getElementById('nickname').value;
            
            fetch('functions.php?action=send_message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `room_id=${room_id}&nickname=${encodeURIComponent(nickname)}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    updateChat();
                }
            });
        });
        
        // Periodically update chat
        function updateChat() {
            const room_id = document.getElementById('room_id').value;
            
            fetch(`functions.php?action=get_messages&room_id=${room_id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.messages) {
                        chatMessages.innerHTML = '';
                        data.messages.forEach(message => {
                            const messageClass = message.sender === 'system' ? 'system-message' : '';
                            const senderClass = message.sender === 'system' ? 'text-muted' : 'text-primary';
                            const time = new Date(message.time * 1000).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                            
                            const messageElement = document.createElement('div');
                            messageElement.className = `message mb-3 ${messageClass}`;
                            messageElement.innerHTML = `
                                <div class="d-flex justify-content-between mb-1">
                                    <strong class="${senderClass}">${escapeHtml(message.sender)}</strong>
                                    <small class="text-muted">${time}</small>
                                </div>
                                <div class="message-content">${formatMessageHtml(message.content)}</div>
                            `;
                            chatMessages.appendChild(messageElement);
                        });
                        
                        scrollToBottom();
                    }
                    
                    if (data.users) {
                        onlineUsers.innerHTML = '';
                        data.users.forEach(user => {
                            const userElement = document.createElement('div');
                            userElement.className = 'list-group-item list-group-item-action py-2';
                            userElement.innerHTML = `<i class="bi bi-person-fill text-primary"></i> ${escapeHtml(user)}`;
                            onlineUsers.appendChild(userElement);
                        });
                    }
                });
        }
        
        // Helper functions
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        function formatMessageHtml(content) {
            // Simple URL detection
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            return content.replace(urlRegex, url => {
                return `<a href="${url}" target="_blank">${url}</a>`;
            });
        }
        
        // Update chat every 2 seconds
        setInterval(updateChat, 2000);
        
        // Initial scroll to bottom
        scrollToBottom();
    </script>
</body>
</html>