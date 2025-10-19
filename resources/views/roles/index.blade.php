@extends('layouts.master')
@section('title', 'Roles')
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Roles') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Roles</li>
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
            @can('create roles')
                <a href="{{ route('roles.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Add Role</a>
            @endcan
        </div>
        <div class="card-body">
            <table id="roles-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>Permissions</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function deleteRole(id) {
            if (confirm('Delete this role?')) {
                $.ajax({
                    url: '{{ url('roles') }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: () => $('#roles-table').DataTable().ajax.reload(),
                    error: () => alert('Failed to delete role')
                });
            }
        }
        $(function() {
            $('#roles-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('roles.index') }}',
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
                        data: 'permissions',
                        name: 'permissions',
                        orderable: false,
                        searchable: false
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
