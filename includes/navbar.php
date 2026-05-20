<nav class="navbar navbar-expand-lg navbar-dark navbar-custom px-4">
<div class="container-fluid">

<a class="navbar-brand fw-bold" href="../events/my_events.php">
🗳️ TrustVote
</a>

<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
<span class="navbar-toggler-icon"></span>
</button>

<div class="collapse navbar-collapse" id="mainNav">
<ul class="navbar-nav me-auto">
<li class="nav-item">
<a class="nav-link" href="../events/my_events.php">🏠 Event Saya</a>
</li>
<li class="nav-item">
<a class="nav-link" href="../events/join.php">🔑 Join Event</a>
</li>
</ul>

<div class="d-flex align-items-center">
<span class="me-3 text-light">👤 <?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
<a href="../auth/logout.php" class="btn btn-outline-warning btn-sm">Logout</a>
</div>
</div>

</div>
</nav>
