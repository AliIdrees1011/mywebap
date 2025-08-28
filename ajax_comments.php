<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

if (isset($_GET['fetch']) && isset($_GET['video_id'])) {
    $vid = intval($_GET['video_id']);
    $stmt = $conn->prepare("SELECT username, comment, uploaded_at FROM comments WHERE video_id=? ORDER BY id DESC LIMIT 200");
    $stmt->bind_param("i", $vid);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    echo json_encode($rows);
    exit;
}

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!isset($_SESSION['username'])) { http_response_code(401); echo json_encode(['error'=>'login']); exit; }
    $vid = intval($_POST['video_id']);
    $comment = trim($_POST['comment'] ?? '');
    $user = $_SESSION['username'];
    if ($comment === '') { http_response_code(400); echo json_encode(['error'=>'empty']); exit; }
    $stmt = $conn->prepare("INSERT INTO comments (video_id, username, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $vid, $user, $comment);
    $stmt->execute();
    echo json_encode(['ok'=>1]);
    exit;
}

echo json_encode([]);
