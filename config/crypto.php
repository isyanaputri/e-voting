<?php

function getPublicKey() {

    return file_get_contents(
        __DIR__ . "/keys/public.pem"
    );
}

function getPrivateKey() {

    return file_get_contents(
        __DIR__ . "/keys/private.pem"
    );
}

function createHash($data) {

    return hash(
        "sha256",
        $data
    );
}

function encryptVote($data, $publicKey) {

    openssl_public_encrypt(
        $data,
        $encrypted,
        $publicKey
    );

    return base64_encode($encrypted);
}

function decryptVote($encryptedData, $privateKey) {

    openssl_private_decrypt(
        base64_decode($encryptedData),
        $decrypted,
        $privateKey
    );

    return $decrypted;
}

function createSignature($data, $privateKey) {

    openssl_sign(
        $data,
        $signature,
        $privateKey,
        OPENSSL_ALGO_SHA256
    );

    return base64_encode($signature);
}

function verifySignature(
    $data,
    $signature,
    $publicKey
) {

    return openssl_verify(
        $data,
        base64_decode($signature),
        $publicKey,
        OPENSSL_ALGO_SHA256
    );
}

?>