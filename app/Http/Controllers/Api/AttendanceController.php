<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $classes = ClassModel::with(['students'])->get()->map(function ($c) {
            $today = now()->toDateString();
            $records = Attendance::where('class_id', $c->id)
                ->whereDate('date', $today)
                ->get();

            $present = $records->where('status', 'present')->count();
            $absent  = $records->where('status', 'absent')->count();
            $total   = $c->students->count();
            $pct     = $total > 0 ? round(($present / $total) * 100) : 0;

            return [
                'classId'       => $c->id,
                'className'     => $c->name,
                'teacherName'   => '',
                'present'       => $present,
                'absent'        => $absent,
                'attendancePct' => $pct,
                'uploaded'      => $records->count() > 0,
            ];
        });

        return response()->json($classes);
    }
}