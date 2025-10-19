<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Grade;
use App\Models\WorkUnit;
use App\Models\User;
use App\Http\Requests\EmployeeStoreRequest;
use App\Http\Requests\EmployeeUpdateRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Employee::query()->with(['grade', 'workUnit', 'user.roles']);
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('grade', fn($e) => $e->grade?->code)
                ->addColumn('work_unit', fn($e) => $e->workUnit?->name)
                ->addColumn('user_email', fn($e) => $e->user?->email)
                ->addColumn('role', fn($e) => $e->user?->roles->pluck('name')->implode(', '))
                ->addColumn('actions', function ($e) {
                    $edit = route('employees.edit', $e->id);
                    $show = route('employees.show', $e->id);
                    $btn = '<span class="d-inline-flex">';
                    if (user()?->can('employee.view')) {
                        $btn .= '<a href="' . $show . '" class="btn btn-sm btn-secondary mr-1" title="Detail"><i class="fas fa-eye"></i></a>';
                    }
                    if (user()?->can('employee.edit')) {
                        $btn .= '<a href="' . $edit . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    if (user()?->can('employee.delete')) {
                        $btn .= '<button onclick="deleteEmployee(' . $e->id . ')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    $btn .= '</span>';
                    return $btn;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        return view('employees.index');
    }

    public function create()
    {
        $grades = Grade::orderBy('code')->get();
        $workUnits = WorkUnit::orderBy('name')->get();
        $users = User::doesntHave('employee')->orderBy('name')->get();
        return view('employees.create', compact('grades', 'workUnits', 'users'));
    }

    public function store(EmployeeStoreRequest $request)
    {
        $data = $request->validated();
        Employee::create($data);
        return redirect()->route('employees.index')->with('success', 'Employee created');
    }

    public function show(Employee $employee)
    {
        return view('employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $grades = Grade::orderBy('code')->get();
        $workUnits = WorkUnit::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return view('employees.edit', compact('employee', 'grades', 'workUnits', 'users'));
    }

    public function update(EmployeeUpdateRequest $request, Employee $employee)
    {
        $employee->update($request->validated());
        return redirect()->route('employees.index')->with('success', 'Employee updated');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->json(['success' => true]);
    }
}
