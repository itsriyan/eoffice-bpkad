@extends('layouts.master')
@section('title', 'Employees')
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Employees</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">Home</a></li>
                        <li class="breadcrumb-item active">Employees</li>
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
            @can('create employees')
                <a href="{{ route('employees.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> Add
                    Employee</a>
            @endcan
        </div>
        <div class="card-body">
            <table id="employees-table" class="table table-bordered table-striped w-100">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Name</th>
                        <th>NIP</th>
                        <th>Position</th>
                        <th>Grade</th>
                        <th>Work Unit</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>User Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function deleteEmployee(id) {
            if (confirm('Delete this employee?')) {
                $.ajax({
                    url: '{{ route('employees.index') }}' + '/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: () => $('#employees-table').DataTable().ajax.reload(),
                    error: () => alert('Delete failed')
                });
            }
        }
        $(function() {
            $('#employees-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('employees.index') }}',
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
                        data: 'nip',
                        name: 'nip'
                    },
                    {
                        data: 'position',
                        name: 'position'
                    },
                    {
                        data: 'grade',
                        name: 'grade'
                    },
                    {
                        data: 'work_unit',
                        name: 'work_unit'
                    },
                    {
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'phone_number',
                        name: 'phone_number'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'user_email',
                        name: 'user_email'
                    },
                    {
                        data: 'role',
                        name: 'role'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false
                    }
                ],
                pageLength: 10,
                responsive: true,
                autoWidth: false
            });
        });
    </script>
@endsection
