<?php
session_start();
require "../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: ../events/my_events.php");
    exit;
}

$message = "";
$messageType = "info";

if (isset($_POST['register'])) {
    $nim      = trim($_POST['nim']);
    $nama     = trim($_POST['nama']);
    $password = $_POST['password'];

    if (strlen($nim) < 5) {
        $message = "NIM minimal 5 karakter.";
        $messageType = "danger";
    } elseif (strlen($nama) < 3) {
        $message = "Nama terlalu pendek.";
        $messageType = "danger";
    } elseif (strlen($password) < 6) {
        $message = "Password minimal 6 karakter.";
        $messageType = "danger";
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE nim=?");
        $check->execute([$nim]);

        if ($check->rowCount() > 0) {
            $message = "NIM sudah terdaftar. Silakan login.";
            $messageType = "warning";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (nim, nama, password_hash) VALUES (?,?,?)")
                ->execute([$nim, $nama, $hash]);
            $message = "✅ Registrasi berhasil! Silakan login.";
            $messageType = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Register - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
<div class="glass-card p-5" style="width:450px;">

<h2 class="title mb-2 text-center">🗳️ TrustVote</h2>
<p class="subtitle text-center mb-4">Daftar Akun Baru</p>

<?php if ($message !== ""): ?>
<div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
<?= htmlspecialchars($message) ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<form method="POST">

<input type="text" name="nim" class="form-control mb-3"
       placeholder="NIM" required
       value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">

<input type="text" name="nama" class="form-control mb-3"
       placeholder="Nama Lengkap" required
       value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">

<input type="password" name="password" class="form-control mb-4"
       placeholder="Password (min. 6 karakter)" required>

<button name="register" class="btn-vote w-100">Register</button>

</form>

<div class="text-center mt-4">
<a href="login.php" class="text-warning">Sudah punya akun? Login</a>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
