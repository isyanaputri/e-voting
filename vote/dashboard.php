<?php
session_start();
require "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$candidates = $pdo->query("SELECT * FROM candidates ORDER BY id ASC")->fetchAll();

$user_id  = $_SESSION['user_id'];
$checkVote = $pdo->prepare("SELECT has_voted FROM users WHERE id=?");
$checkVote->execute([$user_id]);
$user = $checkVote->fetch();

// Helper ambil setting dengan fallback aman
function getSettingValue($pdo, $key, $default = "") {
    try {
        $q = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
        $q->execute([$key]);
        $val = $q->fetchColumn();
        return ($val !== false && $val !== '') ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

$electionTitle    = getSettingValue($pdo, 'election_title',    'Pemilihan Umum');
$electionSubtitle = getSettingValue($pdo, 'election_subtitle', 'Secure E-Voting Berbasis Kriptografi');
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard Voting - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-5">

<div class="text-center mb-5">
<h1 class="title"><?= htmlspecialchars($electionTitle) ?></h1>
<p class="subtitle"><?= htmlspecialchars($electionSubtitle) ?></p>
</div>

<?php if ($user['has_voted'] == 1): ?>
<div class="alert alert-success text-center">
<h4>✅ Anda sudah melakukan voting</h4>
<a href="result.php" class="btn btn-warning mt-3">Lihat Hasil Voting</a>
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
