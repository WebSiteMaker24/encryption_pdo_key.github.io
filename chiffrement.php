<?php
session_start();

function afficherErreur($message) {
    session_unset();
    session_destroy();
    echo "<!DOCTYPE html>
    <html lang='fr'>
    <head><meta charset='UTF-8'><title>Erreur</title></head>
    <body><h2>$message</h2></body>
    </html>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $keys = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    $result = "# Fichier chiffré - Généré automatiquement\n\n";
    $result .= "# Ce fichier contient les clés et les informations nécessaires pour accéder à votre base de données.\n";
    $result .= "# Les valeurs sont chiffrées avec AES-256-GCM pour assurer la sécurité.\n";
    $result .= "# Voici comment utiliser ce fichier dans un fichier .env :\n";
    $result .= "# 1. Copiez les lignes de ce fichier dans votre fichier .env.\n";
    $result .= "# 2. Déchiffrez les valeurs dans votre application avec les clés associées.\n\n";

    $master_key = random_bytes(32);
    $master_key_base64 = base64_encode($master_key);

    foreach ($keys as $key) {
        $value = $_POST[$key] ?? '';

        $binary_key = random_bytes(32);
        $base64_key = base64_encode($binary_key);

        $iv = random_bytes(12);
        $tag = '';

        $encrypted = openssl_encrypt(
            $value,
            'aes-256-gcm',
            $binary_key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            afficherErreur("❌ Erreur de chiffrement pour $key.");
        }

        $result .= "# $key : valeur chiffrée\n";
        $result .= "{$key}_ENC=" . base64_encode($encrypted) . "\n";
        $result .= "# IV (initialisation vector) pour le chiffrement :\n";
        $result .= "{$key}_IV=" . base64_encode($iv) . "\n";
        $result .= "# TAG pour garantir l'intégrité des données chiffrées :\n";
        $result .= "{$key}_TAG=" . base64_encode($tag) . "\n";
        $result .= "# Clé utilisée pour le chiffrement (à garder secrète) :\n";
        $result .= "{$key}_KEY=" . $base64_key . "\n\n";
    }

    $result .= "\n# !!! NE JAMAIS METTRE LA CLÉ DE DÉCHIFFREMENT DANS VOTRE FICHIER .ENV !!!\n";
    $result .= "# La clé de déchiffrement principale (utilisée pour déchiffrer les valeurs chiffrées) :\n";
    $result .= "# MASTER_KEY=" . $master_key_base64 . "\n";

    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="env_chiffre.txt"');
    echo $result;
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔐 Chiffrement Clé DataBase Auto</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            max-width: 600px;
            margin: auto;
        }

        input,
        button {
            display: block;
            width: 100%;
            margin-top: 10px;
            padding: 10px;
            font-size: 1em;
        }

        label {
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>🔐 Générer automatiquement les clés et chiffrer</h2>
    <form method="POST" autocomplete="off">
        <?php
        foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            echo "<label for='$key'>$key (valeur à chiffrer)</label>";
            echo "<input type='text' name='$key' id='$key' required>";
        }
        ?>
        <button type="submit">🔒 Chiffrer et télécharger</button>
        <div style="width: 100%; margin: 25px auto; display: flex;
        justify-content: center;
        align-items: center; gap: 25px;">
            <a style="background: brown; color: antiquewhite; padding: 15px 25px;"
                href="https://greystorm.fr/chiffrement.php">Revenir à l'accueil</a>
            <a style="background: brown; color: antiquewhite; padding: 15px 25px;" href="/">Quitter l'application</a>
        </div>
    </form>
</body>
</html>
