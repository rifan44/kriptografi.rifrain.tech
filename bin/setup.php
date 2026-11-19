<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/crypto.php';

try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS encrypted_records (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        record_id VARCHAR(40) NOT NULL UNIQUE,
        ciphertext MEDIUMTEXT NOT NULL,
        nonce VARCHAR(32) NOT NULL,
        auth_tag VARCHAR(32) NOT NULL,
        wrapped_key TEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    if ((int) $pdo->query('SELECT COUNT(*) FROM encrypted_records')->fetchColumn() > 0) {
        throw new RuntimeException('Tabel sudah berisi data. Setup dihentikan agar tidak menimpa key atau data yang ada.');
    }

    $keyDir = dirname(__DIR__) . '/storage/keys';
    if (!is_dir($keyDir) && !mkdir($keyDir, 0750, true) && !is_dir($keyDir)) {
        throw new RuntimeException('Folder storage/keys tidak dapat dibuat.');
    }
    if (file_exists($keyDir . '/private.pem') || file_exists($keyDir . '/public.pem')) {
        throw new RuntimeException('File key sudah ada. Setup dihentikan agar key tidak tertimpa.');
    }
    $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 3072]);
    if ($key === false || !openssl_pkey_export($key, $privatePem)) {
        throw new RuntimeException('Pembuatan pasangan kunci RSA gagal.');
    }
    $details = openssl_pkey_get_details($key);
    if ($details === false || empty($details['key'])) {
        throw new RuntimeException('Kunci publik RSA tidak dapat dibuat.');
    }
    $privatePath = $keyDir . '/private.pem';
    $publicPath = $keyDir . '/public.pem';
    if (file_put_contents($privatePath, $privatePem, LOCK_EX) === false || file_put_contents($publicPath, $details['key'], LOCK_EX) === false) {
        @unlink($privatePath);
        @unlink($publicPath);
        throw new RuntimeException('File kunci tidak dapat disimpan.');
    }
    chmod($privatePath, 0600);
    chmod($publicPath, 0640);

    $records = [
        ['record_id' => 'record-001', 'data' => ['slug' => 'company-overview', 'type' => 'metrics', 'company' => 'Rifrain Tech', 'tagline' => 'Intelligence, designed for people.', 'revenue' => 'Rp 8,4 M', 'revenue_change' => 14.8, 'active_projects' => 12, 'uptime' => '99,98%', 'clients' => 38, 'employees' => 64, 'period' => 'Q3 2026']],
        ['record_id' => 'record-002', 'data' => ['slug' => 'projects', 'type' => 'projects', 'items' => [
            ['name' => 'NusaVision AI', 'client' => 'Nusantara Logistics', 'category' => 'Computer Vision', 'status' => 'Berjalan', 'progress' => 78, 'color' => 'violet'],
            ['name' => 'Arunika Copilot', 'client' => 'Arunika Finance', 'category' => 'Generative AI', 'status' => 'UAT', 'progress' => 92, 'color' => 'mint'],
            ['name' => 'Sagara Data Lake', 'client' => 'Sagara Energi', 'category' => 'Data Platform', 'status' => 'Berjalan', 'progress' => 54, 'color' => 'blue'],
            ['name' => 'RuangOps', 'client' => 'Internal product', 'category' => 'Cloud & DevOps', 'status' => 'Riset', 'progress' => 31, 'color' => 'orange'],
        ]]],
        ['record_id' => 'record-003', 'data' => ['slug' => 'activity-feed', 'type' => 'activity', 'items' => [
            ['initials' => 'NA', 'name' => 'Nadia Aulia', 'action' => 'merilis model deteksi anomali', 'project' => 'NusaVision AI', 'time' => '12 menit lalu', 'color' => 'violet'],
            ['initials' => 'RF', 'name' => 'Rafi Fadillah', 'action' => 'menyelesaikan evaluasi keamanan', 'project' => 'Arunika Copilot', 'time' => '48 menit lalu', 'color' => 'mint'],
            ['initials' => 'KS', 'name' => 'Kevin Santoso', 'action' => 'menambahkan pipeline data baru', 'project' => 'Sagara Data Lake', 'time' => '2 jam lalu', 'color' => 'blue'],
        ]]],
        ['record_id' => 'record-004', 'data' => ['slug' => 'team-pulse', 'type' => 'team', 'items' => [
            ['name' => 'AI & Research', 'members' => 18, 'capacity' => 82],
            ['name' => 'Product Engineering', 'members' => 24, 'capacity' => 68],
            ['name' => 'Cloud & Security', 'members' => 12, 'capacity' => 74],
            ['name' => 'Design & Strategy', 'members' => 10, 'capacity' => 56],
        ]]],
    ];
    $insert = $pdo->prepare('INSERT INTO encrypted_records (record_id, ciphertext, nonce, auth_tag, wrapped_key) VALUES (:record_id, :ciphertext, :nonce, :auth_tag, :wrapped_key)');
    $encryptedRecords = [];
    foreach ($records as $record) {
        $encryptedRecords[] = ['record_id' => $record['record_id']] + encrypt_record($record['data'], $details['key']);
    }
    $pdo->beginTransaction();
    try {
        foreach ($encryptedRecords as $encrypted) {
            $insert->execute($encrypted);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        @unlink($privatePath);
        @unlink($publicPath);
        throw $error;
    }
    echo "Setup selesai. Data dummy sudah disimpan terenkripsi.\n";
    echo "Kunci privat ada di: {$privatePath}\n";
    echo "Simpan salinannya di perangkat pribadi. Hapus file private.pem dari server setelah memastikan salinan aman.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Setup gagal: ' . $error->getMessage() . "\n");
    exit(1);
}
