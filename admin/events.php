<?php
require_once "includes/admin_auth.php";
require_once "../config/database.php";

$message = "";
$messageType = "success";

// Generate unique join code
function generateJoinCode($pdo, $length = 7) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $check = $pdo->prepare("SELECT id FROM events WHERE join_code=?");
        $check->execute([$code]);
    } while ($check->rowCount() > 0);
    return $code;
}

// ── DELETE EVENT ──────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $pdo->prepare("DELETE FROM events WHERE id=?")->execute([$id]);
    $message = "Event berhasil dihapus.";
    $messageType = "warning";
}

// ── TOGGLE STATUS ──────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $curr = $pdo->prepare("SELECT status FROM events WHERE id=?");
    $curr->execute([$id]);
    $s = $curr->fetchColumn();
    $newStatus = ($s === 'active') ? 'closed' : 'active';
    $pdo->prepare("UPDATE events SET status=? WHERE id=?")->execute([$newStatus, $id]);
    $message = "Status event diubah ke: " . strtoupper($newStatus);
}

// ── REGENERATE CODE ──────────────────────────────────────
if (isset($_GET['regen'])) {
    $id   = (int)$_GET['regen'];
    $code = generateJoinCode($pdo);
    $pdo->prepare("UPDATE events SET join_code=? WHERE id=?")->execute([$code, $id]);
    $message = "Kode event berhasil diperbarui: $code";
}

// ── SAVE EVENT (ADD / EDIT) ──────────────────────────────
if (isset($_POST['save_event'])) {
    $id          = (int)($_POST['id'] ?? 0);
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status      = $_POST['status'];

    if (strlen($title) < 3) {
        $message = "Judul event terlalu pendek.";
        $messageType = "danger";
    } else {
        if ($id > 0) {
            // Edit
            $pdo->prepare("UPDATE events SET title=?, description=?, status=? WHERE id=?")
                ->execute([$title, $description, $status, $id]);
            $message = "Event berhasil diperbarui.";
        } else {
            // Add — auto generate unique code
            $code = generateJoinCode($pdo);
            $pdo->prepare("INSERT INTO events (title, description, join_code, status) VALUES (?,?,?,?)")
                ->execute([$title, $description, $code, $status]);
            $newId = $pdo->lastInsertId();
            $message = "✅ Event berhasil dibuat! Kode Join: <strong>$code</strong>";
        }
    }
}

