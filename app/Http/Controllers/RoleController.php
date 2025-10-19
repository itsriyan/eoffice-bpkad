<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        if (!user()->can('role.view')) abort(403);
        if ($request->ajax()) {
            $roles = Role::query()->with('permissions');
            return DataTables::of($roles)
                ->addIndexColumn()
                ->addColumn('permissions', function ($role) {
                    return $role->permissions->pluck('name')->implode(', ');
                })
                ->addColumn('actions', function ($role) {
                    $buttons = '<span class="d-inline-flex">';
                    if (user()->can('role.view')) {
                        $buttons .= '<a href="' . route('roles.show', $role->id) . '" class="btn btn-sm btn-secondary mr-1" title="Detail"><i class="fas fa-eye"></i></a>';
                    }
                    if (user()->can('role.edit')) {
                        $buttons .= '<a href="' . route('roles.edit', $role->id) . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    if (user()->can('role.delete')) {
                        $buttons .= '<button onclick="deleteRole(' . $role->id . ')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    $buttons .= '</span>';
                    return $buttons;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        return view('roles.index');
    }

    public function create()
    {
        if (!user()->can('role.create')) abort(403);
        $permissions = Permission::orderBy('name')->get();
        return view('roles.create', compact('permissions'));
    }

    public function store(RoleStoreRequest $request)
    {
        $data = $request->validated();
        $role = Role::create(['name' => $data['name']]);
        if (!empty($data['permissions'])) {
            $role->syncPermissions($data['permissions']); // now names
        }
        return redirect()->route('roles.index')->with('success', 'Role created');
    }

    public function show(Role $role)
    {
        if (!user()->can('role.view')) abort(403);
        $role->load('permissions');
        return view('roles.show', compact('role'));
    }

    public function edit(Role $role)
    {
        if (!user()->can('role.edit')) abort(403);
        $permissions = Permission::orderBy('name')->get();
        $role->load('permissions');
        return view('roles.edit', compact('role', 'permissions'));
    }

    public function update(RoleUpdateRequest $request, Role $role)
    {
        $data = $request->validated();
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []); // names
        return redirect()->route('roles.index')->with('success', 'Role updated');
    }

    public function destroy(Role $role)
    {
        if (!user()->can('role.delete')) abort(403);
        $role->delete();
        return response()->json(['success' => true]);
    }
}
