<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * TeacherMiddleware
 *
 * Allows access to teachers AND admins (admins can do everything teachers do).
 * Must be used AFTER auth:sanctum middleware.
 */
class TeacherMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        // Admins can access teacher routes too
        if (! in_array($user->role, ['admin', 'teacher'])) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Teacher or Admin role required.',
                'your_role' => $user->role,
            ], 403);
        }

        return $next($request);
    }
}