// ── FETCH EVENTS ──────────────────────────────────────
$events = $pdo->query("
    SELECT e.*,
        (SELECT COUNT(*) FROM candidates WHERE event_id=e.id) as total_candidates,
        (SELECT COUNT(*) FROM event_participants WHERE event_id=e.id) as total_participants,
        (SELECT COUNT(*) FROM votes WHERE event_id=e.id) as total_votes
    FROM events e
    ORDER BY e.id DESC
")->fetchAll();

// Edit mode
$editEvent = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editEvent = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Kelola Event - TrustVote Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.table-dark-glass td, .table-dark-glass th { color:white; border-color:rgba(255,255,255,0.1); vertical-align:middle; }
.form-label { color: #cbd5e1; font-size: 14px; }
.form-control, .form-select {
    background:rgba(255,255,255,0.1) !important;
    border:1px solid rgba(255,255,255,0.2) !important;
    color:white !important;
}
.form-control::placeholder { color:#94a3b8; }
.form-control:focus, .form-select:focus {
    box-shadow:0 0 0 0.25rem rgba(250,204,21,0.25) !important;
    border-color:#facc15 !important;
}
.form-select option { background: #1e293b; color: white; }
.nav-link.active { color:#facc15 !important; font-weight:600; }
.join-code-badge {
    font-family: monospace;
    font-size: 16px;
    font-weight: 700;
    color: #facc15;
    letter-spacing: 3px;
    background: rgba(250,204,21,0.1);
    padding: 4px 12px;
    border-radius: 8px;
    border: 1px solid rgba(250,204,21,0.3);
}
.badge-active { background:rgba(34,197,94,0.2); color:#4ade80; border:1px solid rgba(34,197,94,0.3); padding:4px 10px; border-radius:15px; font-size:12px; }
.badge-closed { background:rgba(239,68,68,0.2); color:#f87171; border:1px solid rgba(239,68,68,0.3); padding:4px 10px; border-radius:15px; font-size:12px; }
.badge-draft  { background:rgba(250,204,21,0.15); color:#facc15; border:1px solid rgba(250,204,21,0.3); padding:4px 10px; border-radius:15px; font-size:12px; }
</style>
</head>
<body>

<?php include "includes/admin_navbar.php"; ?>

<div class="container py-5">

<h1 class="title mb-2">🗂️ Kelola Event</h1>
<p class="subtitle mb-4">Buat dan kelola event voting dengan kode unik</p>

<?php if ($message !== ""): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
<?= $message ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

<!-- Form -->
<div class="col-lg-4">
<div class="glass-card p-4">
<h4 class="mb-4"><?= $editEvent ? '✏️ Edit Event' : '➕ Buat Event Baru' ?></h4>

<form method="POST">
<input type="hidden" name="id" value="<?= htmlspecialchars($editEvent['id'] ?? '') ?>">

<div class="mb-3">
<label class="form-label">Judul Event <span class="text-warning">*</span></label>
<input type="text" name="title" class="form-control"
       value="<?= htmlspecialchars($editEvent['title'] ?? '') ?>"
       placeholder="Cth: Pemilihan Ketua BEM 2025" required>
</div>

<div class="mb-3">
<label class="form-label">Deskripsi</label>
<textarea name="description" class="form-control" rows="3"
          placeholder="Keterangan singkat event..."><?= htmlspecialchars($editEvent['description'] ?? '') ?></textarea>
</div>

<div class="mb-4">
<label class="form-label">Status</label>
<select name="status" class="form-select">
<option value="active"  <?= ($editEvent['status'] ?? 'active') === 'active'  ? 'selected' : '' ?>>🟢 Aktif</option>
<option value="draft"   <?= ($editEvent['status'] ?? '') === 'draft'          ? 'selected' : '' ?>>⏸️ Draft</option>
<option value="closed"  <?= ($editEvent['status'] ?? '') === 'closed'         ? 'selected' : '' ?>>🔴 Ditutup</option>
</select>
</div>

<div class="d-flex gap-2">
<button name="save_event" class="btn-vote flex-fill">
<?= $editEvent ? '💾 Simpan Perubahan' : '➕ Buat Event' ?>
</button>
<?php if ($editEvent): ?>
<a href="events.php" class="btn btn-outline-secondary">Batal</a>
<?php endif; ?>
</div>

</form>

<?php if (!$editEvent): ?>
<div class="mt-3 p-3" style="background:rgba(250,204,21,0.05); border:1px solid rgba(250,204,21,0.2); border-radius:12px;">
<p class="subtitle small mb-0">💡 Kode join akan <strong>dibuat otomatis</strong> saat event dibuat. Anda bisa regenerate kode kapan saja.</p>
</div>
<?php endif; ?>
</div>
</div>

<!-- Event List -->
<div class="col-lg-8">
<div class="glass-card p-4">
<h4 class="mb-4">📋 Daftar Event (<?= count($events) ?>)</h4>

<?php if (count($events) === 0): ?>
<p class="subtitle text-center py-5">Belum ada event. Buat event pertama!</p>
<?php else: ?>
<?php foreach ($events as $ev): ?>
<div class="glass-card p-4 mb-3" style="background:rgba(255,255,255,0.04);">
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
<div>
<h5 class="fw-bold mb-1"><?= htmlspecialchars($ev['title']) ?></h5>
<p class="subtitle small mb-2"><?= htmlspecialchars($ev['description'] ?? '-') ?></p>
</div>
<span class="badge-<?= $ev['status'] ?>">
<?= $ev['status'] === 'active' ? '🟢 Aktif' : ($ev['status'] === 'closed' ? '🔴 Ditutup' : '⏸️ Draft') ?>
</span>
</div>

<!-- Join code display -->
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
<div>
<small class="text-secondary d-block mb-1">Kode Join:</small>
<span class="join-code-badge"><?= htmlspecialchars($ev['join_code']) ?></span>
</div>
<div class="ms-auto d-flex gap-2 text-center">
<div>
<div style="color:#facc15; font-weight:700; font-size:20px;"><?= $ev['total_candidates'] ?></div>
<small class="subtitle">Kandidat</small>
</div>
<div class="ms-3">
<div style="color:#facc15; font-weight:700; font-size:20px;"><?= $ev['total_participants'] ?></div>
<small class="subtitle">Peserta</small>
</div>
<div class="ms-3">
<div style="color:#facc15; font-weight:700; font-size:20px;"><?= $ev['total_votes'] ?></div>
<small class="subtitle">Suara</small>
</div>
</div>
</div>

<!-- Actions -->
<div class="d-flex gap-2 flex-wrap">
<a href="events.php?edit=<?= $ev['id'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
<a href="candidates.php?event=<?= $ev['id'] ?>" class="btn btn-outline-warning btn-sm">🧑‍💼 Kandidat</a>
<a href="events.php?toggle=<?= $ev['id'] ?>"
   class="btn btn-sm <?= $ev['status'] === 'active' ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
   onclick="return confirm('Ubah status event ini?')">
<?= $ev['status'] === 'active' ? '🔴 Tutup' : '🟢 Aktifkan' ?>
</a>
<a href="events.php?regen=<?= $ev['id'] ?>"
   class="btn btn-sm btn-outline-info"
   onclick="return confirm('Regenerate kode join? Peserta lama masih terdaftar, tapi kode lama tidak berlaku.')">
🔄 Regen Kode
</a>
<a href="events.php?delete=<?= $ev['id'] ?>"
   class="btn btn-sm btn-danger"
   onclick="return confirm('Hapus event ini dan semua datanya? Tindakan tidak dapat dibatalkan.')">
🗑️ Hapus
</a>
</div>

</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>

</div>
</div>

<footer class="text-center py-4 mt-5">
<p class="text-secondary">© 2026 TrustVote Secure E-Voting System — Admin Panel</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
