<div class="form-row">
    <div class="form-group col-md-6">
        <label>{{ __('Name') }}</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $workUnit->name ?? '') }}" required>
    </div>
    <div class="form-group col-md-6">
        <label>{{ __('Description') }}</label>
        <input type="text" name="description" class="form-control"
            value="{{ old('description', $workUnit->description ?? '') }}">
    </div>
</div>
