@extends('layouts.master')
@section('title', __('Add Incoming Letter'))
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0"><i class="fas fa-envelope-open-text mr-2"></i>{{ __('Add Incoming Letter') }}</h1>
        </div>
        <ol class="breadcrumb float-sm-right bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> {{ __('Back') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('incoming_letters.index') }}">{{ __('Incoming Letters') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Add Incoming Letter') }}</li>
        </ol>
    </div>
@stop
@section('content')
    @include('layouts.alerts')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('New Incoming Letter') }}</h3>
        </div>
        <form method="POST" action="{{ route('incoming_letters.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                @include('incoming_letters._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('incoming_letters.index') }}" class="btn btn-secondary btn-sm"><i
                        class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
                <button class="btn btn-success btn-sm"><i class="fas fa-save"></i> {{ __('Save') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        // Translation helpers (fallback to raw text if key missing)
        const t = (key) =>
        key; // server-side already translated in labels; JS alerts can be improved later by passing localized strings.

        function getScannerPort() {
            return localStorage.getItem('scanner_port') || '5000';
        }

        function fetchDevices(port) {
            $('#scanner-device').hide().empty();
            $('#refresh-device-wrapper').hide();
            $.ajax({
                url: `http://localhost:${port}/get-device`,
                method: 'GET',
                timeout: 5000,
                success: function(res) {
                    if (res.status === 'OK' && res.devices && res.devices.length) {
                        $('#scanner-device').show();
                        $('#refresh-device-wrapper').show();
                        const selectedDevice = localStorage.getItem('scanner_device');
                        let found = false;
                        res.devices.forEach(dev => {
                            const sel = (selectedDevice && dev === selectedDevice) ? 'selected' : '';
                            if (sel) found = true;
                            $('#scanner-device').append(
                            `<option value="${dev}" ${sel}>${dev}</option>`);
                        });
                        if (selectedDevice && !found) localStorage.removeItem('scanner_device');
                    }
                }
            });
        }

        $(document).ready(function() {
            // File input label + preview
            $(document).on('change', '#primary_file', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').text(fileName || '{{ __('Choose PDF/JPG/PNG file') }}');
                const file = this.files[0];
                if (file) {
                    $('#file-preview-wrapper').show();
                    const type = file.type;
                    const url = URL.createObjectURL(file);
                    if (type === 'application/pdf') {
                        $('#preview-pdf').attr('src', url).show();
                        $('#preview-img').hide();
                    } else if (type.startsWith('image/')) {
                        $('#preview-img').attr('src', url).show();
                        $('#preview-pdf').hide();
                    } else {
                        $('#preview-img').hide();
                        $('#preview-pdf').hide();
                    }
                    $('#preview-empty').hide();
                } else {
                    $('#preview-empty').show();
                    $('#preview-pdf').hide().attr('src', '');
                    $('#preview-img').hide().attr('src', '');
                }
            });

            // Port init
            const port = getScannerPort();
            $('#scanner-port').val(port);
            if (port) fetchDevices(port);

            // Save port
            $('#save-port-btn').on('click', function() {
                const p = $('#scanner-port').val();
                if (p && +p > 0 && +p <= 65535) {
                    localStorage.setItem('scanner_port', p);
                    fetchDevices(p);
                }
            });

            // Refresh devices
            $('#refresh-device-btn').on('click', function() {
                const p = $('#scanner-port').val();
                fetchDevices(p);
            });

            // Device select persistence
            $('#scanner-device').on('change', function() {
                const dev = $(this).val();
                if (dev) localStorage.setItem('scanner_device', dev);
            });

            // Scan button
            $('#scan-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<i class="fas fa-spinner fa-spin"></i> {{ __('Scanning...') }}');
                const p = getScannerPort();
                let device = $('#scanner-device').val() || localStorage.getItem('scanner_device');
                if (!device) {
                    btn.prop('disabled', false).html(
                        '<i class="fas fa-print"></i> {{ __('Scan Document') }}');
                    return alert('{{ __('Scanner device not selected') }}');
                }
                fetch(`http://localhost:${p}/scan`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            device
                        })
                    })
                    .then(r => {
                        if (!r.ok) throw new Error('scan_failed');
                        return r.blob();
                    })
                    .then(blob => {
                        const file = new File([blob], 'scan.pdf', {
                            type: 'application/pdf'
                        });
                        const dt = new DataTransfer();
                        dt.items.add(file);
                        const input = document.getElementById('primary_file');
                        input.files = dt.files;
                        input.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                        alert('{{ __('Scan successful, file ready to upload') }}');
                    })
                    .catch(() => alert('{{ __('Scan failed, ensure scanner agent active') }}'))
                    .finally(() => btn.prop('disabled', false).html(
                        '<i class="fas fa-print"></i> {{ __('Scan Document') }}'));
            });
        });
    </script>
@stop
