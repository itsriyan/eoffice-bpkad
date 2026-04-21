<?php

namespace App\Services\Integrations;

use App\Models\Disposition;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp client menggunakan Fonnte API (https://fonnte.com).
 *
 * Endpoint : POST https://api.fonnte.com/send
 * Auth     : Header  Authorization: <token>   (bukan Bearer)
 * Response : {"status": true, "id": "...", "detail": "..."}
 */
class WhatsappClient
{
    protected string $apiUrl;
    protected string $token;
    protected int $timeout;

    public function __construct()
    {
        $config = config('e-office.whatsapp');
        $this->apiUrl  = rtrim($config['api_url'] ?? 'https://api.fonnte.com', '/');
        $this->token   = $config['token'] ?? '';
        $this->timeout = (int) ($config['timeout'] ?? 10);
    }

    /**
     * Kirim pesan template – dipetakan ke teks berformat + tombol interaktif Fonnte.
     * Fonnte tidak mendukung WABA template; teks diambil dari config('e-office.whatsapp.templates').
     */
    public function sendTemplate(string $to, string $templateName, ?string $lang = null, array $components = []): array
    {
        $variables = $this->extractVariables($components);
        $template  = config('e-office.whatsapp.templates.' . $templateName);

        $text    = $this->buildTemplateText($template['text'] ?? $templateName, $variables);
        $buttons = $template['buttons'] ?? [];

        if ($buttons) {
            return $this->sendInteractiveButtons($to, $text, $buttons);
        }

        return $this->sendText($to, $text);
    }

    /**
     * Kirim pesan teks biasa.
     */
    public function sendText(string $to, string $text): array
    {
        return $this->dispatch([
            'target'      => $this->normalizePhone($to),
            'message'     => $text,
            'countryCode' => '62',
        ]);
    }

    /**
     * Kirim pesan dengan tombol interaktif (maks. 3 tombol, judul maks. 20 karakter).
     *
     * $buttons: [['id' => 'choose_unit', 'title' => 'Unit Kerja'], ...]
     */
    public function sendInteractiveButtons(string $to, string $bodyText, array $buttons): array
    {
        $titles = implode('|', array_column($buttons, 'title'));
        $ids    = implode('|', array_column($buttons, 'id'));

        return $this->dispatch([
            'target'      => $this->normalizePhone($to),
            'message'     => $bodyText,
            'buttons'     => $titles,
            'buttonId'    => $ids,
            'countryCode' => '62',
        ]);
    }

    /**
     * Kirim pesan list interaktif (Fonnte list message).
     *
     * $sections: [['title' => 'Unit Kerja', 'rows' => [['id'=>'unit:1','title'=>'Sekretariat','description'=>'']]]]
     */
    public function sendInteractiveList(string $to, string $header, string $bodyText, string $footer, array $sections, string $buttonText = 'Pilih'): array
    {
        return $this->dispatch([
            'target'      => $this->normalizePhone($to),
            'message'     => $bodyText,
            'header'      => $header,
            'footer'      => $footer,
            'listMessage' => json_encode([
                'buttonText' => $buttonText,
                'sections'   => array_map(fn ($s) => [
                    'title' => $s['title'],
                    'rows'  => array_map(fn ($r) => [
                        'id'          => $r['id'],
                        'title'       => $r['title'],
                        'description' => $r['description'] ?? '',
                    ], $s['rows']),
                ], $sections),
            ]),
            'countryCode' => '62',
        ]);
    }

    /**
     * Kirim notifikasi disposisi (wrapper template).
     */
    public function sendDisposition(Disposition $disposition, string $template, array $variables = []): array
    {
        $components = $variables
            ? [['type' => 'body', 'parameters' => array_map(fn ($v) => ['text' => (string) $v], $variables)]]
            : [];

        $result = $this->sendTemplate($disposition->to_phone, $template, null, $components);

        if (! $result['success']) {
            Log::channel('whatsapp')->error('Disposition WA send failed', [
                'disposition_id' => $disposition->id,
                'template'       => $template,
                'variables'      => $variables,
                'response'       => $result['response'],
            ]);
            return ['success' => false, 'message_id' => null];
        }

        $messageId = $result['response']['id'] ?? ('WA-' . $disposition->id . '-' . now()->timestamp);
        return ['success' => true, 'message_id' => $messageId];
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    protected function dispatch(array $payload): array
    {
        $endpoint = $this->apiUrl . '/send';
        $start    = microtime(true);

        try {
            $response   = Http::withHeaders(['Authorization' => $this->token])
                ->timeout($this->timeout)
                ->post($endpoint, $payload);
            $durationMs = (int) ((microtime(true) - $start) * 1000);
            $json       = $response->json() ?? [];

            // Fonnte sukses: {"status": true, "id": "...", "detail": "..."}
            $success = $response->successful() && ($json['status'] ?? false);

            Log::channel('whatsapp')->info('WA send (Fonnte)', [
                'target'      => $payload['target'] ?? null,
                'status_http' => $response->status(),
                'duration_ms' => $durationMs,
                'success'     => $success,
                'response'    => $json,
            ]);

            return ['success' => $success, 'response' => $json];
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('WA send exception (Fonnte)', [
                'endpoint' => $endpoint,
                'error'    => $e->getMessage(),
            ]);
            return ['success' => false, 'response' => ['error' => $e->getMessage()]];
        }
    }

    /**
     * Normalisasi nomor telepon ke format 62xxx.
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }
        return $phone;
    }

    /**
     * Ekstrak parameter teks dari components Meta-style (tetap kompatibel dengan job lama).
     */
    protected function extractVariables(array $components): array
    {
        $vars = [];
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'body') {
                foreach ($component['parameters'] ?? [] as $param) {
                    $vars[] = $param['text'] ?? (string) $param;
                }
            }
        }
        return $vars;
    }

    /**
     * Ganti placeholder {var0}, {var1}, ... dalam teks template.
     */
    protected function buildTemplateText(string $textTemplate, array $variables): string
    {
        foreach ($variables as $i => $value) {
            $textTemplate = str_replace('{var' . $i . '}', $value, $textTemplate);
        }
        return $textTemplate;
    }
}
