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
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('incoming_letters.index') }}" class="btn btn-secondary btn-sm"><i
                    class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
            @can('edit incoming_letters')
                <a href="{{ route('incoming_letters.edit', $incoming_letter->id) }}" class="btn btn-info btn-sm"><i
                        class="fas fa-edit"></i> {{ __('Edit') }}</a>
            @endcan
        </div>
    </div>
@endsection
