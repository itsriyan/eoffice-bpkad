<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('Name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $employee->name ?? '') }}"
                required>
        </div>
        <div class="form-group">
            <label>{{ __('NIP') }}</label>
            <input type="text" name="nip" class="form-control" value="{{ old('nip', $employee->nip ?? '') }}"
                required>
        </div>
        <div class="form-group">
            <label>{{ __('Position') }}</label>
            <input type="text" name="position" class="form-control"
                value="{{ old('position', $employee->position ?? '') }}" required>
        </div>
        <div class="form-group">
            <label>{{ __('Email') }}</label>
            <input type="email" name="email" class="form-control"
                value="{{ old('email', $employee->email ?? '') }}">
        </div>
        <div class="form-group">
            <label>{{ __('Phone Number') }}</label>
            <input type="text" name="phone_number" class="form-control"
                value="{{ old('phone_number', $employee->phone_number ?? '') }}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>{{ __('Grade') }}</label>
            <select name="grade_id" class="form-control">
                <option value="">-- {{ __('Select') }} --</option>
                @foreach ($grades as $g)
                    <option value="{{ $g->id }}" @selected(old('grade_id', $employee->grade_id ?? '') == $g->id)>{{ $g->code }}
                        ({{ $g->rank }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>{{ __('Work Unit') }}</label>
            <select name="work_unit_id" class="form-control">
                <option value="">-- {{ __('Select') }} --</option>
                @foreach ($workUnits as $u)
                    <option value="{{ $u->id }}" @selected(old('work_unit_id', $employee->work_unit_id ?? '') == $u->id)>{{ $u->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>{{ __('User (login account)') }}</label>
            <select name="user_id" class="form-control">
                <option value="">-- {{ __('Select') }} --</option>
                @foreach ($users as $usr)
                    <option value="{{ $usr->id }}" @selected(old('user_id', $employee->user_id ?? '') == $usr->id)>{{ $usr->name }}
                        ({{ $usr->email }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label>{{ __('Status') }}</label>
            <select name="status" class="form-control" required>
                @foreach (['active' => __('Active'), 'inactive' => __('Inactive')] as $val => $label)
                    <option value="{{ $val }}" @selected(old('status', $employee->status ?? 'active') == $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>
