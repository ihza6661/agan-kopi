<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

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

    public function index()
    {
        return view('activity_logs.index');
    }

    public function data()
    {
        $query = ActivityLog::query()
            ->with('user')
            ->where(function ($q) {
                $q->where('activity', 'not like', 'GET %')
                    ->where('activity', 'not like', 'HEAD %')
                    ->where('activity', 'not like', 'OPTIONS %');
            })
            ->select(['id', 'user_id', 'activity', 'description', 'ip_address', 'user_agent', 'created_at']);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('user_name', fn(ActivityLog $l) => $l->user->name ?? '-')
            ->editColumn('created_at', fn(ActivityLog $l) => $l->created_at?->format('d/m/Y H:i'))
            ->editColumn('activity', function (ActivityLog $l) {
                $title = e($l->description ?? '');
                $text = e($l->activity);
                return '<span title="' . $title . '">' . $text . '</span>';
            })
            ->editColumn('user_agent', function (ActivityLog $l) {
                $ua = (string) $l->user_agent;
                return e(mb_strlen($ua) > 80 ? (mb_substr($ua, 0, 77) . '...') : $ua);
            })
            ->rawColumns(['activity'])
            ->toJson();
    }
}
