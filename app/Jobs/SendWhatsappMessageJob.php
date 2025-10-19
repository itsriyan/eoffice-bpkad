<?php

namespace App\Jobs;

use App\Services\Integrations\WhatsappClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds

    public function __construct(
        protected string $to,
        protected string $mode = 'template', // template | text
        protected string $templateOrText = '',
        protected array $variables = [],
        protected ?string $correlationId = null,
    ) {}

    public function handle(WhatsappClient $client): void
    {
        $result = $this->mode === 'text'
            ? $client->sendText($this->to, $this->templateOrText)
            : $client->sendTemplate($this->to, $this->templateOrText, null, $this->buildComponents());

        $messageId = $result['response']['messages'][0]['id'] ?? null;
        $success = $result['success'];
        DB::table('integration_logs')->insert([
            'service' => 'whatsapp',
            'endpoint' => $this->mode,
            'method' => 'POST',
            'request_payload' => json_encode([
                'to' => $this->to,
                'mode' => $this->mode,
                'template_or_text' => $this->templateOrText,
                'variables' => $this->variables,
            ]),
            'response_body' => json_encode($result['response']),
            'status_code' => $result['response']['error']['code'] ?? ($success ? 200 : null),
            'success' => $success,
            'attempt' => $this->attempts(),
            'message_id' => $messageId,
            'correlation_id' => $this->correlationId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!$success) {
            Log::channel('whatsapp')->warning('WA message failed', [
                'to' => $this->to,
                'mode' => $this->mode,
                'attempt' => $this->attempts(),
                'correlation_id' => $this->correlationId,
                'response' => $result['response'],
            ]);
            $this->release($this->backoff);
        }
    }

    protected function buildComponents(): array
    {
        $components = [];
        if ($this->variables) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string)$v], $this->variables),
            ];
        }
        return $components;
    }
}
