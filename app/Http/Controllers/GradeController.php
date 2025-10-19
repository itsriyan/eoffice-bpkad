<?php

namespace App\Http\Controllers;

use App\Models\Grade;
use App\Http\Requests\GradeStoreRequest;
use App\Http\Requests\GradeUpdateRequest;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class GradeController extends Controller
{
    public function index(Request $request)
    {
        if (!user()->can('grade.view')) abort(403);
        if ($request->ajax()) {
            $query = Grade::query();
            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('actions', function ($g) {
                    $buttons = '<span class="d-inline-flex">';
                    if (user()->can('grade.view')) {
                        $buttons .= '<a href="' . route('grades.show', $g->id) . '" class="btn btn-sm btn-secondary mr-1" title="Detail"><i class="fas fa-eye"></i></a>';
                    }
                    if (user()->can('grade.edit')) {
                        $buttons .= '<a href="' . route('grades.edit', $g->id) . '" class="btn btn-sm btn-info mr-1" title="Edit"><i class="fas fa-edit"></i></a>';
                    }
                    if (user()->can('grade.delete')) {
                        $buttons .= '<button onclick="deleteGrade(' . $g->id . ')" class="btn btn-sm btn-danger" title="Delete"><i class="fas fa-trash"></i></button>';
                    }
                    $buttons .= '</span>';
                    return $buttons;
                })
                ->rawColumns(['actions'])
                ->make(true);
        }
        return view('grades.index');
    }

    public function create()
    {
        if (!user()->can('grade.create')) abort(403);
        return view('grades.create');
    }

    public function store(GradeStoreRequest $request)
    {
        Grade::create($request->validated());
        return redirect()->route('grades.index')->with('success', 'Grade created');
    }

    public function show(Grade $grade)
    {
        if (!user()->can('grade.view')) abort(403);
        return view('grades.show', compact('grade'));
    }

    public function edit(Grade $grade)
    {
        if (!user()->can('grade.edit')) abort(403);
        return view('grades.edit', compact('grade'));
    }

    public function update(GradeUpdateRequest $request, Grade $grade)
    {
        $grade->update($request->validated());
        return redirect()->route('grades.index')->with('success', 'Grade updated');
    }

    public function destroy(Grade $grade)
    {
        if (!user()->can('grade.delete')) abort(403);
        $grade->delete();
        return response()->json(['success' => true]);
    }
}
