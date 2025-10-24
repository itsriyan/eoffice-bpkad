@extends('layouts.master')
@section('title', __('Incoming Letter Detail'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Incoming Letter Detail') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item"><a
                                href="{{ route('incoming_letters.index') }}">{{ __('Incoming Letters') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Incoming Letter Detail') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@stop
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Incoming Letter Detail') }}</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Letter Number') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->letter_number }}</dd>
                <dt class="col-sm-3">{{ __('Letter Date') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->letter_date?->toDateString() }}</dd>
                <dt class="col-sm-3">{{ __('Received Date') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->received_date?->toDateString() }}</dd>
                <dt class="col-sm-3">{{ __('Sender') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->sender }}</dd>
                <dt class="col-sm-3">{{ __('Subject') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->subject }}</dd>
                <dt class="col-sm-3">{{ __('Summary') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->summary }}</dd>
                <dt class="col-sm-3">{{ __('Status') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->status->value }}</dd>
                <dt class="col-sm-3">{{ __('Disposed At') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->disposed_at }}</dd>
                <dt class="col-sm-3">{{ __('Completed At') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->completed_at }}</dd>
                <dt class="col-sm-3">{{ __('Archived At') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->archived_at }}</dd>
                <dt class="col-sm-3">{{ __('Classification Code') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->classification_code }}</dd>
                <dt class="col-sm-3">{{ __('Security Level') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->security_level }}</dd>
                <dt class="col-sm-3">{{ __('Speed Level') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->speed_level }}</dd>
                <dt class="col-sm-3">{{ __('Origin Agency') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->origin_agency }}</dd>
                <dt class="col-sm-3">{{ __('Physical Location') }}</dt>
                <dd class="col-sm-9">{{ $incoming_letter->physical_location }}</dd>
            </dl>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap">
            <div class="mb-2 mb-sm-0">
                <a href="{{ route('incoming_letters.index') }}" class="btn btn-secondary btn-sm"><i
                        class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
                @can('incoming_letter.edit')
                    <a href="{{ route('incoming_letters.edit', $incoming_letter->id) }}" class="btn btn-info btn-sm"><i
                            class="fas fa-edit"></i> {{ __('Edit') }}</a>
                @endcan
            </div>
            @can('incoming_letter.view')
                <form action="{{ route('incoming_letters.notify_pimpinan', $incoming_letter->id) }}" method="POST"
                    class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm"
                        onclick="return confirm('{{ __('Kirim ulang notifikasi ke pimpinan?') }}')">
                        <i class="fas fa-sync"></i> {{ __('Kirim Ulang WA Pimpinan') }}
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-header">
            <h3 class="card-title">{{ __('Disposition History') }}</h3>
        </div>
        <div class="card-body p-0">
            @php($dispositions = $incoming_letter->dispositions()->orderBy('sequence')->orderBy('id')->get())
            @if ($dispositions->isEmpty())
                <div class="p-3 text-muted">{{ __('No dispositions recorded.') }}</div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Sequence') }}</th>
                                <th>{{ __('Target') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Claimed At') }}</th>
                                <th>{{ __('Received At') }}</th>
                                <th>{{ __('Followed Up At') }}</th>
                                <th>{{ __('Completed At') }}</th>
                                <th>{{ __('Instruction / Note') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($dispositions as $d)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $d->sequence }}</td>
                                    <td>
                                        @if ($d->to_name)
                                            {{ $d->to_name }}
                                        @elseif($d->to_unit_name)
                                            {{ $d->to_unit_name }}
                                        @else
                                            <span class="text-muted">{{ __('Not set') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $d->status?->label() }}</td>
                                    <td>{{ $d->claimed_at }}</td>
                                    <td>{{ $d->received_at }}</td>
                                    <td>{{ $d->followed_up_at }}</td>
                                    <td>{{ $d->completed_at }}</td>
                                    <td>{{ $d->instruction ?? ($d->rejection_reason ?? '-') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
