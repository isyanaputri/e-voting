<?php
session_start();
require_once "config/database.php";

// Jika sudah login, redirect ke events
if (isset($_SESSION['user_id'])) {
    header("Location: events/my_events.php");
    exit;
}

$siteTitle       = getSetting($pdo, 'election_title', 'TrustVote Platform');
$siteSubtitle    = getSetting($pdo, 'election_subtitle', 'Secure Multi-Event E-Voting Berbasis Kriptografi');
$siteDescription = getSetting($pdo, 'election_description', 'Gunakan hak suara Anda dengan bijak dan bertanggung jawab.');

$totalEvents = $pdo->query("SELECT COUNT(*) FROM events WHERE status='active'")->fetchColumn();
$totalUsers  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVotes  = $pdo->query("SELECT COUNT(*) FROM votes")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($siteTitle) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<style>
.hero-section {
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 60px 0;
}
.feature-icon {
    font-size: 40px;
    margin-bottom: 15px;
}
.stat-badge {
    background: rgba(250,204,21,0.1);
    border: 1px solid rgba(250,204,21,0.3);
    border-radius: 20px;
    padding: 20px 30px;
    text-align: center;
}
.stat-number {
    font-size: 42px;
    font-weight: 700;
    color: #facc15;
    line-height: 1;
}
.step-number {
    width: 40px;
    height: 40px;
    background: rgba(250,204,21,0.2);
    border: 2px solid rgba(250,204,21,0.5);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    color: #facc15;
    font-size: 18px;
    flex-shrink: 0;
}
.crypto-badge {
    background: rgba(96,165,250,0.1);
    border: 1px solid rgba(96,165,250,0.3);
    color: #93c5fd;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 13px;
    display: inline-block;
    margin: 4px;
}
</style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4">
<div class="container-fluid">
<a class="navbar-brand fw-bold" href="index.php">🗳️ TrustVote</a>
<div class="ms-auto d-flex gap-2">
<a href="auth/login.php" class="btn btn-outline-warning btn-sm">Login</a>
<a href="auth/register.php" class="btn-vote btn-sm px-3" style="display:inline-block; border-radius:10px; text-decoration:none; font-size:14px; padding:8px 16px; font-weight:600;">Daftar</a>
</div>
</div>
</nav>

<!-- Hero -->
<div class="hero-section">
<div class="container">
<div class="row align-items-center g-5">

<div class="col-lg-7">
<div class="mb-3">
<span class="crypto-badge">🔐 RSA Encryption</span>
<span class="crypto-badge">🔑 SHA-256 Hash</span>
<span class="crypto-badge">✍️ Digital Signature</span>
</div>
<h1 class="title mb-3" style="font-size: clamp(32px, 5vw, 56px); line-height: 1.2;">
🗳️ <?= htmlspecialchars($siteTitle) ?>
</h1>
<p class="subtitle mb-2" style="font-size: 18px;">
<?= htmlspecialchars($siteSubtitle) ?>
</p>
<p class="text-secondary mb-5">
<?= htmlspecialchars($siteDescription) ?>
</p>
<div class="d-flex gap-3 flex-wrap">
<a href="auth/register.php" class="btn-vote px-5"
   style="display:inline-block; text-decoration:none; font-size:16px; padding:14px 32px;">
🗳️ Mulai Voting
</a>
<a href="auth/login.php" class="btn btn-outline-warning px-4" style="padding:14px 28px;">
Login
</a>
</div>
</div>

<div class="col-lg-5">
<!-- Stats -->
<div class="row g-3 mb-4">
<div class="col-4">
<div class="stat-badge">
<div class="stat-number"><?= $totalEvents ?></div>
<small class="subtitle">Event Aktif</small>
</div>
</div>
<div class="col-4">
<div class="stat-badge">
<div class="stat-number"><?= $totalUsers ?></div>
<small class="subtitle">Pengguna</small>
</div>
</div>
<div class="col-4">
<div class="stat-badge">
<div class="stat-number"><?= $totalVotes ?></div>
<small class="subtitle">Total Vote</small>
</div>
</div>
</div>

