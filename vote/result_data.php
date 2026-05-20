<?php

require "../config/database.php";
require "../config/crypto.php";

header('Content-Type: application/json');

$privateKey = getPrivateKey();

$candidateQuery = $pdo->query(
    "SELECT id, nama FROM candidates ORDER BY id ASC"
);

$candidates = $candidateQuery->fetchAll();

$result = [];

foreach($candidates as $candidate){

    $result[$candidate['nama']] = 0;
}

$voteQuery = $pdo->query(
    "SELECT * FROM votes ORDER BY id ASC"
);

$votes = $voteQuery->fetchAll();

foreach($votes as $vote){

    $decryptedVote = decryptVote(

        $vote['encrypted_vote'],

        $privateKey
    );

    $data = json_decode(
        $decryptedVote,
        true
    );

    if(
        isset($data['candidate'])
    ){

        $candidateName = $data['candidate'];

        if(
            isset($result[$candidateName])
        ){

            $result[$candidateName]++;
        }
    }
}

$output = [];

foreach($result as $name => $total){

    $output[] = [

        "name" => $name,

        "total" => $total
    ];
}

echo json_encode($output);