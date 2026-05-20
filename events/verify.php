<?php
session_start();
require "../config/database.php";
require "../config/crypto.php";

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

// Verify user is participant
$partQ = $pdo->prepare("SELECT id FROM event_participants WHERE event_id=? AND user_id=?");
$partQ->execute([$event_id, $user_id]);
if ($partQ->rowCount() === 0) {
    header("Location: ../events/join.php");
    exit;
}

$eventQ = $pdo->prepare("SELECT * FROM events WHERE id=?");
$eventQ->execute([$event_id]);
$event = $eventQ->fetch();

$privateKey = getPrivateKey();
$publicKey  = getPublicKey();

$searchHash = trim($_GET['hash'] ?? '');
if ($searchHash !== '') {
    $stmt = $pdo->prepare("SELECT * FROM votes WHERE event_id=? AND (vote_hash LIKE ? OR id=?)");
    $stmt->execute([$event_id, "%$searchHash%", (int)$searchHash]);
    $votes = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM votes WHERE event_id=? ORDER BY id ASC");
    $stmt->execute([$event_id]);
    $votes = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Verifikasi - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.hash-text { font-size:12px; word-break:break-all; font-family:monospace; color:#94a3b8; }
.badge-valid { background:rgba(34,197,94,0.2); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:4px 10px; border-radius:20px; }
.badge-invalid { background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:4px 10px; border-radius:20px; }
.form-control { background:rgba(255,255,255,0.1) !important; border:1px solid rgba(255,255,255,0.2) !important; color:white !important; }
.form-control::placeholder { color:#94a3b8; }
</style>
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-5">

<div class="text-center mb-5">
<h1 class="title">🔍 Vote Verification</h1>
<p class="subtitle"><?= htmlspecialchars($event['title'] ?? '') ?> — Verifikasi integritas data voting</p>
</div>

<div class="glass-card p-4 mb-4">
<form method="GET" class="d-flex gap-3">
<input type="hidden" name="event" value="<?= $event_id ?>">
<input type="text" name="hash" class="form-control"
       placeholder="🔎 Cari by Vote ID atau Hash..."
       value="<?= htmlspecialchars($searchHash) ?>">
<button class="btn btn-warning px-4">Cari</button>
<?php if ($searchHash): ?>
<a href="verify.php?event=<?= $event_id ?>" class="btn btn-outline-secondary">Reset</a>
<?php endif; ?>
</form>
</div>

<?php if (count($votes) === 0): ?>
<div class="glass-card p-5 text-center">
<p class="subtitle">
<?= $searchHash ? "Tidak ada vote dengan hash/ID tersebut." : "Belum ada data vote di event ini." ?>
</p>
</div>
<?php endif; ?>

<?php foreach ($votes as $vote):
    $decrypted   = decryptVote($vote['encrypted_vote'], $privateKey);
    $reHash      = createHash($decrypted);
    $isValidHash = ($reHash === $vote['vote_hash']);
    $isValidSig  = verifySignature($vote['vote_hash'], $vote['digital_signature'], $publicKey) === 1;
    $data        = json_decode($decrypted, true);
?>
<div class="glass-card p-4 mb-4">

<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="mb-0">Vote ID #<?= htmlspecialchars($vote['id']) ?></h4>
<div class="d-flex gap-2">
<span class="badge-<?= $isValidHash ? 'valid' : 'invalid' ?>">
<?= $isValidHash ? '✅ Hash VALID' : '❌ Hash INVALID' ?>
</span>
<span class="badge-<?= $isValidSig ? 'valid' : 'invalid' ?>">
<?= $isValidSig ? '✅ Signature VALID' : '❌ Signature INVALID' ?>
</span>
</div>
</div>

<div class="row g-3">
<div class="col-md-6">
<p><b class="text-warning">Kandidat Dipilih:</b><br>
<?= htmlspecialchars($data['candidate'] ?? 'N/A') ?></p>
<p><b class="text-warning">Timestamp:</b><br>
<small><?= htmlspecialchars($data['timestamp'] ?? 'N/A') ?></small></p>
</div>
<div class="col-md-6">
<p><b class="text-warning">SHA-256 Hash:</b></p>
<p class="hash-text"><?= htmlspecialchars($vote['vote_hash']) ?></p>
</div>
</div>

<hr style="border-color:rgba(255,255,255,0.1);">

<details>
<summary style="color:#cbd5e1; cursor:pointer;">🔐 Lihat Data Enkripsi Lengkap</summary>
<div class="mt-3 row g-3">
<div class="col-12">
<b class="text-warning">Encrypted Vote (RSA Base64):</b>
<p class="hash-text mt-1"><?= htmlspecialchars($vote['encrypted_vote']) ?></p>
</div>
<div class="col-12">
<b class="text-warning">Decrypted Payload:</b>
<p class="hash-text mt-1"><?= htmlspecialchars($decrypted) ?></p>
</div>
<div class="col-12">
<b class="text-warning">Digital Signature (RSA-SHA256):</b>
<p class="hash-text mt-1"><?= htmlspecialchars($vote['digital_signature']) ?></p>
</div>
</div>
</details>

</div>
<?php endforeach; ?>

<div class="text-center mt-4">
<a href="result.php?event=<?= $event_id ?>" class="btn btn-warning me-2">← Kembali ke Hasil</a>
<a href="my_events.php" class="btn btn-outline-warning">Event Saya</a>
</div>

</div>

<?php include "../includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
