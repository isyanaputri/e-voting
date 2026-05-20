<?php
require_once "includes/admin_auth.php";
require_once "../config/database.php";
require_once "../config/crypto.php";

$privateKey = getPrivateKey();
$publicKey  = getPublicKey();

$eventsList = $pdo->query("SELECT * FROM events ORDER BY id DESC")->fetchAll();
$filterEventId = (int)($_GET['event'] ?? 0);

$whereClause = $filterEventId > 0 ? "AND v.event_id = $filterEventId" : "";
$votesQuery = $pdo->query(
    "SELECT v.*, u.nama, u.nim, e.title as event_title
     FROM votes v
     LEFT JOIN users u ON v.user_id = u.id
     LEFT JOIN events e ON v.event_id = e.id
     WHERE 1=1 $whereClause
     ORDER BY v.created_at DESC"
);
$votes = $votesQuery->fetchAll();

$processedVotes = [];
foreach ($votes as $vote) {
    $decrypted = decryptVote($vote['encrypted_vote'], $privateKey);
    $data      = json_decode($decrypted, true);
    $reHash    = createHash($decrypted);
    $processedVotes[] = [
        'raw'       => $vote,
        'decrypted' => $decrypted,
        'data'      => $data,
        'hashValid' => ($reHash === $vote['vote_hash']),
        'sigValid'  => verifySignature($vote['vote_hash'], $vote['digital_signature'], $publicKey) === 1,
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Data Vote - TrustVote Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.table-dark-glass td, .table-dark-glass th { color:white; border-color:rgba(255,255,255,0.1); vertical-align:middle; }
.badge-valid { background:rgba(34,197,94,0.2); color:#4ade80; border:1px solid rgba(34,197,94,0.3); }
.badge-invalid { background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.3); }
.nav-link.active { color:#facc15 !important; font-weight:600; }
.hash-text { font-size:11px; word-break:break-all; color:#94a3b8; font-family:monospace; }
</style>
</head>
<body>

<?php include "includes/admin_navbar.php"; ?>

<div class="container py-5">

<div class="d-flex justify-content-between align-items-center mb-4">
<div>
<h1 class="title mb-1">🗳️ Data Vote</h1>
<p class="subtitle">Semua suara terenkripsi & terverifikasi — <?= count($processedVotes) ?> vote</p>
</div>
</div>

<!-- Filter by event -->
<div class="glass-card p-3 mb-4 d-flex align-items-center gap-2 flex-wrap">
<span class="subtitle small">Filter Event:</span>
<a href="votes.php" class="btn btn-sm <?= $filterEventId === 0 ? 'btn-warning' : 'btn-outline-warning' ?>">Semua</a>
<?php foreach ($eventsList as $ev): ?>
<a href="votes.php?event=<?= $ev['id'] ?>"
   class="btn btn-sm <?= $filterEventId === (int)$ev['id'] ? 'btn-warning' : 'btn-outline-warning' ?>">
<?= htmlspecialchars($ev['title']) ?>
</a>
<?php endforeach; ?>
</div>

<?php if (count($processedVotes) === 0): ?>
<div class="glass-card p-5 text-center">
<p class="subtitle">Belum ada data vote.</p>
</div>
<?php else: ?>

<div class="glass-card p-4 mb-4">
<h4 class="mb-3">📋 Ringkasan Vote</h4>
<div class="table-responsive">
<table class="table table-dark-glass">
<thead>
<tr><th>#</th><th>NIM</th><th>Pemilih</th><th>Event</th><th>Pilihan</th><th>Waktu</th><th>Hash</th><th>Signature</th></tr>
</thead>
<tbody>
<?php foreach ($processedVotes as $pv): ?>
<tr>
<td><?= htmlspecialchars($pv['raw']['id']) ?></td>
<td><code style="color:#facc15"><?= htmlspecialchars($pv['raw']['nim'] ?? '-') ?></code></td>
<td><?= htmlspecialchars($pv['raw']['nama'] ?? 'Unknown') ?></td>
<td><small><?= htmlspecialchars($pv['raw']['event_title'] ?? '-') ?></small></td>
<td><strong style="color:#facc15"><?= htmlspecialchars($pv['data']['candidate'] ?? '???') ?></strong></td>
<td><small><?= htmlspecialchars($pv['raw']['created_at']) ?></small></td>
<td><span class="badge <?= $pv['hashValid'] ? 'badge-valid' : 'badge-invalid' ?> px-2 py-1 rounded"><?= $pv['hashValid'] ? '✅' : '❌' ?></span></td>
<td><span class="badge <?= $pv['sigValid'] ? 'badge-valid' : 'badge-invalid' ?> px-2 py-1 rounded"><?= $pv['sigValid'] ? '✅' : '❌' ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<?php foreach ($processedVotes as $pv): ?>
<div class="glass-card p-4 mb-3">

<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
<h5 class="mb-0">Vote ID #<?= $pv['raw']['id'] ?> — <span style="color:#facc15"><?= htmlspecialchars($pv['raw']['nama'] ?? 'Unknown') ?></span></h5>
<div class="d-flex gap-2">
<span class="badge <?= $pv['hashValid'] ? 'badge-valid' : 'badge-invalid' ?> px-2 py-1 rounded">Hash: <?= $pv['hashValid'] ? 'VALID' : 'INVALID' ?></span>
<span class="badge <?= $pv['sigValid'] ? 'badge-valid' : 'badge-invalid' ?> px-2 py-1 rounded">Sig: <?= $pv['sigValid'] ? 'VALID' : 'INVALID' ?></span>
</div>
</div>

<div class="row g-3">
<div class="col-md-6">
<small class="text-warning">Event:</small>
<p><?= htmlspecialchars($pv['raw']['event_title'] ?? '-') ?></p>
<small class="text-warning">Pilihan Kandidat:</small>
<p class="fw-bold"><?= htmlspecialchars($pv['data']['candidate'] ?? 'N/A') ?></p>
<small class="text-warning">Timestamp:</small>
<p><?= htmlspecialchars($pv['data']['timestamp'] ?? 'N/A') ?></p>
</div>
<div class="col-md-6">
<small class="text-warning">SHA-256 Hash:</small>
<p class="hash-text"><?= htmlspecialchars($pv['raw']['vote_hash']) ?></p>
</div>
</div>

<details class="mt-2">
<summary style="color:#94a3b8; cursor:pointer; font-size:13px;">Lihat Encrypted Vote & Signature</summary>
<div class="mt-2">
<small class="text-warning">Encrypted Vote (RSA):</small>
<p class="hash-text"><?= htmlspecialchars(substr($pv['raw']['encrypted_vote'], 0, 120)) ?>...</p>
<small class="text-warning">Digital Signature:</small>
<p class="hash-text"><?= htmlspecialchars(substr($pv['raw']['digital_signature'], 0, 120)) ?>...</p>
</div>
</details>

</div>
<?php endforeach; ?>
<?php endif; ?>

</div>

<footer class="text-center py-4 mt-5">
<p class="text-secondary">© 2026 TrustVote Secure E-Voting System — Admin Panel</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
