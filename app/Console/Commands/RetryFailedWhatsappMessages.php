<?php

namespace App\Console\Commands;

use App\Services\Integrations\WhatsappClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Retry pesan WhatsApp yang gagal berdasarkan integration_logs.
 *
 * Kriteria retry:
 *  - service = 'whatsapp'
 *  - success = false
 *  - attempt < 5  (batas maksimal percobaan)
 *  - updated_at < now - 5 menit  (jangan retry terlalu cepat)
 */
class RetryFailedWhatsappMessages extends Command
{
    protected $signature   = 'whatsapp:retry-failed {--limit=20 : Maksimal log diproses per jalan}';
    protected $description = 'Coba kirim ulang pesan WhatsApp yang gagal dari integration_logs';

    public function handle(WhatsappClient $client): int
    {
        $limit = (int) $this->option('limit');

        $logs = DB::table('integration_logs')
            ->where('service', 'whatsapp')
            ->where('success', false)
            ->where('attempt', '<', 5)
            ->where('updated_at', '<', now()->subMinutes(5))
            ->orderBy('updated_at')
            ->limit($limit)
            ->get();

        if ($logs->isEmpty()) {
            $this->info('Tidak ada pesan gagal yang perlu diulang.');
            return self::SUCCESS;
        }

        $this->info("Memproses {$logs->count()} pesan gagal...");
        $succeeded = 0;
        $failed    = 0;

        foreach ($logs as $log) {
            $payload = json_decode($log->request_payload, true);
            $to      = $payload['to'] ?? null;
            $mode    = $payload['mode'] ?? 'text';
            $text    = $payload['template_or_text'] ?? '';

            if (! $to || ! $text) {
                // Payload rusak – tandai attempt max agar tidak diulang
                DB::table('integration_logs')
                    ->where('id', $log->id)
                    ->update(['attempt' => 5, 'updated_at' => now()]);
                $failed++;
                continue;
            }

            try {
                $result = $mode === 'text'
                    ? $client->sendText($to, $text)
                    : $client->sendTemplate($to, $text, null, []);

                $success   = $result['success'];
                $messageId = $result['response']['id'] ?? null;

                DB::table('integration_logs')
                    ->where('id', $log->id)
                    ->update([
                        'success'       => $success,
                        'attempt'       => $log->attempt + 1,
                        'response_body' => json_encode($result['response']),
                        'status_code'   => $success ? 200 : 500,
                        'message_id'    => $messageId ?? $log->message_id,
                        'updated_at'    => now(),
                    ]);

                if ($success) {
                    $succeeded++;
                    Log::channel('whatsapp')->info('WA retry succeeded', ['log_id' => $log->id, 'to' => $to]);
                } else {
                    $failed++;
                    Log::channel('whatsapp')->warning('WA retry failed again', ['log_id' => $log->id, 'to' => $to, 'attempt' => $log->attempt + 1]);
                }
            } catch (\Throwable $e) {
                $failed++;
                DB::table('integration_logs')
                    ->where('id', $log->id)
                    ->update([
                        'attempt'    => $log->attempt + 1,
                        'updated_at' => now(),
                    ]);
                Log::channel('whatsapp')->error('WA retry exception', ['log_id' => $log->id, 'error' => $e->getMessage()]);
            }
        }

        $this->info("Selesai: {$succeeded} berhasil, {$failed} gagal.");
        return self::SUCCESS;
    }
}
