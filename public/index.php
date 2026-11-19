<?php
declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';
require dirname(__DIR__) . '/src/crypto.php';

$page = (string) ($_GET['page'] ?? 'home');
if (!in_array($page, ['home', 'login', 'dashboard', 'lock', 'logout'], true)) {
    $page = 'home';
}
$view = (string) ($_GET['view'] ?? 'overview');
if (!in_array($view, ['overview', 'projects', 'activity', 'team', 'security', 'help'], true)) {
    $view = 'overview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        http_response_code(419);
        exit('Sesi formulir kedaluwarsa. Silakan muat ulang halaman.');
    }
    if ($page === 'login') {
        $privatePem = read_private_key_input();
        try {
            $rows = encrypted_records();
        } catch (Throwable) {
            $rows = [];
        }
        $decrypted = [];
        if ($privatePem !== null && $rows !== []) {
            foreach ($rows as $row) {
                $record = decrypt_record($row, $privatePem);
                if ($record === null) {
                    $decrypted = [];
                    break;
                }
                $decrypted[$record['slug'] ?? $row['record_id']] = $record;
            }
        }
        session_regenerate_id(true);
        $_SESSION['dashboard_unlocked'] = $decrypted !== [] && count($decrypted) === count($rows);
        $_SESSION['dashboard_data'] = $_SESSION['dashboard_unlocked'] ? $decrypted : null;
        if (!$rows) {
            flash('Dashboard dibuka, tetapi database belum memiliki data demo terenkripsi.', 'warning');
        } elseif ($_SESSION['dashboard_unlocked']) {
            flash('Kunci privat cocok. Data berhasil dibuka di sesi ini.', 'success');
        } else {
            flash($privatePem === null ? 'Kunci privat tidak terbaca. Dashboard dibuka dengan data terenkripsi.' : 'Kunci privat tidak cocok. Dashboard dibuka dengan data terenkripsi.', 'warning');
        }
        redirect('?page=dashboard');
    }
    if ($page === 'lock') {
        unset($_SESSION['dashboard_unlocked'], $_SESSION['dashboard_data']);
        flash('Data dikunci kembali. Dashboard tetap tersedia dalam mode ciphertext.', 'info');
        redirect('?page=dashboard&view=' . rawurlencode($view));
    }
    if ($page === 'logout') {
        $_SESSION = [];
        session_regenerate_id(true);
        redirect('?');
    }
}

$flashMessage = take_flash();
$records = [];
$databaseReady = true;
$databaseError = '';
if ($page === 'dashboard') {
    try {
        $records = encrypted_records();
    } catch (Throwable) {
        $databaseReady = false;
        $databaseError = 'Database belum siap. Buat database, isi konfigurasi .env, lalu jalankan php bin/setup.php.';
    }
}
$unlocked = $databaseReady && !empty($_SESSION['dashboard_unlocked']) && is_array($_SESSION['dashboard_data'] ?? null);
$data = $unlocked ? $_SESSION['dashboard_data'] : [];
$metrics = $data['company-overview'] ?? [];
$projects = $data['projects']['items'] ?? [];
$activities = $data['activity-feed']['items'] ?? [];
$teams = $data['team-pulse']['items'] ?? [];
$viewTitles = [
    'overview' => 'Ringkasan',
    'projects' => 'Proyek',
    'activity' => 'Aktivitas',
    'team' => 'Tim',
    'security' => 'Keamanan',
    'help' => 'Pusat bantuan',
];
$pageTitle = match ($page) {
    'login' => 'Buka dashboard',
    'dashboard' => $viewTitles[$view] ?? 'Dashboard',
    default => 'Teknologi yang bekerja untuk manusia',
};
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1221">
    <title><?= h($pageTitle) ?> · Rifrain Tech</title>
    <link rel="stylesheet" href="assets/app.css?v=<?= (int) filemtime(__DIR__ . '/assets/app.css') ?>">
