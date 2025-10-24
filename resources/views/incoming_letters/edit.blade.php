@extends('layouts.master')
@section('title', __('Edit Incoming Letter'))
@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0"><i class="fas fa-envelope-open-text mr-2"></i>{{ __('Edit Incoming Letter') }}</h1>
        </div>
        <ol class="breadcrumb float-sm-right bg-transparent p-0 m-0">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i> {{ __('Back') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ route('incoming_letters.index') }}">{{ __('Incoming Letters') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Edit Incoming Letter') }}</li>
        </ol>
    </div>
@stop
@section('content')
    @include('layouts.alerts')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('Edit Incoming Letter') }}</h3>
        </div>
        <form method="POST" action="{{ route('incoming_letters.update', $incoming_letter->id) }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="card-body">
                @include('incoming_letters._form')
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('incoming_letters.index') }}" class="btn btn-secondary btn-sm"><i
                        class="fas fa-arrow-left"></i> {{ __('Back') }}</a>
                <button class="btn btn-primary btn-sm"><i class="fas fa-save"></i> {{ __('Update') }}</button>
            </div>
        </form>
    </div>
@endsection

@section('js')
    <script>
        // Scanning logic aligned with create view (auto-init + retry fetch)
        function getScannerPort() {
            return localStorage.getItem('scanner_port') || '5000';
        }

        function fetchDevices(port, attempt = 1) {
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
                    } else if (attempt < 3) {
                        setTimeout(() => fetchDevices(port, attempt + 1), 1200);
                    }
                },
                error: function() {
                    if (attempt < 3) setTimeout(() => fetchDevices(port, attempt + 1), 1500);
                }
            });
        }

        function initScanner() {
            const port = getScannerPort();
            $('#scanner-port').val(port);
            fetchDevices(port);
        }

        $(document).ready(function() {
            // File input preview (same as create view)
            $(document).on('change', '#primary_file', function() {
                const fileName = $(this).val().split('\\').pop();
                $(this).next('.custom-file-label').text(fileName || '{{ __('Choose PDF/JPG/PNG file') }}');
                const file = this.files[0];
                if (file) {
                    $('#file-preview-wrapper').show();
                    const url = URL.createObjectURL(file);
                    if (file.type === 'application/pdf') {
                        $('#preview-pdf').attr('src', url).show();
                        $('#preview-img').hide();
                    } else if (file.type.startsWith('image/')) {
                        $('#preview-img').attr('src', url).show();
                        $('#preview-pdf').hide();
                    } else {
                        $('#preview-pdf').hide();
                        $('#preview-img').hide();
                    }
                    $('#preview-empty').hide();
                } else {
                    $('#preview-empty').show();
                    $('#preview-pdf').hide().attr('src', '');
                    $('#preview-img').hide().attr('src', '');
                }
            });

            // Initialize scanner
            initScanner();

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

            // Persist selected device
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
                    alert('{{ __('Scanner device not selected') }}');
                    btn.prop('disabled', false).html(
                        '<i class="fas fa-print"></i> {{ __('Scan Document') }}');
                    return;
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
                        if (!r.ok) throw new Error();
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

        // Early DOMContentLoaded fallback (idempotent)
        document.addEventListener('DOMContentLoaded', () => {
            if (!$('#scanner-device option').length) initScanner();
        });
    </script>
@stop
