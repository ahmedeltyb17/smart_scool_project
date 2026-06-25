<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    public function index(): JsonResponse
    {
        $quizzes = Quiz::all();

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'max_score' => 'required|integer|min:1'

            
]);

    $quiz = Quiz::create($validated);
    
    return response()->json([
        'success' => true,
        'message' => 'Quiz created successfully',
        'data' => [
            'title' => $quiz->title,
            'max_score' => $quiz->max_score,
    ]
], 201);
    }

    public function show($id): JsonResponse
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found'
            ], 404);
        }

        return response()->json([
    'success' => true,
    'message' => 'Quiz created successfully',
    'data' => [
        'title' => $quiz->title,
        'max_score' => $quiz->max_score,
    ]
], 201);
    }

    public function destroy($id): JsonResponse
    {
        $quiz = Quiz::find($id);

        if (!$quiz) {
            return response()->json([
                'success' => false,
                'message' => 'Quiz not found'
            ], 404);
        }

        $quiz->delete();

        return response()->json([
            'success' => true,
            'message' => 'Quiz deleted successfully'
        ]);
    }

    public function studentQuizzes(Request $request): JsonResponse
    {
        $student = $request->user()->student;

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'User is not a student'
            ], 403);
        }

        $quizzes = Quiz::where('class_id', $student->class_id)->get();

        return response()->json([
            'success' => true,
            'data' => $quizzes
        ]);
    }
}