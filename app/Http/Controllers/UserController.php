<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * جلب كل المستخدمين من قاعدة البيانات
     */
    public function index()
    {
        // بنجيب كل المستخدمين من جدول الـ users
        $users = User::all();

        // بنرجع البيانات على هيئة JSON مع كود الحالة 200 (مسترجع بنجاح)
        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully',
            'data'    => $users
        ], 200);
    }
}
