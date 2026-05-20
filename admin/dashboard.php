<?php
require_once "includes/admin_auth.php";
require_once "../config/database.php";

$totalUsers      = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVotes      = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalEvents     = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$activeEvents    = $pdo->query("SELECT COUNT(*) FROM events WHERE status='active'")->fetchColumn();
$totalCandidates = $pdo->query("SELECT COUNT(*) FROM candidates")->fetchColumn();

$recentVotes = $pdo->query(
    "SELECT v.id, u.nama, u.nim, e.title as event_title, v.created_at
     FROM votes v
     JOIN users u ON v.user_id = u.id
     JOIN events e ON v.event_id = e.id
     ORDER BY v.created_at DESC LIMIT 8"
)->fetchAll();

$electionTitle = getSetting($pdo, 'election_title', 'TrustVote Platform');
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.stat-card { text-align:center; padding: 28px 20px; }
.stat-number { font-size: 46px; font-weight: 700; color: #facc15; }
.stat-label { color: #cbd5e1; font-size: 13px; margin-top: 4px; }
.table-dark-glass { background: transparent; --bs-table-bg: transparent; --bs-table-striped-bg: transparent; --bs-table-hover-bg: transparent; }
.table-dark-glass thead tr { background: rgba(255,255,255,0.08); }
.table-dark-glass tbody tr { background: rgba(255,255,255,0.04); }
.table-dark-glass tbody tr:hover { background: rgba(255,255,255,0.1); }
.table-dark-glass td, .table-dark-glass th { color: #f1f5f9; border-color: rgba(255,255,255,0.1); vertical-align: middle; }
.nav-link.active { color: #facc15 !important; font-weight: 600; }
</style>
</head>
<body>

<?php include "includes/admin_navbar.php"; ?>

<div class="container py-5">

<div class="text-center mb-5">
<h1 class="title">Admin Dashboard</h1>
<p class="subtitle"><?= htmlspecialchars($electionTitle) ?></p>
</div>

<div class="row g-4 mb-5">
<div class="col-md-2 col-sm-4 col-6">
<div class="glass-card stat-card">
<div class="stat-number"><?= $totalUsers ?></div>
<div class="stat-label">Pengguna</div>
</div>
</div>
<div class="col-md-2 col-sm-4 col-6">
<div class="glass-card stat-card">
<div class="stat-number"><?= $totalVotes ?></div>
<div class="stat-label">Total Suara</div>
</div>
</div>
<div class="col-md-2 col-sm-4 col-6">
<div class="glass-card stat-card">
<div class="stat-number"><?= $totalEvents ?></div>
<div class="stat-label">Total Event</div>
</div>
</div>
<div class="col-md-2 col-sm-4 col-6">
<div class="glass-card stat-card">
<div class="stat-number"><?= $activeEvents ?></div>
<div class="stat-label">Event Aktif</div>
</div>
</div>
<div class="col-md-2 col-sm-4 col-6">
<div class="glass-card stat-card">
<div class="stat-number"><?= $totalCandidates ?></div>
<div class="stat-label">Kandidat</div>
</div>
</div>
</div>


<div class="glass-card p-4">
<h4 class="mb-4">🕐 Vote Terbaru</h4>
<?php if (count($recentVotes) > 0): ?>
<div class="table-responsive">
<table class="table table-dark-glass">
<thead><tr><th>#</th><th>NIM</th><th>Nama</th><th>Event</th><th>Waktu Vote</th></tr></thead>
<tbody>
<?php foreach ($recentVotes as $rv): ?>
<tr>
<td><?= htmlspecialchars($rv['id']) ?></td>
<td><code style="color:#facc15"><?= htmlspecialchars($rv['nim']) ?></code></td>
<td><?= htmlspecialchars($rv['nama']) ?></td>
<td><small><?= htmlspecialchars($rv['event_title']) ?></small></td>
<td><small><?= htmlspecialchars($rv['created_at']) ?></small></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<p class="subtitle text-center py-3">Belum ada data vote.</p>
<?php endif; ?>
</div>

</div>

<footer class="text-center py-4 mt-5">
<p class="text-secondary">© 2026 TrustVote Secure E-Voting System — Admin Panel</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
