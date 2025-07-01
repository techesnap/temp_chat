<?php
// Helper functions for the chat application

// Create rooms directory if it doesn't exist
if (!file_exists('rooms')) {
    mkdir('rooms', 0755, true);
}

// Sanitize user input
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Format message content (for display)
function formatMessage($content) {
    // Detect URLs and make them clickable
    $content = preg_replace('!(((f|ht)tp(s)?://)[-a-zA-Zа-яА-Я()0-9@:%_+.~#?&;//=]+)!i', '<a href="$1" target="_blank">$1</a>', $content);
    return nl2br($content);
}

// Add message to room
function addMessageToRoom($room_id, $sender, $content) {
    $room_file = "rooms/$room_id.json";
    if (file_exists($room_file)) {
        $room_data = json_decode(file_get_contents($room_file), true);
        $room_data['messages'][] = [
            'sender' => $sender,
            'content' => $content,
            'time' => time()
        ];
        
        // Keep only the last 100 messages to prevent file from growing too large
        if (count($room_data['messages']) > 100) {
            $room_data['messages'] = array_slice($room_data['messages'], -100);
        }
        
        file_put_contents($room_file, json_encode($room_data));
        return true;
    }
    return false;
}

// Clean up old rooms (older than 12 hours)
function cleanUpOldRooms() {
    $files = glob('rooms/*.json');
    $now = time();
    $twelveHours = 12 * 60 * 60;
    
    foreach ($files as $file) {
        $lastModified = filemtime($file);
        if ($now - $lastModified > $twelveHours) {
            unlink($file);
        }
    }
}

// Handle AJAX actions
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'send_message':
            if (isset($_POST['room_id'], $_POST['nickname'], $_POST['message']) && !empty($_POST['message'])) {
                $room_id = sanitizeInput($_POST['room_id']);
                $nickname = sanitizeInput($_POST['nickname']);
                $message = sanitizeInput($_POST['message']);
                
                $success = addMessageToRoom($room_id, $nickname, $message);
                echo json_encode(['success' => $success]);
            } else {
                echo json_encode(['success' => false]);
            }
            break;
            
        case 'get_messages':
            if (isset($_GET['room_id'])) {
                $room_id = sanitizeInput($_GET['room_id']);
                $room_file = "rooms/$room_id.json";
                
                if (file_exists($room_file)) {
                    $room_data = json_decode(file_get_contents($room_file), true);
                    
                    // Update users list (remove users who haven't "pinged" in 5 minutes)
                    $current_users = [];
                    foreach ($room_data['users'] as $user) {
                        $current_users[] = $user;
                    }
                    
                    // Return messages and users
                    echo json_encode([
                        'messages' => $room_data['messages'],
                        'users' => $current_users
                    ]);
                } else {
                    echo json_encode(['error' => 'Room not found']);
                }
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
    }
    exit();
}
?>