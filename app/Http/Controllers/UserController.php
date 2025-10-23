<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use Spatie\Permission\Models\Role;
use Exception;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if (!user()->can('user.view')) abort(403);
        try {
            if ($request->ajax()) {
                $users = User::query()->with(['roles', 'employee']);
                if (user()->hasRole('admin')) {
                    $users->whereDoesntHave('roles', function ($q) {
                        $q->where('name', 'Super Admin');
                    });
                }
                return DataTables::of($users)
                    ->addIndexColumn()
                    ->addColumn('role', function ($user) {
                        return $user->roles->pluck('name')->implode(', ');
                    })
                    ->addColumn('phone_number', function ($user) {
                        return $user->phone_number ?? '-';
                    })
                    ->addColumn('actions', function ($user) {
                        $editUrl = route('users.edit', $user->id);
                        $showUrl = route('users.show', $user->id);
                        $buttons = '<span class="d-inline-flex">';
                        if (user()->can('user.view')) {
                            $buttons .= '<a href="' . $showUrl . '" class="btn btn-sm btn-secondary mr-1" title="Detail">'
                                . '<i class="fas fa-eye"></i></a>';
                        }
                        if (user()->can('user.edit')) {
                            $buttons .= '<a href="' . $editUrl . '" class="btn btn-sm btn-info mr-1" title="Edit">'
                                . '<i class="fas fa-edit"></i></a>';
                        }
                        if (user()->can('user.delete')) {
                            $buttons .= '<button class="btn btn-sm btn-danger" title="Delete" onclick="deleteUser(' . $user->id . ')">'
                                . '<i class="fas fa-trash"></i></button>';
                        }
                        $buttons .= '</span>';
                        return $buttons;
                    })
                    ->rawColumns(['actions'])
                    ->make(true);
            }
            return view('users.index');
        } catch (Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function create()
    {
        if (!user()->can('user.create')) abort(403);
        $roles = Role::orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(UserStoreRequest $request)
    {
        $data = $request->validated();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);
        $user->syncRoles([$data['role']]);
        return redirect()->route('users.index')->with('success', 'User created');
    }

    public function show(User $user)
    {
        if (!user()->can('user.view')) abort(403);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        if (!user()->can('user.edit')) abort(403);
        $roles = Role::orderBy('name')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(UserUpdateRequest $request, User $user)
    {
        $data = $request->validated();
        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];
        if (!empty($data['password'])) {
            $update['password'] = $data['password'];
        }
        $user->update($update);
        $user->syncRoles([$data['role']]);
        return redirect()->route('users.index')->with('success', 'User updated');
    }

    public function destroy(User $user)
    {
        if (!user()->can('user.delete')) abort(403);
        $user->delete();
        return response()->json(['success' => true]);
    }
}
