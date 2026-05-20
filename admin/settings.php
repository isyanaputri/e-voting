<?php
require_once "includes/admin_auth.php";
require_once "../config/database.php";

$message = "";
$messageType = "success";

// Simpan settings
if (isset($_POST['save_settings'])) {
    $fields = [
        'election_title'       => trim($_POST['election_title']),
        'election_subtitle'    => trim($_POST['election_subtitle']),
        'election_description' => trim($_POST['election_description']),
    ];

    foreach ($fields as $key => $value) {
        $check = $pdo->prepare("SELECT id FROM settings WHERE setting_key=?");
        $check->execute([$key]);
        if ($check->rowCount() > 0) {
            $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?")->execute([$value, $key]);
        } else {
            $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?,?)")->execute([$key, $value]);
        }
    }
    $message = "✅ Pengaturan berhasil disimpan!";
}

// FIX: Reset semua vote — sekarang reset JUGA event_participants
if (isset($_POST['reset_all_votes']) && ($_POST['confirm_reset'] ?? '') === 'RESET') {
    // Hapus semua votes
    $pdo->exec("DELETE FROM votes");
    // FIX: Reset has_voted di event_participants (sistem baru)
    $pdo->exec("UPDATE event_participants SET has_voted=0");
    // Reset global users.has_voted juga
    $pdo->exec("UPDATE users SET has_voted=0");
    $message = "⚠️ Semua data vote berhasil direset!";
    $messageType = "warning";
}

// Reset satu event saja
if (isset($_POST['reset_event_votes']) && ($_POST['confirm_event_reset'] ?? '') === 'RESET') {
    $resetEventId = (int)$_POST['reset_event_id'];
    if ($resetEventId > 0) {
        $pdo->prepare("DELETE FROM votes WHERE event_id=?")->execute([$resetEventId]);
        $pdo->prepare("UPDATE event_participants SET has_voted=0 WHERE event_id=?")->execute([$resetEventId]);
        $message = "⚠️ Data vote untuk event tersebut berhasil direset!";
        $messageType = "warning";
    }
}

// Ambil settings saat ini
$allSettings = $pdo->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
$settings = [];
foreach ($allSettings as $s) {
    $settings[$s['setting_key']] = $s['setting_value'];
}
$defaults = [
    'election_title'       => 'TrustVote Platform',
    'election_subtitle'    => 'Secure Multi-Event E-Voting Berbasis Kriptografi',
    'election_description' => 'Gunakan hak suara Anda dengan bijak dan bertanggung jawab.',
];
foreach ($defaults as $k => $v) {
    if (!isset($settings[$k])) $settings[$k] = $v;
}

// Untuk form reset per-event
$eventsList = $pdo->query("SELECT id, title FROM events ORDER BY id DESC")->fetchAll();

