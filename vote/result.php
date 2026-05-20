<?php
session_start();
require "../config/database.php";
require "../config/crypto.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$privateKey = getPrivateKey();
$votes      = $pdo->query("SELECT * FROM votes")->fetchAll();
$result     = [];

foreach ($votes as $vote) {
    $decrypted = decryptVote($vote['encrypted_vote'], $privateKey);
    $data      = json_decode($decrypted, true);
    if (isset($data['candidate'])) {
        $name = $data['candidate'];
        $result[$name] = ($result[$name] ?? 0) + 1;
    }
}

$totalVotes = array_sum($result);

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

$electionTitle = getSettingValue($pdo, 'election_title', 'Pemilihan Umum');
?>
<!DOCTYPE html>
<html>
<head>
<title>Hasil Voting - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-5">

<div class="text-center mb-5">
<h1 class="title">📊 Hasil Voting Realtime</h1>
<p class="subtitle"><?= htmlspecialchars($electionTitle) ?> — <?= $totalVotes ?> suara masuk</p>
</div>

<?php if (isset($_GET['voted'])): ?>
<div class="alert alert-success text-center mb-4">
✅ Vote Anda berhasil disimpan & dienkripsi dengan RSA + SHA-256!
</div>
<?php endif; ?>

<!-- Chart -->
<div class="glass-card p-5 mb-4">
<canvas id="voteChart" style="max-height:350px;"></canvas>
</div>

<!-- Score Cards -->
<div class="row g-4 mb-4">
<?php
arsort($result);
$rank = 1;
foreach ($result as $name => $total):
    $pct = $totalVotes > 0 ? round($total / $totalVotes * 100, 1) : 0;
?>
<div class="col-md-4">
<div class="glass-card p-4 text-center">
<?php if ($rank === 1 && $total > 0): ?><div style="font-size:24px;">🏆</div><?php endif; ?>
<h5 class="fw-bold mt-1"><?= htmlspecialchars($name) ?></h5>
<div style="font-size:40px; font-weight:700; color:#facc15;"><?= $total ?></div>
<p class="subtitle">suara (<?= $pct ?>%)</p>
<div class="progress mt-2" style="height:8px; background:rgba(255,255,255,0.1);">
<div class="progress-bar bg-warning" style="width:<?= $pct ?>%;"></div>
</div>
</div>
</div>
<?php $rank++; endforeach; ?>
</div>

<!-- Verify link -->
<div class="glass-card p-4 text-center">
<p class="subtitle mb-3">🔍 Ingin memverifikasi integritas vote?</p>
<a href="verify.php" class="btn btn-outline-warning me-3">Verifikasi Vote</a>
<a href="dashboard.php" class="btn btn-warning">Kembali ke Dashboard</a>
</div>

</div>

<?php include "../includes/footer.php"; ?>

<script src="../assets/js/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