</head>
<body class="page-<?= h($page) ?>">
<?php if ($page === 'home'): ?>
    <div class="landing-shell">
        <header class="site-header wrap">
            <a class="brand" href="?" aria-label="Rifrain Tech beranda"><span class="brand-mark">r<span>.</span></span><span>rifrain<span class="brand-muted">tech</span></span></a>
            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="#platform">Platform</a><a href="#insight">Tentang kami</a>
            </nav>
            <a class="button button-small button-outline" href="?page=login">Masuk ke dashboard <span>↗</span></a>
        </header>
        <main>
            <section class="hero wrap">
                <div class="hero-copy">
                    <div class="eyebrow"><span class="pulse-dot"></span> STUDIO TEKNOLOGI INDEPENDEN · JAKARTA</div>
                    <h1>Membangun masa depan,<br><span class="gradient-text">satu ide cerdas</span><br>pada satu waktu.</h1>
                    <p class="hero-lead">Kami merancang AI, produk digital, dan infrastruktur yang membuat pekerjaan kompleks terasa lebih sederhana.</p>
                    <div class="hero-actions"><a class="button button-primary" href="?page=login">Jelajahi dashboard <span>↗</span></a><a class="text-link" href="#platform">Kenali cara kami bekerja <span>↓</span></a></div>
                    <div class="hero-note"><span class="avatar-stack"><i>R</i><i>+</i><i>AI</i></span><span>Ide berani. Teknologi yang berguna.</span></div>
                </div>
                <div class="hero-art" aria-label="Ilustrasi jaringan teknologi" role="img">
                    <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div><div class="orbit orbit-three"></div>
                    <div class="art-grid"></div>
                    <div class="core-card"><div class="core-icon">✳</div><div class="core-label">INTELLIGENCE<br><strong>IN MOTION</strong></div><div class="core-spark">↗</div></div>
                    <div class="float-chip chip-ai"><span class="chip-icon violet">✧</span><span><b>AI systems</b><small>Belajar & beradaptasi</small></span></div>
                    <div class="float-chip chip-data"><span class="chip-icon mint">◉</span><span><b>Data platform</b><small>Insight, lebih cepat</small></span></div>
                    <div class="float-chip chip-cloud"><span class="chip-icon blue">⌁</span><span><b>Cloud native</b><small>Siap berkembang</small></span></div>
                    <span class="art-star star-one">✳</span><span class="art-star star-two">✦</span><span class="art-ring ring-one"></span>
                    <div class="art-caption"><span class="caption-line"></span> HUMAN-CENTRIC TECHNOLOGY</div>
                </div>
            </section>
            <section class="trust-strip wrap"><span>MEMBANGUN DI PERSIMPANGAN</span><b>Artificial intelligence</b><b>Product engineering</b><b>Cloud & data</b><b>Digital experience</b></section>
            <section class="intro-section wrap" id="platform"><div class="section-kicker">01 — APA YANG KAMI LAKUKAN</div><div class="intro-grid"><h2>Kompleks di balik layar.<br><span class="muted-heading">Sederhana untuk semua.</span></h2><p>Rifrain Tech adalah perusahaan teknologi fiktif untuk demonstrasi akademik. Dashboard ini menampilkan contoh metrik, proyek, dan aktivitas perusahaan yang disimpan dalam bentuk terenkripsi.</p></div>
                <div class="feature-grid"><article class="feature-card"><span class="feature-number">01</span><div class="feature-icon violet">✧</div><h3>Applied AI</h3><p>Model cerdas untuk otomasi, analisis, dan pengalaman produk yang terasa lebih personal.</p><span class="feature-arrow">↗</span></article><article class="feature-card"><span class="feature-number">02</span><div class="feature-icon mint">◉</div><h3>Digital products</h3><p>Pengalaman digital yang dirancang dengan teliti dari ide awal sampai siap digunakan.</p><span class="feature-arrow">↗</span></article><article class="feature-card"><span class="feature-number">03</span><div class="feature-icon blue">⌁</div><h3>Data & cloud</h3><p>Fondasi data dan cloud yang aman, andal, dan siap mengikuti kebutuhan bisnis.</p><span class="feature-arrow">↗</span></article></div>
            </section>
            <section class="security-band" id="insight"><div class="wrap security-inner"><div><div class="section-kicker">02 — DEMO KRIPTOGRAFI</div><h2>Data terkunci.<br><span class="gradient-text">Insight terbuka.</span></h2><p>Konten dashboard dienkripsi AES-256-GCM sebelum masuk ke database. Kunci AES dilindungi dengan RSA, lalu dibuka melalui private key milikmu.</p><a class="button button-primary" href="?page=login">Coba dashboard <span>↗</span></a></div><div class="security-visual"><div class="lock-orbit"></div><div class="lock-hex">⌑</div><div class="security-label label-a"><span class="signal"></span> AES-256-GCM <small>Enkripsi konten</small></div><div class="security-label label-b"><span class="signal blue-signal"></span> RSA-3072 <small>Perlindungan kunci</small></div><div class="security-label label-c">DATABASE <span class="cipher-preview">8fa2 · 19cc · 75b1</span></div></div></div></section>
        </main>
        <footer class="site-footer wrap"><a class="brand" href="?"><span class="brand-mark">r<span>.</span></span><span>rifrain<span class="brand-muted">tech</span></span></a><span>© 2026 Rifrain Tech. Built for ideas that matter.</span><span>Made with clarity <i>✳</i></span></footer>
    </div>