// Stats ringkasan
$totalVotes  = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
?>
<!DOCTYPE html>
<html>
<head>
<title>Pengaturan - TrustVote Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.form-label { color:#cbd5e1; font-size:14px; }
.form-control, .form-select { background:rgba(255,255,255,0.1) !important; border:1px solid rgba(255,255,255,0.2) !important; color:white !important; }
.form-control::placeholder { color:#94a3b8; }
.form-control:focus, .form-select:focus { box-shadow:0 0 0 0.25rem rgba(250,204,21,0.25) !important; border-color:#facc15 !important; }
.form-select option { background: #1e293b; color: white; }
.nav-link.active { color:#facc15 !important; font-weight:600; }
.danger-zone { border: 1px solid rgba(239,68,68,0.4); background: rgba(239,68,68,0.05); border-radius: 25px; }
.warning-zone { border: 1px solid rgba(250,204,21,0.4); background: rgba(250,204,21,0.05); border-radius: 25px; }
</style>
</head>
<body>

<?php include "includes/admin_navbar.php"; ?>

<div class="container py-5">

<h1 class="title mb-2">⚙️ Pengaturan Platform</h1>
<p class="subtitle mb-4">Konfigurasi teks, judul, dan manajemen data pemilihan</p>

<?php if ($message !== ""): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
<?= $message ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

<!-- Setting Utama -->
<div class="col-lg-7">
<div class="glass-card p-4">
<h4 class="mb-4">📋 Informasi Platform</h4>

<form method="POST">

<div class="mb-3">
<label class="form-label">Nama Platform <span class="text-warning">*</span></label>
<input type="text" name="election_title" class="form-control"
       value="<?= htmlspecialchars($settings['election_title']) ?>"
       placeholder="Cth: TrustVote Platform" required>
<small class="text-secondary">Ditampilkan sebagai judul utama platform</small>
</div>

<div class="mb-3">
<label class="form-label">Tagline / Subjudul</label>
<input type="text" name="election_subtitle" class="form-control"
       value="<?= htmlspecialchars($settings['election_subtitle']) ?>"
       placeholder="Cth: Secure Multi-Event E-Voting Berbasis Kriptografi">
</div>

<div class="mb-4">
<label class="form-label">Deskripsi Tambahan</label>
<textarea name="election_description" class="form-control" rows="3"
          placeholder="Pesan atau deskripsi untuk pengguna..."><?= htmlspecialchars($settings['election_description']) ?></textarea>
</div>

<button name="save_settings" class="btn-vote w-100">💾 Simpan Pengaturan</button>

</form>
</div>
</div>

<!-- Preview & Admin Info -->
<div class="col-lg-5">
<div class="glass-card p-4 mb-4">
<h4 class="mb-3">👁️ Preview Tampilan</h4>
<div class="text-center py-3" style="border: 1px dashed rgba(255,255,255,0.1); border-radius:15px;">
<h3 class="fw-bold" id="preview-title"><?= htmlspecialchars($settings['election_title']) ?></h3>
<p class="subtitle" id="preview-subtitle"><?= htmlspecialchars($settings['election_subtitle']) ?></p>
<p class="text-secondary small" id="preview-desc"><?= htmlspecialchars($settings['election_description']) ?></p>
</div>
</div>

<div class="glass-card p-4 mb-4">
<h5 class="mb-3">📊 Statistik Data</h5>
<div class="d-flex justify-content-around text-center">
<div>
<div style="font-size:28px; font-weight:700; color:#facc15;"><?= $totalEvents ?></div>
<small class="subtitle">Total Event</small>
</div>
<div>
<div style="font-size:28px; font-weight:700; color:#facc15;"><?= $totalVotes ?></div>
<small class="subtitle">Total Vote</small>
</div>
</div>
</div>

<div class="glass-card p-4">
<h5 class="mb-3">🔑 Admin Credentials</h5>
<p class="subtitle small mb-2">Username saat ini:</p>
<code style="color:#facc15">admin</code>
<p class="subtitle small mt-3 mb-1">Untuk ganti password, edit file:</p>
<code style="font-size:11px; color:#94a3b8">config/admin_config.php</code>
<p class="text-secondary mt-2" style="font-size:12px;">
Generate hash baru:<br>
<code style="font-size:11px">password_hash('password_baru', PASSWORD_DEFAULT)</code>
</p>
</div>
</div>

<!-- Warning Zone: Reset per Event -->
<div class="col-12">
<div class="glass-card p-4 warning-zone">
<h4 class="mb-2" style="color:#facc15;">⚠️ Reset Vote Per Event</h4>
<p class="subtitle mb-4">Reset data vote untuk satu event tertentu saja. Peserta dapat voting kembali di event tersebut.</p>

<form method="POST" onsubmit="return confirm('Reset semua vote untuk event ini?\n\nPeserta dapat voting kembali di event tersebut.')">
<div class="row g-3 align-items-end">
<div class="col-md-5">
<label class="form-label">Pilih Event</label>
<select name="reset_event_id" class="form-select" required>
<option value="">-- Pilih Event --</option>
<?php foreach ($eventsList as $ev): ?>
<option value="<?= $ev['id'] ?>"><?= htmlspecialchars($ev['title']) ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="col-md-4">
<label class="form-label">Konfirmasi</label>
<input type="text" name="confirm_event_reset" class="form-control"
       placeholder="Ketik RESET" required>
</div>
<div class="col-md-3">
<button name="reset_event_votes" class="btn btn-warning w-100">
🔄 Reset Event Ini
</button>
</div>
</div>
</form>
</div>
</div>

<!-- Danger Zone: Reset Semua -->
<div class="col-12">
<div class="glass-card p-4 danger-zone">
<h4 class="text-danger mb-2">🚨 Danger Zone — Reset Semua Vote</h4>
<p class="subtitle mb-4">Hapus SEMUA data vote di semua event. Status has_voted semua peserta akan direset. Tindakan ini tidak dapat dibatalkan.</p>

<form method="POST" onsubmit="return confirm('PERINGATAN KERAS!\n\nSemua data vote di SEMUA event akan dihapus permanen dan status voting semua peserta akan direset.\n\nTindakan ini TIDAK dapat dibatalkan!\n\nLanjutkan?')">
<div class="row g-3 align-items-end">
<div class="col-md-6">
<label class="form-label text-danger">Konfirmasi dengan mengetik RESET</label>
<input type="text" name="confirm_reset" class="form-control"
       placeholder="Ketik: RESET" required>
</div>
<div class="col-md-4">
<button name="reset_all_votes" class="btn btn-danger w-100">
🗑️ Reset Semua Vote
</button>
</div>
</div>
</form>
</div>
</div>

</div>
</div>

<footer class="text-center py-4 mt-5">
<p class="text-secondary">© 2026 TrustVote Secure E-Voting System — Admin Panel</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelector('[name="election_title"]').addEventListener('input', function() {
    document.getElementById('preview-title').textContent = this.value || '(kosong)';
});
document.querySelector('[name="election_subtitle"]').addEventListener('input', function() {
    document.getElementById('preview-subtitle').textContent = this.value || '(kosong)';
});
document.querySelector('[name="election_description"]').addEventListener('input', function() {
    document.getElementById('preview-desc').textContent = this.value || '(kosong)';
});
</script>
</body>
</html>
