<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once "../config/admin_config.php";

if (isset($_SESSION['admin_logged_in'])) {
    header("Location: dashboard.php");
    exit;
}

$message = "";

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in']  = true;
        $_SESSION['admin_username']   = $username;
        header("Location: dashboard.php");
        exit;
    } else {
        $message = "Username atau password admin salah.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Login - TrustVote</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="container d-flex justify-content-center align-items-center vh-100">
<div class="glass-card p-5" style="width:430px;">

<h2 class="title text-center mb-2">🛡️ Admin Panel</h2>
<p class="subtitle text-center mb-4">TrustVote Secure E-Voting</p>

<?php if ($message !== ""): ?>
<div class="alert alert-danger"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">

<input type="text" name="username" class="form-control mb-3"
       placeholder="Username Admin" required
       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">

<input type="password" name="password" class="form-control mb-4"
       placeholder="Password" required>

<button name="login" class="btn-vote w-100">🔐 Login Admin</button>

</form>

<div class="text-center mt-4">
<a href="../auth/login.php" class="text-warning small">← Kembali ke halaman user</a>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
