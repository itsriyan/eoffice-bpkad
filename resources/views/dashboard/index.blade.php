@extends('layouts.master')
@section('title', __('Dashboard'))
@section('content_header')
    <h1>{{ __('Dashboard') }}</h1>
@stop
@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $lettersTotal }}</h3>
                    <p>{{ __('Total Letters') }}</p>
                </div>
                <div class="icon"><i class="fas fa-envelope-open-text"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $lettersNew }}</h3>
                    <p>{{ __('New') }}</p>
                </div>
                <div class="icon"><i class="fas fa-plus-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $lettersDisposed }}</h3>
                    <p>{{ __('Disposed') }}</p>
                </div>
                <div class="icon"><i class="fas fa-share"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ $lettersFollowedUp }}</h3>
                    <p>{{ __('Followed Up') }}</p>
                </div>
                <div class="icon"><i class="fas fa-forward"></i></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $lettersCompleted }}</h3>
                    <p>{{ __('Completed') }}</p>
                </div>
                <div class="icon"><i class="fas fa-check"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-dark">
                <div class="inner">
                    <h3>{{ $lettersArchived }}</h3>
                    <p>{{ __('Archived') }}</p>
                </div>
                <div class="icon"><i class="fas fa-archive"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $lettersRejected }}</h3>
                    <p>{{ __('Rejected') }}</p>
                </div>
                <div class="icon"><i class="fas fa-times"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-teal">
                <div class="inner">
                    <h3>{{ $dispositionsTotal }}</h3>
                    <p>{{ __('Total Dispositions') }}</p>
                </div>
                <div class="icon"><i class="fas fa-route"></i></div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-light">
                <div class="inner">
                    <h3>{{ $dispositionsReceived }}</h3>
                    <p>{{ __('Received') }}</p>
                </div>
                <div class="icon"><i class="fas fa-inbox"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-orange">
                <div class="inner">
                    <h3>{{ $dispositionsFollowed }}</h3>
                    <p>{{ __('Followed Up Dispositions') }}</p>
                </div>
                <div class="icon"><i class="fas fa-play"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-green">
                <div class="inner">
                    <h3>{{ $dispositionsCompleted }}</h3>
                    <p>{{ __('Completed Dispositions') }}</p>
                </div>
                <div class="icon"><i class="fas fa-check-double"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="dashboard-section-title"><i class="fas fa-envelope-open"></i> {{ __('Recent Letters') }}
                    </h3>
                    <a href="{{ route('incoming_letters.index') }}"
                        class="btn btn-sm btn-outline-primary">{{ __('View All') }}</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-borderless recent-table mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>{{ __('Letter Number') }}</th>
                                <th>{{ __('Subject') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Received Date') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLetters as $l)
                                <tr>
                                    <td><a class="fw-semibold"
                                            href="{{ route('incoming_letters.show', $l->id) }}">{{ $l->letter_number }}</a><br><small
                                            class="text-muted">{{ $l->sender }}</small></td>
                                    <td>{{ Str::limit($l->subject, 40) }}</td>
                                    <td>
                                        @php($sv = $l->status->value)
                                        <span
                                            class="status-badge badge-{{ $sv }}">{{ $l->status->label() }}</span>
                                    </td>
                                    <td>{{ $l->received_date?->format('d M Y') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-4">{{ __('No data') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mt-3 mt-lg-0">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="dashboard-section-title"><i class="fas fa-chart-line"></i>
                        {{ __('7-Day Letter Throughput') }}</h3>
                    <span class="badge bg-primary" style="font-size:.65rem">{{ __('RAW') }}</span>
                </div>
                <div class="card-body">
                    <div class="sparkline mb-3">
                        @php($max = max(1, collect($dailySeries)->max('count')))
                        @foreach ($dailySeries as $d)
                            @php($h = ($d['count'] / $max) * 40 + 4)
                            <div class="sparkline-bar" style="height:{{ $h }}px">
                                <span>{{ $d['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($dailySeries as $d)
                            <div class="border rounded px-2 py-1" style="font-size:.65rem">
                                {{ \Carbon\Carbon::parse($d['date'])->format('d M') }}:
                                <strong>{{ $d['count'] }}</strong>
                            </div>
                        @endforeach
                    </div>
                    <hr>
                    <p class="text-muted mb-0" style="font-size:.7rem">
                        {{ __('Sparkline placeholder; replace with Chart.js for richer visualization and add caching for performance.') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Next Suggestions') }}</h3>
                </div>
                <div class="card-body p-2">
                    <ul class="mb-0 small">
                        <li>{{ __('Add role-based quick actions (e.g. Create Letter, Pending Claims).') }}</li>
                        <li>{{ __('Replace sparkline with Chart.js mini line.') }}</li>
                        <li>{{ __('Cache metrics (30s) to reduce query cost.') }}</li>
                        <li>{{ __('Add integration failure badge (WhatsApp / Archive).') }}</li>
                        <li>{{ __('Internationalize remaining static labels.') }}</li>
                        <li>{{ __('Implement aging metrics (avg hours disposed → received → completed).') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
