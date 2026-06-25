<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $slots = Schedule::with(['class', 'teacher.user'])->get()->map(function ($s) {
            return [
                'id'          => $s->id,
                'day'         => ucfirst($s->day),
                'period'      => $s->period ?? 0,
                'subject'     => $s->subject ?? '',
                'teacherId'   => $s->teacher_id,
                'teacherName' => $s->teacher->user->name ?? '',
                'classId'     => $s->class_id,
                'className'   => $s->class->name ?? '',
                'room'        => $s->room ?? null,
            ];
        });
        return response()->json($slots);
    }

    public function store(Request $request)
    {
        $request->validate([
            'day'        => 'required|string',
            'period'     => 'required|integer',
            'subject'    => 'required|string',
            'teacher_id' => 'required|exists:teachers,id',
            'class_id'   => 'required|exists:classes,id',
        ]);

        $slot = Schedule::create([
            'day'        => strtolower($request->day),
            'period'     => $request->period,
            'subject'    => $request->subject,
            'teacher_id' => $request->teacher_id,
            'class_id'   => $request->class_id,
            'start_time' => '08:00:00',
            'end_time'   => '08:45:00',
            'room'       => $request->room ?? null,
        ]);

        $slot->load(['class', 'teacher.user']);

        return response()->json([
            'id'          => $slot->id,
            'day'         => ucfirst($slot->day),
            'period'      => $slot->period,
            'subject'     => $slot->subject,
            'teacherId'   => $slot->teacher_id,
            'teacherName' => $slot->teacher->user->name ?? '',
            'classId'     => $slot->class_id,
            'className'   => $slot->class->name ?? '',
            'room'        => $slot->room,
        ], 201);
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return response()->json(null, 204);
    }
}