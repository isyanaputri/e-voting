<?php
session_start();
require "../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: ../events/my_events.php");
    exit;
}

$message = "";

if (isset($_POST['login'])) {
    $nim      = trim($_POST['nim']);
    $password = $_POST['password'];

    $query = $pdo->prepare("SELECT * FROM users WHERE nim=?");
    $query->execute([$nim]);
    $user = $query->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama']    = $user['nama'];
        $_SESSION['nim']     = $user['nim'];
        header("Location: ../events/my_events.php");
        exit;
    } else {
        $message = "NIM atau password salah. Silakan coba lagi.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Login - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
<div class="glass-card p-5" style="width:450px;">

<h2 class="title text-center mb-2">🗳️ TrustVote</h2>
<p class="subtitle text-center mb-4">Secure Multi-Event E-Voting System</p>

<?php if ($message !== ""): ?>
<div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">

<input type="text" name="nim" class="form-control mb-3"
       placeholder="NIM" required autocomplete="off"
       value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>">

<input type="password" name="password" class="form-control mb-4"
       placeholder="Password" required>

<button name="login" class="btn-vote w-100">Login</button>

</form>

<div class="text-center mt-4">
<a href="register.php" class="text-warning">Buat akun baru</a>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
