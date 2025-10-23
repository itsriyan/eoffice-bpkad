<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('Name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
        </div>
        <div class="form-group">
            <label>{{ __('Email') }}</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}"
                required>
        </div>
        <div class="form-group">
            <label>{{ __('Password') }}
                @if (!isset($user))
                    <span class="text-muted">({{ __('required') }})</span>
                @else
                    <span class="text-muted">({{ __('leave blank to keep') }})</span>
                @endif
            </label>
            <input type="password" name="password" class="form-control"
                @if (!isset($user)) required @endif>
        </div>
        <div class="form-group">
            <label>{{ __('Confirm Password') }}</label>
            <input type="password" name="password_confirmation" class="form-control"
                @if (!isset($user)) required @endif>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('Role') }}</label>
            <select name="role" class="form-control" required>
                <option value="">-- {{ __('select role') }} --</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(old('role', isset($user) ? $user->roles->first()->name ?? '' : '') == $role->name)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        @if (isset($user) && $user->employee)
            <div class="form-group">
                <label>{{ __('Linked Employee') }}</label>
                <input type="text" class="form-control"
                    value="{{ $user->employee->name }} ({{ $user->employee->nip }})" disabled>
            </div>
        @endif
    </div>
</div>
