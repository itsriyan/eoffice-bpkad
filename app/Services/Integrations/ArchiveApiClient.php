<?php

namespace App\Services\Integrations;

use App\Models\IncomingLetter;
use Illuminate\Support\Facades\Log;

class ArchiveApiClient
{
    public function __construct(
        private readonly ?string $baseUrl = null,
        private readonly ?string $token = null,
    ) {}

    /**
     * Simulate sending letter metadata to external archive.
     * Replace with real HTTP client integration later.
     */
    public function sendLetter(IncomingLetter $letter): array
    {
        $payload = [
            'letter_number' => $letter->letter_number,
            'letter_date' => $letter->letter_date?->toDateString(),
            'sender' => $letter->sender,
            'subject' => $letter->subject,
            'summary' => $letter->summary,
            'file' => $letter->primary_file,
        ];

        Log::channel('single')->info('[ArchiveApiStub] Sending letter', $payload);

        // Stub response
        return [
            'success' => true,
            'external_id' => 'ARCH-' . $letter->id,
        ];
    }
}