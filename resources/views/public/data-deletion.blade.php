<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Penghapusan Data – E-Office BPKAD</title>
    <style>
        body {
            font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, sans-serif;
            line-height: 1.55;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #222;
        }

        header {
            background: #475569;
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
            color: #334155;
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
            background: #f1f5f9;
            border: 1px solid #94a3b844;
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
            style="background:#334155;color:#fff;border:none;padding:.4rem .7rem;border-radius:4px;cursor:pointer;font-size:.7rem">EN
            / ID</button>
    </div>
    <header>
        <h1>Penghapusan Data – E‑Office BPKAD</h1>
    </header>
    <main>
        <p class="notice"><strong>Ringkas:</strong> Anda dapat meminta penghapusan atau koreksi data tertentu dengan
            menghubungi administrator/developer resmi. Tidak semua data operasional dapat dihapus karena kebutuhan
            audit.</p>
        <h2>1. Jenis Data yang Dapat Diminta Dihapus</h2>
        <ul>
            <li><strong>Nomor Telepon Pegawai</strong> (jika pegawai tidak lagi aktif atau memilih keluar dari integrasi
                WhatsApp).</li>
            <li><strong>Catatan Instruksi Disposisi</strong> yang bersifat pribadi dan tidak relevan lagi (selama tidak
                melemahkan rekam jejak keputusan).</li>
            <li><strong>Data Profil Tambahan</strong> (alamat surel alternatif, dsb.) jika tercatat secara opsional.
            </li>
        </ul>
        <h2>2. Data yang Tidak Dapat Dihapus Penuh</h2>
        <ul>
            <li>Log audit sistem, termasuk histroy status surat & disposisi (diperlukan untuk akuntabilitas).</li>
            <li>Rekam integrasi penting (ID pesan, status) selama masa retensi kebijakan internal.</li>
        </ul>
        <h2>3. Cara Mengajukan Permintaan Penghapusan / Koreksi</h2>
        <ol style="padding-left:1.15rem;">
            <li>Siapkan identitas pegawai (Nama, NIP/NIK internal, Nomor Telepon).</li>
            <li>Tentukan data spesifik yang ingin dihapus atau dikoreksi.</li>
            <li>Kirim email atau pesan resmi ke administrator / developer aplikasi:</li>
        </ol>
        <pre style="background:#f8fafc;border:1px solid #cbd5e1;padding:.75rem;font-size:.75rem;overflow:auto;">Subjek: Permintaan Penghapusan/Koreksi Data E-Office
Isi:
Saya (Nama, NIP) mengajukan permintaan penghapusan/koreksi data berikut:
- (Sebutkan data)
Alasan: (Alasan ringkas)
Tanggal: (Tanggal permintaan)
Terima kasih.</pre>
        <h2>4. Proses Internal</h2>
        <ul>
            <li>Administrator akan memverifikasi identitas & otorisasi.</li>
            <li>Data yang memenuhi kriteria akan dihapus/diperbarui (biasanya 3–7 hari kerja).</li>
            <li>Jika ditolak, alasan penolakan akan diinformasikan.</li>
        </ul>
        <h2>5. Keamanan & Bukti</h2>
        <p>Salinan permintaan dan tindakan akan disimpan sebagai referensi internal. Data yang dihapus tidak dapat
            dipulihkan kecuali masih berada dalam backup yang belum melalui siklus rotasi.</p>
        <h2>6. Kontak</h2>
        <p>Email resmi / kanal komunikasi internal (disesuaikan kebijakan BPKAD) digunakan untuk permintaan ini. Mohon
            hindari mengirim melalui kanal publik.</p>
        <p class="updated">Terakhir diperbarui: 24 Okt 2025</p>
        <hr>
        <h2>English Summary</h2>
        <p>You may request deletion or correction of certain personal data (phone number, disposition notes) by
            contacting the administrator/developer. Audit and operational logs cannot be fully deleted. Requests are
            verified and processed within internal policy timelines.</p>
        <hr>
        <p><a href="/whatsapp-privacy">Kebijakan Privasi</a> · <a href="/whatsapp-terms">Ketentuan Layanan</a></p>
    </main>
    <footer>&copy; 2025 BPKAD Kab. Tangerang – E‑Office</footer>
    <script>
        function toggleLang() {
            const h = document.querySelector('header h1');
            if (document.documentElement.lang === 'id') {
                document.documentElement.lang = 'en';
                h.textContent = 'Data Deletion – E-Office BPKAD';
            } else {
                document.documentElement.lang = 'id';
                h.textContent = 'Penghapusan Data – E‑Office BPKAD';
            }
        }
    </script>
</body>

</html>
