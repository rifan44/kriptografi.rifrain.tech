<?php if ($records): ?>
    <div class="cipher-record-list">
        <?php foreach ($records as $row): ?>
            <details class="cipher-record">
                <summary><span class="record-id"><i>⌑</i><?= h($row['record_id']) ?></span><span class="record-algo">AES-256-GCM + RSA-OAEP</span><span class="record-expand">Lihat ciphertext <b>＋</b></span></summary>
                <div class="cipher-fields">
                    <div><label>NONCE</label><code><?= h($row['nonce']) ?></code></div>
                    <div><label>AUTH TAG</label><code><?= h($row['auth_tag']) ?></code></div>
                    <div><label>WRAPPED AES KEY</label><code><?= h($row['wrapped_key']) ?></code></div>
                    <div class="ciphertext-field"><label>CIPHERTEXT</label><code><?= h($row['ciphertext']) ?></code></div>
                </div>
            </details>
        <?php endforeach; ?>
    </div>
<?php elseif ($databaseReady): ?>
    <div class="empty-state">Belum ada data terenkripsi. Jalankan perintah setup untuk membuat data demo.</div>
<?php endif; ?>
