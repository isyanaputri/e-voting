<?php
$current = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));
?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4">
<div class="container-fluid">

<a class="navbar-brand fw-bold" href="<?= $currentDir === 'admin' ? 'dashboard.php' : '../admin/dashboard.php' ?>">
🛡️ TrustVote Admin
</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="adminNav">
<ul class="navbar-nav me-auto">

<li class="nav-item">
<a class="nav-link <?= $current=='dashboard.php'?'active':'' ?>" href="dashboard.php">
📊 Dashboard
</a>
</li>

<li class="nav-item">
<a class="nav-link <?= $current=='events.php'?'active':'' ?>" href="events.php">
🗂️ Kelola Event
</a>
</li>

<li class="nav-item">
<a class="nav-link <?= $current=='candidates.php'?'active':'' ?>" href="candidates.php">
🧑‍💼 Kandidat
</a>
</li>

<li class="nav-item">
<a class="nav-link <?= $current=='users.php'?'active':'' ?>" href="users.php">
👥 Pengguna
</a>
</li>

<li class="nav-item">
<a class="nav-link <?= $current=='votes.php'?'active':'' ?>" href="votes.php">
🗳️ Data Vote
</a>
</li>

<li class="nav-item">
<a class="nav-link <?= $current=='settings.php'?'active':'' ?>" href="settings.php">
⚙️ Pengaturan
</a>
</li>

</ul>

<div class="d-flex align-items-center">
<span class="me-3 text-warning fw-bold">
🔐 <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>
</span>
<a href="logout.php" class="btn btn-outline-warning btn-sm">Logout</a>
</div>
</div>

</div>
</nav>
