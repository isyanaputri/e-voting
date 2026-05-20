<?php
session_start();
require "../config/database.php";
require "../config/crypto.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_POST['vote'])) {
    header("Location: dashboard.php");
    exit;
}

$user_id      = (int)$_SESSION['user_id'];
$candidate_id = (int)$_POST['candidate_id'];

// CHECK DOUBLE VOTING
$check = $pdo->prepare("SELECT has_voted FROM users WHERE id=?");
$check->execute([$user_id]);
$user = $check->fetch();

if ($user['has_voted'] == 1) {
    header("Location: dashboard.php?err=already_voted");
    exit;
}

// VALIDASI KANDIDAT ADA
$candidateQuery = $pdo->prepare("SELECT * FROM candidates WHERE id=?");
$candidateQuery->execute([$candidate_id]);
$candidate = $candidateQuery->fetch();

if (!$candidate) {
    header("Location: dashboard.php?err=invalid_candidate");
    exit;
}

// PAYLOAD DATA
$voteData = json_encode([
    "user_id"   => $user_id,
    "candidate" => $candidate['nama'],
    "timestamp" => date("Y-m-d H:i:s"),
]);

// SHA-256 HASH
$voteHash = createHash($voteData);

// RSA KEYS
$publicKey  = getPublicKey();
$privateKey = getPrivateKey();

// DIGITAL SIGNATURE
$signature = createSignature($voteHash, $privateKey);

// RSA ENCRYPTION
$encryptedVote = encryptVote($voteData, $publicKey);

// SAVE DATABASE
$insert = $pdo->prepare(
    "INSERT INTO votes (user_id, encrypted_vote, vote_hash, digital_signature)
     VALUES (?,?,?,?)"
);
$insert->execute([$user_id, $encryptedVote, $voteHash, $signature]);

// UPDATE STATUS
$pdo->prepare("UPDATE users SET has_voted=1 WHERE id=?")->execute([$user_id]);

header("Location: result.php?voted=1");
exit;
