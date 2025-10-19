@extends('layouts.master')
@section('title', __('Edit Work Unit'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Edit Work Unit') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('work_units.index') }}">{{ __('Work Units') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Edit Work Unit') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@stop
@section('content')
    @include('layouts.alerts')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Edit Work Unit') }}</h3>
        </div>
        <form method="POST" action="{{ route('work_units.update', $workUnit->id) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('work_units._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('work_units.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                    {{ __('Back') }}</a>
                <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('Update') }}</button>
            </div>
        </form>
    </div>
@endsection
