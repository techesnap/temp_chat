<?php
require_once 'functions.php';

// Clean up old rooms (older than 12 hours)
cleanUpOldRooms();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nickname'])) {
    $nickname = sanitizeInput($_POST['nickname']);
    if (!empty($nickname)) {
        if (isset($_POST['join_room']) && !empty($_POST['room_id'])) {
            $room_id = sanitizeInput($_POST['room_id']);
            if (file_exists("rooms/$room_id.json")) {
                header("Location: chat.php?room=$room_id&nickname=" . urlencode($nickname));
                exit();
            } else {
                $error = "Room not found!";
            }
        } else {
            // Create new room
            $room_id = uniqid();
            $room_data = [
                'created_at' => time(),
                'users' => [],
                'messages' => []
            ];
            file_put_contents("rooms/$room_id.json", json_encode($room_data));
            header("Location: chat.php?room=$room_id&nickname=" . urlencode($nickname));
            exit();
        }
    } else {
        $error = "Please enter a nickname!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anonymous Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Anonymous Live Chat</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label for="nickname" class="form-label">Your Nickname</label>
                                <input type="text" class="form-control" id="nickname" name="nickname" placeholder="e.g. Anonymous123" required maxlength="20">
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100" name="create_room">
                                    <i class="bi bi-plus-circle"></i> Create New Chat Room
                                </button>
                            </div>
                            
                            <div class="text-center mb-3 text-muted">OR</div>
                            
                            <div class="mb-3">
                                <label for="room_id" class="form-label">Join Existing Room</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="room_id" name="room_id" placeholder="Enter Room ID">
                                    <button type="submit" class="btn btn-success" name="join_room">
                                        <i class="bi bi-box-arrow-in-right"></i> Join
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-light">
                        <small class="text-muted">No registration needed. Chats are temporary and disappear when closed.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>