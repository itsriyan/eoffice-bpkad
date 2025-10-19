@extends('adminlte::page')

@section('title', __('Profile'))

@section('content_header')
    <h1>{{ __('Profile') }}</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-7">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Update Profile') }}</h3>
                </div>
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if (session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="form-group">
                            <label>{{ __('Name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}"
                                required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Email') }}</label>
                            <input type="email" name="email" class="form-control"
                                value="{{ old('email', $user->email) }}" required>
                        </div>
                        <hr>
                        <h5>{{ __('Employee Details') }}</h5>
                        <div class="form-group">
                            <label>{{ __('Employee Name') }}</label>
                            <input type="text" name="employee_name" class="form-control"
                                value="{{ old('employee_name', $employee?->name ?? $user->name) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ __('NIP') }}</label>
                            <input type="text" name="nip" class="form-control"
                                value="{{ old('nip', $employee?->nip) }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Position') }}</label>
                            <input type="text" name="position" class="form-control"
                                value="{{ old('position', $employee?->position) }}" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Phone Number') }}</label>
                            <input type="text" name="phone_number" class="form-control"
                                value="{{ old('phone_number', $employee?->phone_number) }}">
                        </div>
                        <div class="form-group">
                            <label>{{ __('Grade') }}</label>
                            <select name="grade_id" class="form-control">
                                <option value="">-- {{ __('Select') }} --</option>
                                @foreach (\App\Models\Grade::orderBy('code')->get() as $g)
                                    <option value="{{ $g->id }}" @selected(old('grade_id', $employee?->grade_id) == $g->id)>{{ $g->code }} -
                                        {{ $g->rank }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Work Unit') }}</label>
                            <select name="work_unit_id" class="form-control">
                                <option value="">-- {{ __('Select') }} --</option>
                                @foreach (\App\Models\WorkUnit::orderBy('name')->get() as $wu)
                                    <option value="{{ $wu->id }}" @selected(old('work_unit_id', $employee?->work_unit_id) == $wu->id)>{{ $wu->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card card-secondary">
                <div class="card-header">
                    <h3 class="card-title">{{ __('Change Password') }}</h3>
                </div>
                <form method="POST" action="{{ route('profile.password') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ __('Current Password') }}</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('New Password') }}</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>{{ __('Confirm New Password') }}</label>
                            <input type="password" name="new_password_confirmation" class="form-control" required>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <button type="submit" class="btn btn-secondary">{{ __('Change') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop
