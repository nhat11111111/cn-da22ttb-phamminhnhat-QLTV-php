<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// Provide basic DB config for local/dev usage. This intentionally exposes only the URI
// because this project is a local development demo. Do NOT expose in production.
$mongoUri = getenv('QLTV_MONGO_URI') ?: null;
if (!$mongoUri) {
    $candidateFile = __DIR__ . '/../../Database/mongo_uri.txt';
    if (file_exists($candidateFile)) {
        $read = trim(@file_get_contents($candidateFile));
        if ($read !== '') {
            $mongoUri = $read;
        }
    }
}

echo json_encode(['mongo_uri' => $mongoUri]);
