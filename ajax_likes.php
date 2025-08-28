<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$vid = isset($_POST['video_id']) ? intval($_POST['video_id']) : (isset($_GET['video_id']) ? intval($_GET['video_id']) : 0);

if (isset($_GET['count']) && $vid) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM likes WHERE video_id=?");
    $stmt->bind_param("i", $vid);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

    $liked = false;
    if (isset($_SESSION['username'])) {
        $u = $_SESSION['username'];
        $chk = $conn->prepare("SELECT 1 FROM likes WHERE video_id=? AND username=? LIMIT 1");
        $chk->bind_param("is", $vid, $u);
        $chk->execute();
        $liked = (bool)$chk->get_result()->fetch_row();
    }
    echo json_encode(['count'=>intval($c), 'liked'=>$liked]);
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!isset($_SESSION['username'])) { http_response_code(401); echo json_encode(['error'=>'login']); exit; }
    $u = $_SESSION['username'];
    // toggle like
    $chk = $conn->prepare("SELECT id FROM likes WHERE video_id=? AND username=? LIMIT 1");
    $chk->bind_param("is", $vid, $u);
    $chk->execute();
    if ($row = $chk->get_result()->fetch_assoc()) {
        $del = $conn->prepare("DELETE FROM likes WHERE id=?");
        $del->bind_param("i", $row['id']);
        $del->execute();
    } else {
        $ins = $conn->prepare("INSERT INTO likes (video_id, username) VALUES (?, ?)");
        $ins->bind_param("is", $vid, $u);
        $ins->execute();
    }
    // return new count
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM likes WHERE video_id=?");
    $stmt->bind_param("i", $vid);
    $stmt->execute();
    $c = $stmt->get_result()->fetch_assoc()['c'] ?? 0;
    echo json_encode(['count'=>intval($c)]);
    exit;
}

echo json_encode([]);
