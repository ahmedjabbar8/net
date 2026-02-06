<?php
include 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit();
}

$my_id = $_SESSION['user_id'];

// 1. Send Message
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $receiver_id = intval($_POST['receiver_id']);
    $message = $conn->real_escape_string($_POST['message']);

    $conn->query("INSERT INTO chat_messages (sender_id, receiver_id, message) VALUES ($my_id, $receiver_id, '$message')");
    echo json_encode(['success' => true]);
    exit();
}

// 2. Fetch Messages & User Statuses
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $friend_id = intval($_GET['with'] ?? 0);
    $get_status = isset($_GET['get_status']);

    $response = [];

    if ($friend_id > 0) {
        $res = $conn->query("SELECT * FROM chat_messages 
                            WHERE (sender_id = $my_id AND receiver_id = $friend_id) 
                               OR (sender_id = $friend_id AND receiver_id = $my_id) 
                            ORDER BY created_at ASC");

        $messages = [];
        while ($m = $res->fetch_assoc()) {
            $messages[] = [
                'text' => $m['message'],
                'type' => ($m['sender_id'] == $my_id ? 'sent' : 'received'),
                'time' => date('H:i', strtotime($m['created_at']))
            ];
        }
        $response['messages'] = $messages;
    }

    if ($get_status) {
        $users_res = $conn->query("SELECT user_id, last_activity, current_task, active_patient_name FROM users WHERE is_active = 1");
        $statuses = [];
        while ($u = $users_res->fetch_assoc()) {
            $last_active = strtotime($u['last_activity']);
            $is_online = (time() - $last_active < 45); // Active in last 45 seconds
            $statuses[$u['user_id']] = [
                'online' => $is_online,
                'task' => $u['current_task'],
                'patient' => $u['active_patient_name']
            ];
        }
        $response['statuses'] = $statuses;
    }

    echo json_encode($response);
}
?>