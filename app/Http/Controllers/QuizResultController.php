<?php

namespace App\Http\Controllers;

use App\Models\QuizResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class QuizResultController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'quiz_id' => 'required|exists:quizzes,id',
            'student_id' => 'required|exists:students,id',
            'score' => 'required|integer|min:0'
        ]);

        $result = QuizResult::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Result added successfully',
            'data' => $result
        ], 201);
    }
}