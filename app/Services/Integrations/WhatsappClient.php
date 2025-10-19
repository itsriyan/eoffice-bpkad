<?php

namespace App\Services\Integrations;

use App\Models\Disposition;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappClient
{
    protected string $baseUrl;
    protected string $phoneNumberId;
    protected string $token;
    protected int $timeout;

    public function __construct(?string $phoneNumberId = null, ?string $token = null)
    {
        $config = config('e-office.whatsapp');
        $this->baseUrl = rtrim($config['base_url'] ?? '', '/');
        $this->phoneNumberId = $phoneNumberId ?? ($config['phone_number_id'] ?? '');
        $this->token = $token ?? ($config['access_token'] ?? '');
        $this->timeout = (int)($config['timeout'] ?? 10);
    }

    /**
     * Send template message via Meta WhatsApp API.
     */
    public function sendTemplate(string $to, string $templateName, ?string $lang = null, array $components = []): array
    {
        $langCode = $lang ?? (config('e-office.whatsapp.default_language') ?? 'id');
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $langCode],
            ],
        ];
        if ($components) {
            $payload['template']['components'] = $components;
        }
        return $this->dispatch('template', $payload);
    }

    /**
     * Send plain text message.
     */
    public function sendText(string $to, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text],
        ];
        return $this->dispatch('text', $payload);
    }

    /**
     * Send interactive buttons message (non-template) for choosing next action.
     * $buttons: [['id' => 'choose_unit', 'title' => 'Unit Kerja'], ['id' => 'choose_employee', 'title' => 'Pegawai']]
     */
    public function sendInteractiveButtons(string $to, string $bodyText, array $buttons): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => [
                    'buttons' => array_map(fn($b) => [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $b['id'],
                            'title' => $b['title'],
                        ],
                    ], $buttons),
                ],
            ],
        ];
        return $this->dispatch('interactive_buttons', $payload);
    }

    /**
     * Send interactive list message.
     * $sections: [['title' => 'Unit Kerja', 'rows' => [['id'=>'unit:1','title'=>'Sekretariat','description'=>'']]]]
     */
    public function sendInteractiveList(string $to, string $header, string $bodyText, string $footer, array $sections, string $buttonText = 'Pilih'): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'header' => ['type' => 'text', 'text' => $header],
                'body' => ['text' => $bodyText],
                'footer' => ['text' => $footer],
                'action' => [
                    'button' => $buttonText,
                    'sections' => array_map(fn($s) => [
                        'title' => $s['title'],
                        'rows' => array_map(fn($r) => [
                            'id' => $r['id'],
                            'title' => $r['title'],
                            'description' => $r['description'] ?? '',
                        ], $s['rows']),
                    ], $sections),
                ],
            ],
        ];
        return $this->dispatch('interactive_list', $payload);
    }

    /**
     * Legacy stub for disposition notification (wraps template send eventually).
     */
    public function sendDisposition(Disposition $disposition, string $template, array $variables = []): array
    {
        $components = [];
        if ($variables) {
            // Map variables to body parameters for simple templates
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string)$v], $variables),
            ];
        }
        $result = $this->sendTemplate($disposition->to_phone, $template, null, $components);
        if (!$result['success']) {
            Log::channel('whatsapp')->error('Disposition WA send failed', [
                'disposition_id' => $disposition->id,
                'template' => $template,
                'variables' => $variables,
                'response' => $result['response'],
            ]);
            return ['success' => false, 'message_id' => null];
        }
        $messageId = $result['response']['messages'][0]['id'] ?? ('WA-' . $disposition->id . '-' . now()->timestamp);
        return ['success' => true, 'message_id' => $messageId];
    }

    protected function dispatch(string $kind, array $payload): array
    {
        $endpoint = $this->baseUrl . '/' . $this->phoneNumberId . '/messages';
        $start = microtime(true);
        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->post($endpoint, $payload);
            $durationMs = (int)((microtime(true) - $start) * 1000);
            $success = $response->successful();
            Log::channel('whatsapp')->info('WA send', [
                'kind' => $kind,
                'endpoint' => $endpoint,
                'payload' => $payload,
                'status' => $response->status(),
                'duration_ms' => $durationMs,
                'success' => $success,
                'response' => $response->json(),
            ]);
            return ['success' => $success, 'response' => $response->json()];
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('WA send exception', [
                'kind' => $kind,
                'endpoint' => $endpoint,
                'payload' => $payload,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'response' => ['error' => $e->getMessage()]];
        }
    }
}
