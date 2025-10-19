@extends('layouts.master')
@section('title', __('Grades'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Grades') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Grades') }}</li>
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
            @can('create grades')
                <a href="{{ route('grades.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i>
                    {{ __('Add Grade') }}</a>
            @endcan
        </div>
        <div class="card-body">
            <table id="grades-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ __('No') }}</th>
                        <th>{{ __('Code') }}</th>
                        <th>{{ __('Category') }}</th>
                        <th>{{ __('Rank') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function deleteGrade(id) {
            if (confirm('Delete this grade?')) {
                $.ajax({
                    url: '{{ url('grades') }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: () => $('#grades-table').DataTable().ajax.reload(),
                    error: () => alert('Failed to delete grade')
                });
            }
        }
        $(function() {
            $('#grades-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ route('grades.index') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'category',
                        name: 'category'
                    },
                    {
                        data: 'rank',
                        name: 'rank'
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
