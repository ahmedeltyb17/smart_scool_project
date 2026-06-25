<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['user', 'class'])->get()->map(function ($s) {
            return [
                'id'            => $s->id,
                'name'          => $s->user->name ?? '',
                'className'     => $s->class->name ?? '',
                'classId'       => $s->class_id,
                'parentName'    => '',
                'avgGrade'      => 0,
                'attendancePct' => 0,
            ];
        });
        return response()->json($students);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string',
            'email'     => 'required|email|unique:users,email',
            'class_id'  => 'required|exists:classes,id',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => bcrypt('password123'),
            'role'     => 'student',
        ]);

        $student = Student::create([
            'user_id'     => $user->id,
            'class_id'    => $request->class_id,
            'student_id'  => 'STD-' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
            'grade_level' => $request->grade_level ?? null,
        ]);

        return response()->json([
            'id'            => $student->id,
            'name'          => $user->name,
            'className'     => $student->class->name ?? '',
            'classId'       => $student->class_id,
            'parentName'    => '',
            'avgGrade'      => 0,
            'attendancePct' => 0,
        ], 201);
    }

    public function destroy(Student $student)
    {
        $student->user->delete();
        return response()->json(null, 204);
    }
}