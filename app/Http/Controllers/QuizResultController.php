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
        'title' => 'required|exists:quizzes,title',
        'student_id' => 'required|exists:students,student_id',
        'score' => 'required|integer|min:0|max:100'
    ]);

    $quiz = Quiz::where('title', $validated['title'])->firstOrFail();

    $student = Student::where('student_id', $validated['student_id'])->firstOrFail();

    $result = QuizResult::updateOrCreate(
        [
            'quiz_id' => $quiz->id,
            'student_id' => $student->id
        ],
        [
            'score' => $validated['score']
        ]
    );

    return response()->json([
    'success' => true,
    'message' => 'Result saved successfully',
    'data' => [
        'title' => $quiz->title,
        'student_id' => $student->student_id,
        'score' => $result->score,
        'max_score' => $quiz->max_score
    ]
]);
}

public function studentResults(Request $request): JsonResponse
{
    $student = $request->user()->student;

    if (!$student) {
        return response()->json([
            'success' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $results = QuizResult::with('quiz')
        ->where('student_id', $student->id)
        ->get()
        ->map(function ($result) {
            return [
                'title' => $result->quiz->title,
                'score' => $result->score,
                'max_score' => $result->quiz->max_score,
                'percentage' => round(
                    ($result->score / $result->quiz->max_score) * 100,
                    2
                )
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $results
    ]);
}

public function parentResults(Request $request): JsonResponse
{
    $parent = $request->user()->parentProfile;

    $studentIds = $parent->students()->pluck('students.id');

    $results = QuizResult::with(['quiz', 'student.user'])
        ->whereIn('student_id', $studentIds)
        ->get()
        ->map(function ($result) {
            return [
                'student_name' => $result->student->user->name ?? 'N/A',
                'quiz_title' => $result->quiz->title ?? 'N/A',
                'score' => $result->score,
                'max_score' => $result->quiz->max_score
            ];
        });

    return response()->json([
        'success' => true,
        'data' => $results
    ]);
}
}