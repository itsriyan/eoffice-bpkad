@extends('layouts.master')
@section('title', __('Role Detail'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Role Detail') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">{{ __('Roles') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Detail') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@stop
@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Role Info') }}</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Name') }}</dt>
                <dd class="col-sm-9">{{ $role->name }}</dd>
                <dt class="col-sm-3">{{ __('Permissions') }}</dt>
                <dd class="col-sm-9">{{ $role->permissions->pluck('name')->implode(', ') ?: '-' }}</dd>
                <dt class="col-sm-3">{{ __('Created At') }}</dt>
                <dd class="col-sm-9">{{ $role->created_at }}</dd>
                <dt class="col-sm-3">{{ __('Updated At') }}</dt>
                <dd class="col-sm-9">{{ $role->updated_at }}</dd>
            </dl>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                {{ __('Back') }}</a>
            @can('role.edit')
                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-info btn-sm"><i class="fas fa-edit"></i>
                    {{ __('Edit') }}</a>
            @endcan
        </div>
    </div>
@endsection
