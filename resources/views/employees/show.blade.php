@extends('layouts.master')
@section('title', __('Employee Detail'))

@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Employee Detail') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">{{ __('Employees') }}</a></li>
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
            <h3 class="card-title">{{ __('Employee Info') }}</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">{{ __('Name') }}</dt>
                <dd class="col-sm-9">{{ $employee->name }}</dd>
                <dt class="col-sm-3">{{ __('NIP') }}</dt>
                <dd class="col-sm-9">{{ $employee->nip }}</dd>
                <dt class="col-sm-3">{{ __('Position') }}</dt>
                <dd class="col-sm-9">{{ $employee->position }}</dd>
                <dt class="col-sm-3">{{ __('Grade') }}</dt>
                <dd class="col-sm-9">{{ $employee->grade?->code }} {{ $employee->grade?->rank }}</dd>
                <dt class="col-sm-3">{{ __('Work Unit') }}</dt>
                <dd class="col-sm-9">{{ $employee->workUnit?->name }}</dd>
                <dt class="col-sm-3">{{ __('Email') }}</dt>
                <dd class="col-sm-9">{{ $employee->email }}</dd>
                <dt class="col-sm-3">{{ __('Phone') }}</dt>
                <dd class="col-sm-9">{{ $employee->phone_number }}</dd>
                <dt class="col-sm-3">{{ __('Status') }}</dt>
                <dd class="col-sm-9"><span
                        class="badge badge-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->status) }}</span>
                </dd>
                <dt class="col-sm-3">{{ __('User Account') }}</dt>
                <dd class="col-sm-9">{{ $employee->user?->email }}</dd>
                <dt class="col-sm-3">{{ __('Role') }}</dt>
                <dd class="col-sm-9">{{ $employee->user?->roles->pluck('name')->implode(', ') }}</dd>
            </dl>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                {{ __('Back') }}</a>
            @can('employee.edit')
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-info btn-sm"><i class="fas fa-edit"></i>
                    {{ __('Edit') }}</a>
            @endcan
        </div>
    </div>
@endsection
