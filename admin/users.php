<?php
require_once "includes/admin_auth.php";
require_once "../config/database.php";

$message = "";
$messageType = "success";

// ── HAPUS USER ──────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Cascade akan hapus event_participants & votes otomatis via FK
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    $message = "Pengguna dan semua data terkait berhasil dihapus.";
    $messageType = "warning";
}

// ── RESET VOTE STATUS (per user, semua event) ────────────────────────────────
if (isset($_GET['reset_vote'])) {
    $id = (int)$_GET['reset_vote'];
    // FIX: Reset semua event_participants.has_voted untuk user ini
    $pdo->prepare("UPDATE event_participants SET has_voted=0 WHERE user_id=?")->execute([$id]);
    // FIX: Hapus semua votes dari user ini
    $pdo->prepare("DELETE FROM votes WHERE user_id=?")->execute([$id]);
    // FIX: Reset global flag juga
    $pdo->prepare("UPDATE users SET has_voted=0 WHERE id=?")->execute([$id]);
    $message = "Status voting pengguna berhasil direset di semua event. Pengguna dapat voting kembali.";
    $messageType = "info";
}

// ── SEARCH ───────────────────────────────────────────
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare(
        "SELECT u.*,
            (SELECT COUNT(*) FROM event_participants ep WHERE ep.user_id = u.id) as total_events_joined,
            (SELECT COUNT(*) FROM event_participants ep WHERE ep.user_id = u.id AND ep.has_voted = 1) as total_votes_cast
         FROM users u
         WHERE u.nim LIKE ? OR u.nama LIKE ?
         ORDER BY u.created_at DESC"
    );
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt = $pdo->query(
        "SELECT u.*,
            (SELECT COUNT(*) FROM event_participants ep WHERE ep.user_id = u.id) as total_events_joined,
            (SELECT COUNT(*) FROM event_participants ep WHERE ep.user_id = u.id AND ep.has_voted = 1) as total_votes_cast
         FROM users u
         ORDER BY u.created_at DESC"
    );
}
$users = $stmt->fetchAll();

