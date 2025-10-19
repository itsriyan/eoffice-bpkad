@extends('layouts.master')
@section('title', 'Permissions')
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Permissions') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Permissions</li>
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
            @can('create permissions')
                <a href="{{ route('permissions.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Bulk
                    Create</a>
            @endcan
            @can('edit permissions')
                <a href="{{ route('permissions.edit') }}" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Bulk
                    Edit</a>
            @endcan
        </div>
        <div class="card-body">
            <table id="permissions-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function deletePermission(id) {
            if (confirm('Delete this permission?')) {
                $.ajax({
                    url: '{{ url('permissions') }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: () => $('#permissions-table').DataTable().ajax.reload(),
                    error: () => alert('Failed to delete permission')
                });
            }
        }
        $(function() {
            $('#permissions-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('permissions.index') }}',
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
