@extends('layouts.master')
@section('title', __('Bulk Edit Permissions'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Bulk Edit Permissions') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">{{ __('Permissions') }}</a>
                        </li>
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
            <h3 class="card-title">{{ __('Edit Multiple Permissions') }}</h3>
        </div>
        <form method="POST" action="{{ route('permissions.update') }}" id="bulkEditForm">
            @csrf
            @method('PUT')
            <div class="card-body">
                <div id="permissionEditRows">
                    @foreach ($permissions as $perm)
                        <div class="form-row align-items-end mb-2" data-row="perm-{{ $perm->id }}">
                            <input type="hidden" name="permissions[{{ $loop->index }}][id]" value="{{ $perm->id }}">
                            <div class="col-md-6">
                                <label class="small mb-1">{{ __('Permission Name') }}</label>
                                <input type="text" name="permissions[{{ $loop->index }}][name]"
                                    value="{{ $perm->name }}" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-sm mt-4"
                                    onclick="removeEditRow('{{ $perm->id }}')"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <small class="text-muted">{{ __('Removing a row will exclude it from update (will not delete).') }}</small>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('permissions.index') }}" class="btn btn-secondary btn-sm"><i
                        class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
                <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('Update All') }}</button>
            </div>
        </form>
    </div>
@endsection
@section('js')
    <script>
        function removeEditRow(id) {
            const row = $('[data-row="perm-' + id + '"]');
            // mark inputs as disabled so they are not submitted
            row.find('input,select').prop('disabled', true);
            row.hide();
        }
    </script>
@endsection