<?php elseif ($page === 'login'): ?>
    <main class="login-layout">
        <div class="login-aside"><a class="brand" href="?"><span class="brand-mark">r<span>.</span></span><span>rifrain<span class="brand-muted">tech</span></span></a><div class="login-aside-content"><div class="eyebrow"><span class="pulse-dot"></span> SECURE DATA EXPERIENCE</div><h1>Setiap data punya<br><span class="gradient-text">kuncinya sendiri.</span></h1><p>Gunakan private key RSA untuk membuka data demo yang tersimpan terenkripsi di dashboard.</p><div class="mini-security"><div class="mini-lock">⌑</div><div><b>Hybrid encryption</b><small>AES-256-GCM + RSA-3072</small></div><span class="mini-status">READY</span></div></div><div class="aside-footer">RIFRAIN TECH <span>·</span> SECURITY DEMO</div></div>
        <section class="login-panel-wrap"><div class="login-panel"><a class="back-link" href="?">← Kembali ke beranda</a><div class="login-heading"><div class="login-emblem">⌑</div><div class="section-kicker">PRIVATE KEY ACCESS</div><h2>Buka dashboard</h2><p>Unggah file private key <code>.txt</code> atau tempelkan isinya.</p></div>
            <form class="key-form" method="post" action="?page=login" enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
                <div class="source-tabs" role="tablist" aria-label="Pilih sumber private key"><button class="source-tab active" type="button" data-source="upload">Unggah file <span>⌃</span></button><button class="source-tab" type="button" data-source="paste">Tempel teks <span>⌘</span></button></div>
                <div class="source-panel" data-panel="upload"><label class="upload-box" for="private-key-file"><span class="upload-icon">↑</span><b>Pilih file private key</b><small>Format .txt · Maksimal 16 KB</small><span class="file-name" id="file-name">Belum ada file dipilih</span></label><input class="visually-hidden" id="private-key-file" name="private_key" type="file" accept=".txt,text/plain"></div>
                <div class="source-panel hidden" data-panel="paste"><label class="field-label" for="private-key-text">Isi private key PEM</label><textarea id="private-key-text" name="private_key" rows="8" spellcheck="false" autocomplete="off" placeholder="-----BEGIN PRIVATE KEY-----&#10;Tempel private key PEM di sini&#10;-----END PRIVATE KEY-----"></textarea><small class="field-hint">Private key tidak disimpan oleh aplikasi.</small></div>
                <div class="key-tip"><span>i</span><p>Private key adalah teks PEM panjang. Kunci yang benar akan membuka data asli; kunci lain tetap masuk ke dashboard dengan tampilan ciphertext.</p></div>
                <button class="button button-primary button-wide" type="submit">Lanjutkan ke dashboard <span>↗</span></button>
                <div class="privacy-note"><span>⌑</span> Kunci diproses untuk membuka data dan tidak dicatat ke database.</div>
            </form><div class="login-bottom">Tidak perlu akun atau kata sandi <span>·</span> Demo akademik</div>
        </div></section>
    </main>
<?php else: ?>
    <?php require dirname(__DIR__) . '/src/views/dashboard.php'; ?>
<?php endif; ?>
<script src="assets/app.js?v=<?= (int) filemtime(__DIR__ . '/assets/app.js') ?>" defer></script>
</body>
</html>
