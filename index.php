<?php
session_start();
require_once 'db.php';
$user = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;
?>
<!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ALI TIKTOK APP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body{margin:0;background:#0b0b0c;color:#fff;font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;}
.header{position:sticky;top:0;background:#000;padding:10px 12px;z-index:100;border-bottom:1px solid #222;display:flex;gap:10px;align-items:center;}
.brand{color:#fff;font-weight:800;}
.search{flex:1;display:flex;gap:8px;background:#fff;border-radius:999px;padding:6px 10px;}
.search input{flex:1;border:0;outline:none;}
.btn-yellow{background:#ffd400;color:#000;border:0;font-weight:700;border-radius:999px;padding:6px 14px;}
.container-feeds{max-width:900px;margin:0 auto;padding:16px;}
.video-card{border:1px solid #2a2a2a;border-radius:16px;background:#101010;margin-bottom:16px;padding:12px;}
.video-card video{width:100%;max-height:70vh;border-radius:12px;box-shadow:0 4px 16px rgba(255,212,0,.25);}
.username{color:#ffd400;font-weight:700;}
.like-btn{background:transparent;border:1px solid #ffd400;color:#ffd400;border-radius:8px;padding:4px 10px;}
.like-btn.liked{background:#ffd400;color:#000;}
.comment-item{background:#151515;border:1px solid #262626;border-radius:8px;padding:6px 8px;margin-top:6px;}
.form-control{background:#151515;border:1px solid #333;color:#fff;}
</style>
</head><body>
<div class="header">
  <div class="brand">ALI TIKTOK APP</div>
  <div class="search">
    <input id="search" placeholder="Search title, publisher, producer, genre, user">
    <button id="searchBtn" class="btn-yellow">Search</button>
  </div>
  <div class="ms-auto d-flex gap-2">
    <?php if($user): ?>
      <a class="btn btn-outline-light btn-sm" href="dashboard.php">Dashboard</a>
      <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
    <?php else: ?>
      <a class="btn btn-outline-light btn-sm" href="auth.php">Login / Sign up</a>
    <?php endif; ?>
  </div>
</div>

<div class="container-feeds">
  <div id="feed"></div>
  <div id="loading" class="text-center py-3">Loading…</div>
</div>

<script>
let start=0, limit=8, loading=false, done=false;
const feedEl = document.getElementById('feed');
const loadingEl = document.getElementById('loading');
const searchEl = document.getElementById('search');
const searchBtn = document.getElementById('searchBtn');

function escapeHtml(s){return (s||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}

async function fetchBatch(reset=false){
  if(loading || done) return;
  loading=true; loadingEl.style.display='block';

  const params = new URLSearchParams({start:String(start), limit:String(limit)});
  if(searchEl.value.trim()!=='') params.set('q', searchEl.value.trim());

  try{
    const res = await fetch('fetch.php?' + params.toString());
    const list = await res.json();
    if(reset){ feedEl.innerHTML=''; start=0; done=false; }
    if(!Array.isArray(list) || list.length===0){ done=true; return; }
    list.forEach(v => {
      const card = document.createElement('div');
      card.className='video-card';
      card.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div><div class="username">@${escapeHtml(v.username||'')}</div><div class="text-secondary small">${escapeHtml(v.title||'')} • ${escapeHtml(v.genre||'')}</div></div>
          <button class="btn btn-sm btn-outline-light" onclick="copyLink(${v.id})">Share</button>
        </div>
        <video src="public/uploads/${escapeHtml(v.filename)}" controls playsinline preload="metadata"></video>
        <div class="mt-2 d-flex gap-2 align-items-center">
          <button class="like-btn" data-id="${v.id}" onclick="toggleLike(${v.id}, this)">❤ <span class="like-count">0</span></button>
        </div>
        <div class="mt-3">
          <form onsubmit="return postComment(event, ${v.id})" class="d-flex gap-2">
            <input class="form-control" name="comment" placeholder="Write a comment…" required>
            <button class="btn-yellow">Post</button>
          </form>
          <div id="comments-${v.id}" class="mt-2"></div>
        </div>
      `;
      feedEl.appendChild(card);
      refreshLike(v.id, card.querySelector('.like-count'));
      loadComments(v.id);
    });
    start += list.length;
  }catch(e){ console.error(e); }
  finally{ loading=false; loadingEl.style.display='none'; }
}

async function refreshLike(id, span){
  const res = await fetch('ajax_likes.php?count=1&video_id='+encodeURIComponent(id));
  const j = await res.json();
  if(span) span.textContent = j.count ?? 0;
}

async function toggleLike(id, btn){
  const fd = new FormData(); fd.append('video_id', id);
  const res = await fetch('ajax_likes.php', {method:'POST', body:fd});
  if(res.status===401){ alert('Please login to like'); return; }
  const j = await res.json();
  const span = btn.querySelector('.like-count'); if(span) span.textContent = j.count ?? 0;
  btn.classList.toggle('liked');
}

async function loadComments(id){
  const res = await fetch('ajax_comments.php?fetch=1&video_id='+encodeURIComponent(id));
  const arr = await res.json();
  const listEl = document.getElementById('comments-'+id);
  if(!listEl) return;
  listEl.innerHTML = arr.map(c => `<div class="comment-item"><strong>@${escapeHtml(c.username)}</strong>: ${escapeHtml(c.comment)}</div>`).join('');
}

async function postComment(e, id){
  e.preventDefault();
  const form = e.target;
  const input = form.querySelector('input[name="comment"]');
  const fd = new FormData();
  fd.append('video_id', id);
  fd.append('comment', input.value);
  const res = await fetch('ajax_comments.php', {method:'POST', body:fd});
  if(res.status===401){ alert('Please login to comment'); return false; }
  input.value='';
  loadComments(id);
  return false;
}

function copyLink(id){
  const url = window.location.origin + window.location.pathname + '?video=' + id;
  navigator.clipboard.writeText(url); alert('Link copied');
}

window.addEventListener('scroll', () => {
  if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 400) fetchBatch();
});
let t=null;
searchEl.addEventListener('input', ()=>{ clearTimeout(t); t=setTimeout(()=>{ start=0; fetchBatch(true); }, 350); });
searchBtn.addEventListener('click', ()=>{ start=0; fetchBatch(true); });

fetchBatch();
</script>
</body></html>
