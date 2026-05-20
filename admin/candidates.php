<?php
require_once "includes/admin_auth.php";
require_once "../config/database.php";

$message = "";
$messageType = "success";

// Filter by event
$filterEventId = (int)($_GET['event'] ?? 0);

// Get events list for dropdown
$eventsList = $pdo->query("SELECT * FROM events ORDER BY id DESC")->fetchAll();

// ── DELETE ──────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Delete photo if custom
    $c = $pdo->prepare("SELECT foto FROM candidates WHERE id=?");
    $c->execute([$id]);
    $foto = $c->fetchColumn();
    if ($foto && strpos($foto, 'kandidat_') !== false) {
        @unlink(__DIR__ . '/../' . $foto);
    }
    $pdo->prepare("DELETE FROM candidates WHERE id=?")->execute([$id]);
    $message = "Kandidat berhasil dihapus.";
    $messageType = "warning";
}

// ── SAVE (ADD / EDIT) ──────────────────────────────────────
if (isset($_POST['save_candidate'])) {
    $id        = (int)($_POST['id'] ?? 0);
    $event_id  = (int)$_POST['event_id'];
    $nama      = trim($_POST['nama']);
    $deskripsi = trim($_POST['deskripsi']);
    $fotoPath  = $_POST['existing_foto'] ?? '';

    if ($event_id === 0) {
        $message = "Pilih event terlebih dahulu.";
        $messageType = "danger";
    } elseif (strlen($nama) < 2) {
        $message = "Nama kandidat terlalu pendek.";
        $messageType = "danger";
    } else {
        // Handle upload
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg','jpeg','png','webp','gif'];
            if (!in_array($ext, $allowed)) {
                $message = "Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.";
                $messageType = "danger";
            } else {
                $filename  = 'kandidat_' . time() . '_' . rand(100,999) . '.' . $ext;
                $uploadDir = __DIR__ . '/../assets/img/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $filename)) {
                    $fotoPath = 'assets/img/' . $filename;
                } else {
                    $message = "Gagal upload foto.";
                    $messageType = "danger";
                }
            }
        }

        if ($message === "") {
            if ($id > 0) {
                $pdo->prepare("UPDATE candidates SET event_id=?, nama=?, deskripsi=?, foto=? WHERE id=?")
                    ->execute([$event_id, $nama, $deskripsi, $fotoPath, $id]);
                $message = "Kandidat berhasil diperbarui.";
            } else {
                $pdo->prepare("INSERT INTO candidates (event_id, nama, foto, deskripsi) VALUES (?,?,?,?)")
                    ->execute([$event_id, $nama, $fotoPath, $deskripsi]);
                $message = "Kandidat berhasil ditambahkan.";
            }
            if ($filterEventId === 0) $filterEventId = $event_id;
        }
    }
}

