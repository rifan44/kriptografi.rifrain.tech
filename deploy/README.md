# Aktifkan subdomain

Domain `rifrain.tech` memakai nameserver Cloudflare. Cloudflare Tunnel bernama `asus` dan vhost Nginx sudah disiapkan dari server. Sertifikat login `cloudflared` di server terikat ke zone `rifrain.my.id`, jadi jangan gunakan `cloudflared tunnel route dns` untuk domain `rifrain.tech`.

Jalankan satu perintah dari SSH:

```sh
sudo bash /var/www/kriptografi.rifrain.tech/bin/deploy-cloudflare-tunnel.sh
```

Script memasang vhost untuk document root `public/`, menambahkan hostname ke ingress tunnel, lalu me-restart layanan tunnel. Restart tunnel dapat membuat situs lain pada tunnel yang sama terputus beberapa detik.

Di Cloudflare DNS untuk zone `rifrain.tech`, buat record:

- Type: `CNAME`
- Name: `kriptografi`
- Target: `8e74caeb-487a-4c3d-adcc-70bf4b0e0ac1.cfargotunnel.com`
- Proxy: aktif / Proxied

Hapus record keliru `kriptografi.rifrain.tech.rifrain.my.id` dari DNS zone `rifrain.my.id`; record itu dibuat oleh percobaan sebelumnya dan bukan alamat aplikasi yang benar.

Setelah script selesai, buka `https://kriptografi.rifrain.tech`. Unduh private key dari `/var/www/kriptografi.rifrain.tech/storage/keys/private.pem` ke perangkat pribadi setelah halaman HTTPS bisa dibuka, lalu hapus salinan server setelah kunci berhasil dicoba. Jangan menempelkan private key ke chat.
