<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Models\Shift;
use App\Services\Settings\SettingsServiceInterface;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    public function __construct(
        private readonly SettingsServiceInterface $settings,
        private readonly ActivityLoggerInterface $logger
    ) {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || !in_array(Auth::user()->role, [RoleStatus::ADMIN->value, RoleStatus::CASHIER->value], true)) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    /**
     * Get current shift status for the logged-in user.
     */
    public function status(): JsonResponse
    {
        $userId = Auth::id();
        $shift = Shift::getActiveForUser($userId);

        if (!$shift) {
            return response()->json([
                'has_active_shift' => false,
                'shift' => null,
            ]);
        }

        return response()->json([
            'has_active_shift' => true,
            'shift' => [
                'id' => $shift->id,
                'started_at' => $shift->started_at->toIso8601String(),
                'opening_cash' => (float) $shift->opening_cash,
                'cash_total' => $shift->cash_total,
                'qris_total' => $shift->qris_total,
                'total_sales' => $shift->total_sales,
                'transaction_count' => $shift->transaction_count,
            ],
        ]);
    }

    /**
     * Start a new shift.
     */
    public function start(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // Check for existing active shift
        $existingShift = Shift::getActiveForUser($userId);
        if ($existingShift) {
            return response()->json([
                'message' => 'Anda masih memiliki shift aktif. Akhiri shift terlebih dahulu.',
                'shift_id' => $existingShift->id,
            ], 409);
        }

        $request->validate([
            'opening_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $shift = Shift::create([
            'user_id' => $userId,
            'started_at' => now(),
            'opening_cash' => $request->input('opening_cash', 0),
            'notes' => $request->input('notes'),
        ]);

        $this->logger->log('Mulai Shift', 'Shift baru dimulai', [
            'shift_id' => $shift->id,
            'opening_cash' => $shift->opening_cash,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil dimulai.',
            'shift' => [
                'id' => $shift->id,
                'started_at' => $shift->started_at->toIso8601String(),
                'opening_cash' => (float) $shift->opening_cash,
            ],
        ]);
    }

    /**
     * End the current active shift.
     */
    public function end(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $shift = Shift::getActiveForUser($userId);

        if (!$shift) {
            return response()->json([
                'message' => 'Tidak ada shift aktif untuk diakhiri.',
            ], 404);
        }

        $request->validate([
            'closing_cash' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $closingCash = (float) $request->input('closing_cash');
        $notes = $request->input('notes');

        // Update with closing information
        $shift->update([
            'ended_at' => now(),
            'closing_cash' => $closingCash,
            'notes' => $notes ?: $shift->notes,
        ]);

        $this->logger->log('Akhiri Shift', 'Shift diakhiri', [
            'shift_id' => $shift->id,
            'opening_cash' => $shift->opening_cash,
            'closing_cash' => $closingCash,
            'cash_total' => $shift->cash_total,
            'qris_total' => $shift->qris_total,
            'total_sales' => $shift->total_sales,
            'variance' => $shift->variance,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift berhasil diakhiri.',
            'summary' => [
                'id' => $shift->id,
                'started_at' => $shift->started_at->toIso8601String(),
                'ended_at' => $shift->ended_at->toIso8601String(),
                'opening_cash' => (float) $shift->opening_cash,
                'closing_cash' => (float) $shift->closing_cash,
                'cash_total' => $shift->cash_total,
                'qris_total' => $shift->qris_total,
                'total_sales' => $shift->total_sales,
                'transaction_count' => $shift->transaction_count,
                'expected_cash' => $shift->expected_cash,
                'variance' => $shift->variance,
            ],
        ]);
    }

    /**
     * Shift history view (admin can see all, cashier sees own).
     */
    public function index(): Response
    {
        return Inertia::render('Shifts/Index', [
            'currency' => $this->settings->currency(),
        ]);
    }

    /**
     * Shift history data endpoint.
     */
    public function data(Request $request): JsonResponse
    {
        $isAdmin = Auth::user()->role === RoleStatus::ADMIN->value;
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));
        $from = $request->input('from');
        $to = $request->input('to');

        $query = Shift::query()
            ->with('user:id,name')
            ->when(!$isAdmin, function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->when($from, fn($q) => $q->whereDate('started_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('started_at', '<=', $to))
            ->orderByDesc('started_at');

        $paginated = $query->paginate($perPage);

        $items = $paginated->getCollection()->map(fn (Shift $s) => [
            'id' => $s->id,
            'user' => $s->user?->name ?? '-',
            'started_at' => $s->started_at->toIso8601String(),
            'ended_at' => $s->ended_at?->toIso8601String(),
            'is_active' => $s->isActive(),
            'opening_cash' => (float) $s->opening_cash,
            'closing_cash' => $s->closing_cash !== null ? (float) $s->closing_cash : null,
            'cash_total' => $s->cash_total,
            'qris_total' => $s->qris_total,
            'total_sales' => $s->total_sales,
            'transaction_count' => $s->transaction_count,
            'expected_cash' => $s->expected_cash,
            'variance' => $s->variance,
        ]);

        return response()->json([
            'data' => $items,
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }
}
