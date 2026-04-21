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
            // BANTUAN command
            if (preg_match('/^BANTUAN$/i', $msgText)) {
                $limit = config('e-office.whatsapp.rate_limit.help_per_minute');
                if (wa_rate_limit_exceeded($from, 'help', $limit)) {
                    app(WhatsappClient::class)->sendText($from, __('Terlalu sering meminta bantuan. Coba lagi sebentar.'));
                    $this->logRateLimit($from, 'rate-help', $msgId, 429);
                    return response()->json(['status' => 'ok']);
                }
                wa_rate_limit_hit($from, 'help');
                if ($multi && $multi['letters']) {
                    $letters  = \App\Models\IncomingLetter::whereIn('id', $multi['letters'])->get();
                    $activeId = $multi['active_letter_id'];
                    $lines    = $letters->map(function ($l) use ($activeId) {
                        $prefix = $l->id === $activeId ? '*' : '-';
                        return $prefix . ' ' . $l->letter_number . ' (' . $l->status->value . ')';
                    })->implode("\n");
                    $usage = __('Perintah: SWITCH <nomor_surat> untuk berpindah konteks.');
                    app(WhatsappClient::class)->sendText($from, __('Surat pending:') . "\n" . $lines . "\n\n" . $usage);
                } else {
                    app(WhatsappClient::class)->sendText($from, __('Tidak ada surat pending.'));
                }
                return response()->json(['status' => 'ok']);
            }

            // SWITCH command
            if (preg_match('/^SWITCH\s+(.+)/i', $msgText, $m)) {
                $limit = config('e-office.whatsapp.rate_limit.switch_per_minute');
                if (wa_rate_limit_exceeded($from, 'switch', $limit)) {
                    app(WhatsappClient::class)->sendText($from, __('Terlalu sering ganti konteks. Tunggu sebentar.'));
                    $this->logRateLimit($from, 'rate-switch', $msgId, 429);
                    return response()->json(['status' => 'ok']);
                }
                wa_rate_limit_hit($from, 'switch');
                $targetNumber = trim($m[1]);
                $letter = \App\Models\IncomingLetter::where('letter_number', $targetNumber)->first();
                if ($letter && $multi && in_array($letter->id, $multi['letters'], true)) {
                    wa_multi_session_set_active($from, $letter->id);
                    wa_session_set($from, [
                        'letter_id' => $letter->id,
                        'phase'     => 'switched',
                        'ts'        => now()->timestamp,
                    ]);
                    app(WhatsappClient::class)->sendText($from, __('Konteks berpindah ke surat :num', ['num' => $letter->letter_number]));
                } else {
                    app(WhatsappClient::class)->sendText($from, __('Surat tidak ditemukan atau tidak dalam daftar pending.'));
                }
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

            // ── Menu angka – tindakan surat ──────────────────────────────────
            // Pimpinan membalas angka dari menu notifikasi surat masuk
            if (preg_match('/^\s*([1-3])\s*$/', $msgText, $m)) {
                $letterId = $session['letter_id'] ?? ($multi['active_letter_id'] ?? null);
                if ($letterId) {
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
        }

        return response()->json(['status' => 'ok']);
    }

    // =========================================================================
    // Private action handlers (tidak berubah dari implementasi sebelumnya)
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
            } else {
                app(WhatsappClient::class)->sendText($from, __('Tidak menemukan disposisi untuk diberi catatan.'));
            }
        }
        wa_session_forget($from);
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
            $employees = Employee::where('status', 'active')->limit(20)->get();
            $items     = $employees->values()->map(fn($e, $i) => [
                'num'         => $i + 1,
                'title'       => $e->name,
                'description' => $e->position ?? '',
                'id'          => $e->id,
            ])->all();

            $session['phase']      = 'list_employee';
            $session['list_items'] = collect($items)->pluck('id', 'num')->all();
            $session['letter_id']  = $letterId;
            wa_session_set($from, $session);

            app(WhatsappClient::class)->sendNumberedList(
                $from,
                __('Pilih pegawai tujuan disposisi surat :num:', ['num' => $letter?->letter_number ?? '-']),
                $items
            );
        }
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
            app(WhatsappClient::class)->sendText($from, __('Disposisi tercatat ke Unit Kerja: :name', ['name' => $workUnit->name]));
            $session            = $from ? wa_session_get($from) ?? [] : [];
            $session['expect']         = 'disposition_note';
            $session['letter_id']      = $letter?->id;
            $session['disposition_id'] = $disp?->id;
            wa_session_set($from, $session);

            // Broadcast tombol AMBIL ke semua pegawai aktif di unit
            $unitEmployees = Employee::where('work_unit_id', $workUnit->id)->where('status', 'active')->get();
            foreach ($unitEmployees as $emp) {
                if (! $emp->phone_number) continue;
                $targetPhone = preg_replace('/[^0-9]/', '', $emp->phone_number);
                if (str_starts_with($targetPhone, '0')) {
                    $targetPhone = '62' . substr($targetPhone, 1);
                }
                if ($targetPhone === $from) continue;
                wa_session_set($targetPhone, [
                    'letter_id'      => $letter?->id,
                    'phase'          => 'claim_broadcast',
                    'disposition_id' => $disp?->id,
                ]);
                app(WhatsappClient::class)->sendText(
                    $targetPhone,
                    __("Surat *:num* menunggu penanggung jawab.\n\nBalas *1* untuk mengambil disposisi ini.", ['num' => $letter->letter_number])
                );
            }
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
            app(WhatsappClient::class)->sendText($from, __('Disposisi tercatat ke Pegawai: :name', ['name' => $employee->name]));
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
