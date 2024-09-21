## Radius Dashboard Management

### Informasi
- Dashboard Pelanggan Untuk Billing Freeradius.
- Mendukung daloradius management.
- Sebelum memasang ini, sudah harus memasang daloradius, php, mysql, tinyfm/tinyfilemanager, ttyd dan paket-paket pendukung daloradius lainnya.
- Instalasi dibawah ini hanya berlaku untuk OS OpenWrt, untuk OS selain itu silahkan dicoba sendiri, lalu kirimkan instruksi instalasi melalui [pull request](https://github.com/umblox/raddash/pulls) atau [issue](https://github.com/umblox/raddash/issues)

### Instalasi
1. Jalankan SSH melalui Terminal/Termius/Putty/TTYD, lalu jalankan perintah dibawah ini:
    ```sh
    opkg update && opkg install git git-http && cd /www && git clone https://github.com/umblox/raddash
    ```
2. Masuk ke folder `/www/raddash/config` dengan **tinyfm atau sftp**, lalu hapus kata `.default` dari nama-nama file yang ada dalam folder tersebut.
3. Masuk ke `database radius` lalu import `radiusbilling.sql`.

    > Abaikan step ke 3 jika sudah pernah memasang **radiusbot** dari Arneta.ID

4. Register 1 akun untuk `admin`.
5. Kembali ke `database radius` dan buka table `users`. edit akun yang akan di jadikan admin tadi, lalu setting kolom `is_admin` menjadi 1.
6. Pasang `url raddash` di `loginpage` agar mudah di akses pelanggan, silahkan sesuaikan kode html dibawah ini untuk dimasukkan ke file login page

   Kode tombol beli voucher, letakkan di dalam tag `<body>`:

   ```html
    <a href="http://192.168.1.1/raddash" class="custom-button"><i class="icon icon-voucher">&#xe803;</i>Beli Voucher</a>
    ```

   Style tombol beli voucher, letakkan di atas kode `<body>` (biasanya dibagian atas, didalam kode `<head>`):

   ```html
    <style>
    .custom-button {
        display: inline-block;
        background: linear-gradient(45deg, #ff4b2b, #ff416c); /* Warna gradien */
        color: white;
        padding: 12px 25px;
        font-size: 18px;
        text-align: center;
        text-decoration: none;
        border-radius: 50px; /* Bentuk tombol lebih bulat */
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2); /* Tambahkan efek bayangan */
        transition: all 0.3s ease-in-out; /* Efek transisi halus saat hover */
        position: relative;
        overflow: hidden;
    }
    
    .custom-button:hover {
        background: linear-gradient(45deg, #ff416c, #ff4b2b); /* Warna berubah saat hover */
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4); /* Bayangan lebih besar saat hover */
        transform: translateY(-3px); /* Tombol sedikit naik saat hover */
    }
    
    .custom-button i {
        margin-right: 10px; /* Jarak ikon dengan teks */
        vertical-align: middle;
    }
    
    .custom-button:before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        width: 300%;
        height: 300%;
        background: rgba(255, 255, 255, 0.2);
        transition: all 0.5s ease-in-out;
        transform: translateX(-100%) rotate(45deg);
    }
    
    .custom-button:hover:before {
        transform: translateX(100%) rotate(45deg); /* Efek highlight saat hover */
    }
    </style>
    ```
     > Sumber kode html dari mas [ʜᴇᴍᴋᴇʀ ғʀᴏᴍ ᴘᴀʟᴇsᴛɪɴᴇ 𝕿𝖆𝖓𝖙𝖊](https://t.me/mutiara_wrt/1/15005)

7. Coba register lagi sebagai akun pelanggan, lalu lakukan request topup.
8. Login lagi sebagai admin untuk melihat request topup dan mengambil tindakan konfirmasi jika sudah menerima pembayaran atau tolak jika tidak menerima pembayaran.
9. Juga akan update biar admin bisa konfirmasi topup melalui admin.

    > Untuk Radius Monitor kemungkinan bisa mengabaikan step 11.
    
10. Setting prefix voucher di config/prefix.php sesuaikan prefix yang di inginkan dan sesuaikan dengan nama plan.
11. Wajib menyamakan antara nama profile di **Management -> Profiles** dan nama plans di **Billing -> Plans** pada konfigurasi daloradius.

### To Do Next Update
- Notif telegram saat ada pelanggan request topup maupun saat pelanggan membeli voucher.
- Kombinasi dengan Radius Monitor.

### Credits
- Owner raddash: [Arneta.ID](https://github.com/umblox/raddash)
- Penulis Dokumentasi: [Helmi Amirudin](https://helmiau.com/pay)
- Penulis Contoh Kode HTML: [ʜᴇᴍᴋᴇʀ ғʀᴏᴍ ᴘᴀʟᴇsᴛɪɴᴇ 𝕿𝖆𝖓𝖙𝖊](https://t.me/mutiara_wrt/1/15005)
