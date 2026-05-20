<?php
session_start();
require "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$event_id = (int)($_GET['event'] ?? 0);

if ($event_id === 0) {
    header("Location: my_events.php");
    exit;
}

// Check event exists and user is participant
$event = $pdo->prepare("SELECT * FROM events WHERE id=?");
$event->execute([$event_id]);
$event = $event->fetch();

if (!$event) {
    header("Location: my_events.php?err=not_found");
    exit;
}

$participant = $pdo->prepare("SELECT * FROM event_participants WHERE event_id=? AND user_id=?");
$participant->execute([$event_id, $user_id]);
$participant = $participant->fetch();

if (!$participant) {
    header("Location: ../events/join.php?err=not_joined");
    exit;
}

if ($event['status'] !== 'active') {
    header("Location: result.php?event=$event_id&err=closed");
    exit;
}

// Get candidates for this event
$candidates = $pdo->prepare("SELECT * FROM candidates WHERE event_id=? ORDER BY id ASC");
$candidates->execute([$event_id]);
$candidates = $candidates->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title><?= htmlspecialchars($event['title']) ?> - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-5">

<div class="text-center mb-5">
<h1 class="title"><?= htmlspecialchars($event['title']) ?></h1>
<p class="subtitle"><?= htmlspecialchars($event['description'] ?? 'Secure E-Voting Berbasis Kriptografi') ?></p>
</div>

<?php if ($participant['has_voted'] == 1): ?>
<div class="glass-card p-5 text-center">
<div style="font-size:50px; margin-bottom:15px;">✅</div>
<h4 class="fw-bold mb-2">Anda sudah melakukan voting</h4>
<p class="subtitle mb-4">Suara Anda telah dienkripsi dan tersimpan aman.</p>
<div class="d-flex gap-3 justify-content-center flex-wrap">
<a href="result.php?event=<?= $event_id ?>" class="btn btn-warning">📊 Lihat Hasil</a>
<a href="verify.php?event=<?= $event_id ?>" class="btn btn-outline-warning">🔍 Verifikasi Vote</a>
<a href="my_events.php" class="btn btn-outline-secondary">← Event Saya</a>
</div>
</div>

<?php elseif (count($candidates) === 0): ?>
<div class="glass-card p-5 text-center">
<h4>⏳ Belum ada kandidat</h4>
<p class="subtitle mt-2">Kandidat belum ditambahkan oleh administrator.</p>
</div>

<?php else: ?>
<div class="row g-4">
<?php foreach ($candidates as $candidate): ?>
<div class="col-lg-4">
<div class="glass-card overflow-hidden h-100">

<?php if (!empty($candidate['foto'])): ?>
<img src="../<?= htmlspecialchars($candidate['foto']) ?>" class="candidate-img" alt="<?= htmlspecialchars($candidate['nama']) ?>">
<?php endif; ?>

<div class="p-4">
<h4 class="fw-bold"><?= htmlspecialchars($candidate['nama']) ?></h4>
<p class="subtitle"><?= htmlspecialchars($candidate['deskripsi']) ?></p>

<form method="POST" action="process_vote.php"
      onsubmit="return confirm('Anda yakin memilih <?= htmlspecialchars(addslashes($candidate['nama'])) ?>?\n\nVote tidak dapat diubah setelah dikonfirmasi.')">
<input type="hidden" name="candidate_id" value="<?= (int)$candidate['id'] ?>">
<input type="hidden" name="event_id" value="<?= $event_id ?>">
<button class="btn-vote w-100 mt-3" type="submit" name="vote">
🗳️ Vote Sekarang
</button>
</form>

</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>

<?php include "../includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
