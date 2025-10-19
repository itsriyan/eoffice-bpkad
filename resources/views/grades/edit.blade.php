@extends('layouts.master')
@section('title', __('Edit Grade'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Edit Grade') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('grades.index') }}">{{ __('Grades') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Edit Grade') }}</li>
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
            <h3 class="card-title">{{ __('Edit Grade') }}</h3>
        </div>
        <form method="POST" action="{{ route('grades.update', $grade->id) }}">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('grades._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('grades.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                    {{ __('Back') }}</a>
                <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('Update') }}</button>
            </div>
        </form>
    </div>
@endsection
