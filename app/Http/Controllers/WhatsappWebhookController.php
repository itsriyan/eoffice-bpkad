<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Integrations\WhatsappClient;
use App\Models\WorkUnit;
use App\Models\Employee;

class WhatsappWebhookController extends Controller
{
    /**
     * GET handler for webhook verification (Meta challenge).
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expected = config('e-office.whatsapp.verify_token'); // Add to config/env if not present

        Log::channel('whatsapp')->info('Webhook verification request', [
            'mode' => $mode,
            'token_provided' => $token ? 'yes' : 'no',
            'challenge_length' => strlen($challenge ?? ''),
        ]);

        // Log the verification attempt
        DB::table('integration_logs')->insert([
            'service' => 'whatsapp-webhook',
            'endpoint' => 'verification',
            'method' => 'GET',
            'request_payload' => json_encode($request->query()),
            'response_body' => null,
            'status_code' => $mode === 'subscribe' && $token && $token === $expected ? 200 : 403,
            'success' => $mode === 'subscribe' && $token && $token === $expected,
            'attempt' => 1,
            'message_id' => null,
            'correlation_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($mode === 'subscribe' && $token && $token === $expected) {
            Log::channel('whatsapp')->info('Webhook verification successful');
            return response($challenge, 200);
        }
        Log::channel('whatsapp')->warning('Webhook verification failed', ['reason' => 'invalid mode or token']);
        return response('Invalid verification token', 403);
    }

    /**
     * POST handler for incoming WhatsApp events.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        Log::channel('whatsapp')->info('Incoming webhook event', [
            'object' => $payload['object'] ?? 'unknown',
            'entry_count' => count($payload['entry'] ?? []),
        ]);

        // Store raw webhook for audit/investigation
        DB::table('integration_logs')->insert([
            'service' => 'whatsapp-webhook',
            'endpoint' => 'incoming',
            'method' => 'POST',
            'request_payload' => json_encode($payload),
            'response_body' => null,
            'status_code' => 200,
            'success' => true,
            'attempt' => 1,
            'message_id' => $payload['entry'][0]['changes'][0]['value']['messages'][0]['id'] ?? null,
            'correlation_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $messages = $payload['entry'][0]['changes'][0]['value']['messages'] ?? [];
        foreach ($messages as $message) {
            $from = $message['from'] ?? null; // sender phone
            // If expecting a note (text) capture
            $session = $from ? wa_session_get($from) : null;
            $multi = $from ? wa_multi_session_get($from) : null;
            // SWITCH command handling
            if (($message['type'] ?? '') === 'text') {
                $textBody = $message['text']['body'] ?? '';
                // BANTUAN command
                if (preg_match('/^BANTUAN$/i', trim($textBody))) {
                    $limit = config('e-office.whatsapp.rate_limit.help_per_minute');
                    if (wa_rate_limit_exceeded($from, 'help', $limit)) {
                        app(WhatsappClient::class)->sendText($from, __('Terlalu sering meminta bantuan. Coba lagi sebentar.'));
                        DB::table('integration_logs')->insert([
                            'service' => 'whatsapp-webhook',
                            'endpoint' => 'rate-help',
                            'method' => 'SYSTEM',
                            'request_payload' => json_encode(['phone' => $from]),
                            'response_body' => null,
                            'status_code' => 429,
                            'success' => false,
                            'attempt' => 1,
                            'message_id' => $message['id'] ?? null,
                            'correlation_id' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        continue;
                    }
                    wa_rate_limit_hit($from, 'help');
                    if ($multi && $multi['letters']) {
                        $letters = \App\Models\IncomingLetter::whereIn('id', $multi['letters'])->get();
                        $activeId = $multi['active_letter_id'];
                        $lines = [];
                        foreach ($letters as $l) {
                            $prefix = $l->id === $activeId ? '*' : '-';
                            $lines[] = $prefix . ' ' . $l->letter_number . ' (' . $l->status->value . ')';
                        }
                        $usage = __('Perintah: SWITCH <nomor_surat> untuk berpindah konteks. Kirim catatan setelah memilih target.');
                        app(WhatsappClient::class)->sendText($from, __('Surat pending:\n:lines\n:usage', [
                            'lines' => implode("\n", $lines),
                            'usage' => $usage,
                        ]));
                    } else {
                        app(WhatsappClient::class)->sendText($from, __('Tidak ada surat pending.'));
                    }
                    continue;
                }
                if (preg_match('/^SWITCH\s+(.+)/i', $textBody, $m)) {
                    $limit = config('e-office.whatsapp.rate_limit.switch_per_minute');
                    if (wa_rate_limit_exceeded($from, 'switch', $limit)) {
                        app(WhatsappClient::class)->sendText($from, __('Terlalu sering ganti konteks. Tunggu sebentar.'));
                        DB::table('integration_logs')->insert([
                            'service' => 'whatsapp-webhook',
                            'endpoint' => 'rate-switch',
                            'method' => 'SYSTEM',
                            'request_payload' => json_encode(['phone' => $from]),
                            'response_body' => null,
                            'status_code' => 429,
                            'success' => false,
                            'attempt' => 1,
                            'message_id' => $message['id'] ?? null,
                            'correlation_id' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        continue;
                    }
                    wa_rate_limit_hit($from, 'switch');
                    $targetNumber = trim($m[1]);
                    $letter = \App\Models\IncomingLetter::where('letter_number', $targetNumber)->first();
                    if ($letter && $multi && in_array($letter->id, $multi['letters'], true)) {
                        wa_multi_session_set_active($from, $letter->id);
                        app(WhatsappClient::class)->sendText($from, __('Konteks berpindah ke surat :num', ['num' => $letter->letter_number]));
                        // Refresh single-letter session
                        wa_session_set($from, [
                            'letter_id' => $letter->id,
                            'phase' => 'switched',
                            'ts' => now()->timestamp,
                        ]);
                    } else {
                        app(WhatsappClient::class)->sendText($from, __('Surat tidak ditemukan atau tidak dalam daftar pending.'));
                    }
                    continue; // handled
                }
            }
            if (($message['type'] ?? '') === 'text' && $session && isset($session['expect'])) {
                $this->handleExpectedNote($from, $session, $message['text']['body'] ?? '');
                continue;
            }
            // Button replies vary by structure
            $action = null;
            if (($message['type'] ?? '') === 'button') {
                $action = $message['button']['payload'] ?? null;
            } elseif (($message['type'] ?? '') === 'interactive' && isset($message['interactive']['button_reply'])) {
                $action = $message['interactive']['button_reply']['id'] ?? $message['interactive']['button_reply']['title'] ?? null;
                if (in_array($action, ['choose_unit', 'choose_employee'], true)) {
                    $this->handleButtonChoice($from, $action);
                    continue;
                }
                if ($action === 'claim_disposition') {
                    $limit = config('e-office.whatsapp.rate_limit.claim_per_minute');
                    if (wa_rate_limit_exceeded($from, 'claim', $limit)) {
                        app(WhatsappClient::class)->sendText($from, __('Terlalu banyak percobaan klaim. Coba lagi nanti.'));
                        DB::table('integration_logs')->insert([
                            'service' => 'whatsapp-webhook',
                            'endpoint' => 'rate-claim',
                            'method' => 'SYSTEM',
                            'request_payload' => json_encode(['phone' => $from]),
                            'response_body' => null,
                            'status_code' => 429,
                            'success' => false,
                            'attempt' => 1,
                            'message_id' => $message['id'] ?? null,
                            'correlation_id' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        continue;
                    }
                    wa_rate_limit_hit($from, 'claim');
                    $this->handleClaimDisposition($from, $message);
                    continue;
                }
            } elseif (($message['type'] ?? '') === 'interactive' && isset($message['interactive']['list_reply'])) {
                $action = $message['interactive']['list_reply']['id'] ?? null; // list row id
                if ($action) {
                    $this->handleListSelection($from, $action, $message);
                    continue; // skip normal action switch
                }
            }
            if ($action) {
                $normalized = strtoupper(trim($action));
                switch ($normalized) {
                    case 'DISPOSISI':
                        $this->handleDisposisi($from, $message);
                        break;
                    case 'ARSIPKAN':
                        $this->requestArchiveNote($from, $message);
                        break;
                    case 'TOLAK':
                        $this->requestRejectNote($from, $message);
                        break;
                    default:
                        Log::channel('whatsapp')->info('Unhandled button action', ['action' => $normalized, 'from' => $from]);
                }
            }
        }
        return response()->json(['status' => 'ok']);
    }

    private function handleDisposisi(?string $from, array $message): void
    {
        Log::channel('whatsapp')->info('Disposition action received', ['from' => $from, 'message_id' => $message['id'] ?? null]);
        // Send interactive buttons asking for target type
        if (!$from) return;
        app(WhatsappClient::class)->sendInteractiveButtons($from, __('Pilih tujuan disposisi:'), [
            ['id' => 'choose_unit', 'title' => __('Unit Kerja')],
            ['id' => 'choose_employee', 'title' => __('Pegawai')],
        ]);
    }

    private function handleArchive(?string $from, array $message): void
    {
        Log::channel('whatsapp')->info('Archive action received', ['from' => $from, 'message_id' => $message['id'] ?? null]);
        // TODO: Implement letter archive marking
    }

    private function handleReject(?string $from, array $message): void
    {
        Log::channel('whatsapp')->info('Reject action received', ['from' => $from, 'message_id' => $message['id'] ?? null]);
        // TODO: Implement letter rejection logic
    }

    private function handleClaimDisposition(?string $from, array $message): void
    {
        if (!$from) return;
        $session = wa_session_get($from) ?? [];
        $dispId = $session['disposition_id'] ?? null;
        if (!$dispId) {
            app(WhatsappClient::class)->sendText($from, __('Tidak ada disposisi untuk diambil.'));
            return;
        }
        $disp = \App\Models\Disposition::find($dispId);
        if (!$disp) {
            app(WhatsappClient::class)->sendText($from, __('Disposisi tidak ditemukan.'));
            return;
        }
        // Attempt claim (idempotent)
        $claimedUser = Employee::where('phone_number', 'like', '%' . substr($from, -9) . '%')->first();
        $userId = $claimedUser?->user_id;
        if (!$userId) {
            app(WhatsappClient::class)->sendText($from, __('Nomor tidak terdaftar sebagai pegawai.'));
            return;
        }
        $updated = \App\Models\Disposition::where('id', $disp->id)
            ->whereNull('claimed_by_user_id')
            ->update([
                'claimed_by_user_id' => $userId,
                'claimed_at' => now(),
                'to_user_id' => $userId,
                'to_name' => $claimedUser->name,
                'to_phone' => $claimedUser->phone_number,
                'status' => \App\Enums\DispositionStatus::Received,
                'received_at' => now(),
            ]);
        if ($updated === 0) {
            // Already claimed
            $claimer = $disp->claimed_by_user_id ? $disp->claimedByUser?->name : __('Tidak diketahui');
            app(WhatsappClient::class)->sendText($from, __('Disposisi sudah diambil oleh :name.', ['name' => $claimer]));
            return;
        }
        // Refresh disposition instance
        $disp->refresh();
        // If letter status still new -> mark disposed timestamp and status
        $letter = $disp->letter;
        if ($letter && $letter->status === \App\Enums\IncomingLetterStatus::New) {
            $letter->update([
                'status' => \App\Enums\IncomingLetterStatus::Disposed,
                'disposed_at' => now(),
                'last_disposition' => $disp->id,
            ]);
        } else {
            // Update last_disposition reference for tracking
            if ($letter) {
                $letter->update(['last_disposition' => $disp->id]);
            }
        }
        DB::table('integration_logs')->insert([
            'service' => 'whatsapp-webhook',
            'endpoint' => 'claim-disposition',
            'method' => 'SYSTEM',
            'request_payload' => json_encode(['disposition_id' => $disp->id, 'claimer_user_id' => $userId]),
            'response_body' => null,
            'status_code' => 200,
            'success' => true,
            'attempt' => 1,
            'message_id' => $message['id'] ?? null,
            'correlation_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Set expectation for note
        wa_session_set($from, [
            'letter_id' => $disp->incoming_letter_id,
            'expect' => 'disposition_note',
            'disposition_id' => $disp->id,
        ]);
        app(WhatsappClient::class)->sendText($from, __('Anda berhasil mengambil disposisi. Kirim catatan instruksi (balas dengan teks).'));
    }

    private function requestArchiveNote(?string $from, array $message): void
    {
        if (!$from) return;
        $session = wa_session_get($from) ?? [];
        $session['expect'] = 'archive_note';
        wa_session_set($from, $session);
        app(WhatsappClient::class)->sendText($from, __('Ketik catatan pengarsipan, lalu kirim.'));
    }

    private function requestRejectNote(?string $from, array $message): void
    {
        if (!$from) return;
        $session = wa_session_get($from) ?? [];
        $session['expect'] = 'reject_note';
        wa_session_set($from, $session);
        app(WhatsappClient::class)->sendText($from, __('Ketik alasan penolakan, lalu kirim.'));
    }

    private function handleExpectedNote(string $from, array $session, string $note): void
    {
        $expect = $session['expect'];
        $letterId = $session['letter_id'] ?? null;
        $letter = $letterId ? \App\Models\IncomingLetter::find($letterId) : null;
        if (!$letter) {
            app(WhatsappClient::class)->sendText($from, __('Surat tidak ditemukan dalam sesi.'));
            wa_session_forget($from);
            return;
        }
        if ($expect === 'archive_note') {
            $limit = config('e-office.whatsapp.rate_limit.note_per_minute');
            if (wa_rate_limit_exceeded($from, 'note', $limit)) {
                app(WhatsappClient::class)->sendText($from, __('Terlalu banyak catatan dikirim. Coba lagi nanti.'));
                return;
            }
            wa_rate_limit_hit($from, 'note');
            $letter->update([
                'status' => \App\Enums\IncomingLetterStatus::Archived,
                'archived_at' => now(),
                'last_disposition' => 'Arsipkan',
            ]);
            DB::table('integration_logs')->insert([
                'service' => 'whatsapp-webhook',
                'endpoint' => 'archive-note',
                'method' => 'SYSTEM',
                'request_payload' => json_encode(['note' => $note, 'letter_id' => $letterId]),
                'response_body' => null,
                'status_code' => 200,
                'success' => true,
                'attempt' => 1,
                'message_id' => null,
                'correlation_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            app(WhatsappClient::class)->sendText($from, __('Surat diarsipkan. Catatan: :note', ['note' => $note]));
        } elseif ($expect === 'reject_note') {
            $limit = config('e-office.whatsapp.rate_limit.note_per_minute');
            if (wa_rate_limit_exceeded($from, 'note', $limit)) {
                app(WhatsappClient::class)->sendText($from, __('Terlalu banyak catatan dikirim. Coba lagi nanti.'));
                return;
            }
            wa_rate_limit_hit($from, 'note');
            $letter->update([
                'status' => \App\Enums\IncomingLetterStatus::Rejected,
                'last_disposition' => 'Ditolak',
            ]);
            // Create disposition record with rejection reason for audit
            \App\Models\Disposition::create([
                'incoming_letter_id' => $letter->id,
                'from_user_id' => $letter->user_id,
                'from_name' => $letter->user?->name ?? 'System',
                'from_phone' => $from,
                'rejection_reason' => $note,
                'status' => \App\Enums\DispositionStatus::Rejected,
                'channel' => 'whatsapp',
                'sequence' => ($letter->dispositions()->count() + 1),
            ]);
            DB::table('integration_logs')->insert([
                'service' => 'whatsapp-webhook',
                'endpoint' => 'reject-note',
                'method' => 'SYSTEM',
                'request_payload' => json_encode(['note' => $note, 'letter_id' => $letterId]),
                'response_body' => null,
                'status_code' => 200,
                'success' => true,
                'attempt' => 1,
                'message_id' => null,
                'correlation_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            app(WhatsappClient::class)->sendText($from, __('Surat ditolak. Alasan: :note', ['note' => $note]));
        } elseif ($expect === 'disposition_note') {
            $limit = config('e-office.whatsapp.rate_limit.note_per_minute');
            if (wa_rate_limit_exceeded($from, 'note', $limit)) {
                app(WhatsappClient::class)->sendText($from, __('Terlalu banyak catatan dikirim. Coba lagi nanti.'));
                return;
            }
            wa_rate_limit_hit($from, 'note');
            // Find last created disposition for this letter (sequence highest) without instruction
            $disp = \App\Models\Disposition::where('incoming_letter_id', $letter->id)
                ->orderByDesc('id')
                ->first();
            if ($disp) {
                $disp->update(['instruction' => $note]);
                DB::table('integration_logs')->insert([
                    'service' => 'whatsapp-webhook',
                    'endpoint' => 'disposition-note',
                    'method' => 'SYSTEM',
                    'request_payload' => json_encode(['note' => $note, 'letter_id' => $letterId, 'disposition_id' => $disp->id]),
                    'response_body' => null,
                    'status_code' => 200,
                    'success' => true,
                    'attempt' => 1,
                    'message_id' => null,
                    'correlation_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                app(WhatsappClient::class)->sendText($from, __('Catatan disposisi tersimpan: :note', ['note' => $note]));
            } else {
                app(WhatsappClient::class)->sendText($from, __('Tidak menemukan disposisi untuk diberi catatan.'));
            }
        }
        wa_session_forget($from);
    }

    private function handleListSelection(?string $from, string $id, array $message): void
    {
        Log::channel('whatsapp')->info('List selection received', ['from' => $from, 'id' => $id]);
        $session = $from ? wa_session_get($from) : null;
        $letterId = $session['letter_id'] ?? null;
        $multi = $from ? wa_multi_session_get($from) : null;
        if ($multi && $multi['active_letter_id']) {
            $letterId = $multi['active_letter_id'];
        }
        $letter = $letterId ? \App\Models\IncomingLetter::find($letterId) : null;
        if (str_starts_with($id, 'unit:')) {
            $unitId = (int)substr($id, 5);
            $workUnit = WorkUnit::find($unitId);
            if ($workUnit) {
                $disp = null;
                if ($letter) {
                    // Idempotency: check existing disposition for same letter & unit
                    $exists = \App\Models\Disposition::where('incoming_letter_id', $letter->id)
                        ->where('to_unit_id', $unitId)
                        ->exists();
                    if ($exists) {
                        app(WhatsappClient::class)->sendText($from, __('Disposisi sudah ada untuk Unit Kerja: :name', ['name' => $workUnit->name]));
                        return;
                    }
                    $disp = \App\Models\Disposition::create([
                        'incoming_letter_id' => $letter->id,
                        'from_user_id' => $letter->user_id,
                        'from_name' => $letter->user?->name ?? 'System',
                        'from_phone' => $from,
                        'to_unit_id' => $unitId,
                        'to_unit_name' => $workUnit->name,
                        'status' => \App\Enums\DispositionStatus::New,
                        'channel' => 'whatsapp',
                        'sequence' => ($letter->dispositions()->count() + 1),
                    ]);
                    // Update letter disposition metadata
                    $letter->increment('disposition_count');
                    $letter->update([
                        'status' => \App\Enums\IncomingLetterStatus::Disposed,
                        'disposed_at' => now(),
                        'last_disposition' => $workUnit->name,
                    ]);
                }
                DB::table('integration_logs')->insert([
                    'service' => 'whatsapp-webhook',
                    'endpoint' => 'create-disposition-unit',
                    'method' => 'SYSTEM',
                    'request_payload' => json_encode(['from' => $from, 'unit_id' => $unitId, 'letter_id' => $letterId, 'disposition_id' => $disp?->id]),
                    'response_body' => null,
                    'status_code' => 200,
                    'success' => true,
                    'attempt' => 1,
                    'message_id' => $message['id'] ?? null,
                    'correlation_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                app(WhatsappClient::class)->sendText($from, __('Disposisi tercatat ke Unit Kerja: :name', ['name' => $workUnit->name]));
                // Expect disposition note next
                $session = $from ? wa_session_get($from) ?? [] : [];
                $session['expect'] = 'disposition_note';
                $session['letter_id'] = $letter?->id;
                wa_session_set($from, $session);

                // Broadcast claim button to all active employees in unit (excluding initiator if phone matches)
                $unitEmployees = Employee::where('work_unit_id', $workUnit->id)->where('status', 'active')->get();
                foreach ($unitEmployees as $emp) {
                    if (!$emp->phone_number) continue;
                    $targetPhone = preg_replace('/[^0-9]/', '', $emp->phone_number);
                    if (str_starts_with($targetPhone, '0')) {
                        $targetPhone = '62' . substr($targetPhone, 1);
                    }
                    // Simple skip if same as 'from'
                    if ($targetPhone === $from) continue;
                    wa_session_set($targetPhone, [
                        'letter_id' => $letter?->id,
                        'phase' => 'claim_broadcast',
                        'disposition_id' => $disp?->id,
                    ]);
                    app(WhatsappClient::class)->sendInteractiveButtons($targetPhone, __('Surat :num menunggu penanggung jawab. Tekan AMBIL untuk mengambil.', ['num' => $letter->letter_number]), [
                        ['id' => 'claim_disposition', 'title' => __('AMBIL')],
                    ]);
                }
            } else {
                app(WhatsappClient::class)->sendText($from, __('Unit Kerja tidak ditemukan.'));
            }
        } elseif (str_starts_with($id, 'emp:')) {
            $empId = (int)substr($id, 4);
            $employee = Employee::find($empId);
            if ($employee) {
                $disp = null;
                if ($letter) {
                    $exists = \App\Models\Disposition::where('incoming_letter_id', $letter->id)
                        ->where('to_user_id', $employee->user_id)
                        ->exists();
                    if ($exists) {
                        app(WhatsappClient::class)->sendText($from, __('Disposisi sudah ada untuk Pegawai: :name', ['name' => $employee->name]));
                        return;
                    }
                    $disp = \App\Models\Disposition::create([
                        'incoming_letter_id' => $letter->id,
                        'from_user_id' => $letter->user_id,
                        'from_name' => $letter->user?->name ?? 'System',
                        'from_phone' => $from,
                        'to_user_id' => $employee->user_id,
                        'to_name' => $employee->name,
                        'to_phone' => $employee->phone_number,
                        'status' => \App\Enums\DispositionStatus::New,
                        'channel' => 'whatsapp',
                        'sequence' => ($letter->dispositions()->count() + 1),
                    ]);
                    $letter->increment('disposition_count');
                    $letter->update([
                        'status' => \App\Enums\IncomingLetterStatus::Disposed,
                        'disposed_at' => now(),
                        'last_disposition' => $employee->name,
                    ]);
                }
                DB::table('integration_logs')->insert([
                    'service' => 'whatsapp-webhook',
                    'endpoint' => 'create-disposition-employee',
                    'method' => 'SYSTEM',
                    'request_payload' => json_encode(['from' => $from, 'employee_id' => $empId, 'letter_id' => $letterId, 'disposition_id' => $disp?->id]),
                    'response_body' => null,
                    'status_code' => 200,
                    'success' => true,
                    'attempt' => 1,
                    'message_id' => $message['id'] ?? null,
                    'correlation_id' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                app(WhatsappClient::class)->sendText($from, __('Disposisi tercatat ke Pegawai: :name', ['name' => $employee->name]));
                $session = $from ? wa_session_get($from) ?? [] : [];
                $session['expect'] = 'disposition_note';
                $session['letter_id'] = $letter?->id;
                wa_session_set($from, $session);
            } else {
                app(WhatsappClient::class)->sendText($from, __('Pegawai tidak ditemukan.'));
            }
        }
    }

    // Handle button choice for choose_unit or choose_employee
    private function handleButtonChoice(?string $from, string $choice): void
    {
        if (!$from) return;
        $choice = strtolower($choice);
        if ($choice === 'choose_unit') {
            $units = WorkUnit::limit(10)->get();
            if ($units->isEmpty()) {
                app(WhatsappClient::class)->sendText($from, __('Tidak ada Unit Kerja tersedia.'));
                return;
            }
            $sections = [[
                'title' => __('Unit Kerja'),
                'rows' => $units->map(fn($u) => [
                    'id' => 'unit:' . $u->id,
                    'title' => $u->name,
                    'description' => $u->description ?? '',
                ])->toArray(),
            ]];
            app(WhatsappClient::class)->sendInteractiveList($from, __('Daftar Unit Kerja'), __('Pilih unit tujuan disposisi'), __('Pilih salah satu'), $sections);
        } elseif ($choice === 'choose_employee') {
            $employees = Employee::where('status', 'active')->limit(10)->get();
            if ($employees->isEmpty()) {
                app(WhatsappClient::class)->sendText($from, __('Tidak ada Pegawai aktif tersedia.'));
                return;
            }
            $sections = [[
                'title' => __('Pegawai'),
                'rows' => $employees->map(fn($e) => [
                    'id' => 'emp:' . $e->id,
                    'title' => $e->name,
                    'description' => $e->position ?? '',
                ])->toArray(),
            ]];
            app(WhatsappClient::class)->sendInteractiveList($from, __('Daftar Pegawai'), __('Pilih pegawai tujuan disposisi'), __('Pilih salah satu'), $sections);
        }
    }
}