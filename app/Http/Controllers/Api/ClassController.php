<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        $classes = ClassModel::withCount('students')->get()->map(function ($c) {
            return [
                'id'             => $c->id,
                'name'           => $c->name,
                'grade'          => $c->description ?? '',
                'teacherName'    => '',
                'studentsCount'  => $c->students_count ?? 0,
                'attendancePct'  => 0,
            ];
        });
        return response()->json($classes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:classes,name',
        ]);

        $class = ClassModel::create([
            'name'        => $request->name,
            'description' => $request->grade ?? null,
        ]);

        return response()->json([
            'id'            => $class->id,
            'name'          => $class->name,
            'grade'         => $class->description ?? '',
            'teacherName'   => '',
            'studentsCount' => 0,
            'attendancePct' => 0,
        ], 201);
    }

    public function destroy(ClassModel $schoolClass)
    {
        $schoolClass->delete();
        return response()->json(null, 204);
    }
}