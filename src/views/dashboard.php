<?php
declare(strict_types=1);

$viewMeta = [
    'overview' => ['title' => 'Selamat datang di ruang kendali', 'subtitle' => 'Gambaran singkat perjalanan Rifrain Tech hari ini.', 'eyebrow' => 'COMPANY OVERVIEW'],
    'projects' => ['title' => 'Proyek perusahaan', 'subtitle' => 'Pantau portfolio dan progres tim dalam satu tempat.', 'eyebrow' => 'PROJECT PORTFOLIO'],
    'activity' => ['title' => 'Aktivitas terbaru', 'subtitle' => 'Ikuti pekerjaan yang sedang bergerak di seluruh tim.', 'eyebrow' => 'TEAM ACTIVITY'],
    'team' => ['title' => 'Tim & kapasitas', 'subtitle' => 'Lihat komposisi tim dan ruang kerja yang tersedia.', 'eyebrow' => 'PEOPLE & CAPACITY'],
    'security' => ['title' => 'Pusat keamanan', 'subtitle' => 'Ringkasan cara data demo dilindungi dan dibuka.', 'eyebrow' => 'SECURITY CENTER'],
    'help' => ['title' => 'Pusat bantuan', 'subtitle' => 'Panduan singkat untuk menjelajahi dashboard demo.', 'eyebrow' => 'HELP CENTER'],
];
$currentView = $viewMeta[$view] ?? $viewMeta['overview'];
$viewUrl = static fn (string $target): string => '?page=dashboard&view=' . rawurlencode($target);
$today = new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta'));
$dayNames = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$monthNames = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$todayLabel = ($dayNames[$today->format('l')] ?? $today->format('l')) . ', ' . $today->format('j') . ' ' . $monthNames[(int) $today->format('n')] . ' ' . $today->format('Y');

$renderProjects = static function (array $items): void { ?>
    <div class="table-wrap">
        <table id="project-table">
            <thead><tr><th>NAMA PROYEK</th><th>KATEGORI</th><th>STATUS</th><th>PROGRES</th></tr></thead>
            <tbody>
            <?php foreach ($items as $project):
                $searchText = strtolower(implode(' ', [$project['name'] ?? '', $project['client'] ?? '', $project['category'] ?? '', $project['status'] ?? ''])); ?>
                <tr data-filter-item="project" data-search="<?= h($searchText) ?>" data-status="<?= h($project['status'] ?? '') ?>">
                    <td><div class="project-name"><span class="project-mark <?= h($project['color'] ?? 'violet') ?>"><?= h(substr($project['name'] ?? 'P', 0, 1)) ?></span><span><b><?= h($project['name'] ?? '') ?></b><small><?= h($project['client'] ?? '') ?></small></span></div></td>
                    <td><span class="category-tag"><?= h($project['category'] ?? '') ?></span></td>
                    <td><span class="status-tag"><?= h($project['status'] ?? '') ?></span></td>
                    <td><div class="progress-cell"><div class="progress-track"><i style="width:<?= max(0, min(100, (int) ($project['progress'] ?? 0))) ?>%"></i></div><span><?= h($project['progress'] ?? 0) ?>%</span></div></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php };

$renderActivities = static function (array $items): void { ?>
    <div class="activity-list activity-list-expanded">
        <?php foreach ($items as $activity):
            $searchText = strtolower(implode(' ', [$activity['name'] ?? '', $activity['action'] ?? '', $activity['project'] ?? '', $activity['time'] ?? ''])); ?>
            <article class="activity-item" data-filter-item="activity" data-search="<?= h($searchText) ?>">
                <span class="activity-avatar <?= h($activity['color'] ?? 'violet') ?>"><?= h($activity['initials'] ?? 'RT') ?></span>
                <div><p><b><?= h($activity['name'] ?? '') ?></b> <?= h($activity['action'] ?? '') ?></p><small><?= h($activity['project'] ?? '') ?> <i>·</i> <?= h($activity['time'] ?? '') ?></small></div>
                <span class="activity-check">✓</span>
            </article>
        <?php endforeach; ?>
    </div>
<?php };

