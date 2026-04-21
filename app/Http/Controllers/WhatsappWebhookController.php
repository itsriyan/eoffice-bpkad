<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Integrations\WhatsappClient;
use App\Models\WorkUnit;
use App\Models\Employee;

/**
 * Webhook handler untuk Fonnte (https://fonnte.com).
 *
 * Format payload Fonnte (POST) – FLAT, tidak ada wrapper:
 * {
 *   "device"   : "628xxx",
 *   "sender"   : "628xxx",
 *   "message"  : "teks pesan",
 *   "text"     : "teks tombol (deprecated)",
 *   "name"     : "Nama Pengirim",
 *   "member"   : "",
 *   "location" : "",
 *   "inboxid"  : "<id_pesan>",
 *   "timestamp": 1713687626,
 *   "url"      : "",
 *   "filename" : "",
 *   "extension": ""
 * }
 */
class WhatsappWebhookController extends Controller
{
    /**
     * GET – tetap tersedia agar URL webhook dapat di-ping.
     * Fonnte tidak menggunakan challenge verification seperti Meta.
     */
    public function verify(Request $request)
    {
        return response('OK', 200);
    }

    /**
     * POST – terima pesan masuk dari Fonnte.
     *
     * Payload Fonnte FLAT (tidak ada wrapper "data"):
     *   device   = nomor device
     *   sender   = nomor pengirim (628xxx, tanpa @s.whatsapp.net)
     *   message  = isi pesan teks
     *   text     = teks tombol yang diklik (hanya untuk balasan tombol)
     *   name     = nama pengirim
     *   inboxid  = id pesan masuk
     *   member   = anggota grup yang kirim (jika dari grup)
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // Fonnte kirim payload flat – tidak ada wrapper 'data'
        // Minimal harus ada field 'sender' agar bisa diproses
        $sender = $payload['sender'] ?? null;
        if (! $sender) {
            Log::channel('whatsapp')->info('Incoming Fonnte webhook – no sender field', ['payload_keys' => array_keys($payload)]);
            return response()->json(['status' => 'ok']);
        }

        // Fonnte tidak mengirim field 'type'.
        // Field 'text' berisi teks tombol jika balasan tombol (fitur deprecated).
        // Kita gabungkan: utamakan 'message', fallback ke 'text' (tombol deprecated).
        $msgText = trim($payload['message'] ?? '') ?: trim($payload['text'] ?? '');
        $type    = 'text'; // Fonnte selalu dianggap teks; kita handle sendiri
        $msgId   = $payload['inboxid'] ?? null;

        Log::channel('whatsapp')->info('Incoming Fonnte webhook', [
            'sender' => $sender,
            'msg'    => $msgText,
            'inboxid' => $msgId,
        ]);

        // Simpan raw webhook untuk audit
        DB::table('integration_logs')->insert([
            'service'          => 'whatsapp-webhook',
            'endpoint'         => 'incoming',
            'method'           => 'POST',
            'request_payload'  => json_encode($payload),
            'response_body'    => null,
            'status_code'      => 200,
            'success'          => true,
            'attempt'          => 1,
            'message_id'       => $msgId,
            'correlation_id'   => null,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Normalisasi nomor pengirim ke format 62xxx
        $from = preg_replace('/[^0-9]/', '', $sender);
        if (str_starts_with($from, '0')) {
            $from = '62' . substr($from, 1);
        }

        if (! $from) {
            return response()->json(['status' => 'ok']);
        }

        $session = wa_session_get($from);
        $multi   = wa_multi_session_get($from);

        // ── Pesan teks ───────────────────────────────────────────────────────
        if ($type === 'text') {
            // ── DAFTAR command ────────────────────────────────────────────────
            if (preg_match('/^DAFTAR$/i', $msgText)) {
                $this->handleDaftar($from, $session, $multi);
                return response()->json(['status' => 'ok']);
            }

            // ── BATAL command ─────────────────────────────────────────────────
            if (preg_match('/^BATAL$/i', $msgText)) {
                $this->handleBatal($from, $session);
                return response()->json(['status' => 'ok']);
            }

            // ── GANTI command (minta pindah surat saat mid-flow) ──────────────
            if (preg_match('/^GANTI$/i', $msgText)) {
                $this->handleGanti($from, $session, $multi);
                return response()->json(['status' => 'ok']);
            }

            // BANTUAN command
            if (preg_match('/^BANTUAN$/i', $msgText)) {
                $limit = config('e-office.whatsapp.rate_limit.help_per_minute');
                if (wa_rate_limit_exceeded($from, 'help', $limit)) {
                    app(WhatsappClient::class)->sendText($from, __('Terlalu sering meminta bantuan. Coba lagi sebentar.'));
                    $this->logRateLimit($from, 'rate-help', $msgId, 429);
                    return response()->json(['status' => 'ok']);
                }
                wa_rate_limit_hit($from, 'help');
                $this->sendHelpText($from, $session, $multi);
                return response()->json(['status' => 'ok']);
            }

            // SWITCH command (lama – tetap didukung)
            if (preg_match('/^SWITCH\s+(.+)/i', $msgText, $m)) {
                $limit = config('e-office.whatsapp.rate_limit.switch_per_minute');
                if (wa_rate_limit_exceeded($from, 'switch', $limit)) {
                    app(WhatsappClient::class)->sendText($from, __('Terlalu sering ganti konteks. Tunggu sebentar.'));
                    $this->logRateLimit($from, 'rate-switch', $msgId, 429);
                    return response()->json(['status' => 'ok']);
                }
                wa_rate_limit_hit($from, 'switch');
                $targetNumber = trim($m[1]);
                $letter       = \App\Models\IncomingLetter::where('letter_number', $targetNumber)->first();
                if ($letter && $multi && in_array($letter->id, $multi['letters'], true)) {
                    wa_multi_session_set_active($from, $letter->id);
                    wa_session_set($from, [
                        'letter_id' => $letter->id,
                        'phase'     => 'awaiting_action',
                        'ts'        => now()->timestamp,
                    ]);
                    app(WhatsappClient::class)->sendText($from, __('Konteks berpindah ke surat :num', ['num' => $letter->letter_number]));
                } else {
                    app(WhatsappClient::class)->sendText($from, __('Surat tidak ditemukan atau tidak dalam daftar pending.'));
                }
                return response()->json(['status' => 'ok']);
            }

            // ── Phase: pilih surat dari daftar (DAFTAR command) ──────────────
            if ($session && ($session['phase'] ?? '') === 'select_letter') {
                if (preg_match('/^\s*(\d+)\s*$/', $msgText, $m)) {
                    $this->handleSelectLetter($from, (int) $m[1], $session);
                } else {
                    app(WhatsappClient::class)->sendText($from, __('Balas dengan angka sesuai nomor surat, atau ketik *BATAL*.'));
                }
                return response()->json(['status' => 'ok']);
            }

            // ── Phase: konfirmasi GANTI saat mid-flow ────────────────────────
            if ($session && ($session['phase'] ?? '') === 'confirm_switch') {
                if (preg_match('/^\s*1\s*$/', $msgText)) {
                    // Lanjutkan alur sebelumnya
                    if (wa_session_restore_snapshot($from)) {
                        $restored = wa_session_get($from);
                        $num      = \App\Models\IncomingLetter::find($restored['letter_id'] ?? 0)?->letter_number ?? '?';
                        app(WhatsappClient::class)->sendText($from, "Kembali ke alur surat *{$num}*. Silakan lanjutkan.");
                    } else {
                        wa_session_forget($from);
                        app(WhatsappClient::class)->sendText($from, __('Sesi sebelumnya sudah kedaluwarsa. Ketik *DAFTAR* untuk mulai.'));
                    }
                } elseif (preg_match('/^\s*2\s*$/', $msgText)) {
                    // Batalkan alur lama, tampilkan daftar
                    wa_session_forget_snapshot($from);
                    wa_session_forget($from);
                    $this->handleDaftar($from, null, wa_multi_session_get($from));
                } else {
                    app(WhatsappClient::class)->sendText($from, __('Balas *1* untuk lanjutkan atau *2* untuk pindah surat.'));
                }
                return response()->json(['status' => 'ok']);
            }

            // ── Phase: konfirmasi BATAL saat mid-flow ────────────────────────
            if ($session && ($session['phase'] ?? '') === 'confirm_cancel') {
                if (preg_match('/^\s*1\s*$/', $msgText)) {
                    // Ya, batalkan
                    wa_session_forget($from);
                    app(WhatsappClient::class)->sendText($from, __('Alur dibatalkan. Ketik *DAFTAR* untuk membuka daftar surat, atau *BANTUAN* untuk bantuan.'));
                } elseif (preg_match('/^\s*2\s*$/', $msgText)) {
                    // Tidak, lanjutkan – restore
                    if (wa_session_restore_snapshot($from)) {
                        $restored = wa_session_get($from);
                        $num      = \App\Models\IncomingLetter::find($restored['letter_id'] ?? 0)?->letter_number ?? '?';
                        app(WhatsappClient::class)->sendText($from, "Kembali ke alur surat *{$num}*. Silakan lanjutkan.");
                    } else {
                        wa_session_forget($from);
                        app(WhatsappClient::class)->sendText($from, __('Sesi sudah kedaluwarsa. Ketik *DAFTAR* untuk mulai.'));
                    }
                } else {
                    app(WhatsappClient::class)->sendText($from, __('Balas *1* untuk batalkan atau *2* untuk lanjutkan.'));
                }
                return response()->json(['status' => 'ok']);
            }

            // ── Pencarian pegawai berdasarkan nama ──────────────────────────
            if ($session && ($session['phase'] ?? '') === 'search_employee') {
                $this->handleEmployeeSearch($from, $msgText, $session);
                return response()->json(['status' => 'ok']);
            }

            // Catatan yang sedang ditunggu (expect)
            if ($session && isset($session['expect'])) {
                $this->handleExpectedNote($from, $session, $msgText);
                return response()->json(['status' => 'ok']);
            }

            // ── Menu angka – pilih tipe disposisi ────────────────────────────
            // Harus dicek SEBELUM menu tindakan surat agar "1"/"2" tidak disalahartikan
            if (preg_match('/^\s*([1-2])\s*$/', $msgText, $m) && $session && ($session['phase'] ?? '') === 'choose_disposition_type') {
                $choice = $m[1] === '1' ? 'choose_unit' : 'choose_employee';
                $this->handleButtonChoice($from, $choice);
                return response()->json(['status' => 'ok']);
            }

            // ── Menu angka – pilih unit/pegawai dari daftar ──────────────────
            if (preg_match('/^\s*(\d+)\s*$/', $msgText, $m) && $session && in_array($session['phase'] ?? '', ['list_unit', 'list_employee'], true)) {
                $this->handleNumberedListSelection($from, (int) $m[1], $session, $msgId);
                return response()->json(['status' => 'ok']);
            }

            // ── Menu angka – klaim disposisi ─────────────────────────────────
            if (preg_match('/^\s*1\s*$/', $msgText) && $session && ($session['phase'] ?? '') === 'claim_broadcast') {
                $limit = config('e-office.whatsapp.rate_limit.claim_per_minute');
                if (wa_rate_limit_exceeded($from, 'claim', $limit)) {
                    app(WhatsappClient::class)->sendText($from, __('Terlalu banyak percobaan klaim. Coba lagi nanti.'));
                    $this->logRateLimit($from, 'rate-claim', $msgId, 429);
                    return response()->json(['status' => 'ok']);
                }
                wa_rate_limit_hit($from, 'claim');
                $this->handleClaimDisposition($from, ['id' => $msgId]);
                return response()->json(['status' => 'ok']);
            }

            // ── Menu angka – tindakan surat (1/2/3) ──────────────────────────
            if (preg_match('/^\s*([1-3])\s*$/', $msgText, $m) && $session && ($session['phase'] ?? '') === 'awaiting_action') {
                switch ($m[1]) {
                    case '1':
                        $this->handleDisposisi($from, ['id' => $msgId]);
                        break;
                    case '2':
                        $this->requestArchiveNote($from, ['id' => $msgId]);
                        break;
                    case '3':
                        $this->requestRejectNote($from, ['id' => $msgId]);
                        break;
                }
                return response()->json(['status' => 'ok']);
            }
        }

        return response()->json(['status' => 'ok']);
    }

    // =========================================================================
    // Command handlers: DAFTAR / BATAL / GANTI
    // =========================================================================

    /**
     * DAFTAR – tampilkan semua surat pending sebagai daftar bernomor.
     * Jika tidak ada surat pending, beri tahu user.
     */
    private function handleDaftar(string $from, ?array $session, ?array $multi): void
    {
        if (empty($multi['letters'])) {
            app(WhatsappClient::class)->sendText($from, __('Tidak ada surat pending saat ini.'));
            return;
        }

        $letters  = \App\Models\IncomingLetter::whereIn('id', $multi['letters'])->get()->keyBy('id');
        $activeId = $multi['active_letter_id'];
        $items    = [];
        $listMap  = [];
        $i        = 1;
        foreach ($multi['letters'] as $lid) {
            $l = $letters[$lid] ?? null;
            if (! $l) continue;
            $marker      = $l->id === $activeId ? ' ✓' : '';
            $items[]     = [
                'num'         => $i,
                'title'       => $l->letter_number . $marker,
                'description' => 'Dari: ' . $l->sender . ' | ' . ($l->status->value ?? '-'),
                'id'          => $l->id,
            ];
            $listMap[$i] = $l->id;
            $i++;
        }

        wa_session_set($from, [
            'phase'      => 'select_letter',
            'list_items' => $listMap,
            'ts'         => now()->timestamp,
        ]);

        app(WhatsappClient::class)->sendNumberedList(
            $from,
            "*Daftar Surat Pending* (" . count($items) . ")\nPilih angka untuk mulai tindakan, atau ketik *BATAL* untuk keluar:",
            $items
        );
    }

