@extends('layouts.master')

@section('title', __('User Detail'))

@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('User Detail') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">{{ __('Users') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Detail') }}</li>
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
            <h3 class="card-title">{{ __('User Info') }}</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Name') }}</dt>
                <dd class="col-sm-9">{{ $user->name }}</dd>
                <dt class="col-sm-3">{{ __('Email') }}</dt>
                <dd class="col-sm-9">{{ $user->email }}</dd>
                <dt class="col-sm-3">{{ __('Role') }}</dt>
                <dd class="col-sm-9">{{ $user->roles->pluck('name')->implode(', ') ?: '-' }}</dd>
                <dt class="col-sm-3">{{ __('Created At') }}</dt>
                <dd class="col-sm-9">{{ $user->created_at }}</dd>
                <dt class="col-sm-3">{{ __('Updated At') }}</dt>
                <dd class="col-sm-9">{{ $user->updated_at }}</dd>
                @if ($user->employee)
                    <dt class="col-sm-3">{{ __('Employee') }}</dt>
                    <dd class="col-sm-9">{{ $user->employee->name }} ({{ $user->employee->nip }})</dd>
                @endif
            </dl>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                {{ __('Back') }}</a>
            @can('user.edit')
                <a href="{{ route('users.edit', $user) }}" class="btn btn-info btn-sm"><i class="fas fa-edit"></i>
                    {{ __('Edit') }}</a>
            @endcan
        </div>
    </div>
@endsection
