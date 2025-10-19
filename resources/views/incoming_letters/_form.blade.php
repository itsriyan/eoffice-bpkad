<div class="form-row">
    <div class="form-group col-md-4">
        <label>{{ __('Letter Number') }}</label>
        <input type="text" name="letter_number" class="form-control"
            value="{{ old('letter_number', $incoming_letter->letter_number ?? '') }}" required>
    </div>
    <div class="form-group col-md-4">
        <label>{{ __('Letter Date') }}</label>
        <input type="date" name="letter_date" class="form-control"
            value="{{ old('letter_date', isset($incoming_letter->letter_date) ? $incoming_letter->letter_date->toDateString() : '') }}"
            required>
    </div>
    <div class="form-group col-md-4">
        <label>{{ __('Received Date') }}</label>
        <input type="date" name="received_date" class="form-control"
            value="{{ old('received_date', isset($incoming_letter->received_date) ? $incoming_letter->received_date->toDateString() : '') }}"
            required>
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>{{ __('Sender') }}</label>
        <input type="text" name="sender" class="form-control"
            value="{{ old('sender', $incoming_letter->sender ?? '') }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>{{ __('Subject') }}</label>
        <input type="text" name="subject" class="form-control"
            value="{{ old('subject', $incoming_letter->subject ?? '') }}" required>
    </div>
</div>
<div class="form-group">
    <label>{{ __('Summary') }}</label>
    <textarea name="summary" class="form-control" rows="3">{{ old('summary', $incoming_letter->summary ?? '') }}</textarea>
</div>
<div class="form-row">
    <div class="form-group col-md-6">
        <label>{{ __('Primary File') }}</label>
        <div class="custom-file">
            <input type="file" name="primary_file" id="primary_file" class="custom-file-input"
                accept="application/pdf,image/*" {{ isset($incoming_letter) ? '' : 'required' }}>
            <label class="custom-file-label" for="primary_file">{{ __('Choose PDF/JPG/PNG file') }}</label>
        </div>
        @if (isset($incoming_letter) && $incoming_letter->primary_file)
            <small class="text-muted d-block mt-1">{{ __('Existing file stored') }}</small>
        @endif
        <div class="mt-3">
            <div class="input-group input-group-sm mb-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-network-wired"></i></span>
                </div>
                <input type="number" min="1" max="65535" class="form-control" id="scanner-port"
                    placeholder="{{ __('Scanner Port') }}" style="max-width:120px;">
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button"
                        id="save-port-btn">{{ __('Save Port') }}</button>
                </div>
                <select class="form-control ml-2" id="scanner-device" style="max-width:220px; display:none;"></select>
                <div class="input-group-append" id="refresh-device-wrapper" style="display:none;">
                    <button type="button" class="btn btn-outline-info" id="refresh-device-btn"
                        title="{{ __('Refresh Device') }}"><i class="fas fa-sync"></i></button>
                </div>
            </div>
            <button type="button" class="btn btn-info btn-sm" id="scan-btn"><i class="fas fa-print"></i>
                {{ __('Scan Document') }}</button>
            <small
                class="form-text text-muted">{{ __('Select a PDF/JPG/PNG file OR scan directly from a connected scanner.') }}</small>
        </div>
    </div>
    <div class="form-group col-md-6">
        <label>{{ __('Classification Code') }}</label>
        <input type="text" name="classification_code" class="form-control"
            value="{{ old('classification_code', $incoming_letter->classification_code ?? '') }}">
    </div>
</div>
<div class="form-row">
    <div class="form-group col-md-4">
        <label>{{ __('Security Level') }}</label>
        <input type="text" name="security_level" class="form-control"
            value="{{ old('security_level', $incoming_letter->security_level ?? '') }}">
    </div>
    <div class="form-group col-md-4">
        <label>{{ __('Speed Level') }}</label>
        <input type="text" name="speed_level" class="form-control"
            value="{{ old('speed_level', $incoming_letter->speed_level ?? '') }}">
    </div>
    <div class="form-group col-md-4">
        <label>{{ __('Origin Agency') }}</label>
        <input type="text" name="origin_agency" class="form-control"
            value="{{ old('origin_agency', $incoming_letter->origin_agency ?? '') }}">
    </div>
</div>
<div class="form-group">
    <label>{{ __('Physical Location') }}</label>
    <input type="text" name="physical_location" class="form-control"
        value="{{ old('physical_location', $incoming_letter->physical_location ?? '') }}">
</div>

<div class="row justify-content-center mt-3" id="file-preview-wrapper" style="display:none;">
    <div class="col-md-12">
        <div class="card card-secondary card-outline">
            <div class="card-header py-2"><i class="fas fa-eye mr-2"></i>{{ __('File Preview') }}</div>
            <div class="card-body" style="min-height:280px;">
                <div id="preview-empty" class="text-muted text-center">{{ __('No file selected') }}</div>
                <embed id="preview-pdf" type="application/pdf" width="100%" height="400px"
                    style="display:none;" />
                <img id="preview-img" src="" style="display:none;max-width:100%;max-height:400px;" />
            </div>
        </div>
    </div>
</div>
