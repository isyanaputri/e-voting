<?php
session_start();
require "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Events that user has joined
$myEvents = $pdo->prepare("
    SELECT e.*, ep.has_voted, ep.joined_at
    FROM event_participants ep
    JOIN events e ON ep.event_id = e.id
    WHERE ep.user_id = ?
    ORDER BY ep.joined_at DESC
");
$myEvents->execute([$user_id]);
$myEvents = $myEvents->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>Event Saya - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.event-card { transition: 0.3s; }
.event-card:hover { transform: translateY(-5px); }
.badge-active { background: rgba(34,197,94,0.2); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:5px 12px; border-radius:20px; font-size:12px; }
.badge-closed { background: rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:5px 12px; border-radius:20px; font-size:12px; }
.badge-draft { background: rgba(250,204,21,0.15); color:#facc15; border:1px solid rgba(250,204,21,0.3); padding:5px 12px; border-radius:20px; font-size:12px; }
.join-code-display { font-family: monospace; font-size: 22px; font-weight: 700; color: #facc15; letter-spacing: 4px; }
</style>
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-5">

<div class="text-center mb-5">
<h1 class="title">🗳️ Event Saya</h1>
<p class="subtitle">Kelola partisipasi voting Anda di berbagai event</p>
</div>

<?php if (count($myEvents) === 0): ?>

<!-- Empty state -->
<div class="glass-card p-5 text-center">
<div style="font-size: 60px; margin-bottom: 20px;">🔑</div>
<h3 class="fw-bold mb-3">Belum Ikut Event</h3>
<p class="subtitle mb-4">Anda belum bergabung ke event voting manapun.<br>Masukkan kode event untuk mulai berpartisipasi.</p>
<a href="join.php" class="btn-vote px-5" style="display:inline-block; text-decoration:none;">
🔑 Join Event Sekarang
</a>
</div>

<?php else: ?>

<div class="row g-4">
<?php foreach ($myEvents as $event): ?>
<div class="col-md-6 col-lg-4">
<div class="glass-card p-4 event-card h-100 d-flex flex-column">

<div class="d-flex justify-content-between align-items-start mb-3">
<h5 class="fw-bold mb-0" style="line-height:1.3;"><?= htmlspecialchars($event['title']) ?></h5>
<span class="badge-<?= $event['status'] === 'active' ? 'active' : ($event['status'] === 'closed' ? 'closed' : 'draft') ?> ms-2 flex-shrink-0">
<?= $event['status'] === 'active' ? '🟢 Aktif' : ($event['status'] === 'closed' ? '🔴 Ditutup' : '⏸️ Draft') ?>
</span>
</div>

<p class="subtitle small mb-3"><?= htmlspecialchars($event['description'] ?? '-') ?></p>

<div class="mb-3">
<small class="text-secondary">Kode Event:</small>
<div class="join-code-display"><?= htmlspecialchars($event['join_code']) ?></div>
</div>

<div class="mb-4">
<?php if ($event['has_voted']): ?>
<div class="d-flex align-items-center gap-2" style="color:#4ade80;">
<span>✅</span> <span class="small">Sudah voting</span>
</div>
<?php else: ?>
<div class="d-flex align-items-center gap-2" style="color:#facc15;">
<span>⏳</span> <span class="small">Belum voting</span>
</div>
<?php endif; ?>
</div>

<div class="mt-auto d-flex gap-2 flex-wrap">
<?php if ($event['status'] === 'active' && !$event['has_voted']): ?>
<a href="vote.php?event=<?= $event['id'] ?>" class="btn-vote flex-fill text-center" style="text-decoration:none; display:block; padding:10px;">
🗳️ Vote Sekarang
</a>
<?php endif; ?>
<a href="result.php?event=<?= $event['id'] ?>" class="btn btn-outline-warning flex-fill">
📊 Hasil
</a>
</div>

</div>
</div>
<?php endforeach; ?>

<!-- Join new event card -->
<div class="col-md-6 col-lg-4">
<div class="glass-card p-4 event-card h-100 d-flex flex-column justify-content-center align-items-center text-center" style="border: 2px dashed rgba(250,204,21,0.3); min-height: 220px;">
<div style="font-size:40px; margin-bottom:15px;">➕</div>
<p class="subtitle mb-3">Bergabung ke event lain?</p>
<a href="join.php" class="btn btn-outline-warning">🔑 Join Event Baru</a>
</div>
</div>

</div>
<?php endif; ?>

</div>

<?php include "../includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
