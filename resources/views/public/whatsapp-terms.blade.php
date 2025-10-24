<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Ketentuan Layanan WhatsApp – E-Office BPKAD</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, sans-serif;
            line-height: 1.55;
            margin: 0;
            padding: 0;
            background: #f9fafb;
            color: #222;
        }

        header {
            background: #334155;
            color: #fff;
            padding: 1.25rem 1rem;
        }

        header h1 {
            margin: 0;
            font-size: 1.35rem;
        }

        main {
            max-width: 880px;
            margin: 0 auto;
            padding: 1.5rem 1rem 3rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }

        h2 {
            margin-top: 2rem;
            font-size: 1.15rem;
            color: #1e293b;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        ul {
            padding-left: 1.15rem;
        }

        footer {
            text-align: center;
            font-size: .75rem;
            color: #555;
            margin: 2rem 0 1rem;
        }

        .updated {
            font-size: .7rem;
            color: #555;
            margin-top: .25rem;
        }

        .lang-switch {
            position: fixed;
            top: .5rem;
            right: .5rem;
        }

        .notice {
            background: #eff6ff;
            border: 1px solid #60a5fa55;
            padding: .75rem 1rem;
            border-radius: 6px;
            font-size: .85rem;
        }

        .section {
            margin-top: 1.25rem;
        }

        code {
            background: #eee;
            padding: .15rem .35rem;
            border-radius: 4px;
            font-size: .75rem;
        }

        @media (max-width:600px) {
            main {
                padding: 1rem .75rem;
            }

            header h1 {
                font-size: 1.15rem;
            }
        }
    </style>
</head>

<body>
    <div class="lang-switch">
        <button onclick="toggleLang()"
            style="background:#1e293b;color:#fff;border:none;padding:.4rem .7rem;border-radius:4px;cursor:pointer;font-size:.7rem">EN
            / ID</button>
    </div>
    <header>
        <h1>Ketentuan Layanan WhatsApp – E‑Office BPKAD</h1>
    </header>
    <main>
        <p class="notice"><strong>Tujuan:</strong> Integrasi WhatsApp digunakan hanya untuk mempercepat notifikasi dan
            proses disposisi surat. Dengan menggunakan sistem ini, pengguna menyetujui ketentuan berikut.</p>
        <h2>1. Definisi</h2>
        <ul>
            <li><strong>Sistem</strong>: Aplikasi E‑Office BPKAD Kab. Tangerang.</li>
            <li><strong>Integrasi WhatsApp</strong>: Fitur pengiriman dan penerimaan perintah via API resmi Meta.</li>
            <li><strong>Pengguna</strong>: Pegawai yang terdaftar dan memiliki hak akses sesuai peran.</li>
        </ul>
        <h2>2. Ruang Lingkup Penggunaan</h2>
        <ul>
            <li>Hanya untuk keperluan kedinasan (notifikasi surat masuk, disposisi, klaim pekerjaan).</li>
            <li>Dilarang mengirim konten promosi, spam, atau informasi di luar konteks pekerjaan.</li>
            <li>Penyalahgunaan (memaksa klaim, manipulasi instruksi) dapat dicatat dan ditindak.</li>
        </ul>
        <h2>3. Akses & Peran</h2>
        <ul>
            <li>Nomor pimpinan menerima notifikasi awal dan memulai disposisi.</li>
            <li>Pegawai yang dituju dapat melakukan klaim (AMBIL) dan mengirim catatan tindak lanjut.</li>
            <li>Rate limit diberlakukan untuk mencegah spam perintah (SWITCH, BANTUAN, AMBIL, catatan).</li>
        </ul>
        <h2>4. Tanggung Jawab Pengguna</h2>
        <ul>
            <li>Menjaga keamanan perangkat dan aplikasi WhatsApp.</li>
            <li>Tidak membagikan informasi internal ke luar tanpa otorisasi.</li>
            <li>Segera melaporkan kesalahan atau akses tidak sah ke administrator.</li>
        </ul>
        <h2>5. Keandalan & Batasan</h2>
        <ul>
            <li>Pengiriman pesan bergantung pada ketersediaan layanan WhatsApp dan koneksi internet.</li>
            <li>Sistem tidak menjamin 100% pesan selalu sampai (retry logis terbatas).</li>
            <li>Jika API gagal sementara, proses dapat dilanjutkan melalui aplikasi web langsung.</li>
        </ul>
        <h2>6. Penangguhan & Penghentian</h2>
        <ul>
            <li>Administrator dapat menonaktifkan integrasi sementara untuk pemeliharaan.</li>
            <li>Nomor yang disalahgunakan dapat diblokir dari menerima perintah otomatis.</li>
        </ul>
        <h2>7. Perubahan Ketentuan</h2>
        <p>Ketentuan dapat diperbarui. Versi terbaru menggantikan versi sebelumnya. Perubahan material akan
            diinformasikan melalui pengumuman internal.</p>
        <h2>8. Kontak</h2>
        <p>Masalah atau pelanggaran silakan hubungi administrator/developer E‑Office BPKAD.</p>
        <p class="updated">Terakhir diperbarui: 24 Okt 2025</p>
        <hr>
        <h2>English Summary</h2>
        <p>WhatsApp integration is for official letter notification and disposition workflow only. No commercial or
            unrelated use permitted. Abuse may result in suspension. Service reliability depends on WhatsApp platform
            availability.</p>
        <hr>
        <p><a href="/whatsapp-privacy">Kebijakan Privasi</a> · <a href="/data-deletion">Penghapusan Data</a></p>
    </main>
    <footer>&copy; 2025 BPKAD Kab. Tangerang – E‑Office</footer>
    <script>
        function toggleLang() {
            const h = document.querySelector('header h1');
            if (document.documentElement.lang === 'id') {
                document.documentElement.lang = 'en';
                h.textContent = 'WhatsApp Terms of Service – E-Office BPKAD';
            } else {
                document.documentElement.lang = 'id';
                h.textContent = 'Ketentuan Layanan WhatsApp – E‑Office BPKAD';
            }
        }
    </script>
</body>

</html>
