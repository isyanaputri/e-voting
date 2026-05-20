<?php
session_start();
require "../config/database.php";
require "../config/crypto.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_POST['vote'])) {
    header("Location: ../events/my_events.php");
    exit;
}

$user_id      = (int)$_SESSION['user_id'];
$candidate_id = (int)$_POST['candidate_id'];
$event_id     = (int)$_POST['event_id'];

// Verify event exists and is active
$eventQ = $pdo->prepare("SELECT * FROM events WHERE id=? AND status='active'");
$eventQ->execute([$event_id]);
$event = $eventQ->fetch();

if (!$event) {
    header("Location: ../events/my_events.php?err=invalid_event");
    exit;
}

// Check participant status (double vote guard via event_participants)
$participantQ = $pdo->prepare("SELECT * FROM event_participants WHERE event_id=? AND user_id=?");
$participantQ->execute([$event_id, $user_id]);
$participant = $participantQ->fetch();

if (!$participant) {
    header("Location: ../events/join.php?err=not_joined");
    exit;
}

if ($participant['has_voted'] == 1) {
    header("Location: ../events/vote.php?event=$event_id&err=already_voted");
    exit;
}

// Validate candidate belongs to this event
$candidateQ = $pdo->prepare("SELECT * FROM candidates WHERE id=? AND event_id=?");
$candidateQ->execute([$candidate_id, $event_id]);
$candidate = $candidateQ->fetch();

if (!$candidate) {
    header("Location: ../events/vote.php?event=$event_id&err=invalid_candidate");
    exit;
}

// Build vote payload
$voteData = json_encode([
    "event_id"    => $event_id,
    "event_title" => $event['title'],
    "user_id"     => $user_id,
    "candidate"   => $candidate['nama'],
    "timestamp"   => date("Y-m-d H:i:s"),
]);

// Cryptographic operations
$publicKey  = getPublicKey();
$privateKey = getPrivateKey();

$voteHash      = createHash($voteData);
$signature     = createSignature($voteHash, $privateKey);
$encryptedVote = encryptVote($voteData, $publicKey);

// Store vote
$insert = $pdo->prepare(
    "INSERT INTO votes (event_id, user_id, encrypted_vote, vote_hash, digital_signature)
     VALUES (?,?,?,?,?)"
);
$insert->execute([$event_id, $user_id, $encryptedVote, $voteHash, $signature]);

// FIX: Mark as voted in event_participants (per-event tracking)
$pdo->prepare("UPDATE event_participants SET has_voted=1 WHERE event_id=? AND user_id=?")
    ->execute([$event_id, $user_id]);

// FIX: Also sync global users.has_voted flag for backward compatibility
$pdo->prepare("UPDATE users SET has_voted=1 WHERE id=?")
    ->execute([$user_id]);

header("Location: ../events/result.php?event=$event_id&voted=1");
exit;