$renderTeams = static function (array $items): void { ?>
    <div class="team-grid team-grid-expanded">
        <?php foreach ($items as $team): ?>
            <article class="team-item team-card"><div class="team-heading"><span><?= h($team['name'] ?? '') ?></span><b><?= h($team['members'] ?? 0) ?> <small>anggota</small></b></div>
                <div class="team-progress"><i style="width:<?= max(0, min(100, (int) ($team['capacity'] ?? 0))) ?>%"></i></div>
                <div class="team-meta"><span><?= h($team['capacity'] ?? 0) ?>% terpakai</span><span><?= max(0, 100 - (int) ($team['capacity'] ?? 0)) ?>% tersedia</span></div>
                <div class="team-people"><span><?= h(substr($team['name'] ?? 'T', 0, 1)) ?></span><span>AI</span><span>+<?= max(0, (int) ($team['members'] ?? 1) - 2) ?></span><small>anggota tim</small></div>
            </article>
        <?php endforeach; ?>
    </div>
<?php };
?>
<div class="dashboard-shell">
    <aside class="sidebar">
        <a class="brand" href="?" aria-label="Rifrain Tech beranda"><span class="brand-mark">r<span>.</span></span><span>rifrain<span class="brand-muted">tech</span></span></a>
        <div class="workspace-label">WORKSPACE <span>⌄</span></div>
        <a class="workspace-select" href="<?= h($viewUrl('overview')) ?>"><span class="workspace-avatar">R</span><span><b>Rifrain Tech</b><small>Company workspace</small></span><span class="select-dots">↗</span></a>
        <div class="side-section-label">MENU UTAMA</div>
        <nav class="side-nav" aria-label="Navigasi dashboard">
            <?php foreach (['overview' => ['▦', 'Ringkasan'], 'projects' => ['◫', 'Proyek'], 'activity' => ['◷', 'Aktivitas'], 'team' => ['♧', 'Tim']] as $key => [$icon, $label]): ?>
                <a class="side-link<?= $view === $key ? ' active' : '' ?>" href="<?= h($viewUrl($key)) ?>"<?= $view === $key ? ' aria-current="page"' : '' ?>><span><?= h($icon) ?></span><?= h($label) ?><?php if ($key === 'projects'): ?><em><?= h($metrics['active_projects'] ?? '12') ?></em><?php endif; ?><i></i></a>
            <?php endforeach; ?>
        </nav>
        <div class="side-section-label side-lower-label">PREFERENSI</div>
        <nav class="side-nav" aria-label="Bantuan dan keamanan">
            <?php foreach (['security' => ['⌑', 'Keamanan'], 'help' => ['ⓘ', 'Pusat bantuan']] as $key => [$icon, $label]): ?>
                <a class="side-link<?= $view === $key ? ' active' : '' ?>" href="<?= h($viewUrl($key)) ?>"<?= $view === $key ? ' aria-current="page"' : '' ?>><span><?= h($icon) ?></span><?= h($label) ?><i></i></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-bottom">
            <div class="plan-card"><span class="plan-icon">✧</span><b>Workspace demo</b><p>Contoh lingkungan terenkripsi untuk Rifrain Tech.</p><a href="<?= h($viewUrl('security')) ?>">Lihat keamanan <span>↗</span></a></div>
            <div class="user-card"><a class="user-profile" href="<?= h($viewUrl('security')) ?>"><span class="user-avatar">RT</span><span><b>Rifrain Tech</b><small><?= $unlocked ? 'Data terbuka di sesi ini' : 'Demo workspace' ?></small></span></a><a class="user-menu" href="<?= h($viewUrl('security')) ?>" aria-label="Pengaturan keamanan">⚙</a></div>
        </div>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <div class="breadcrumbs"><a href="<?= h($viewUrl('overview')) ?>">Workspace</a><span>/</span><b><?= h($currentView['title'] === 'Selamat datang di ruang kendali' ? 'Ringkasan' : $currentView['title']) ?></b></div>
            <div class="top-actions"><span class="today-label"><?= h($todayLabel) ?></span>
                <details class="notification-menu"><summary class="icon-button" aria-label="Buka notifikasi">♧<i class="notification-dot"></i></summary><div class="notification-popover"><div class="notification-heading"><b>Notifikasi</b><span><?= $unlocked ? count($activities) : '•' ?></span></div><?php if ($unlocked && $activities): foreach (array_slice($activities, 0, 3) as $activity): ?><div class="notification-item"><span class="notification-bullet"></span><p><b><?= h($activity['name'] ?? '') ?></b> <?= h($activity['action'] ?? '') ?><small><?= h($activity['time'] ?? '') ?></small></p></div><?php endforeach; else: ?><p class="notification-empty">Buka data dengan private key untuk melihat aktivitas terbaru.</p><?php endif; ?></div></details>
                <details class="account-menu"><summary class="top-avatar" aria-label="Buka menu akun">RT</summary><div class="account-popover"><div class="account-popover-user"><span class="user-avatar">RT</span><span><b>Rifrain Tech</b><small><?= $unlocked ? 'Sesi data terbuka' : 'Demo workspace' ?></small></span></div><a href="<?= h($viewUrl('security')) ?>">Pengaturan keamanan <span>→</span></a><form method="post" action="?page=logout"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="logout-button" type="submit"><span>↪</span> Keluar dari sesi</button></form></div></details>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="dashboard-heading"><div><div class="section-kicker"><?= h($currentView['eyebrow']) ?> <span class="live-pill"><i></i> LIVE DEMO</span></div><h1><?= h($currentView['title']) ?><span class="heading-period">.</span></h1><p><?= h($currentView['subtitle']) ?></p></div>
                <div class="heading-actions"><span class="date-chip">▦ <b>Q3 2026</b><small>DATA DEMO</small></span><?php if ($unlocked): ?><form method="post" action="?page=lock&amp;view=<?= h($view) ?>"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="button button-dark" type="submit">Kunci data <span>⌑</span></button></form><?php else: ?><a class="button button-primary" href="?page=login">Buka dengan private key <span>↗</span></a><?php endif; ?></div>
            </div>

            <?php if ($flashMessage): ?><div class="flash flash-<?= h($flashMessage['type'] ?? 'info') ?>" role="status"><?= h($flashMessage['message'] ?? '') ?></div><?php endif; ?>
            <?php if (!$databaseReady): ?><div class="setup-notice"><span>!</span><div><b>Dashboard belum terhubung ke database</b><p><?= h($databaseError) ?></p></div></div><?php elseif (!$unlocked): ?><div class="cipher-banner"><span class="banner-lock">⌑</span><div><b>Data terenkripsi</b><p>Dashboard tetap terbuka. Masukkan private key yang sesuai untuk membaca data asli.</p></div><span class="banner-algo">AES-256-GCM <i>·</i> RSA-3072</span></div><?php else: ?><div class="unlocked-banner"><span>✓</span><div><b>Data berhasil dibuka</b><small>Isi dashboard didekripsi di sesi ini.</small></div><span class="unlock-badge">PRIVATE KEY VERIFIED</span></div><?php endif; ?>

            <?php if ($view === 'help'): ?>
                <section class="help-grid">
                    <article class="help-card help-feature"><span class="help-icon">✳</span><div class="section-kicker">RIFRAIN TECH · DEMO GUIDE</div><h2>Mulai dari mana?</h2><p>Gunakan menu di kiri untuk berpindah antara ringkasan, proyek, aktivitas, tim, dan keamanan. Data yang terkunci tetap tampil dalam bentuk ciphertext.</p><a class="button button-primary" href="<?= h($viewUrl('overview')) ?>">Kembali ke ringkasan <span>↗</span></a></article>
                    <div class="faq-list"><details><summary>Bagaimana membuka data dashboard?<span>＋</span></summary><p>Unggah file private key `.txt` atau tempel isi PEM melalui tombol “Buka dengan private key”. Kunci yang sesuai membuka data di sesi browser saat ini.</p></details><details><summary>Apa yang terjadi jika private key salah?<span>＋</span></summary><p>Dashboard tetap terbuka, tetapi data dari database ditampilkan sebagai ciphertext.</p></details><details><summary>Bagaimana data dienkripsi?<span>＋</span></summary><p>Setiap record memakai AES-256-GCM. Kunci AES dibungkus menggunakan RSA-3072 sebelum disimpan di database.</p></details><details><summary>Bisakah private key dipulihkan dari aplikasi?<span>＋</span></summary><p>Tidak. Simpan salinan private key di perangkat pribadi. Tanpa kunci yang sesuai, data demo tetap terenkripsi.</p></details></div>
                </section>
            <?php elseif ($view === 'security'): ?>
                <section class="security-overview-grid"><article class="security-status-card"><div class="security-card-top"><span class="security-large-icon">⌑</span><span class="security-state <?= $unlocked ? 'state-open' : 'state-locked' ?>"><?= $unlocked ? 'UNLOCKED' : 'LOCKED' ?></span></div><h2><?= $unlocked ? 'Kunci privat terverifikasi' : 'Data sedang terkunci' ?></h2><p><?= $unlocked ? 'Isi data tersedia di sesi browser ini. Kunci privat tidak disimpan di database.' : 'Masukkan private key untuk membaca record yang tersimpan.' ?></p><?php if ($unlocked): ?><form method="post" action="?page=lock&amp;view=security"><input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>"><button class="button button-dark" type="submit">Kunci kembali <span>⌑</span></button></form><?php else: ?><a class="button button-primary" href="?page=login">Masukkan private key <span>↗</span></a><?php endif; ?></article><div class="security-facts"><article class="security-fact"><span class="fact-icon violet">◈</span><div><small>ENKRIPSI DATA</small><b>AES-256-GCM</b><p>Enkripsi terautentikasi untuk isi setiap record.</p></div></article><article class="security-fact"><span class="fact-icon mint">⌑</span><div><small>PERLINDUNGAN KUNCI</small><b>RSA-3072 · OAEP</b><p>Kunci AES dibungkus dengan kunci publik.</p></div></article><article class="security-fact"><span class="fact-icon blue">▤</span><div><small>DATABASE</small><b><?= count($records) ?> record terenkripsi</b><p>Nonce dan authentication tag ikut tersimpan.</p></div></article></div></section>
                <?php if (!$unlocked): ?><section class="cipher-section"><div class="cipher-section-heading"><div><div class="section-kicker">ENCRYPTED STORAGE</div><h2>Record tersimpan</h2><p>Contoh ciphertext dari database MySQL.</p></div><span class="cipher-count"><?= count($records) ?> RECORDS</span></div><?php include __DIR__ . '/cipher-records.php'; ?></section><?php endif; ?>
            <?php elseif (!$unlocked): ?>
                <section class="cipher-section"><div class="cipher-section-heading"><div><div class="section-kicker">ENCRYPTED STORAGE · <?= h(strtoupper($currentView['title'])) ?></div><h2>Data masih terkunci</h2><p>Gunakan private key untuk membaca isi halaman ini.</p></div><span class="cipher-count"><?= count($records) ?> RECORDS</span></div><?php include __DIR__ . '/cipher-records.php'; ?></section>
            <?php elseif ($view === 'overview'): ?>
                <section class="metric-grid"><article class="metric-card metric-highlight"><div class="metric-top"><span class="metric-label">PENDAPATAN PERUSAHAAN</span><span class="metric-icon">↗</span></div><div class="metric-number"><?= h($metrics['revenue'] ?? '—') ?><span class="metric-change">+<?= h($metrics['revenue_change'] ?? '0') ?>%</span></div><div class="metric-foot"><span>vs. kuartal sebelumnya</span><span class="sparkline">⌁⌁⌁⌁</span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">PROYEK AKTIF</span><span class="metric-icon icon-violet">◫</span></div><div class="metric-number"><?= h($metrics['active_projects'] ?? count($projects)) ?><small> proyek</small></div><div class="metric-foot"><span class="positive-text">3 proyek</span><span>menjelang rilis</span><span class="metric-avatars"><i>NA</i><i>RF</i><i>+4</i></span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">KLIEN AKTIF</span><span class="metric-icon icon-blue">◉</span></div><div class="metric-number"><?= h($metrics['clients'] ?? '—') ?><small> klien</small></div><div class="metric-foot"><span class="positive-text">+6 klien baru</span><span>kuartal ini</span></div></article><article class="metric-card"><div class="metric-top"><span class="metric-label">KETERSEDIAAN SISTEM</span><span class="metric-icon icon-mint">⌁</span></div><div class="metric-number"><?= h($metrics['uptime'] ?? '—') ?></div><div class="metric-foot"><span class="status-dot"></span><span>Semua sistem beroperasi</span></div></article></section>
                <div class="dashboard-grid"><section class="panel projects-panel"><div class="panel-heading"><div><div class="section-kicker">PORTFOLIO</div><h2>Proyek berjalan <span class="count-pill"><?= count($projects) ?></span></h2></div><a class="subtle-link" href="<?= h($viewUrl('projects')) ?>">Lihat semua <span>↗</span></a></div><?php $renderProjects(array_slice($projects, 0, 3)); ?><div class="panel-foot"><span>Data contoh untuk kebutuhan akademik</span><a class="secure-mini" href="<?= h($viewUrl('security')) ?>">⌑ Encrypted at rest</a></div></section>
                    <section class="panel activity-panel"><div class="panel-heading"><div><div class="section-kicker">REAL-TIME FEED</div><h2>Aktivitas terbaru</h2></div><a class="dots-button" href="<?= h($viewUrl('activity')) ?>" aria-label="Lihat semua aktivitas">•••</a></div><?php $renderActivities(array_slice($activities, 0, 3)); ?><a class="activity-more" href="<?= h($viewUrl('activity')) ?>">Lihat semua aktivitas <span>→</span></a></section></div>
                <section class="panel team-panel"><div class="panel-heading"><div><div class="section-kicker">TEAM PULSE</div><h2>Kapasitas tim</h2></div><a class="subtle-link" href="<?= h($viewUrl('team')) ?>">Lihat tim <span>↗</span></a></div><?php $renderTeams($teams); ?></section>
            <?php elseif ($view === 'projects'): ?>
                <section class="panel content-panel"><div class="panel-heading"><div><div class="section-kicker">PORTFOLIO · Q3 2026</div><h2>Semua proyek <span class="count-pill"><?= count($projects) ?></span></h2></div><button class="button button-light" type="button" data-export-projects>Ekspor CSV <span>↓</span></button></div><div class="data-toolbar"><label class="search-field"><span>⌕</span><input type="search" data-filter-input="project" placeholder="Cari nama, klien, kategori…"></label><select data-filter-select="project" aria-label="Filter status proyek"><option value="">Semua status</option><option value="Berjalan">Berjalan</option><option value="UAT">UAT</option><option value="Riset">Riset</option></select><span class="toolbar-result" data-filter-count="project"><?= count($projects) ?> proyek</span></div><?php $renderProjects($projects); ?><div class="panel-foot"><span>Semua angka dan proyek adalah data dummy.</span><span class="secure-mini">⌑ Encrypted at rest</span></div></section>
            <?php elseif ($view === 'activity'): ?>
                <section class="panel content-panel"><div class="panel-heading"><div><div class="section-kicker">REAL-TIME FEED</div><h2>Linimasa aktivitas</h2></div><span class="count-pill"><?= count($activities) ?> AKTIVITAS</span></div><div class="data-toolbar"><label class="search-field"><span>⌕</span><input type="search" data-filter-input="activity" placeholder="Cari anggota atau aktivitas…"></label><span class="toolbar-result" data-filter-count="activity"><?= count($activities) ?> aktivitas</span></div><?php $renderActivities($activities); ?></section>
            <?php elseif ($view === 'team'): ?>
                <section class="panel content-panel"><div class="panel-heading"><div><div class="section-kicker">PEOPLE & CAPACITY</div><h2>Tim Rifrain Tech</h2></div><span class="count-pill"><?= h($metrics['employees'] ?? '64') ?> ANGGOTA</span></div><?php $renderTeams($teams); ?><div class="team-note"><span>✦</span><p>Kapasitas adalah estimasi dummy untuk contoh tampilan perencanaan tim.</p></div></section>
            <?php endif; ?>

            <footer class="dashboard-footer"><span>© 2026 Rifrain Tech <i>·</i> Academic demo</span><span><span class="footer-green-dot"></span> Database encryption active</span></footer>
        </div>
    </main>
</div>
