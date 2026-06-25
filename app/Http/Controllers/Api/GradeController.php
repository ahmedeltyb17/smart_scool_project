<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Student;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        $students = Student::with(['user', 'class', 'grades'])->get()->map(function ($s) {
            $avg = $s->grades->avg('score') ?? 0;
            return [
                'id'        => $s->id,
                'name'      => $s->user->name ?? '',
                'className' => $s->class->name ?? '',
                'avgGrade'  => round($avg),
            ];
        });

        return response()->json($students);
    }
}