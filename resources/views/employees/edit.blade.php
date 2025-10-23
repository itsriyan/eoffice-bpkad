@extends('layouts.master')
@section('title', __('Edit Employee'))

@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Edit Employee') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('employees.index') }}">{{ __('Employees') }}</a></li>
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
            <h3 class="card-title">{{ __('Edit Employee') }}</h3>
        </div>
        <form method="POST" action="{{ route('employees.update', $employee->id) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('employees._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('employees.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                    {{ __('Back') }}</a>
                <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('Update') }}</button>
            </div>
        </form>
    </div>
@endsection
