<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kebijakan Privasi WhatsApp – E-Office BPKAD</title>
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
            background: #164e63;
            color: #fff;
            padding: 1.25rem 1rem;
        }

        header h1 {
            margin: 0;
            font-size: 1.4rem;
        }

        main {
            max-width: 860px;
            margin: 0 auto;
            padding: 1.5rem 1rem 3rem;
            background: #fff;
            box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        }

        h2 {
            margin-top: 2rem;
            font-size: 1.15rem;
            color: #0f3d4c;
        }

        a {
            color: #0d6efd;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        ul {
            padding-left: 1.1rem;
        }

        footer {
            text-align: center;
            font-size: .75rem;
            color: #555;
            margin: 2rem 0 1rem;
        }

        .updated {
            font-size: .75rem;
            color: #555;
            margin-top: .25rem;
        }

        .lang-switch {
            position: fixed;
            top: .5rem;
            right: .5rem;
        }

        .notice {
            background: #ecfdf5;
            border: 1px solid #10b98133;
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
            style="background:#0f766e;color:#fff;border:none;padding:.4rem .7rem;border-radius:4px;cursor:pointer;font-size:.7rem">EN
            / ID</button>
    </div>
    <header>
        <h1>Kebijakan Privasi WhatsApp – E‑Office BPKAD</h1>
    </header>
    <main id="content">
        <p class="notice"><strong>Ringkas:</strong> Sistem E‑Office hanya menggunakan WhatsApp untuk notifikasi surat
            masuk dan proses disposisi. Kami tidak menjual atau memindahkan data pribadi ke pihak ketiga komersial.</p>
        <h2>1. Ruang Lingkup</h2>
        <p>Halaman ini menjelaskan bagaimana integrasi WhatsApp pada aplikasi E‑Office BPKAD Kabupaten Tangerang
            mengelola data kontak dan pesan yang dikirim/diterima melalui API resmi Meta (WhatsApp Business Cloud API).
        </p>
        <h2>2. Data yang Dikumpulkan / Diproses</h2>
        <ul>
            <li><strong>Nomor WhatsApp Pimpinan & Pegawai</strong>: Diambil dari data kepegawaian untuk mengirim
                notifikasi dan interaksi disposisi.</li>
            <li><strong>Konten Pesan Sistem → Pengguna</strong>: Nomor surat, pengirim, perihal, tanggal, tautan
                internal aplikasi, serta tombol interaktif (DISPOSISI, AMBIL, dsb.).</li>
            <li><strong>Balasan Pengguna → Sistem</strong>: Perintah (SWITCH, BANTUAN, DISPOSISI, AMBIL) dan catatan
                instruksi/penolakan disposisi.</li>
            <li><strong>Log Integrasi</strong>: Status pengiriman (sukses/gagal), kode respons, ID pesan (wamid) untuk
                audit & troubleshooting.</li>
        </ul>
        <h2>3. Tujuan Penggunaan Data</h2>
        <ul>
            <li>Mempercepat alur persetujuan dan disposisi surat masuk.</li>
            <li>Mencatat jejak tindakan (audit trail) untuk akuntabilitas.</li>
            <li>Memastikan siapa yang pertama kali melakukan klaim disposisi secara atomik.</li>
        </ul>
        <h2>4. Dasar Hukum / Legal Basis</h2>
        <ul>
            <li>Pelaksanaan tugas kedinasan (manajemen surat & disposisi internal).</li>
            <li>Persetujuan implisit pegawai sebagai bagian penggunaan sistem kerja E‑Office (onboarding internal).</li>
        </ul>
        <h2>5. Penyimpanan & Retensi</h2>
        <ul>
            <li>Nomor telepon disimpan di basis data kepegawaian.</li>
            <li>Log integrasi disimpan untuk periode operasional & audit (retensi dapat diatur kebijakan internal).</li>
            <li>Cache sesi WhatsApp (context surat aktif) disimpan sementara (TTL maksimal 30 menit).</li>
        </ul>
        <h2>6. Keamanan</h2>
        <ul>
            <li>Token API WhatsApp disimpan di variabel lingkungan (.env) dan tidak dicatat di log.</li>
            <li>Komunikasi ke API menggunakan HTTPS.</li>
            <li>Validasi perintah & rate limiting mencegah spam/penyalahgunaan.</li>
        </ul>
        <h2>7. Berbagi Data</h2>
        <p>Tidak ada penjualan atau distribusi data nomor telepon/isi disposisi ke pihak ketiga komersial. Data hanya
            bergerak antara sistem E‑Office dan platform WhatsApp (Meta) sesuai proses pengiriman pesan.</p>
        <h2>8. Hak Pengguna / Subjek Data</h2>
        <ul>
            <li>Meminta koreksi nomor telepon yang salah.</li>
            <li>Meminta penghapusan catatan instruksi personal yang tidak lagi relevan (selama tidak melanggar ketentuan
                retensi audit).</li>
            <li>Meminta penonaktifan penggunaan WhatsApp (opsi fallback manual, jika disetujui manajemen).</li>
        </ul>
        <h2>9. Otomatisasi & Profiling</h2>
        <p>Tidak ada profiling komersial. Otomatisasi terbatas pada pengiriman notifikasi dan validasi perintah
            disposisi.</p>
        <h2>10. Perubahan Kebijakan</h2>
        <p>Kebijakan ini dapat diperbarui. Tanggal pembaruan terakhir akan ditampilkan di bawah.</p>
        <h2>11. Kontak</h2>
        <p>Untuk pertanyaan atau permintaan terkait privasi WhatsApp, hubungi admin E‑Office BPKAD Kabupaten Tangerang.
        </p>
        <p class="updated">Terakhir diperbarui: 24 Okt 2025</p>
        <hr>
        <h2>English Summary</h2>
        <p>This WhatsApp integration only uses phone numbers of internal staff to send inbound letter notifications and
            manage disposition workflow. No data is sold to third parties. Logs are kept for audit. You may request
            correction or deactivation if policy permits.</p>
    </main>
    <footer>
        &copy; 2025 BPKAD Kab. Tangerang – E‑Office
    </footer>
    <script>
        function toggleLang() {
            const html = document.documentElement;
            const current = html.lang || 'id';
            if (current === 'id') {
                html.lang = 'en';
                document.querySelector('header h1').textContent = 'WhatsApp Privacy Policy – E-Office BPKAD';
            } else {
                html.lang = 'id';
                document.querySelector('header h1').textContent = 'Kebijakan Privasi WhatsApp – E‑Office BPKAD';
            }
        }
    </script>
</body>

</html>
