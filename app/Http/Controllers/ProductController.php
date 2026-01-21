<?php

namespace App\Http\Controllers;

use App\Enums\RoleStatus;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Product\ProductServiceInterface;
use App\Services\Settings\SettingsServiceInterface;
use App\Services\ActivityLog\ActivityLoggerInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductServiceInterface $service,
        private readonly ActivityLoggerInterface $logger,
        private readonly SettingsServiceInterface $settings
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
        return Inertia::render('Products/Index', [
            'currency' => $this->settings->currency(),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $q = trim((string) $request->input('q', ''));
        $perPage = max(1, min(50, (int) $request->input('per_page', 15)));

        $query = Product::query()
            ->with('category:id,name')
            ->select(['id', 'category_id', 'name', 'sku', 'price', 'stock', 'created_at'])
            ->when($q !== '', function ($w) use ($q) {
                $w->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%");
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
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Products/Form', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $p = $this->service->create($request->validated());
        $this->logger->log('Tambah Produk', "Menambahkan produk '{$p->name}'", ['product_id' => $p->id, 'sku' => $p->sku]);
        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product): Response
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        return Inertia::render('Products/Form', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => (float) $product->price,
                'stock' => $product->stock,
                'category_id' => $product->category_id,
                'description' => $product->description,
            ],
            'categories' => $categories,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $before = $product->only(['name', 'sku', 'price', 'stock', 'category_id']);
        $this->service->update($product, $request->validated());
        $after = $product->only(['name', 'sku', 'price', 'stock', 'category_id']);
        $this->logger->log('Ubah Produk', "Mengubah produk '{$before['name']}'", ['before' => $before, 'after' => $after, 'product_id' => $product->id]);
        return redirect()->route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse|JsonResponse
    {
        $name = $product->name;
        $id = $product->id;
        $sku = $product->sku;

        try {
            $this->service->delete($product);
            $this->logger->log('Hapus Produk', "Menghapus produk '{$name}'", ['product_id' => $id, 'sku' => $sku]);

            if (request()->expectsJson()) {
                return response()->json(['deleted' => true]);
            }

            return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return redirect()->route('produk.index')->with('error', $e->getMessage());
        }
    }
}

