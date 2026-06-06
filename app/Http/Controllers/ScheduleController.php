<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedules;
use Illuminate\Http\JsonResponse;

class ScheduleController extends Controller
{
    public function studentSchedule(Request $request): JsonResponse
{
    $student = $request->user()->student;

    $schedule = Schedules::where('class_id', $student->class_id)->get();

    return response()->json([
        'success' => true,
        'data' => $schedule
    ]);
}
}
