<?php
declare(strict_types=1);

function encrypt_record(array $record, string $publicPem): array
{
    $publicKey = openssl_pkey_get_public($publicPem);
    if ($publicKey === false) {
        throw new RuntimeException('Kunci publik tidak dapat dibaca. Jalankan ulang setup dengan key pair yang valid.');
    }
    $dataKey = random_bytes(32);
    $nonce = random_bytes(12);
    $tag = '';
    $json = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $ciphertext = openssl_encrypt($json, 'aes-256-gcm', $dataKey, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($ciphertext === false) {
        throw new RuntimeException('Enkripsi AES-GCM gagal.');
    }
    $wrappedKey = '';
    if (!openssl_public_encrypt($dataKey, $wrappedKey, $publicKey, OPENSSL_PKCS1_OAEP_PADDING)) {
        throw new RuntimeException('Pembungkusan kunci AES dengan RSA gagal.');
    }
    return [
        'ciphertext' => base64_encode($ciphertext),
        'nonce' => base64_encode($nonce),
        'auth_tag' => base64_encode($tag),
        'wrapped_key' => base64_encode($wrappedKey),
    ];
}

function decrypt_record(array $row, string $privatePem): ?array
{
    $privateKey = @openssl_pkey_get_private($privatePem);
    if ($privateKey === false) {
        return null;
    }
    $wrappedKey = base64_decode((string) ($row['wrapped_key'] ?? ''), true);
    $nonce = base64_decode((string) ($row['nonce'] ?? ''), true);
    $tag = base64_decode((string) ($row['auth_tag'] ?? ''), true);
    $ciphertext = base64_decode((string) ($row['ciphertext'] ?? ''), true);
    if ($wrappedKey === false || $nonce === false || $tag === false || $ciphertext === false) {
        return null;
    }
    $dataKey = '';
    if (!@openssl_private_decrypt($wrappedKey, $dataKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING)) {
        return null;
    }
    $plain = @openssl_decrypt($ciphertext, 'aes-256-gcm', $dataKey, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($plain === false) {
        return null;
    }
    try {
        $decoded = json_decode($plain, true, 512, JSON_THROW_ON_ERROR);
        return is_array($decoded) ? $decoded : null;
    } catch (JsonException) {
        return null;
    }
}

function read_private_key_input(): ?string
{
    $content = '';
    if (isset($_FILES['private_key']) && is_array($_FILES['private_key']) && ($_FILES['private_key']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['private_key'];
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
            return null;
        }
        if ((int) ($file['size'] ?? 0) > 16384 || strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)) !== 'txt') {
            return null;
        }
        $content = (string) file_get_contents((string) $file['tmp_name']);
    } elseif (isset($_POST['private_key']) && is_string($_POST['private_key'])) {
        $content = $_POST['private_key'];
    }
    $content = trim($content);
    if ($content === '' || strlen($content) > 16384 || !str_contains($content, 'PRIVATE KEY')) {
        return null;
    }
    return $content;
}

function encrypted_records(): array
{
    return db()->query('SELECT id, record_id, ciphertext, nonce, auth_tag, wrapped_key FROM encrypted_records ORDER BY id ASC')->fetchAll();
}
