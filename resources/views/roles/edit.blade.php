@extends('layouts.master')
@section('title', __('Edit Role'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Edit Role') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">{{ __('Roles') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Edit') }}</li>
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
            <h3 class="card-title">{{ __('Edit Role') }}</h3>
        </div>
        <form method="POST" action="{{ route('roles.update', $role->id) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div class="form-group">
                    <label>{{ __('Role Name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $role->name) }}" class="form-control"
                        required>
                </div>
                <div class="form-group">
                    <label>{{ __('Permissions') }}</label>
                    <div class="row">
                        @foreach ($permissions as $perm)
                            <div class="col-md-3 col-sm-4 col-6 mb-1">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="perm-{{ $perm->id }}"
                                        name="permissions[]" value="{{ $perm->name }}" @checked(in_array($perm->name, old('permissions', $role->permissions->pluck('name')->toArray())))>
                                    <label class="custom-control-label"
                                        for="perm-{{ $perm->id }}">{{ $perm->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('roles.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                    {{ __('Back') }}</a>
                <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('Update') }}</button>
            </div>
        </form>
    </div>
@endsection
