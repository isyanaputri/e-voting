<?php
session_start();
require "../config/database.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = "danger";
$joinedEvent = null;

if (isset($_POST['join'])) {
    $code = strtoupper(trim($_POST['join_code']));

    if (strlen($code) < 3) {
        $message = "Kode event tidak valid.";
    } else {
        // Find event
        $stmt = $pdo->prepare("SELECT * FROM events WHERE join_code = ?");
        $stmt->execute([$code]);
        $event = $stmt->fetch();

        if (!$event) {
            $message = "❌ Kode event tidak ditemukan. Periksa kembali kode yang Anda masukkan.";
        } elseif ($event['status'] === 'closed') {
            $message = "❌ Event ini sudah ditutup dan tidak menerima peserta baru.";
        } elseif ($event['status'] === 'draft') {
            $message = "❌ Event ini belum dibuka oleh admin.";
        } else {
            // Check if already joined
            $check = $pdo->prepare("SELECT id FROM event_participants WHERE event_id=? AND user_id=?");
            $check->execute([$event['id'], $user_id]);

            if ($check->rowCount() > 0) {
                $message = "ℹ️ Anda sudah terdaftar di event ini.";
                $messageType = "info";
                $joinedEvent = $event;
            } else {
                // Join the event
                $pdo->prepare("INSERT INTO event_participants (event_id, user_id) VALUES (?,?)")
                    ->execute([$event['id'], $user_id]);
                $joinedEvent = $event;
                $message = "✅ Berhasil bergabung ke event <strong>" . htmlspecialchars($event['title']) . "</strong>!";
                $messageType = "success";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Join Event - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.code-input {
    font-family: monospace;
    font-size: 28px;
    font-weight: 700;
    letter-spacing: 8px;
    text-align: center;
    text-transform: uppercase;
    background: rgba(255,255,255,0.08) !important;
    border: 2px solid rgba(250,204,21,0.4) !important;
    color: #facc15 !important;
    border-radius: 15px !important;
    padding: 18px !important;
}
.code-input::placeholder { color: rgba(250,204,21,0.3) !important; letter-spacing: 4px; font-size: 18px; }
.code-input:focus { box-shadow: 0 0 0 0.25rem rgba(250,204,21,0.25) !important; border-color: #facc15 !important; }
</style>
</head>
<body>

<?php include "../includes/navbar.php"; ?>

<div class="container py-5">

<div class="row justify-content-center">
<div class="col-md-6 col-lg-5">

<div class="text-center mb-5">
<h1 class="title">🔑 Join Event</h1>
<p class="subtitle">Masukkan kode unik event untuk mulai voting</p>
</div>

<?php if ($message !== ""): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show mb-4">
<?= $message ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($joinedEvent): ?>
<!-- Success: show event actions -->
<div class="glass-card p-4 mb-4 text-center">
<div style="font-size:40px; margin-bottom:12px;">🎉</div>
<h4 class="fw-bold mb-1"><?= htmlspecialchars($joinedEvent['title']) ?></h4>
<p class="subtitle small mb-4"><?= htmlspecialchars($joinedEvent['description'] ?? '') ?></p>
<div class="d-flex gap-3 justify-content-center flex-wrap">
<a href="vote.php?event=<?= $joinedEvent['id'] ?>" class="btn-vote px-4" style="text-decoration:none; display:inline-block;">
🗳️ Vote Sekarang
</a>
<a href="my_events.php" class="btn btn-outline-warning">
🏠 Event Saya
</a>
</div>
</div>
<?php endif; ?>

<div class="glass-card p-5">
<h4 class="mb-4 text-center">Masukkan Kode Event</h4>

<form method="POST">
<input type="text" name="join_code" class="form-control code-input mb-4"
       placeholder="KODE01"
       maxlength="10"
       value="<?= htmlspecialchars(strtoupper($_POST['join_code'] ?? '')) ?>"
       required
       oninput="this.value = this.value.toUpperCase()">

<button name="join" class="btn-vote w-100" style="font-size:16px;">
🔑 Bergabung ke Event
</button>
</form>

<div class="text-center mt-4">
<p class="subtitle small">Kode event diberikan oleh penyelenggara atau admin voting.</p>
<a href="my_events.php" class="text-warning">← Kembali ke Event Saya</a>
</div>
</div>

<!-- Info cards -->
<div class="glass-card p-4 mt-4">
<h6 class="mb-3">📖 Cara Bergabung</h6>
<div class="d-flex gap-3 mb-3">
<div style="color:#facc15; font-size:20px;">1</div>
<div><p class="subtitle small mb-0">Dapatkan kode event dari penyelenggara atau admin</p></div>
</div>
<div class="d-flex gap-3 mb-3">
<div style="color:#facc15; font-size:20px;">2</div>
<div><p class="subtitle small mb-0">Masukkan kode di form di atas</p></div>
</div>
<div class="d-flex gap-3">
<div style="color:#facc15; font-size:20px;">3</div>
<div><p class="subtitle small mb-0">Pilih kandidat pilihan Anda — vote dienkripsi dengan RSA & SHA-256</p></div>
</div>
</div>

</div>
</div>

</div>

<?php include "../includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