// ── FETCH DATA ──────────────────────────────────────
$whereClause = $filterEventId > 0 ? "WHERE c.event_id = $filterEventId" : "";
$candidates = $pdo->query("
    SELECT c.*, e.title as event_title
    FROM candidates c
    LEFT JOIN events e ON c.event_id = e.id
    $whereClause
    ORDER BY c.event_id ASC, c.id ASC
")->fetchAll();

// Edit mode
$editCandidate = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE id=?");
    $stmt->execute([(int)$_GET['edit']]);
    $editCandidate = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Kelola Kandidat - TrustVote Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.table-dark-glass { background: transparent; --bs-table-bg: transparent; --bs-table-striped-bg: transparent; --bs-table-hover-bg: transparent; }
.table-dark-glass thead tr { background: rgba(255,255,255,0.08); }
.table-dark-glass tbody tr { background: rgba(255,255,255,0.04); }
.table-dark-glass tbody tr:hover { background: rgba(255,255,255,0.1); }
.table-dark-glass td, .table-dark-glass th { color: #f1f5f9; border-color:rgba(255,255,255,0.1); vertical-align:middle; }
.candidate-thumb { width:60px; height:60px; object-fit:cover; border-radius:10px; }
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
.foto-preview { width: 100%; max-height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 8px; }
</style>
</head>
<body>

<?php include "includes/admin_navbar.php"; ?>

<div class="container py-5">

<h1 class="title mb-2">🧑‍💼 Kelola Kandidat</h1>
<p class="subtitle mb-4">Tambah, edit, atau hapus kandidat per event</p>

<?php if ($message !== ""): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
<?= $message ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filter by event -->
<div class="glass-card p-3 mb-4 d-flex align-items-center gap-3 flex-wrap">
<span class="subtitle small">Filter Event:</span>
<a href="candidates.php" class="btn btn-sm <?= $filterEventId === 0 ? 'btn-warning' : 'btn-outline-warning' ?>">Semua</a>
<?php foreach ($eventsList as $ev): ?>
<a href="candidates.php?event=<?= $ev['id'] ?>"
   class="btn btn-sm <?= $filterEventId === $ev['id'] ? 'btn-warning' : 'btn-outline-warning' ?>">
<?= htmlspecialchars($ev['title']) ?>
</a>
<?php endforeach; ?>
</div>

<div class="row g-4">

<!-- Form Tambah/Edit -->
<div class="col-lg-4">
<div class="glass-card p-4">
<h4 class="mb-4"><?= $editCandidate ? '✏️ Edit Kandidat' : '➕ Tambah Kandidat' ?></h4>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?= htmlspecialchars($editCandidate['id'] ?? '') ?>">
<input type="hidden" name="existing_foto" value="<?= htmlspecialchars($editCandidate['foto'] ?? '') ?>">

<div class="mb-3">
<label class="form-label">Event <span class="text-warning">*</span></label>
<select name="event_id" class="form-select" required>
<option value="">-- Pilih Event --</option>
<?php foreach ($eventsList as $ev): ?>
<option value="<?= $ev['id'] ?>"
    <?= ((int)($editCandidate['event_id'] ?? $filterEventId)) === (int)$ev['id'] ? 'selected' : '' ?>>
    <?= htmlspecialchars($ev['title']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="mb-3">
<label class="form-label">Nama Kandidat / Pasangan <span class="text-warning">*</span></label>
<input type="text" name="nama" class="form-control"
       value="<?= htmlspecialchars($editCandidate['nama'] ?? '') ?>"
       placeholder="Cth: Anies Baswedan & Muhaimin" required>
</div>

<div class="mb-3">
<label class="form-label">Deskripsi</label>
<textarea name="deskripsi" class="form-control" rows="3"
          placeholder="Pasangan calon nomor urut ..."><?= htmlspecialchars($editCandidate['deskripsi'] ?? '') ?></textarea>
</div>

<div class="mb-4">
<label class="form-label">Foto Kandidat</label>
<?php if (!empty($editCandidate['foto'])): ?>
<img src="../<?= htmlspecialchars($editCandidate['foto']) ?>" class="foto-preview d-block">
<small class="text-secondary d-block mb-2">Biarkan kosong jika tidak ingin mengganti foto</small>
<?php endif; ?>
<input type="file" name="foto" class="form-control" accept="image/*"
       id="fotoInput"
       <?= $editCandidate ? '' : '' ?>>
<small class="text-secondary">Format: JPG, PNG, WEBP (max ~5MB)</small>
<div id="fotoPreviewWrap" class="mt-2" style="display:none;">
<img id="fotoPreviewImg" class="foto-preview" src="#">
</div>
</div>

<div class="d-flex gap-2">
<button name="save_candidate" class="btn-vote flex-fill">
<?= $editCandidate ? '💾 Simpan Perubahan' : '➕ Tambah Kandidat' ?>
</button>
<?php if ($editCandidate): ?>
<a href="candidates.php<?= $filterEventId ? '?event='.$filterEventId : '' ?>" class="btn btn-outline-secondary">Batal</a>
<?php endif; ?>
</div>

</form>
</div>
</div>

<!-- Daftar Kandidat -->
<div class="col-lg-8">
<div class="glass-card p-4">
<h4 class="mb-4">📋 Daftar Kandidat (<?= count($candidates) ?>)</h4>

<?php if (count($candidates) > 0): ?>
<div class="table-responsive">
<table class="table table-dark-glass">
<thead>
<tr><th>No</th><th>Foto</th><th>Nama</th><th>Event</th><th>Deskripsi</th><th>Aksi</th></tr>
</thead>
<tbody>
<?php foreach ($candidates as $i => $c): ?>
<tr>
<td><?= $i + 1 ?></td>
<td>
<?php if (!empty($c['foto'])): ?>
<img src="../<?= htmlspecialchars($c['foto']) ?>" class="candidate-thumb">
<?php else: ?>
<span class="text-secondary">-</span>
<?php endif; ?>
</td>
<td class="fw-bold"><?= htmlspecialchars($c['nama']) ?></td>
<td><small style="color:#facc15;"><?= htmlspecialchars($c['event_title'] ?? '-') ?></small></td>
<td><small><?= htmlspecialchars($c['deskripsi']) ?></small></td>
<td>
<div class="d-flex gap-1 flex-wrap">
<a href="candidates.php?edit=<?= $c['id'] ?><?= $filterEventId ? '&event='.$filterEventId : '' ?>" class="btn btn-warning btn-sm">✏️</a>
<a href="candidates.php?delete=<?= $c['id'] ?><?= $filterEventId ? '&event='.$filterEventId : '' ?>"
   class="btn btn-danger btn-sm"
   onclick="return confirm('Hapus kandidat ini?')">🗑️</a>
</div>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php else: ?>
<p class="subtitle text-center py-4">
<?= $filterEventId > 0 ? "Belum ada kandidat di event ini." : "Belum ada kandidat." ?>
</p>
<?php endif; ?>
</div>
</div>

</div>
</div>

<footer class="text-center py-4 mt-5">
<p class="text-secondary">© 2026 TrustVote Secure E-Voting System — Admin Panel</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Live foto preview
document.getElementById('fotoInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            document.getElementById('fotoPreviewImg').src = ev.target.result;
            document.getElementById('fotoPreviewWrap').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});
</script>
</body>
</html>
