<?php
session_start();
require_once 'db.php';

function redirect($p){ header("Location: $p"); exit; }

$mode = $_GET['mode'] ?? 'login';
$error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['action']) && $_POST['action']==='signup') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = ($_POST['role'] ?? 'consumer') === 'creator' ? 'creator' : 'consumer';
        if ($username === '' || $password === '') { $error = 'Username and password are required'; }
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?,?,?)");
            $stmt->bind_param("sss", $username, $hash, $role);
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                redirect('dashboard.php');
            } else {
                $error = 'Username may already exist.';
            }
        }
    }
    if (isset($_POST['action']) && $_POST['action']==='login') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (password_verify($password, $row['password'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $row['role'];
                redirect('dashboard.php');
            } else { $error = 'Invalid credentials'; }
        } else { $error = 'Invalid credentials'; }
    }
}

?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ALI TIKTOK APP - Auth</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0b0b0c;color:#fff;}
.card{background:#111;border:1px solid #333;border-radius:16px;}
.btn-primary{background:#ffd400;border-color:#ffd400;color:#000;font-weight:700;}
.form-control{background:#1a1b1d;color:#fff;border-color:#333;}
a{color:#ffd400;}.form-label {
    margin-bottom: .5rem;
    color: white;
}h5.mb-3 {
    color: white;
}
</style>
</head><body>
<div class="container py-5">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="h4 m-0 text-white">ALI TIKTOK APP</div>
    <a class="btn btn-outline-light" href="index.php">Home</a>
  </div>

  <?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <div class="row g-4">
    <div class="col-md-6">
      <div class="card p-4">
        <h5 class="mb-3">Login</h5>
        <form method="post">
          <input type="hidden" name="action" value="login">
          <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
          <button class="btn btn-primary">Login</button>
        </form>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card p-4">
        <h5 class="mb-3">Sign up</h5>
        <form method="post">
          <input type="hidden" name="action" value="signup">
          <div class="mb-3"><label class="form-label">Username</label><input class="form-control" name="username" required></div>
          <div class="mb-3"><label class="form-label">Password</label><input type="password" class="form-control" name="password" required></div>
          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
              <option value="consumer">Consumer</option>
              <option value="creator">Creator</option>
            </select>
          </div>
          <button class="btn btn-primary">Create account</button>
        </form>
      </div>
    </div>
  </div>
</div>
</body></html>