// Stats
$totalUsers  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVoters = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM event_participants WHERE has_voted=1")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
<title>Kelola Pengguna - TrustVote Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.table-dark-glass { background: transparent; --bs-table-bg: transparent; --bs-table-striped-bg: transparent; --bs-table-hover-bg: transparent; }
.table-dark-glass thead tr { background: rgba(255,255,255,0.08); }
.table-dark-glass tbody tr { background: rgba(255,255,255,0.04); }
.table-dark-glass tbody tr:hover { background: rgba(255,255,255,0.1); }
.table-dark-glass td, .table-dark-glass th { color: #f1f5f9; border-color:rgba(255,255,255,0.1); vertical-align:middle; }
.form-control { background:rgba(255,255,255,0.1) !important; border:1px solid rgba(255,255,255,0.2) !important; color:white !important; }
.form-control::placeholder { color:#94a3b8; }
.form-control:focus { box-shadow:0 0 0 0.25rem rgba(250,204,21,0.25) !important; border-color:#facc15 !important; }
.nav-link.active { color:#facc15 !important; font-weight:600; }
.badge-voted { background:rgba(34,197,94,0.2); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:4px 10px; border-radius:15px; font-size:12px; }
.badge-notvoted { background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:4px 10px; border-radius:15px; font-size:12px; }
.badge-partial { background:rgba(250,204,21,0.15); color:#facc15; border:1px solid rgba(250,204,21,0.3); padding:4px 10px; border-radius:15px; font-size:12px; }
</style>
</head>
<body>

<?php include "includes/admin_navbar.php"; ?>

<div class="container py-5">

<h1 class="title mb-2">👥 Kelola Pengguna</h1>
<p class="subtitle mb-4">Manajemen pengguna terdaftar dan status voting per event</p>

<?php if ($message !== ""): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
<?= htmlspecialchars($message) ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats row -->
<div class="row g-3 mb-4">
<div class="col-md-3">
<div class="glass-card p-4 text-center">
<div style="font-size:32px; font-weight:700; color:#facc15;"><?= $totalUsers ?></div>
<p class="subtitle small">Total Pengguna</p>
</div>
</div>
<div class="col-md-3">
<div class="glass-card p-4 text-center">
<div style="font-size:32px; font-weight:700; color:#4ade80;"><?= $totalVoters ?></div>
<p class="subtitle small">Sudah Vote (≥1 Event)</p>
</div>
</div>
<div class="col-md-3">
<div class="glass-card p-4 text-center">
<div style="font-size:32px; font-weight:700; color:#f87171;"><?= $totalUsers - $totalVoters ?></div>
<p class="subtitle small">Belum Pernah Vote</p>
</div>
</div>
<div class="col-md-3">
<div class="glass-card p-4 text-center">
<div style="font-size:32px; font-weight:700; color:#60a5fa;"><?= count($users) ?></div>
<p class="subtitle small">Hasil Pencarian</p>
</div>
</div>
</div>

<!-- Search -->
<div class="glass-card p-3 mb-4">
<form method="GET" class="d-flex gap-2">
<input type="text" name="search" class="form-control"
       placeholder="🔍 Cari berdasarkan NIM atau Nama..."
       value="<?= htmlspecialchars($search) ?>">
<button type="submit" class="btn btn-warning px-4">Cari</button>
<?php if ($search): ?>
<a href="users.php" class="btn btn-outline-secondary">Reset</a>
<?php endif; ?>
</form>
</div>

<!-- Tabel Pengguna -->
<div class="glass-card p-4">
<?php if (count($users) > 0): ?>
<div class="table-responsive">
<table class="table table-dark-glass">
<thead>
<tr>
<th>#</th>
<th>NIM</th>
<th>Nama</th>
<th>Event Diikuti</th>
<th>Vote Dilakukan</th>
<th>Terdaftar</th>
<th>Aksi</th>
</tr>
</thead>
<tbody>
<?php foreach ($users as $i => $u):
    $joined = (int)$u['total_events_joined'];
    $voted  = (int)$u['total_votes_cast'];
?>
<tr>
<td><?= $i + 1 ?></td>
<td><code style="color:#facc15"><?= htmlspecialchars($u['nim']) ?></code></td>
<td><?= htmlspecialchars($u['nama']) ?></td>
<td>
  <span style="color:#facc15; font-weight:600;"><?= $joined ?></span>
  <small class="subtitle"> event</small>
</td>
<td>
<?php if ($voted === 0 && $joined === 0): ?>
  <span class="badge-notvoted">⏳ Belum Join</span>
<?php elseif ($voted === 0): ?>
  <span class="badge-notvoted">⏳ Belum Vote</span>
<?php elseif ($voted < $joined): ?>
  <span class="badge-partial">⚡ <?= $voted ?>/<?= $joined ?> Event</span>
<?php else: ?>
  <span class="badge-voted">✅ <?= $voted ?>/<?= $joined ?> Event</span>
<?php endif; ?>
</td>
<td><small><?= htmlspecialchars($u['created_at']) ?></small></td>
<td>
<div class="d-flex gap-1 flex-wrap">
<?php if ($voted > 0): ?>
<a href="users.php?reset_vote=<?= $u['id'] ?>"
   class="btn btn-sm btn-outline-warning"
   onclick="return confirm('Reset SEMUA status voting <?= htmlspecialchars(addslashes($u['nama'])) ?>?\n\nSemua vote mereka di semua event akan dihapus dan mereka dapat voting kembali.')">
🔄 Reset Semua Vote
</a>
<?php endif; ?>
<a href="users.php?delete=<?= $u['id'] ?>"
   class="btn btn-sm btn-danger"
   onclick="return confirm('Hapus permanen pengguna <?= htmlspecialchars(addslashes($u['nama'])) ?>?\n\nSemua data terkait (vote, partisipasi) akan ikut terhapus.')">
🗑️ Hapus
</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<p class="subtitle text-center py-4">
<?= $search ? "Tidak ada pengguna yang cocok dengan pencarian \"" . htmlspecialchars($search) . "\"." : "Belum ada pengguna terdaftar." ?>
</p>
<?php endif; ?>
</div>

</div>

<footer class="text-center py-4 mt-5">
<p class="text-secondary">© 2026 TrustVote Secure E-Voting System — Admin Panel</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