    /**
     * User memilih angka dari daftar DAFTAR.
     * Set surat aktif dan tampilkan menu tindakan.
     */
    private function handleSelectLetter(string $from, int $num, array $session): void
    {
        $listMap = $session['list_items'] ?? [];
        $letterId = $listMap[$num] ?? null;

        if (! $letterId) {
            app(WhatsappClient::class)->sendText($from, __('Pilihan tidak valid. Balas dengan angka yang sesuai, atau ketik *BATAL*.'));
            return;
        }

        $letter = \App\Models\IncomingLetter::find($letterId);
        if (! $letter) {
            app(WhatsappClient::class)->sendText($from, __('Surat tidak ditemukan.'));
            wa_session_forget($from);
            return;
        }

        wa_multi_session_set_active($from, $letterId);
        wa_session_set($from, [
            'letter_id' => $letterId,
            'phase'     => 'awaiting_action',
            'ts'        => now()->timestamp,
        ]);

        $text = "*Surat: {$letter->letter_number}*\n"
            . "Dari: {$letter->sender}\n"
            . "Perihal: {$letter->subject}\n"
            . "Tgl Terima: " . ($letter->received_date?->translatedFormat('d M Y') ?? '-') . "\n\n"
            . "Pilih tindakan:\n"
            . "*1* - Disposisi\n"
            . "*2* - Arsipkan\n"
            . "*3* - Tolak\n\n"
            . "Ketik *BATAL* untuk kembali ke daftar.";

        app(WhatsappClient::class)->sendText($from, $text);
    }

