@extends('layouts.master')
@section('title', __('Work Unit Detail'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Work Unit Detail') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('work_units.index') }}">{{ __('Work Units') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Work Unit Detail') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@stop
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Work Unit Detail') }}</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Name') }}</dt>
                <dd class="col-sm-9">{{ $workUnit->name }}</dd>
                <dt class="col-sm-3">{{ __('Description') }}</dt>
                <dd class="col-sm-9">{{ $workUnit->description }}</dd>
                <dt class="col-sm-3">{{ __('Created At') }}</dt>
                <dd class="col-sm-9">{{ $workUnit->created_at }}</dd>
                <dt class="col-sm-3">{{ __('Updated At') }}</dt>
                <dd class="col-sm-9">{{ $workUnit->updated_at }}</dd>
            </dl>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('work_units.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                {{ __('Back') }}</a>
            @can('edit work_units')
                <a href="{{ route('work_units.edit', $workUnit->id) }}" class="btn btn-info btn-sm"><i class="fas fa-edit"></i>
                    {{ __('Edit') }}</a>
            @endcan
        </div>
    </div>
@endsection
