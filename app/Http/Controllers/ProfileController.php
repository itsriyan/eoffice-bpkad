<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Models\Employee;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\PasswordChangeRequest;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = user();
        $employee = $user->employee; // may be null
        return view('profile.show', compact('user', 'employee'));
    }

    public function update(ProfileUpdateRequest $request)
    {
        $user = user();
        $data = $request->validated();

        // Update user basic fields
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        // Upsert employee details
        $empPayload = [
            'name' => $data['employee_name'] ?? $data['name'],
            'nip' => $data['nip'],
            'position' => $data['position'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'status' => 'active',
        ];
        if (!empty($data['grade_id'])) {
            $empPayload['grade_id'] = $data['grade_id'];
        }
        if (!empty($data['work_unit_id'])) {
            $empPayload['work_unit_id'] = $data['work_unit_id'];
        }

        Employee::updateOrCreate(['user_id' => $user->id], $empPayload);

        return redirect()->route('profile.show')->with('success', __('Profile updated'));
    }

    public function changePassword(PasswordChangeRequest $request)
    {
        $user = user();
        $data = $request->validated();
        $user->update([
            'password' => Hash::make($data['new_password'])
        ]);
        return redirect()->route('profile.show')->with('success', __('Password changed'));
    }
}
