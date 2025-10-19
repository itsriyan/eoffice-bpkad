<div class="form-row">
    <div class="form-group col-md-4">
        <label>{{ __('Code') }}</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $grade->code ?? '') }}" required>
    </div>
    <div class="form-group col-md-4">
        <label>{{ __('Category') }}</label>
        <input type="text" name="category" class="form-control" value="{{ old('category', $grade->category ?? '') }}"
            required>
    </div>
    <div class="form-group col-md-4">
        <label>{{ __('Rank') }}</label>
        <input type="text" name="rank" class="form-control" value="{{ old('rank', $grade->rank ?? '') }}"
            required>
    </div>
</div>
