@extends('layouts.master')

@section('title', __('List Users'))

@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('List Users') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active"><a href="/">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item active"><a href="/users">{{ __('Users') }}</a></li>
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
            @can('user.create')
                <a href="{{ route('users.create') }}" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> {{ __('Add User') }}
                </a>
            @endcan
        </div>
        <div class="card-body">
            <table id="users-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('No') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Phone Number') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function deleteUser(id) {
            if (confirm(@json(__('Delete this user?')))) {
                $.ajax({
                    url: '{{ url('users') }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(res) {
                        $('#users-table').DataTable().ajax.reload();
                    },
                    error: function(err) {
                        alert(@json(__('Failed to delete user')));
                    }
                });
            }
        }

        $(function() {
            $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('users.index') }}',
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
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'phone_number',
                        name: 'phone_number'
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
                autoWidth: true
            });
        });
    </script>
@endsection
