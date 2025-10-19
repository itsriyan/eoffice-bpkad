@extends('layouts.master')
@section('title', 'Employee Detail')

@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Employee Detail') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">Employees</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Employee Info</h3>
        </div>
        <div class="card-body">
            <dl class="row">
                <dt class="col-sm-3">Name</dt>
                <dd class="col-sm-9">{{ $employee->name }}</dd>
                <dt class="col-sm-3">NIP</dt>
                <dd class="col-sm-9">{{ $employee->nip }}</dd>
                <dt class="col-sm-3">Position</dt>
                <dd class="col-sm-9">{{ $employee->position }}</dd>
                <dt class="col-sm-3">Grade</dt>
                <dd class="col-sm-9">{{ $employee->grade?->code }} {{ $employee->grade?->rank }}</dd>
                <dt class="col-sm-3">Work Unit</dt>
                <dd class="col-sm-9">{{ $employee->workUnit?->name }}</dd>
                <dt class="col-sm-3">Email</dt>
                <dd class="col-sm-9">{{ $employee->email }}</dd>
                <dt class="col-sm-3">Phone</dt>
                <dd class="col-sm-9">{{ $employee->phone_number }}</dd>
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9"><span
                        class="badge badge-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($employee->status) }}</span>
                </dd>
                <dt class="col-sm-3">User Account</dt>
                <dd class="col-sm-9">{{ $employee->user?->email }}</dd>
                <dt class="col-sm-3">Role</dt>
                <dd class="col-sm-9">{{ $employee->user?->roles->pluck('name')->implode(', ') }}</dd>
            </dl>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                Back</a>
            @can('edit employees')
                <a href="{{ route('employees.edit', $employee->id) }}" class="btn btn-info btn-sm"><i class="fas fa-edit"></i>
                    Edit</a>
            @endcan
        </div>
    </div>
@endsection
