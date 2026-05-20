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

// Get event
$eventQ = $pdo->prepare("SELECT * FROM events WHERE id=?");
$eventQ->execute([$event_id]);
$event = $eventQ->fetch();

if (!$event) {
    header("Location: my_events.php");
    exit;
}

// Check user is participant
$partQ = $pdo->prepare("SELECT * FROM event_participants WHERE event_id=? AND user_id=?");
$partQ->execute([$event_id, $user_id]);
if ($partQ->rowCount() === 0) {
    header("Location: ../events/join.php?err=not_joined");
    exit;
}

// Get candidates for this event (to show all, even with 0 votes)
$candidateList = $pdo->prepare("SELECT * FROM candidates WHERE event_id=? ORDER BY id ASC");
$candidateList->execute([$event_id]);
$candidateList = $candidateList->fetchAll();

// Decrypt votes and tally
$privateKey = getPrivateKey();
$votes = $pdo->prepare("SELECT * FROM votes WHERE event_id=?");
$votes->execute([$event_id]);
$votes = $votes->fetchAll();

$result = [];
// Initialize with 0 for each candidate
foreach ($candidateList as $c) {
    $result[$c['nama']] = 0;
}

foreach ($votes as $vote) {
    $decrypted = decryptVote($vote['encrypted_vote'], $privateKey);
    $data      = json_decode($decrypted, true);
    if (isset($data['candidate']) && isset($result[$data['candidate']])) {
        $result[$data['candidate']]++;
    }
}

$totalVotes     = array_sum($result);
$totalParticipants = $pdo->prepare("SELECT COUNT(*) FROM event_participants WHERE event_id=?");
$totalParticipants->execute([$event_id]);
$totalParticipants = $totalParticipants->fetchColumn();

// Sort for display
arsort($result);

$chartLabels = json_encode(array_keys($result));
$chartData   = json_encode(array_values($result));
?>
<!DOCTYPE html>
<html>
<head>
<title>Hasil Voting - <?= htmlspecialchars($event['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-5">

<div class="text-center mb-2">
<a href="my_events.php" class="text-warning small">← Event Saya</a>
</div>

<div class="text-center mb-5">
<h1 class="title">📊 Hasil Voting Realtime</h1>
<p class="subtitle"><?= htmlspecialchars($event['title']) ?></p>
</div>

<?php if (isset($_GET['voted'])): ?>
<div class="alert alert-success text-center mb-4">
✅ Vote Anda berhasil disimpan & dienkripsi dengan RSA + SHA-256!
</div>
<?php endif; ?>

<!-- Stats row -->
<div class="row g-3 mb-4">
<div class="col-md-4">
<div class="glass-card p-4 text-center">
<div style="font-size:36px; font-weight:700; color:#facc15;"><?= $totalVotes ?></div>
<p class="subtitle small">Suara Masuk</p>
</div>
</div>
<div class="col-md-4">
<div class="glass-card p-4 text-center">
<div style="font-size:36px; font-weight:700; color:#facc15;"><?= $totalParticipants ?></div>
<p class="subtitle small">Peserta Terdaftar</p>
</div>
</div>
<div class="col-md-4">
<div class="glass-card p-4 text-center">
<div style="font-size:36px; font-weight:700; color:#facc15;">
<?= $totalParticipants > 0 ? round($totalVotes / $totalParticipants * 100, 1) : 0 ?>%
</div>
<p class="subtitle small">Tingkat Partisipasi</p>
</div>
</div>
</div>

<!-- Chart -->
<div class="glass-card p-5 mb-4">
<canvas id="voteChart" style="max-height:350px;"></canvas>
</div>

<!-- Score Cards -->
<div class="row g-4 mb-4">
<?php
$rank = 1;
foreach ($result as $name => $total):
    $pct = $totalVotes > 0 ? round($total / $totalVotes * 100, 1) : 0;
?>
<div class="col-md-4">
<div class="glass-card p-4 text-center">
<?php if ($rank === 1 && $total > 0): ?><div style="font-size:28px;">🏆</div><?php endif; ?>
<h5 class="fw-bold mt-1"><?= htmlspecialchars($name) ?></h5>
<div style="font-size:42px; font-weight:700; color:#facc15;"><?= $total ?></div>
<p class="subtitle">suara (<?= $pct ?>%)</p>
<div class="progress mt-2" style="height:8px; background:rgba(255,255,255,0.1);">
<div class="progress-bar bg-warning" style="width:<?= $pct ?>%;"></div>
</div>
</div>
</div>
<?php $rank++; endforeach; ?>
</div>

<!-- Actions -->
<div class="glass-card p-4 text-center">
<p class="subtitle mb-3">🔍 Ingin memverifikasi integritas vote?</p>
<a href="verify.php?event=<?= $event_id ?>" class="btn btn-outline-warning me-3">Verifikasi Vote</a>
<a href="my_events.php" class="btn btn-warning">← Event Saya</a>
</div>

</div>

<?php include "../includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('voteChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [{
            label: 'Jumlah Suara',
            data: <?= $chartData ?>,
            backgroundColor: [
                'rgba(250,204,21,0.7)',
                'rgba(96,165,250,0.7)',
                'rgba(52,211,153,0.7)',
                'rgba(251,113,133,0.7)',
                'rgba(167,139,250,0.7)',
            ],
            borderColor: [
                'rgba(250,204,21,1)',
                'rgba(96,165,250,1)',
                'rgba(52,211,153,1)',
                'rgba(251,113,133,1)',
                'rgba(167,139,250,1)',
            ],
            borderWidth: 2,
            borderRadius: 10,
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
        },
        scales: {
            x: {
                ticks: { color: '#cbd5e1', font: { size: 13 } },
                grid: { color: 'rgba(255,255,255,0.07)' }
            },
            y: {
                ticks: { color: '#cbd5e1', stepSize: 1 },
                grid: { color: 'rgba(255,255,255,0.07)' },
                beginAtZero: true
            }
        }
    }
});
</script>
</body>
</html>