    /**
     * BATAL – batalkan alur saat ini.
     * Jika sedang mid-flow: minta konfirmasi dulu.
     * Jika idle / di select_letter / awaiting_action: langsung batal.
     */
    private function handleBatal(string $from, ?array $session): void
    {
        $phase = $session['phase'] ?? null;

        // Phase yang langsung bisa dibatalkan tanpa konfirmasi
        $directCancel = [null, 'awaiting_action', 'select_letter', 'template_sent', 'template_sent_manual'];

        if (in_array($phase, $directCancel, true)) {
            wa_session_forget($from);
            app(WhatsappClient::class)->sendText($from, __('Dibatalkan. Ketik *DAFTAR* untuk membuka daftar surat, atau *BANTUAN* untuk bantuan.'));
            return;
        }

        // Mid-flow – minta konfirmasi
        wa_session_save_snapshot($from);
        wa_session_set($from, [
            'phase' => 'confirm_cancel',
            'ts'    => now()->timestamp,
        ]);
        $num = \App\Models\IncomingLetter::find($session['letter_id'] ?? 0)?->letter_number ?? '?';
        app(WhatsappClient::class)->sendText(
            $from,
            "Anda sedang dalam alur surat *{$num}*. Batalkan?\n\n*1* - Ya, batalkan\n*2* - Tidak, lanjutkan"
        );
    }

