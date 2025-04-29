# 🔐 Générateur de fichier `.env` chiffré (AES-256-GCM)

## 📌 Description

Ce projet PHP permet de chiffrer automatiquement vos identifiants de connexion à une base de données (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) à l'aide de l'algorithme **AES-256-GCM**.

L'objectif est de générer un fichier texte contenant des variables d’environnement chiffrées, prêtes à être copiées dans un fichier `.env`.

Chaque variable est chiffrée de manière indépendante avec :
- Une **clé aléatoire** (32 octets, base64),
- Un **vecteur d’initialisation (IV)** unique (12 octets, base64),
- Un **tag d’authentification** garantissant l’intégrité (GCM tag).

---

## 🧰 Fonctionnement

### 1. Interface web

L'utilisateur renseigne les 4 variables suivantes via un formulaire HTML :
- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

À la soumission, un fichier texte est automatiquement généré, contenant les versions chiffrées de ces variables.

### 2. Contenu généré

```env
# DB_HOST : valeur chiffrée
DB_HOST_ENC=...
DB_HOST_IV=...
DB_HOST_TAG=...
DB_HOST_KEY=...  # À ne **pas** garder en production
```

Le fichier contient les données suivantes **pour chaque variable** :
- `xxx_ENC` → valeur chiffrée
- `xxx_IV` → vecteur d’initialisation
- `xxx_TAG` → tag d’authentification
- `xxx_KEY` → clé de chiffrement (à stocker de manière sécurisée)

---

## ✅ Utilisation dans un projet PHP

### 1. Déchiffrement des variables

```php
function decrypt_env_value($enc_b64, $key_b64, $iv_b64, $tag_b64) {
    $encrypted = base64_decode($enc_b64);
    $key       = base64_decode($key_b64);
    $iv        = base64_decode($iv_b64);
    $tag       = base64_decode($tag_b64);

    $decrypted = openssl_decrypt(
        $encrypted,
        'aes-256-gcm',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );

    if ($decrypted === false) {
        throw new Exception("Erreur de déchiffrement.");
    }

    return $decrypted;
}
```

### 2. Chargement via `getenv()`

```php
$db_host = decrypt_env_value(
    getenv('DB_HOST_ENC'),
    getenv('DB_HOST_KEY'),
    getenv('DB_HOST_IV'),
    getenv('DB_HOST_TAG')
);

$db_name = decrypt_env_value(
    getenv('DB_NAME_ENC'),
    getenv('DB_NAME_KEY'),
    getenv('DB_NAME_IV'),
    getenv('DB_NAME_TAG')
);

$db_user = decrypt_env_value(
    getenv('DB_USER_ENC'),
    getenv('DB_USER_KEY'),
    getenv('DB_USER_IV'),
    getenv('DB_USER_TAG')
);

$db_pass = decrypt_env_value(
    getenv('DB_PASS_ENC'),
    getenv('DB_PASS_KEY'),
    getenv('DB_PASS_IV'),
    getenv('DB_PASS_TAG')
);
```

### 3. Connexion PDO

```php
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connexion échouée : " . $e->getMessage());
}
```

---

## 🔒 Sécurité

- ❌ **Ne jamais conserver les clés (`_KEY`) dans le `.env` de production.**
- ✅ Utiliser un gestionnaire de secrets (ex : Vault, AWS Secrets Manager).
- 🔁 Régénérer régulièrement les clés.
- 🛡️ Possibilité d’utiliser `sodium_crypto_pwhash()` pour générer les clés à partir d’une passphrase.

---

## 📄 Exemple de sortie générée

```env
# DB_HOST : valeur chiffrée
DB_HOST_ENC=jBfj3A==
DB_HOST_IV=vn4YQD/YEtoyG9bk
DB_HOST_TAG=UjyZ0BaUYVHn9DRy+vvv1Q==
DB_HOST_KEY=zauRumt0LchUke86iM1ne78OsTKh8GGlAFilRqjfHMg=
```

---

## 📂 Arborescence

| Fichier                     | Rôle                                                        |
|----------------------------|-------------------------------------------------------------|
| `index.php` / `chiffrement.php` | Interface HTML + génération du fichier chiffré               |
| `env_chiffre.txt`          | Résultat généré à copier dans `.env`                         |
| `.env`                     | Fichier final utilisé par l'application (chiffré uniquement) |

---

## 🧪 Tests & Vérifications

- Chiffrement : `openssl_encrypt()` avec `AES-256-GCM`
- Intégrité : tag d'authentification inclus automatiquement
- Erreurs gérées : IV, clé, tag ou données invalides → exception levée

---

## 🧠 Philosophie du projet

Ce projet est conçu pour :
- Proposer une sécurisation des fichiers `.env` même pour les débutants,
- Automatiser la génération de fichiers prêts à l'emploi,
- Promouvoir les bonnes pratiques de gestion de secrets en environnement PHP.

---

