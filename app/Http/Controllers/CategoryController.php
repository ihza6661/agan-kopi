<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Category\CategoryServiceInterface;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryServiceInterface $service,
        private readonly ActivityLoggerInterface $logger
    ) {
        $this->middleware(function ($request, $next) {
            if (!Auth::check() || Auth::user()->role !== RoleStatus::ADMIN->value) {
                abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
            }
            return $next($request);
        });
    }

    public function index(): Response
    {
        return Inertia::render('Categories/Index');
    }

    public function data(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));

        $query = Category::query()
            ->select(['id', 'name', 'description', 'created_at'])
            ->when($q !== '', function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%");
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
        return Inertia::render('Categories/Form');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $cat = $this->service->create($request->validated());
        $this->logger->log('Tambah Kategori', "Menambahkan kategori '{$cat->name}'", ['category_id' => $cat->id]);

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('Categories/Form', [
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
            ],
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $before = $category->only(['name', 'description']);
        $this->service->update($category, $request->validated());
        $after = $category->only(['name', 'description']);
        $this->logger->log('Ubah Kategori', "Mengubah kategori '{$before['name']}'", ['before' => $before, 'after' => $after, 'category_id' => $category->id]);

        return redirect()
            ->route('kategori.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse|JsonResponse
    {
        $name = $category->name;
        $id = $category->id;
        
        try {
            $this->service->delete($category);
            $this->logger->log('Hapus Kategori', "Menghapus kategori '{$name}'", ['category_id' => $id]);

            if (request()->expectsJson()) {
                return response()->json(['deleted' => true]);
            }

            return redirect()
                ->route('kategori.index')
                ->with('success', 'Kategori berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage()
                ], 422);
            }

            return redirect()
                ->route('kategori.index')
                ->with('error', $e->getMessage());
        }
    }
}

