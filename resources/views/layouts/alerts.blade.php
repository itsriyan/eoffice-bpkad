@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="alert-success">
        {{ session('success') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="alert-error">
        {{ $errors->first() }}
    </div>
@endif

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var successAlert = document.getElementById('alert-success');
                var errorAlert = document.getElementById('alert-error');
                if (successAlert) successAlert.style.display = 'none';
                if (errorAlert) errorAlert.style.display = 'none';
            }, 10000);
        });
    </script>
@endpush
