# Rifrain Tech · Crypto Dashboard

Aplikasi demo PHP 8.4 + MySQL untuk tugas kuliah. Landing page mengenalkan perusahaan teknologi fiktif Rifrain Tech. Dashboard selalu dapat dibuka; private key yang benar akan membuka data, sedangkan private key lain menampilkan ciphertext.

## Skema kriptografi

- Setiap record JSON dienkripsi memakai kunci acak AES-256-GCM yang berbeda.
- Kunci AES dibungkus dengan RSA-3072 menggunakan OAEP.
- `public.pem` dipakai saat setup untuk membungkus kunci AES; kunci publik boleh dibagikan dan tidak dapat membuka data. `private.pem` diperlukan untuk membuka kunci AES dan mendekripsi record.
- MySQL menyimpan ciphertext, nonce, authentication tag, dan wrapped AES key. `record_id` hanya nomor opaque (`record-001`, dan seterusnya).
- Pengguna mengunggah file `.txt` atau menempelkan private key PEM. Private key tidak dikirim ke database dan tidak disimpan ke sesi.
- Private key demo dibuat tanpa passphrase. Jangan gunakan key ini untuk data produksi.

## Persiapan

1. Arahkan document root virtual host ke folder `public/`, bukan root proyek. Untuk server ini targetnya `/var/www/kriptografi.rifrain.tech/public`.
2. Siapkan database dengan salah satu cara berikut:

   - **Otomatis di server ini:** jalankan `sudo bash /var/www/kriptografi.rifrain.tech/bin/provision-server.sh`. Script membuat database khusus, password acak di `.env`, data demo, lalu mencabut hak `INSERT` dan `CREATE` dari user runtime. Script menolak menimpa `.env`, database, atau user yang sudah ada.
   - **Manual:** buat database dan akun seperti contoh di bawah, salin `.env.example` ke `.env`, lalu isi kredensialnya.

3. Jika setup manual, jalankan sekali dari root proyek:

   ```sh
   php bin/setup.php
   ```

   Perintah ini membuat tabel, pasangan kunci RSA-3072, dan empat record dummy terenkripsi. Setup menolak menimpa data atau key yang sudah ada.

4. Salin `storage/keys/private.pem` ke perangkat pribadi yang aman. Setelah salinan dipastikan berfungsi, hapus file private key dari server. Aplikasi web hanya perlu private key yang dimasukkan pada form untuk membuka isi dashboard.
5. Pastikan PHP memiliki ekstensi `openssl` dan `pdo_mysql`, serta Apache/Nginx melayani isi folder `public/`.

Contoh pembuatan database (ganti password dengan nilai kuat dan unik):

```sql
CREATE DATABASE rifrain_crypto_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rifrain_crypto'@'127.0.0.1' IDENTIFIED BY 'ganti-dengan-password-kuat';
GRANT SELECT, INSERT, CREATE ON rifrain_crypto_demo.* TO 'rifrain_crypto'@'127.0.0.1';
FLUSH PRIVILEGES;
```

Jika `DB_HOST` diatur ke alamat lain, sesuaikan host akun database dengan konfigurasi server. `.env` berisi kredensial database dan tidak boleh masuk version control.

## Struktur

- `public/` — halaman landing, login, dashboard, CSS, dan JavaScript.
- `src/` — koneksi database, sesi, validasi form, serta helper AES/RSA.
- `bin/setup.php` — setup database dan seed data terenkripsi (CLI only).
- `bin/deploy-cloudflare-tunnel.sh` — pemasangan vhost, tunnel ingress, dan DNS route.
- `storage/keys/` — key hasil setup; folder berada di luar document root.
- `database/schema.sql` — skema tabel.
- `deploy/` — konfigurasi Nginx untuk Cloudflare Tunnel.

## Subdomain melalui Cloudflare Tunnel

Server ini sudah memakai Cloudflare Tunnel untuk menghubungkan domain ke Nginx lokal. Jalankan `sudo bash /var/www/kriptografi.rifrain.tech/bin/deploy-cloudflare-tunnel.sh` untuk memasang vhost Nginx dan menambahkan hostname ke tunnel. DNS zone `rifrain.tech` harus memiliki CNAME yang menunjuk ke tunnel. Sertifikat `cloudflared` yang tersimpan lokal hanya berwenang mengubah zone `rifrain.my.id`, jadi buat CNAME di zone `rifrain.tech` melalui Cloudflare DNS. Perintah ini tidak memakai alamat IP router atau panel registrar. TLS publik berakhir di Cloudflare.

Konfigurasi memakai PHP-FPM 8.5 yang terpasang di server; kodenya kompatibel dengan PHP 8.4.

Dashboard ini sengaja selalu terbuka agar perbedaan akses data mudah didemonstrasikan. Karena itu, ini adalah simulasi dekripsi data dan bukan sistem autentikasi identitas.
