<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || Auth::user()->role !== RoleStatus::ADMIN->value) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    public function index(): Response
    {
        return Inertia::render('ActivityLogs/Index');
    }

    public function data(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));

        $query = ActivityLog::query()
            ->with('user:id,name')
            ->where(function ($q) {
                $q->where('activity', 'not like', 'GET %')
                    ->where('activity', 'not like', 'HEAD %')
                    ->where('activity', 'not like', 'OPTIONS %');
            })
            ->orderByDesc('created_at')
            ->select(['id', 'user_id', 'activity', 'description', 'ip_address', 'user_agent', 'created_at']);

        $paginated = $query->paginate($perPage);

        $data = $paginated->getCollection()->map(function (ActivityLog $log) {
            return [
                'id' => $log->id,
                'user_name' => $log->user?->name ?? '-',
                'activity' => $log->activity,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }
}
