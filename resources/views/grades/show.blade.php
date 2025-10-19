@extends('layouts.master')
@section('title', __('Grade Detail'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Grade Detail') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('grades.index') }}">{{ __('Grades') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Grade Detail') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@stop
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Grade Detail') }}</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Code') }}</dt>
                <dd class="col-sm-9">{{ $grade->code }}</dd>
                <dt class="col-sm-3">{{ __('Category') }}</dt>
                <dd class="col-sm-9">{{ $grade->category }}</dd>
                <dt class="col-sm-3">{{ __('Rank') }}</dt>
                <dd class="col-sm-9">{{ $grade->rank }}</dd>
                <dt class="col-sm-3">{{ __('Created At') }}</dt>
                <dd class="col-sm-9">{{ $grade->created_at }}</dd>
                <dt class="col-sm-3">{{ __('Updated At') }}</dt>
                <dd class="col-sm-9">{{ $grade->updated_at }}</dd>
            </dl>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('grades.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                {{ __('Back') }}</a>
            @can('edit grades')
                <a href="{{ route('grades.edit', $grade->id) }}" class="btn btn-info btn-sm"><i class="fas fa-edit"></i>
                    {{ __('Edit') }}</a>
            @endcan
        </div>
    </div>
@endsection
