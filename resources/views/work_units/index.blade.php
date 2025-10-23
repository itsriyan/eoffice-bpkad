@extends('layouts.master')
@section('title', __('Work Units'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Work Units') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Work Units') }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>
@stop
@section('content')
    @include('layouts.alerts')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            @can('work_unit.create')
                <a href="{{ route('work_units.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i>
                    {{ __('Add Work Unit') }}</a>
            @endcan
        </div>
        <div class="card-body">
            <table id="work-units-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('No') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function deleteWorkUnit(id) {
            if (confirm(@json(__('Delete this work unit?')))) {
                $.ajax({
                    url: '{{ url('work-units') }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: () => $('#work-units-table').DataTable().ajax.reload(),
                    error: () => alert(@json(__('Failed to delete work unit')))
                });
            }
        }
        $(function() {
            $('#work-units-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('work_units.index') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'description',
                        name: 'description'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                pageLength: 25,
                responsive: true,
                autoWidth: false
            });
        });
    </script>
@endsection
