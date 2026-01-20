<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\Services\User\UserServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserServiceInterface $service)
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
        return Inertia::render('Users/Index');
    }

    public function data(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));

        $query = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name');

        $paginated = $query->paginate($perPage);

        return response()->json([
            'data' => $paginated->items(),
            'current_page' => $paginated->currentPage(),
            'last_page' => $paginated->lastPage(),
            'per_page' => $paginated->perPage(),
            'total' => $paginated->total(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Users/Form', [
            'roles' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'cashier', 'label' => 'Kasir'],
            ],
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user): Response
    {
        return Inertia::render('Users/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => is_string($user->role) ? $user->role : ($user->role?->value ?? 'cashier'),
            ],
            'roles' => [
                ['value' => 'admin', 'label' => 'Admin'],
                ['value' => 'cashier', 'label' => 'Kasir'],
            ],
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated());
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse|JsonResponse
    {
        if (Auth::id() === $user->id) {
            if (request()->expectsJson()) {
                return response()->json(['message' => 'Tidak dapat menghapus akun sendiri.'], 422);
            }
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }
        
        $this->service->delete($user);
        
        if (request()->expectsJson()) {
            return response()->json(['deleted' => true]);
        }
        
        return redirect()->route('pengguna.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}