<!-- Fitur utama -->
<div class="glass-card p-4">
<h5 class="fw-bold mb-4">⚡ Cara Kerja</h5>
<div class="d-flex gap-3 align-items-start mb-3">
<div class="step-number">1</div>
<div>
<p class="mb-0 fw-semibold">Register & Login</p>
<small class="subtitle">Daftar dengan NIM dan nama lengkap</small>
</div>
</div>
<div class="d-flex gap-3 align-items-start mb-3">
<div class="step-number">2</div>
<div>
<p class="mb-0 fw-semibold">Join Event dengan Kode</p>
<small class="subtitle">Masukkan kode unik dari penyelenggara</small>
</div>
</div>
<div class="d-flex gap-3 align-items-start mb-3">
<div class="step-number">3</div>
<div>
<p class="mb-0 fw-semibold">Pilih Kandidat</p>
<small class="subtitle">Vote dienkripsi dengan RSA & SHA-256</small>
</div>
</div>
<div class="d-flex gap-3 align-items-start">
<div class="step-number">4</div>
<div>
<p class="mb-0 fw-semibold">Lihat Hasil Realtime</p>
<small class="subtitle">Verifikasi integritas vote kapan saja</small>
</div>
</div>
</div>
</div>

</div>

<!-- Features -->
<div class="row g-4 mt-2">
<div class="col-md-4">
<div class="glass-card p-4 text-center h-100">
<div class="feature-icon">🔐</div>
<h5 class="fw-bold">RSA Encryption</h5>
<p class="subtitle small">Setiap vote dienkripsi dengan RSA sebelum disimpan ke database. Isi vote tidak dapat dibaca secara langsung.</p>
</div>
</div>
<div class="col-md-4">
<div class="glass-card p-4 text-center h-100">
<div class="feature-icon">🔑</div>
<h5 class="fw-bold">SHA-256 Hashing</h5>
<p class="subtitle small">Hash unik setiap vote menjamin integritas data. Manipulasi sekecil apapun akan terdeteksi oleh sistem.</p>
</div>
</div>
<div class="col-md-4">
<div class="glass-card p-4 text-center h-100">
<div class="feature-icon">✍️</div>
<h5 class="fw-bold">Digital Signature</h5>
<p class="subtitle small">Tanda tangan digital memverifikasi keaslian setiap vote, memastikan data tidak dimodifikasi pihak manapun.</p>
</div>
</div>
<div class="col-md-4">
<div class="glass-card p-4 text-center h-100">
<div class="feature-icon">🗂️</div>
<h5 class="fw-bold">Multi-Event System</h5>
<p class="subtitle small">Satu platform untuk banyak event voting. Setiap event punya kode unik, kandidat sendiri, dan hasil terpisah.</p>
</div>
</div>
<div class="col-md-4">
<div class="glass-card p-4 text-center h-100">
<div class="feature-icon">🛡️</div>
<h5 class="fw-bold">Anti Double Voting</h5>
<p class="subtitle small">Sistem mencegah pengguna yang sama memilih lebih dari satu kali di setiap event yang sama.</p>
</div>
</div>
<div class="col-md-4">
<div class="glass-card p-4 text-center h-100">
<div class="feature-icon">📊</div>
<h5 class="fw-bold">Realtime Analytics</h5>
<p class="subtitle small">Lihat hasil voting secara realtime dengan grafik interaktif dan statistik partisipasi yang detail.</p>
</div>
</div>
</div>

</div>
</div>

<!-- Footer -->
<footer class="text-center py-5">
<p class="subtitle small">© 2026 TrustVote Secure E-Voting System</p>
<p class="text-secondary" style="font-size:12px;">Dibangun dengan PHP, MySQL, RSA, SHA-256 & Digital Signature</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
