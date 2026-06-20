<?php

namespace App\Http\Controllers;

use App\Models\QuizResult;
use App\Models\Student;
use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuizResultController extends Controller
{
    public function store(Request $request): JsonResponse
{
    if ($request->user()->role !== 'teacher') {
        return response()->json([
            'success' => false,
            'message' => 'Only teachers can submit quiz results'
        ], 403);
    }

    $validated = $request->validate([
        'quiz_id' => 'required|exists:quizzes,id',
        'student_id' => 'required|exists:students,student_id',
        'score' => 'required|integer|min:0|max:100'
    ]);

    $student = Student::where('student_id', $validated['student_id'])->firstOrFail();

    $result = QuizResult::updateOrCreate(
        [
            'quiz_id' => $validated['quiz_id'],
            'student_id' => $student->id
        ],
        [
            'score' => $validated['score']
        ]
    );

    return response()->json([
        'success' => true,
        'message' => 'Result saved successfully',
        'data' => $result
    ]);
}
}