<?php

declare(strict_types=1);

// Déchiffrement AES-256-GCM sécurisé
function decrypt(string $encrypted, string $key, string $iv, string $tag): string
{
    $cipherText = base64_decode($encrypted, true);
    $iv = base64_decode($iv, true);
    $tag = base64_decode($tag, true);

    if ($cipherText === false || $iv === false || $tag === false) {
        throw new RuntimeException('Erreur de décodage Base64.');
    }

    $decrypted = openssl_decrypt($cipherText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

    if ($decrypted === false) {
        throw new RuntimeException('Erreur de déchiffrement AES-256-GCM.');
    }

    return $decrypted;
}

// Chargement et validation du fichier .env
$envPath = __DIR__ . '/.env';

if (!is_readable($envPath)) {
    http_response_code(500);
    exit('Erreur serveur : fichier .env inaccessible.');
}

$dotenv = parse_ini_file($envPath, false, INI_SCANNER_RAW);

if (!is_array($dotenv)) {
    http_response_code(500);
    exit('Erreur serveur : chargement des variables .env échoué.');
}

// Liste des variables obligatoires
$requiredKeys = [
    'DB_HOST_ENC',
    'DB_HOST_IV',
    'DB_HOST_TAG',
    'DB_NAME_ENC',
    'DB_NAME_IV',
    'DB_NAME_TAG',
    'DB_USER_ENC',
    'DB_USER_IV',
    'DB_USER_TAG',
    'DB_PASS_ENC',
    'DB_PASS_IV',
    'DB_PASS_TAG',
];

// Récupérer les clés de déchiffrement depuis les variables d'environnement
$dbHostKey = getenv('DB_HOST_KEY') ?: exit("Clé DB_HOST_KEY manquante");
$dbNameKey = getenv('DB_NAME_KEY') ?: exit("Clé DB_NAME_KEY manquante");
$dbUserKey = getenv('DB_USER_KEY') ?: exit("Clé DB_USER_KEY manquante");
$dbPassKey = getenv('DB_PASS_KEY') ?: exit("Clé DB_PASS_KEY manquante");

foreach ($requiredKeys as $key) {
    if (empty($dotenv[$key])) {
        http_response_code(500);
        exit("Erreur serveur : variable d'environnement manquante ou vide ($key).");
    }
}

// Déchiffrement sécurisé des paramètres de connexion
try {
    $config = [
        'db_host' => decrypt($dotenv['DB_HOST_ENC'], $dbHostKey, $dotenv['DB_HOST_IV'], $dotenv['DB_HOST_TAG']),
        'db_name' => decrypt($dotenv['DB_NAME_ENC'], $dbNameKey, $dotenv['DB_NAME_IV'], $dotenv['DB_NAME_TAG']),
        'db_user' => decrypt($dotenv['DB_USER_ENC'], $dbUserKey, $dotenv['DB_USER_IV'], $dotenv['DB_USER_TAG']),
        'db_pass' => decrypt($dotenv['DB_PASS_ENC'], $dbPassKey, $dotenv['DB_PASS_IV'], $dotenv['DB_PASS_TAG']),
        'ip_bann' => $dotenv['IP_BANN'] ?? null,
        'api_key_verif' => $dotenv['API_KEY_IP_VERIF'] ?? null,
        'pass_admin' => isset($dotenv['PASS_ADMIN'])
            ? decrypt($dotenv['PASS_ADMIN'], $dbPassKey, $dotenv['DB_PASS_IV'], $dotenv['DB_PASS_TAG'])
            : null,
    ];
} catch (Throwable $e) {
    http_response_code(500);
    exit('Erreur serveur : ' . $e->getMessage());
}

// Configuration prête pour la production
// Par exemple :
// $pdo = new PDO("mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4", $config['db_user'], $config['db_pass']);