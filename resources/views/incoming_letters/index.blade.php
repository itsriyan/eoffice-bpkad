@extends('layouts.master')
@section('title', __('Incoming Letters'))
@section('content_header')
    <section class="content-header p-1">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ __('Incoming Letters') }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/">{{ __('Back') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Incoming Letters') }}</li>
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
            @can('create incoming_letters')
                <a href="{{ route('incoming_letters.create') }}" class="btn btn-success btn-sm"><i class="fas fa-plus"></i>
                    {{ __('Add Incoming Letter') }}</a>
            @endcan
        </div>
        <div class="card-body">
            <div class="mb-2 d-flex flex-wrap align-items-center">
                <div class="mr-2">
                    <label class="mb-0 small">{{ __('Status') }}</label>
                    <select id="filter-status" class="form-control form-control-sm" style="min-width:140px">
                        <option value="">{{ __('All') }}</option>
                        <option value="new">{{ __('New') }}</option>
                        <option value="disposed">{{ __('Disposed') }}</option>
                        <option value="followed_up">{{ __('Followed Up') }}</option>
                        <option value="rejected">{{ __('Rejected') }}</option>
                        <option value="completed">{{ __('Completed') }}</option>
                        <option value="archived">{{ __('Archived') }}</option>
                    </select>
                </div>
            </div>
            <table id="incoming-letters-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width:50px">#</th>
                        <th>{{ __('Letter Number') }}</th>
                        <th>{{ __('Letter Date') }}</th>
                        <th>{{ __('Sender') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Disposed At') }}</th>
                        <th>{{ __('Completed At') }}</th>
                        <th>{{ __('Archived At') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
@endsection
@section('js')
    <script>
        function deleteLetter(id) {
            if (confirm('{{ __('Delete this incoming letter?') }}')) {
                $.ajax({
                    url: '{{ url('incoming-letters') }}/' + id,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: () => $('#incoming-letters-table').DataTable().ajax.reload(),
                    error: () => alert('{{ __('Failed to delete') }}')
                });
            }
        }
        $(function() {
            const table = $('#incoming-letters-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: '{{ route('incoming_letters.index') }}',
                    data: function(d) {
                        d.status = $('#filter-status').val();
                    }
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'letter_number',
                        name: 'letter_number'
                    },
                    {
                        data: 'letter_date',
                        name: 'letter_date'
                    },
                    {
                        data: 'sender',
                        name: 'sender'
                    },
                    {
                        data: 'subject',
                        name: 'subject'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'disposed_at',
                        name: 'disposed_at'
                    },
                    {
                        data: 'completed_at',
                        name: 'completed_at'
                    },
                    {
                        data: 'archived_at',
                        name: 'archived_at'
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
            $('#filter-status').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endsection
