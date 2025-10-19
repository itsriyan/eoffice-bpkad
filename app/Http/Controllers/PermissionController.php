<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionStoreRequest;
use App\Http\Requests\PermissionUpdateRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        if (!user()->can('permission.view')) abort(403);
        if ($request->ajax()) {
            $query = Permission::query();
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('actions', function ($perm) {
                    $buttons = '<span class="d-inline-flex">';
                    if (user()->can('permission.edit')) {
                        $buttons .= '<a href="' . route('permissions.edit', $perm->id) . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    if (user()->can('permission.delete')) {
                        $buttons .= '<button onclick="deletePermission(' . $perm->id . ')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    $buttons .= '</span>';
                    return $buttons;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        return view('permissions.index');
    }

    public function create()
    {
        if (!user()->can('permission.create')) abort(403);
        return view('permissions.create');
    }

    public function store(PermissionStoreRequest $request)
    {
        $data = $request->validated();
        foreach ($data['permissions'] as $row) {
            Permission::create([
                'name' => $row['name'],
                'guard_name' => 'web',
            ]);
        }
        return redirect()->route('permissions.index')->with('success', 'Permissions created');
    }

    public function edit()
    {
        if (!user()->can('permission.edit')) abort(403);
        $permissions = Permission::orderBy('name')->get();
        return view('permissions.edit', compact('permissions'));
    }

    public function update(PermissionUpdateRequest $request)
    {
        $data = $request->validated();
        foreach ($data['permissions'] as $row) {
            $perm = Permission::find($row['id']);
            if ($perm) {
                // uniqueness: ensure no other permission shares this name
                if (Permission::where('name', $row['name'])->where('id', '!=', $perm->id)->exists()) {
                    return redirect()->back()->withErrors(['permissions' => 'Duplicate name: ' . $row['name']]);
                }
                $perm->update([
                    'name' => $row['name'],
                    'guard_name' => 'web',
                ]);
            }
        }
        return redirect()->route('permissions.index')->with('success', 'Permissions updated');
    }

    public function destroy(Permission $permission)
    {
        if (!user()->can('permission.delete')) abort(403);
        $permission->delete();
        return response()->json(['success' => true]);
    }
}
