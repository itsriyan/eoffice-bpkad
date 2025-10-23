@extends('layouts.master')
@section('title', __('Bulk Create Permissions'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Bulk Create Permissions') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">{{ __('Permissions') }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ __('Create') }}</li>
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
            <h3 class="card-title">{{ __('Add Multiple Permissions') }}</h3>
        </div>
        <form method="POST" action="{{ route('permissions.store') }}" id="bulkCreateForm">
            @csrf
            <div class="card-body">
                <div id="permissionRows"></div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addRow()"><i
                        class="fas fa-plus"></i> Add Row</button>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('permissions.index') }}" class="btn btn-secondary btn-sm"><i
                        class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
                <button class="btn btn-success btn-sm"><i class="fas fa-save"></i> {{ __('Save All') }}</button>
            </div>
        </form>
    </div>
@endsection
@section('js')
    <script>
        let rowIdx = 0;

        function addRow(initial = '') {
            rowIdx++;
            const html = `<div class="form-row align-items-end mb-2" data-row="${rowIdx}">
    <div class="col-md-6"><label class="small mb-1">{{ __('Permission Name') }}</label>
    <input type="text" name="permissions[${rowIdx}][name]" value="${initial}" class="form-control" required></div>
  <div class="col-md-2"><button type="button" class="btn btn-danger btn-sm mt-4" onclick="removeRow(${rowIdx})"><i class="fas fa-times"></i></button></div>
</div>`;
            $('#permissionRows').append(html);
        }

        function removeRow(i) {
            $('[data-row="' + i + '"]').remove();
        }
        // prefill a few common actions example
        ['view users', 'create users', 'edit users', 'delete users'].forEach(n => addRow(n));
    </script>
@endsection