    /**
     * GANTI – minta pindah ke surat lain saat mid-flow.
     */
    private function handleGanti(string $from, ?array $session, ?array $multi): void
    {
        if (empty($multi['letters']) || count($multi['letters']) <= 1) {
            app(WhatsappClient::class)->sendText($from, __('Tidak ada surat lain yang pending.'));
            return;
        }

        if (wa_session_is_mid_flow($session)) {
            // Mid-flow: simpan snapshot lalu konfirmasi
            wa_session_save_snapshot($from);
            wa_session_set($from, [
                'phase' => 'confirm_switch',
                'ts'    => now()->timestamp,
            ]);
            $num = \App\Models\IncomingLetter::find($session['letter_id'] ?? 0)?->letter_number ?? '?';
            app(WhatsappClient::class)->sendText(
                $from,
                "Alur surat *{$num}* belum selesai.\n\n*1* - Lanjutkan surat ini\n*2* - Batalkan & pilih surat lain"
            );
        } else {
            // Idle / awaiting: langsung tampilkan daftar
            wa_session_forget($from);
            $this->handleDaftar($from, null, $multi);
        }
    }

    /**
     * BANTUAN – tampilkan daftar perintah yang tersedia.
     */
    private function sendHelpText(string $from, ?array $session, ?array $multi): void
    {
        $count   = count($multi['letters'] ?? []);
        $phase   = $session['phase'] ?? null;
        $pending = $count > 0 ? "\n\n📋 Surat pending: *{$count}*. Ketik *DAFTAR* untuk lihat." : '';

        $text = "*Perintah yang tersedia:*\n"
            . "*DAFTAR* - Lihat semua surat pending\n"
            . "*BATAL* - Batalkan alur saat ini\n"
            . "*GANTI* - Pindah ke surat lain\n"
            . "*BANTUAN* - Tampilkan pesan ini"
            . $pending;

        if ($phase && ! in_array($phase, [null, 'awaiting_action', 'select_letter'], true)) {
            $num  = \App\Models\IncomingLetter::find($session['letter_id'] ?? 0)?->letter_number ?? '?';
            $text .= "\n\n⚠️ Alur aktif: *{$num}* (fase: {$phase})";
        }

        app(WhatsappClient::class)->sendText($from, $text);
    }

    // =========================================================================
    // Private action handlers
    // =========================================================================

    private function handleDisposisi(?string $from, array $message): void
    {
        if (! $from) return;
        $session          = wa_session_get($from) ?? [];
        $session['phase'] = 'choose_disposition_type';
        wa_session_set($from, $session);

        app(WhatsappClient::class)->sendMenu($from, __('Pilih tujuan disposisi:'), [
            '1' => __('Unit Kerja'),
            '2' => __('Pegawai'),
        ]);
    }

