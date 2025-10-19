<?php

namespace App\Http\Controllers;

use App\Models\IncomingLetter;
use Illuminate\Http\Request;
use App\Http\Requests\IncomingLetterStoreRequest;
use App\Http\Requests\IncomingLetterUpdateRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class IncomingLetterController extends Controller
{
    public function index(Request $request)
    {
        if (!user()->can('incoming_letter.view')) abort(403);
        if ($request->ajax()) {
            $query = IncomingLetter::query();

            // Optional status filter
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }

            // Global search handled by DataTables search callback (subject, sender, letter_number)
            return DataTables::of($query)
                ->addIndexColumn()
                ->filter(function ($q) use ($request) {
                    if ($search = $request->get('search')['value'] ?? null) {
                        $q->where(function ($sub) use ($search) {
                            $sub->where('subject', 'like', "%{$search}%")
                                ->orWhere('sender', 'like', "%{$search}%")
                                ->orWhere('letter_number', 'like', "%{$search}%");
                        });
                    }
                })
                ->editColumn('letter_date', function (IncomingLetter $l) {
                    return $l->letter_date?->toDateString();
                })
                ->editColumn('disposed_at', function (IncomingLetter $l) {
                    return optional($l->disposed_at)->toDateTimeString();
                })
                ->editColumn('completed_at', function (IncomingLetter $l) {
                    return optional($l->completed_at)->toDateTimeString();
                })
                ->editColumn('archived_at', function (IncomingLetter $l) {
                    return optional($l->archived_at)->toDateTimeString();
                })
                ->addColumn('actions', function (IncomingLetter $l) {
                    return $this->buildActions($l);
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        return view('incoming_letters.index');
    }

    public function create()
    {
        if (!user()->can('incoming_letter.create')) abort(403);
        return view('incoming_letters.create');
    }

    public function store(IncomingLetterStoreRequest $request)
    {
        $data = $request->validated();
        $archiveExternalId = null;
        $archiveViewPath = null;
        // Upload file locally first
        if ($request->hasFile('primary_file')) {
            $file = $request->file('primary_file');
            $path = $file->store('incoming_letters', 'private');
            $data['primary_file'] = $path;
            $data['file_hash'] = hash_file('sha256', $file->getRealPath());

            // Send to external archive API (sync)
            try {
                $arsipResponse = Http::withHeaders([
                    'X-API-TOKEN' => config('e-office.arsip_token')
                ])->attach(
                    'file',
                    fopen($file->getRealPath(), 'r'),
                    $file->getClientOriginalName()
                )->post(rtrim(config('e-office.arsip_api_url'), '/') . '/api/v1/dokumen-arsip', [
                    'judul' => $data['subject'],
                    'nomor_dokumen' => $data['letter_number'],
                    'pengirim' => $data['sender'],
                    'kategori' => 'Surat Masuk',
                    'keterangan' => $data['summary'] ?? '',
                ]);
                if ($arsipResponse->successful() && $arsipResponse->json('id')) {
                    $archiveExternalId = $arsipResponse->json('id');
                    $archiveViewPath = rtrim(config('e-office.arsip_api_url'), '/') . '/dokumen-arsip/' . $archiveExternalId . '/view';
                } else {
                    // Optionally log failure, but throw to abort create
                    throw new \Exception('Archive upload failed: ' . $arsipResponse->body());
                }
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['primary_file' => __('Failed to upload to archive: :msg', ['msg' => $e->getMessage()])]);
            }
        }

        $data['archive_external_id'] = $archiveExternalId;
        $data['status'] = \App\Enums\IncomingLetterStatus::New;
        $data['user_id'] = user()->id;
        $letter = IncomingLetter::create($data);

        // Dispatch WhatsApp notification (Template Option A: link inline in body + 3 quick replies)
        try {
            $pimpinanPhone = $this->resolvePimpinanPhone();
            if ($pimpinanPhone) {
                $variables = [
                    $letter->letter_number,
                    $letter->sender,
                    $letter->subject,
                    $letter->received_date?->translatedFormat('d M Y') ?? $letter->received_date?->toDateString(),
                    route('incoming_letters.show', $letter->id), // inline document link as body placeholder
                ];
                // Store WA session context for disposition flow
                wa_session_set($pimpinanPhone, [
                    'letter_id' => $letter->id,
                    'phase' => 'template_sent',
                    'ts' => now()->timestamp,
                ]);
                // Multi-letter tracking
                wa_multi_session_add_letter($pimpinanPhone, $letter->id);
                $multi = wa_multi_session_get($pimpinanPhone);
                if ($multi && count($multi['letters']) > 1) {
                    \App\Jobs\SendWhatsappMessageJob::dispatch(
                        to: $pimpinanPhone,
                        mode: 'text',
                        templateOrText: __('Anda memiliki :count surat menunggu tindakan. Aktif: :active. Gunakan SWITCH <nomor_surat> untuk berpindah konteks.', [
                            'count' => count($multi['letters']),
                            'active' => $letter->letter_number,
                        ]),
                        variables: [],
                        correlationId: 'multi-letter-hint-' . $letter->id
                    );
                }
                \App\Jobs\SendWhatsappMessageJob::dispatch(
                    to: $pimpinanPhone,
                    mode: 'template',
                    templateOrText: config('e-office.whatsapp.default_template', 'surat_masuk_baru'),
                    variables: $variables,
                    correlationId: 'letter-create-' . $letter->id
                );
            }
        } catch (\Throwable $e) {
            // Non-blocking: log and continue
            Log::channel('whatsapp')->error('Failed to queue WA notification for incoming letter', [
                'incoming_letter_id' => $letter->id,
                'error' => $e->getMessage(),
            ]);
        }
        return redirect()->route('incoming_letters.index')->with('success', __('Incoming letter created'));
    }

    public function show(IncomingLetter $incoming_letter)
    {
        if (!user()->can('incoming_letter.view')) abort(403);
        return view('incoming_letters.show', compact('incoming_letter'));
    }

    public function edit(IncomingLetter $incoming_letter)
    {
        if (!user()->can('incoming_letter.edit')) abort(403);
        return view('incoming_letters.edit', compact('incoming_letter'));
    }

    public function update(IncomingLetterUpdateRequest $request, IncomingLetter $incoming_letter)
    {
        $data = $request->validated();
        $archiveId = $incoming_letter->archive_external_id;
        $newFileUploaded = false;
        $storedPath = null;
        if ($request->hasFile('primary_file')) {
            $file = $request->file('primary_file');
            // Store new file first (do not delete old until remote succeeds)
            $storedPath = $file->store('incoming_letters', 'private');
            $data['primary_file'] = $storedPath;
            $data['file_hash'] = hash_file('sha256', $file->getRealPath());
            $newFileUploaded = true;
            // Remote archive update/create
            try {
                $baseUrl = rtrim(config('e-office.arsip_api_url'), '/');
                if ($archiveId) {
                    $response = Http::withHeaders([
                        'X-API-TOKEN' => config('e-office.arsip_token')
                    ])->attach(
                        'file',
                        fopen($file->getRealPath(), 'r'),
                        $file->getClientOriginalName()
                    )->post($baseUrl . '/api/v1/dokumen-arsip/' . $archiveId . '/update-file');
                    if (!$response->successful()) {
                        throw new \Exception($response->body());
                    }
                } else {
                    $response = Http::withHeaders([
                        'X-API-TOKEN' => config('e-office.arsip_token')
                    ])->attach(
                        'file',
                        fopen($file->getRealPath(), 'r'),
                        $file->getClientOriginalName()
                    )->post($baseUrl . '/api/v1/dokumen-arsip', [
                        'judul' => $data['subject'] ?? '',
                        'nomor_dokumen' => $data['letter_number'] ?? $incoming_letter->letter_number,
                        'pengirim' => $data['sender'] ?? $incoming_letter->sender,
                        'kategori' => 'Surat Masuk',
                        'keterangan' => $data['summary'] ?? $incoming_letter->summary ?? '',
                    ]);
                    if ($response->successful() && $response->json('id')) {
                        $archiveId = $response->json('id');
                        $data['archive_external_id'] = $archiveId;
                    } else {
                        throw new \Exception($response->body());
                    }
                }
            } catch (\Throwable $e) {
                // Rollback new file storage if remote failed
                if ($storedPath && Storage::disk('private')->exists($storedPath)) {
                    Storage::disk('private')->delete($storedPath);
                }
                return back()->withInput()->withErrors(['primary_file' => __('Failed to update archive file: :msg', ['msg' => $e->getMessage()])]);
            }
            // If remote succeeded, delete old file
            if ($incoming_letter->primary_file && Storage::disk('private')->exists($incoming_letter->primary_file)) {
                Storage::disk('private')->delete($incoming_letter->primary_file);
            }
        }
        $incoming_letter->update($data);
        return redirect()->route('incoming_letters.index')->with('success', __('Incoming letter updated'));
    }

    public function destroy(IncomingLetter $incoming_letter)
    {
        if (!user()->can('incoming_letter.delete')) abort(403);
        // Soft delete; optional file removal left for later archiving policy
        $incoming_letter->delete();
        return response()->json(['success' => true]);
    }

    private function buildActions(IncomingLetter $l): string
    {
        $actions = '<span class="d-inline-flex">';
        if (user()->can('incoming_letter.view')) {
            $actions .= '<a href="' . route('incoming_letters.show', $l->id) . '" class="btn btn-sm btn-secondary mr-1" title="' . __('Detail') . '"><i class="fas fa-eye"></i></a>';
        }
        if (user()->can('incoming_letter.edit')) {
            $actions .= '<a href="' . route('incoming_letters.edit', $l->id) . '" class="btn btn-sm btn-info mr-1" title="' . __('Edit') . '"><i class="fas fa-edit"></i></a>';
        }
        if (user()->can('incoming_letter.delete')) {
            $actions .= '<button onclick="deleteLetter(' . $l->id . ')" class="btn btn-sm btn-danger" title="' . __('Delete') . '"><i class="fas fa-trash"></i></button>';
        }
        $actions .= '</span>';
        return $actions;
    }

    /**
     * Resolve pimpinan (leadership) phone number for initial notification.
     * Strategy: first user with role 'pimpinan' having active employee with phone number.
     * Fallback: any active employee position containing leadership keywords.
     */
    private function resolvePimpinanPhone(): ?string
    {
        $pimpinanUser = \App\Models\User::whereHas('roles', fn($q) => $q->where('name', 'pimpinan'))->first();
        if ($pimpinanUser && $pimpinanUser->employee && $pimpinanUser->employee->phone_number) {
            return $this->sanitizePhone($pimpinanUser->employee->phone_number);
        }
        $employee = \App\Models\Employee::where('status', 'active')
            ->where(function ($q) {
                $q->where('position', 'like', '%lead%')
                    ->orWhere('position', 'like', '%kepala%');
            })
            ->orderBy('id')
            ->first();
        return $employee?->phone_number ? $this->sanitizePhone($employee->phone_number) : null;
    }

    private function sanitizePhone(string $raw): string
    {
        // Basic normalization: remove non-digits, ensure country code prefix if missing
        $digits = preg_replace('/[^0-9]/', '', $raw);
        if (str_starts_with($digits, '0')) {
            // Assume Indonesia country code 62
            $digits = '62' . substr($digits, 1);
        }
        return $digits;
    }
}
