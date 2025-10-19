@extends('layouts.master')

@section('title', 'Create User')

@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Create User') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                        <li class="breadcrumb-item active">Create</li>
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
            <h3 class="card-title">New User</h3>
        </div>
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="card-body">
                @include('users._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i>
                    Back</a>
                <button class="btn btn-success btn-sm"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>
    </div>
@endsection