    private function handleClaimDisposition(?string $from, array $message): void
    {
        if (! $from) return;
        $session = wa_session_get($from) ?? [];
        $dispId  = $session['disposition_id'] ?? null;
        if (! $dispId) {
            app(WhatsappClient::class)->sendText($from, __('Tidak ada disposisi untuk diambil.'));
            return;
        }
        $disp = \App\Models\Disposition::find($dispId);
        if (! $disp) {
            app(WhatsappClient::class)->sendText($from, __('Disposisi tidak ditemukan.'));
            return;
        }
        $claimedUser = Employee::where('phone_number', 'like', '%' . substr($from, -9) . '%')->first();
        $userId      = $claimedUser?->user_id;
        if (! $userId) {
            app(WhatsappClient::class)->sendText($from, __('Nomor tidak terdaftar sebagai pegawai.'));
            return;
        }
        $updated = \App\Models\Disposition::where('id', $disp->id)
            ->whereNull('claimed_by_user_id')
            ->update([
                'claimed_by_user_id' => $userId,
                'claimed_at'         => now(),
                'to_user_id'         => $userId,
                'to_name'            => $claimedUser->name,
                'to_phone'           => $claimedUser->phone_number,
                'status'             => \App\Enums\DispositionStatus::Received,
                'received_at'        => now(),
            ]);
        if ($updated === 0) {
            $claimer = $disp->claimed_by_user_id ? ($disp->claimedByUser?->name ?? __('Tidak diketahui')) : __('Tidak diketahui');
            app(WhatsappClient::class)->sendText($from, __('Disposisi sudah diambil oleh :name.', ['name' => $claimer]));
            return;
        }
        $disp->refresh();
        $letter = $disp->letter;
        if ($letter && $letter->status === \App\Enums\IncomingLetterStatus::New) {
            $letter->update([
                'status'           => \App\Enums\IncomingLetterStatus::Disposed,
                'disposed_at'      => now(),
                'last_disposition' => $disp->id,
            ]);
        } elseif ($letter) {
            $letter->update(['last_disposition' => $disp->id]);
        }
        DB::table('integration_logs')->insert([
            'service'         => 'whatsapp-webhook',
            'endpoint'        => 'claim-disposition',
            'method'          => 'SYSTEM',
            'request_payload' => json_encode(['disposition_id' => $disp->id, 'claimer_user_id' => $userId]),
            'response_body'   => null,
            'status_code'     => 200,
            'success'         => true,
            'attempt'         => 1,
            'message_id'      => $message['id'] ?? null,
            'correlation_id'  => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
        wa_session_set($from, [
            'letter_id'      => $disp->incoming_letter_id,
            'expect'         => 'disposition_note',
            'disposition_id' => $disp->id,
        ]);
        app(WhatsappClient::class)->sendText($from, __('Anda berhasil mengambil disposisi. Kirim catatan instruksi (balas dengan teks).'));
    }

    private function requestArchiveNote(?string $from, array $message): void
    {
        if (! $from) return;
        $session  = wa_session_get($from) ?? [];
        $multi    = wa_multi_session_get($from);
        // Pastikan letter_id tersimpan di session sebelum menunggu catatan
        if (empty($session['letter_id']) && ! empty($multi['active_letter_id'])) {
            $session['letter_id'] = $multi['active_letter_id'];
        }
        if (empty($session['letter_id'])) {
            app(WhatsappClient::class)->sendText($from, __('Tidak ada surat aktif dalam sesi.'));
            return;
        }
        $session['expect'] = 'archive_note';
        wa_session_set($from, $session);
        app(WhatsappClient::class)->sendText($from, __('Ketik catatan pengarsipan, lalu kirim.'));
    }

    private function requestRejectNote(?string $from, array $message): void
    {
        if (! $from) return;
        $session  = wa_session_get($from) ?? [];
        $multi    = wa_multi_session_get($from);
        // Pastikan letter_id tersimpan di session sebelum menunggu catatan
        if (empty($session['letter_id']) && ! empty($multi['active_letter_id'])) {
            $session['letter_id'] = $multi['active_letter_id'];
        }
        if (empty($session['letter_id'])) {
            app(WhatsappClient::class)->sendText($from, __('Tidak ada surat aktif dalam sesi.'));
            return;
        }
        $session['expect'] = 'reject_note';
        wa_session_set($from, $session);
        app(WhatsappClient::class)->sendText($from, __('Ketik alasan penolakan, lalu kirim.'));
    }

    private function handleExpectedNote(string $from, array $session, string $note): void
    {
        $expect   = $session['expect'];
        $letterId = $session['letter_id'] ?? null;
        $letter   = $letterId ? \App\Models\IncomingLetter::find($letterId) : null;
        if (! $letter) {
            app(WhatsappClient::class)->sendText($from, __('Surat tidak ditemukan dalam sesi.'));
            wa_session_forget($from);
            return;
        }
        $limit = config('e-office.whatsapp.rate_limit.note_per_minute');
        if (wa_rate_limit_exceeded($from, 'note', $limit)) {
            app(WhatsappClient::class)->sendText($from, __('Terlalu banyak catatan dikirim. Coba lagi nanti.'));
            return;
        }
        wa_rate_limit_hit($from, 'note');
        if ($expect === 'archive_note') {
            $letter->update([
                'status'           => \App\Enums\IncomingLetterStatus::Archived,
                'archived_at'      => now(),
                'last_disposition' => 'Arsipkan',
            ]);
            DB::table('integration_logs')->insert([
                'service'         => 'whatsapp-webhook',
                'endpoint'        => 'archive-note',
                'method'          => 'SYSTEM',
                'request_payload' => json_encode(['note' => $note, 'letter_id' => $letterId]),
                'response_body'   => null,
                'status_code'     => 200,
                'success'         => true,
                'attempt'         => 1,
                'message_id'      => null,
                'correlation_id'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            app(WhatsappClient::class)->sendText($from, __('Surat diarsipkan. Catatan: :note', ['note' => $note]));
        } elseif ($expect === 'reject_note') {
            $letter->update([
                'status'           => \App\Enums\IncomingLetterStatus::Rejected,
                'last_disposition' => 'Ditolak',
            ]);
            \App\Models\Disposition::create([
                'incoming_letter_id' => $letter->id,
                'from_user_id'       => $letter->user_id,
                'from_name'          => $letter->user?->name ?? 'System',
                'from_phone'         => $from,
                'rejection_reason'   => $note,
                'status'             => \App\Enums\DispositionStatus::Rejected,
                'channel'            => 'whatsapp',
                'sequence'           => ($letter->dispositions()->count() + 1),
            ]);
            DB::table('integration_logs')->insert([
                'service'         => 'whatsapp-webhook',
                'endpoint'        => 'reject-note',
                'method'          => 'SYSTEM',
                'request_payload' => json_encode(['note' => $note, 'letter_id' => $letterId]),
                'response_body'   => null,
                'status_code'     => 200,
                'success'         => true,
                'attempt'         => 1,
                'message_id'      => null,
                'correlation_id'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            app(WhatsappClient::class)->sendText($from, __('Surat ditolak. Alasan: :note', ['note' => $note]));
        } elseif ($expect === 'disposition_note') {
            // Gunakan disposition_id dari session jika ada, fallback ke yang terbaru
            $dispId = $session['disposition_id'] ?? null;
            $disp   = $dispId
                ? \App\Models\Disposition::find($dispId)
                : \App\Models\Disposition::where('incoming_letter_id', $letter->id)->orderByDesc('id')->first();
            if ($disp) {
                $disp->update(['instruction' => $note]);
                DB::table('integration_logs')->insert([
                    'service'         => 'whatsapp-webhook',
                    'endpoint'        => 'disposition-note',
                    'method'          => 'SYSTEM',
                    'request_payload' => json_encode(['note' => $note, 'letter_id' => $letterId, 'disposition_id' => $disp->id]),
                    'response_body'   => null,
                    'status_code'     => 200,
                    'success'         => true,
                    'attempt'         => 1,
                    'message_id'      => null,
                    'correlation_id'  => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
                app(WhatsappClient::class)->sendText($from, __('Catatan disposisi tersimpan: :note', ['note' => $note]));

                // Kirim notifikasi + instruksi ke tujuan disposisi
                if ($disp->to_phone) {
                    // Disposisi ke pegawai individual
                    $targetPhone = preg_replace('/[^0-9]/', '', $disp->to_phone);
                    if (str_starts_with($targetPhone, '0')) {
                        $targetPhone = '62' . substr($targetPhone, 1);
                    }
                    if ($targetPhone) {
                        wa_session_set($targetPhone, [
                            'letter_id'      => $letter->id,
                            'phase'          => 'claim_broadcast',
                            'disposition_id' => $disp->id,
                        ]);
                        app(WhatsappClient::class)->sendText(
                            $targetPhone,
                            "*Disposisi Surat Masuk*\n\nSurat *{$letter->letter_number}* didisposisikan kepada Anda.\n\n*Instruksi:* {$note}\n\nBalas *1* untuk mengambil."
                        );
                    }
                } elseif ($disp->to_unit_id) {
                    // Disposisi ke unit kerja – broadcast ke semua pegawai aktif di unit
                    $unitEmployees = Employee::where('work_unit_id', $disp->to_unit_id)
                        ->where('status', 'active')
                        ->get();
                    foreach ($unitEmployees as $emp) {
                        if (! $emp->phone_number) continue;
                        $targetPhone = preg_replace('/[^0-9]/', '', $emp->phone_number);
                        if (str_starts_with($targetPhone, '0')) {
                            $targetPhone = '62' . substr($targetPhone, 1);
                        }
                        if (! $targetPhone || $targetPhone === $from) continue;
                        wa_session_set($targetPhone, [
                            'letter_id'      => $letter->id,
                            'phase'          => 'claim_broadcast',
                            'disposition_id' => $disp->id,
                        ]);
                        app(WhatsappClient::class)->sendText(
                            $targetPhone,
                            "*Disposisi Surat Masuk*\n\nSurat *{$letter->letter_number}* menunggu penanggung jawab.\n\n*Instruksi:* {$note}\n\nBalas *1* untuk mengambil disposisi ini."
                        );
                    }
                }
            } else {
                app(WhatsappClient::class)->sendText($from, __('Tidak menemukan disposisi untuk diberi catatan.'));
            }
        }
        wa_session_forget($from);
        // Hapus surat yang sudah selesai dari multi-session
        if ($letterId) {
            wa_multi_session_remove_letter($from, $letterId);
        }
        // Jika masih ada surat pending, tawarkan daftar
        $multi = wa_multi_session_get($from);
        if (! empty($multi['letters'])) {
            $count = count($multi['letters']);
            app(WhatsappClient::class)->sendText(
                $from,
                "Masih ada *{$count}* surat pending. Ketik *DAFTAR* untuk lihat, atau *BANTUAN* untuk perintah lain."
            );
        }
    }

    private function handleButtonChoice(?string $from, string $action): void
    {
        if (! $from) return;
        $session  = wa_session_get($from) ?? [];
        $multi    = wa_multi_session_get($from);
        $letterId = $session['letter_id'] ?? ($multi['active_letter_id'] ?? null);
        $letter   = $letterId ? \App\Models\IncomingLetter::find($letterId) : null;

        if ($action === 'choose_unit') {
            $units = WorkUnit::limit(20)->get();
            $items = $units->values()->map(fn($u, $i) => [
                'num'         => $i + 1,
                'title'       => $u->name,
                'description' => $u->description ?? '',
                'id'          => $u->id,
            ])->all();

            $session['phase']      = 'list_unit';
            $session['list_items'] = collect($items)->pluck('id', 'num')->all();
            $session['letter_id']  = $letterId;
            wa_session_set($from, $session);

            app(WhatsappClient::class)->sendNumberedList(
                $from,
                __('Pilih unit kerja tujuan disposisi surat :num:', ['num' => $letter?->letter_number ?? '-']),
                $items
            );
        } elseif ($action === 'choose_employee') {
            $session['phase']     = 'search_employee';
            $session['letter_id'] = $letterId;
            wa_session_set($from, $session);

            app(WhatsappClient::class)->sendText(
                $from,
                __('Ketik nama pegawai yang ingin dituju (minimal 2 karakter):')
            );
        }
    }

    /**
     * Cari pegawai berdasarkan nama dan tampilkan hasil sebagai daftar bernomor.
     */
    private function handleEmployeeSearch(string $from, string $keyword, array $session): void
    {
        $keyword = trim($keyword);
        if (mb_strlen($keyword) < 2) {
            app(WhatsappClient::class)->sendText($from, __('Nama terlalu pendek. Ketik minimal 2 karakter.'));
            return;
        }

        $employees = Employee::where('status', 'active')
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('name')
            ->limit(20)
            ->get();

        if ($employees->isEmpty()) {
            app(WhatsappClient::class)->sendText(
                $from,
                __('Tidak ada pegawai dengan nama ":keyword". Coba kata kunci lain.', ['keyword' => $keyword])
            );
            return;
        }

        $items = $employees->values()->map(fn($e, $i) => [
            'num'         => $i + 1,
            'title'       => $e->name,
            'description' => $e->position ?? '',
            'id'          => $e->id,
        ])->all();

        $session['phase']      = 'list_employee';
        $session['list_items'] = collect($items)->pluck('id', 'num')->all();
        wa_session_set($from, $session);

        $letterId = $session['letter_id'] ?? null;
        $letter   = $letterId ? \App\Models\IncomingLetter::find($letterId) : null;

        app(WhatsappClient::class)->sendNumberedList(
            $from,
            __('Hasil ":keyword" - pilih pegawai tujuan disposisi surat :num:', [
                'keyword' => $keyword,
                'num'     => $letter?->letter_number ?? '-',
            ]),
            $items
        );
    }

    /**
     * Tangani pilihan angka dari daftar unit/pegawai bernomor.
     */
    private function handleNumberedListSelection(string $from, int $num, array $session, ?string $msgId): void
    {
        $phase     = $session['phase'];
        $listItems = $session['list_items'] ?? [];
        $itemId    = $listItems[$num] ?? null;

        if (! $itemId) {
            app(WhatsappClient::class)->sendText($from, __('Pilihan tidak valid. Balas dengan angka yang sesuai.'));
            return;
        }

        $id = $phase === 'list_unit' ? 'unit:' . $itemId : 'emp:' . $itemId;
        $this->handleListSelection($from, $id, ['id' => $msgId]);
    }

    private function handleListSelection(?string $from, string $id, array $message): void
    {
        Log::channel('whatsapp')->info('List selection received', ['from' => $from, 'id' => $id]);
        $session  = $from ? wa_session_get($from) : null;
        $letterId = $session['letter_id'] ?? null;
        $multi    = $from ? wa_multi_session_get($from) : null;
        if ($multi && $multi['active_letter_id']) {
            $letterId = $multi['active_letter_id'];
        }
        $letter = $letterId ? \App\Models\IncomingLetter::find($letterId) : null;

        if (str_starts_with($id, 'unit:')) {
            $unitId   = (int) substr($id, 5);
            $workUnit = WorkUnit::find($unitId);
            if (! $workUnit) {
                app(WhatsappClient::class)->sendText($from, __('Unit Kerja tidak ditemukan.'));
                return;
            }
            $disp = null;
            if ($letter) {
                $exists = \App\Models\Disposition::where('incoming_letter_id', $letter->id)
                    ->where('to_unit_id', $unitId)->exists();
                if ($exists) {
                    app(WhatsappClient::class)->sendText($from, __('Disposisi sudah ada untuk Unit Kerja: :name', ['name' => $workUnit->name]));
                    return;
                }
                $disp = \App\Models\Disposition::create([
                    'incoming_letter_id' => $letter->id,
                    'from_user_id'       => $letter->user_id,
                    'from_name'          => $letter->user?->name ?? 'System',
                    'from_phone'         => $from,
                    'to_unit_id'         => $unitId,
                    'to_unit_name'       => $workUnit->name,
                    'status'             => \App\Enums\DispositionStatus::New,
                    'channel'            => 'whatsapp',
                    'sequence'           => ($letter->dispositions()->count() + 1),
                ]);
                $letter->increment('disposition_count');
                $letter->update([
                    'status'           => \App\Enums\IncomingLetterStatus::Disposed,
                    'disposed_at'      => now(),
                    'last_disposition' => $workUnit->name,
                ]);
            }
            DB::table('integration_logs')->insert([
                'service'         => 'whatsapp-webhook',
                'endpoint'        => 'create-disposition-unit',
                'method'          => 'SYSTEM',
                'request_payload' => json_encode(['from' => $from, 'unit_id' => $unitId, 'letter_id' => $letterId, 'disposition_id' => $disp?->id]),
                'response_body'   => null,
                'status_code'     => 200,
                'success'         => true,
                'attempt'         => 1,
                'message_id'      => $message['id'] ?? null,
                'correlation_id'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            app(WhatsappClient::class)->sendText($from, __('Disposisi tercatat ke Unit Kerja: :name. Ketik catatan instruksi untuk dikirimkan ke pegawai:', ['name' => $workUnit->name]));
            $session            = $from ? wa_session_get($from) ?? [] : [];
            $session['expect']         = 'disposition_note';
            $session['letter_id']      = $letter?->id;
            $session['disposition_id'] = $disp?->id;
            wa_session_set($from, $session);
            // Notifikasi ke pegawai unit akan dikirim setelah catatan instruksi tersimpan
        } elseif (str_starts_with($id, 'emp:')) {
            $empId    = (int) substr($id, 4);
            $employee = Employee::find($empId);
            if (! $employee) {
                app(WhatsappClient::class)->sendText($from, __('Pegawai tidak ditemukan.'));
                return;
            }
            $disp = null;
            if ($letter) {
                $exists = \App\Models\Disposition::where('incoming_letter_id', $letter->id)
                    ->where('to_user_id', $employee->user_id)->exists();
                if ($exists) {
                    app(WhatsappClient::class)->sendText($from, __('Disposisi sudah ada untuk Pegawai: :name', ['name' => $employee->name]));
                    return;
                }
                $disp = \App\Models\Disposition::create([
                    'incoming_letter_id' => $letter->id,
                    'from_user_id'       => $letter->user_id,
                    'from_name'          => $letter->user?->name ?? 'System',
                    'from_phone'         => $from,
                    'to_user_id'         => $employee->user_id,
                    'to_name'            => $employee->name,
                    'to_phone'           => $employee->phone_number,
                    'status'             => \App\Enums\DispositionStatus::New,
                    'channel'            => 'whatsapp',
                    'sequence'           => ($letter->dispositions()->count() + 1),
                ]);
                $letter->increment('disposition_count');
                $letter->update([
                    'status'           => \App\Enums\IncomingLetterStatus::Disposed,
                    'disposed_at'      => now(),
                    'last_disposition' => $employee->name,
                ]);
            }
            DB::table('integration_logs')->insert([
                'service'         => 'whatsapp-webhook',
                'endpoint'        => 'create-disposition-employee',
                'method'          => 'SYSTEM',
                'request_payload' => json_encode(['from' => $from, 'employee_id' => $empId, 'letter_id' => $letterId, 'disposition_id' => $disp?->id]),
                'response_body'   => null,
                'status_code'     => 200,
                'success'         => true,
                'attempt'         => 1,
                'message_id'      => $message['id'] ?? null,
                'correlation_id'  => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            app(WhatsappClient::class)->sendText($from, __('Disposisi tercatat ke Pegawai: :name. Ketik catatan instruksi untuk dikirimkan:', ['name' => $employee->name]));
            $session              = $from ? wa_session_get($from) ?? [] : [];
            $session['expect']    = 'disposition_note';
            $session['letter_id'] = $letter?->id;
            if ($disp) $session['disposition_id'] = $disp->id;
            wa_session_set($from, $session);
        }
    }

    // ─── Helper ──────────────────────────────────────────────────────────────

    private function logRateLimit(string $from, string $endpoint, ?string $msgId, int $statusCode): void
    {
        DB::table('integration_logs')->insert([
            'service'         => 'whatsapp-webhook',
            'endpoint'        => $endpoint,
            'method'          => 'SYSTEM',
            'request_payload' => json_encode(['phone' => $from]),
            'response_body'   => null,
            'status_code'     => $statusCode,
            'success'         => false,
            'attempt'         => 1,
            'message_id'      => $msgId,
            'correlation_id'  => null,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);
    }
}
