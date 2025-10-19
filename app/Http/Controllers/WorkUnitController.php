<?php

namespace App\Http\Controllers;

use App\Models\WorkUnit;
use App\Http\Requests\WorkUnitStoreRequest;
use App\Http\Requests\WorkUnitUpdateRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class WorkUnitController extends Controller
{
    public function index(Request $request)
    {
        if (!user()->can('work_unit.view')) abort(403);
        if ($request->ajax()) {
            $query = WorkUnit::query();
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('actions', function ($u) {
                    $buttons = '<span class="d-inline-flex">';
                    if (user()->can('work_unit.view')) {
                        $buttons .= '<a href="' . route('work_units.show', $u->id) . '" class="btn btn-sm btn-secondary mr-1" title="Detail"><i class="fas fa-eye"></i></a>';
                    }
                    if (user()->can('work_unit.edit')) {
                        $buttons .= '<a href="' . route('work_units.edit', $u->id) . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    if (user()->can('work_unit.delete')) {
                        $buttons .= '<button onclick="deleteWorkUnit(' . $u->id . ')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    $buttons .= '</span>';
                    return $buttons;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        return view('work_units.index');
    }

    public function create()
    {
        if (!user()->can('work_unit.create')) abort(403);
        return view('work_units.create');
    }

    public function store(WorkUnitStoreRequest $request)
    {
        WorkUnit::create($request->validated());
        return redirect()->route('work_units.index')->with('success', 'Work Unit created');
    }

    public function show(WorkUnit $work_unit)
    {
        if (!user()->can('work_unit.view')) abort(403);
        return view('work_units.show', ['workUnit' => $work_unit]);
    }

    public function edit(WorkUnit $work_unit)
    {
        if (!user()->can('work_unit.edit')) abort(403);
        return view('work_units.edit', ['workUnit' => $work_unit]);
    }

    public function update(WorkUnitUpdateRequest $request, WorkUnit $work_unit)
    {
        $work_unit->update($request->validated());
        return redirect()->route('work_units.index')->with('success', 'Work Unit updated');
    }

    public function destroy(WorkUnit $work_unit)
    {
        if (!user()->can('work_unit.delete')) abort(403);
        $work_unit->delete();
        return response()->json(['success' => true]);
    }
}