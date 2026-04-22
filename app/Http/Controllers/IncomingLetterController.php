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
        // Direct upload to external archive without local storage
        if ($request->hasFile('primary_file')) {
            $file = $request->file('primary_file');
            try {
                $response = Http::withHeaders([
                    'X-API-TOKEN' => config('e-office.arsip_token'),
                    'Accept' => 'application/json'
                ])->attach(
                    'file',
                    fopen($file->getRealPath(), 'r'),
                    $file->getClientOriginalName()
                )->post(rtrim(config('e-office.arsip_api_url'), '/') . '/api/v1/dokumen-arsip', [
                    'judul' => $data['subject'],
                    'nomor_dokumen' => $data['letter_number'],
                    'pengirim' => $data['sender'],
                    'penerima' => 'BPKAD Kab. Tangerang',
                    'tanggal_dokumen' => $data['letter_date'],
                    // API expects kategori as array
                    'kategori' => 'Surat Masuk',
                    'keterangan' => $data['summary'] ?? '',
                ]);
                $body = $response->body();
                if (str_starts_with(ltrim($body), '<!DOCTYPE') || str_starts_with(ltrim($body), '<html')) {
                    throw new \Exception('Unexpected HTML response (possible 419 or auth issue)');
                }
                $respJson = $response->json();
                $extId = $response->json('id') ?? ($respJson['data']['id'] ?? null);
                if ($response->successful() && $extId) {
                    $data['archive_external_id'] = $extId;
                    // Optionally store hash for integrity; remote only
                    $data['file_hash'] = hash_file('sha256', $file->getRealPath());
                    // No local primary_file path saved now
                } else {
                    throw new \Exception($response->body());
                }
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['primary_file' => __('Failed remote upload: :msg', ['msg' => $e->getMessage()])]);
            }
        }
        $data['status'] = \App\Enums\IncomingLetterStatus::New;
        $data['user_id'] = user()->id;
        $letter = IncomingLetter::create($data);

        // Dispatch WhatsApp notification (Template Option A: link inline in body + 3 quick replies)
        try {
            $pimpinanPhone = $this->resolvePimpinanPhone();
            if ($pimpinanPhone) {
                $docUrl = $letter->archive_external_id
                    ? rtrim(config('e-office.arsip_api_url'), '/') . '/dokumen-arsip/' . $letter->archive_external_id . '/view'
                    : '-';

                $variables = [
                    $letter->letter_number,
                    $letter->sender,
                    $letter->subject,
                    $letter->received_date?->translatedFormat('d M Y') ?? $letter->received_date?->toDateString(),
                    $docUrl,
                ];

                // Multi-letter tracking – tambah ke daftar pending pimpinan
                wa_multi_session_add_letter($pimpinanPhone, $letter->id);
                $multi       = wa_multi_session_get($pimpinanPhone);
                $pendingCount = count($multi['letters'] ?? []);

                // Cek apakah pimpinan sedang mid-flow
                $isMidFlow = wa_session_is_mid_flow(wa_session_get($pimpinanPhone));

                if ($isMidFlow) {
                    // Jangan timpa session – kirim notif singkat saja
                    \App\Jobs\SendWhatsappMessageJob::dispatch(
                        to: $pimpinanPhone,
                        mode: 'text',
                        templateOrText: "📩 *Surat Masuk Baru*\n\nNo. Surat : {$letter->letter_number}\nPengirim  : {$letter->sender}\nPerihal   : {$letter->subject}\n\nTotal pending: *{$pendingCount} surat*. Selesaikan alur saat ini, lalu ketik *DAFTAR* untuk berpindah.",
                        variables: [],
                        correlationId: 'letter-notify-mid-flow-' . $letter->id
                    );
                } else {
                    // Idle – kirim template normal
                    \App\Jobs\SendWhatsappMessageJob::dispatch(
                        to: $pimpinanPhone,
                        mode: 'template',
                        templateOrText: config('e-office.whatsapp.default_template', 'surat_masuk_baru'),
                        variables: $variables,
                        correlationId: 'letter-create-' . $letter->id
                    );
                }
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
        if ($request->hasFile('primary_file')) {
            $file = $request->file('primary_file');
            try {
                $baseUrl = rtrim(config('e-office.arsip_api_url'), '/');
                if ($archiveId) {
                    $resp = Http::withHeaders([
                        'X-API-TOKEN' => config('e-office.arsip_token'),
                        'Accept' => 'application/json'
                    ])->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
                        ->post($baseUrl . '/api/v1/dokumen-arsip/' . $archiveId . '/update-file');
                    $body = $resp->body();
                    if (str_starts_with(ltrim($body), '<!DOCTYPE') || str_starts_with(ltrim($body), '<html')) {
                        throw new \Exception('Unexpected HTML response (possible 419 or auth issue)');
                    }
                    if (!$resp->successful()) throw new \Exception($body);
                } else {
                    $resp = Http::withHeaders([
                        'X-API-TOKEN' => config('e-office.arsip_token'),
                        'Accept' => 'application/json'
                    ])->attach('file', fopen($file->getRealPath(), 'r'), $file->getClientOriginalName())
                        ->post($baseUrl . '/api/v1/dokumen-arsip', [
                            'judul' => $data['subject'] ?? $incoming_letter->subject ?? '',
                            'nomor_dokumen' => $data['letter_number'] ?? $incoming_letter->letter_number,
                            'pengirim' => $data['sender'] ?? $incoming_letter->sender,
                            'penerima' => 'BPKAD Kab. Tangerang',
                            'tanggal_dokumen' => $data['letter_date'] ?? $incoming_letter->letter_date?->toDateString(),
                            // API expects kategori as array
                            'kategori' => 'Surat Masuk',
                            'keterangan' => $data['summary'] ?? $incoming_letter->summary ?? '',
                        ]);
                    $body = $resp->body();
                    if (str_starts_with(ltrim($body), '<!DOCTYPE') || str_starts_with(ltrim($body), '<html')) {
                        throw new \Exception('Unexpected HTML response (possible 419 or auth issue)');
                    }
                    $respJson = $resp->json();
                    $extId = $resp->json('id') ?? ($respJson['data']['id'] ?? null);
                    if ($resp->successful() && $extId) {
                        $archiveId = $extId;
                        $data['archive_external_id'] = $archiveId;
                    } else throw new \Exception($body);
                }
                // Update file hash only (no local path)
                $data['file_hash'] = hash_file('sha256', $file->getRealPath());
                // Remove local primary_file reference if existed earlier
                $data['primary_file'] = null;
            } catch (\Throwable $e) {
                return back()->withInput()->withErrors(['primary_file' => __('Failed remote update: :msg', ['msg' => $e->getMessage()])]);
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

    public function notifyPimpinan(IncomingLetter $incoming_letter)
    {
        // Allow roles that can view letters (view permission sufficient) or dispose (pimpinan)
        if (!user()->can('incoming_letter.view')) abort(403);
        try {
            // Jika surat sudah didisposisi, kirim ulang ke tujuan disposisi (bukan pimpinan)
            $isDisposed = in_array($incoming_letter->status, [
                \App\Enums\IncomingLetterStatus::Disposed,
                \App\Enums\IncomingLetterStatus::FollowedUp,
                \App\Enums\IncomingLetterStatus::Completed,
            ]);

            if ($isDisposed) {
                return $this->resendToDispositionTarget($incoming_letter);
            }

            // Status new/rejected – kirim ulang ke pimpinan
            $pimpinanPhone = $this->resolvePimpinanPhone();
            if (!$pimpinanPhone) {
                return back()->with('error', __('Nomor pimpinan tidak ditemukan.'));
            }
            $variables = [
                $incoming_letter->letter_number,
                $incoming_letter->sender,
                $incoming_letter->subject,
                $incoming_letter->received_date?->translatedFormat('d M Y') ?? $incoming_letter->received_date?->toDateString(),
                route('incoming_letters.show', $incoming_letter->id),
            ];
            // Tambahkan ke daftar pending; JANGAN set session – user mulai dari DAFTAR
            wa_multi_session_add_letter($pimpinanPhone, $incoming_letter->id);
            \App\Jobs\SendWhatsappMessageJob::dispatch(
                to: $pimpinanPhone,
                mode: 'template',
                templateOrText: config('e-office.whatsapp.default_template', 'surat_masuk_baru'),
                variables: $variables,
                correlationId: 'letter-notify-manual-' . $incoming_letter->id
            );
            return back()->with('success', __('Notifikasi WhatsApp dikirim ulang ke pimpinan.'));
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->error('Failed manual WA notify', [
                'incoming_letter_id' => $incoming_letter->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', __('Gagal kirim ulang: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /**
     * Kirim ulang notifikasi disposisi ke tujuan (pegawai/unit) berdasarkan disposisi terakhir.
     */
    private function resendToDispositionTarget(IncomingLetter $letter): \Illuminate\Http\RedirectResponse
    {
        // Ambil disposisi aktif terakhir (bukan rejected/completed)
        $disp = $letter->dispositions()
            ->whereNotIn('status', [
                \App\Enums\DispositionStatus::Rejected->value,
                \App\Enums\DispositionStatus::Completed->value,
            ])
            ->orderByDesc('sequence')
            ->first();

        if (! $disp) {
            // Fallback: disposisi terbaru apapun statusnya
            $disp = $letter->dispositions()->orderByDesc('sequence')->first();
        }

        if (! $disp) {
            return back()->with('error', __('Tidak ada data disposisi untuk surat ini.'));
        }

        $note        = $disp->instruction ?? '-';
        $letterNum   = $letter->letter_number;
        $sent        = 0;

        if ($disp->to_phone) {
            // Tujuan: pegawai individual
            $targetPhone = preg_replace('/[^0-9]/', '', $disp->to_phone);
            if (str_starts_with($targetPhone, '0')) {
                $targetPhone = '62' . substr($targetPhone, 1);
            }
            if ($targetPhone) {
                wa_multi_session_add_letter($targetPhone, $letter->id);
                \App\Jobs\SendWhatsappMessageJob::dispatch(
                    to: $targetPhone,
                    mode: 'text',
                    templateOrText: "*[Kirim Ulang] Disposisi Surat Masuk* 📨\n\nSurat *{$letterNum}* dari {$letter->sender} didisposisikan kepada Anda.\n\n*Instruksi:* {$note}\n\nKetik *DAFTAR* untuk melihat disposisi Anda dan memilih tindakan.",
                    variables: [],
                    correlationId: 'resend-disp-emp-' . $disp->id
                );
                $sent++;
            }
        } elseif ($disp->to_unit_id) {
            // Tujuan: unit kerja – broadcast ke semua pegawai aktif di unit
            $unitEmployees = \App\Models\Employee::where('work_unit_id', $disp->to_unit_id)
                ->where('status', 'active')
                ->get();

            foreach ($unitEmployees as $emp) {
                if (! $emp->phone_number) continue;
                $targetPhone = preg_replace('/[^0-9]/', '', $emp->phone_number);
                if (str_starts_with($targetPhone, '0')) {
                    $targetPhone = '62' . substr($targetPhone, 1);
                }
                if (! $targetPhone) continue;
                wa_multi_session_add_letter($targetPhone, $letter->id);
                \App\Jobs\SendWhatsappMessageJob::dispatch(
                    to: $targetPhone,
                    mode: 'text',
                    templateOrText: "*[Kirim Ulang] Disposisi Surat Masuk* 📨\n\nSurat *{$letterNum}* dari {$letter->sender} menunggu penanggung jawab di unit *{$disp->to_unit_name}*.\n\n*Instruksi:* {$note}\n\nKetik *DAFTAR* untuk melihat disposisi Anda dan memilih tindakan.",
                    variables: [],
                    correlationId: 'resend-disp-unit-' . $disp->id . '-emp-' . $emp->id
                );
                $sent++;
            }
        }

        if ($sent === 0) {
            return back()->with('error', __('Tidak ada nomor tujuan yang valid untuk dikirim ulang.'));
        }

        $target = $disp->to_name ?? $disp->to_unit_name ?? '-';
        return back()->with('success', __('Notifikasi disposisi dikirim ulang ke :target (:count penerima).', [
            'target' => $target,
            'count'  => $sent,
        ]));
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
