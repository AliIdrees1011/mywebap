<?php
require_once 'db.php'; session_start();
if(!isset($_SESSION['user_id'])){ header('Location: auth.php'); exit; }
$username = $_SESSION['username']; $role = $_SESSION['role'] ?? 'consumer';
$err=''; $msg='';

if($_SERVER['REQUEST_METHOD']==='POST' && $role==='creator' && isset($_FILES['video'])){
    $title = trim($_POST['title'] ?? 'Untitled');
    $publisher = trim($_POST['publisher'] ?? '');
    $producer = trim($_POST['producer'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $age_rating = trim($_POST['age_rating'] ?? '');
    $orig = $_FILES['video']['name'];
    $filename = time().'_'.preg_replace('/[^A-Za-z0-9._-]+/','_', $orig);
    $dest = __DIR__ . '/public/uploads/' . $filename;
    if(!is_dir(__DIR__ . '/public/uploads')) mkdir(__DIR__ . '/public/uploads', 0775, true);
    if(move_uploaded_file($_FILES['video']['tmp_name'], $dest)){
        $stmt = $conn->prepare("INSERT INTO videos (title, name, filename, username, publisher, producer, genre, age_rating) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("ssssssss", $title, $orig, $filename, $username, $publisher, $producer, $genre, $age_rating);
        if($stmt->execute()){ $msg='Uploaded successfully'; } else { $err='DB insert failed'; }
    } else { $err='Upload failed'; }
}
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ALI TIKTOK APP - Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{background:#0b0b0c;color:white;}
.header{background:#000;padding:12px;}
.btn-primary{background:#ffd400;border-color:#ffd400;color:#000;font-weight:700;}
.card{background:#111;border:1px solid #333;border-radius:16px;}
.form-control{color:#fff;}h5.mb-3 {
    color: white;
}
</style>
</head><body>
<div class="header d-flex justify-content-between align-items-center">
  <div class="h5 text-white m-0">ALI TIKTOK APP Dashboard</div>
  <div>
    <a class="btn btn-outline-light btn-sm" href="index.php">Home</a>
    <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
  </div>
</div>
<div class="container py-4">
  <?php if($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <?php if($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>

  <?php if($role==='creator'): ?>
  <div class="card p-4 mb-4">
    <h5 class="mb-3">Upload a new video</h5>
    <form method="post" enctype="multipart/form-data" class="row g-3">
      <div class="col-md-6"><input class="form-control" name="title" placeholder="Title"></div>
      <div class="col-md-6"><input class="form-control" name="publisher" placeholder="Publisher"></div>
      <div class="col-md-6"><input class="form-control" name="producer" placeholder="Producer"></div>
      <div class="col-md-3"><input class="form-control" name="genre" placeholder="Genre"></div>
      <div class="col-md-3"><input class="form-control" name="age_rating" placeholder="Age rating"></div>
      <div class="col-md-12"><input type="file" class="form-control" name="video" accept="video/*" required></div>
      <div class="col-12"><button class="btn btn-primary">Upload</button></div>
    </form>
  </div>
  <?php else: ?>
    <div class="alert alert-info">You are signed in as a consumer. Switch to a creator account to upload.</div>
  <?php endif; ?>

  <h5 class="mb-3">Latest videos</h5>
  <div id="grid" class="row g-3"></div>
</div>

<script>
async function loadLatest(){
  const res = await fetch('fetch.php?start=0&limit=12');
  const data = await res.json();
  const grid = document.getElementById('grid');
  grid.innerHTML='';
  data.forEach(v => {
    const col = document.createElement('div'); col.className='col-md-4';
    col.innerHTML = `
      <div class="card p-2">
        <div class="small text-secondary mb-2">@${v.username} • ${v.genre||''}</div>
        <video controls preload="metadata" src="public/uploads/${v.filename}" style="width:100%;max-height:260px;border-radius:10px;"></video>
        <div class="mt-2 fw-bold text-white">${v.title||''}</div>
      </div>`;
    grid.appendChild(col);
  });
}
loadLatest();
</script>
</body></html>
